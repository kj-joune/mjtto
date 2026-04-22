<?php
/*  chat-GPT ERP sign: sysempire@gmail.com  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();

if ($auth['role'] !== 'BRANCH_ADMIN') {
    alert('지점 관리자만 접근할 수 있습니다.');
}

$branch_company_id = (int)$auth['company_id'];

$branch = sql_fetch("
    SELECT company_id, parent_company_id, company_name, company_code, coupon_prefix, issue_game_count
      FROM mz_company
     WHERE company_id = '{$branch_company_id}'
       AND company_type = 'BRANCH'
       AND status = 1
     LIMIT 1
");

if (!$branch || empty($branch['company_id'])) {
    alert('지점 정보를 확인할 수 없습니다.');
}

$current_round = sql_fetch("
    SELECT r.round_id, r.round_no, r.draw_date, r.payout_deadline, r.status
      FROM mz_round r
     WHERE r.round_no > (
            SELECT COALESCE(MAX(r2.round_no), 0)
              FROM mz_round r2
              INNER JOIN mz_draw_result d2
                      ON r2.round_id = d2.round_id
      )
     ORDER BY r.round_no ASC
     LIMIT 1
");

if (!$current_round || empty($current_round['round_id'])) {
    $current_round = array(
        'round_id'         => 0,
        'round_no'         => '',
        'draw_date'        => '',
        'payout_deadline'  => '',
        'status'           => ''
    );
}

$available = sql_fetch("
    SELECT COUNT(*) AS cnt
      FROM mjtto_db
     WHERE chk = 0
");
$available_cnt = (int)($available['cnt'] ?? 0);
$issue_game_count = isset($branch['issue_game_count']) ? (int)$branch['issue_game_count'] : 5;
if (!in_array($issue_game_count, array(1,2,3,4,5), true)) {
    $issue_game_count = 5;
}

if (!function_exists('mjtto_is_issue_blocked_time')) {
    function mjtto_is_issue_blocked_time()
    {
        $week_no = (int)date('w');
        $time_his = date('H:i:s');
        return $week_no === 6 && $time_his >= '20:00:00' && $time_his <= '21:59:59';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (mjtto_is_issue_blocked_time()) {
        alert('이번회차 경품권 발행종료 20시 이후부터 다음회차 발권 가능');
    }

    $issue_qty = isset($_POST['issue_qty']) ? (int)$_POST['issue_qty'] : 0; // 발권 매수

    if (!$current_round['round_id']) {
        alert('현재 발권 가능한 회차가 없습니다.');
    }

    if ($issue_qty < 1) {
        alert('발권 매수는 1 이상이어야 합니다.');
    }

    if ($issue_qty > 100) {
        alert('한 번에 최대 100매까지 발권할 수 있습니다.');
    }

    $total_game_count = $issue_qty * $issue_game_count;

    if ($available_cnt < $total_game_count) {
        alert('발권 가능한 번호가 부족합니다.');
    }

    $round_no      = (int)$current_round['round_no'];
    $contract_id   = (int)$branch['parent_company_id'];
    $branch_id     = (int)$branch['company_id'];
    $branch_code   = trim($branch['company_code']);
    $created_by    = trim($member['mb_id']);
    $created_ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $now           = G5_TIME_YMDHIS;

    sql_query('START TRANSACTION', false);

    $rows = array();
    $res = sql_query("
        SELECT uid, a, b, c, d, e, f, draw_order
          FROM mjtto_db
         WHERE chk = 0
         ORDER BY draw_order ASC, uid ASC
         LIMIT {$total_game_count}
         FOR UPDATE
    ", false);

    while ($row = sql_fetch_array($res)) {
        $rows[] = $row;
    }

    if (count($rows) !== $total_game_count) {
        sql_query('ROLLBACK', false);
        alert('발권 가능한 번호 조회 중 오류가 발생했습니다.');
    }

    $insert_issue = sql_query("
        INSERT INTO mz_issue
            SET issue_no      = '',
                company_id    = '{$contract_id}',
                branch_id     = '{$branch_id}',
                round_no      = '{$round_no}',
                issue_qty     = '{$issue_qty}',
                issue_game_count = '{$issue_game_count}',
                issue_status  = 'ISSUED',
                issue_memo    = '',
                created_by    = '".sql_real_escape_string($created_by)."',
                created_ip    = '".sql_real_escape_string($created_ip)."',
                created_at    = '{$now}'
    ", false);

    if (!$insert_issue) {
        sql_query('ROLLBACK', false);
        alert('발권 헤더 생성 중 오류가 발생했습니다.');
    }

    $issue_id = (int)sql_insert_id();

    if (!$issue_id) {
        sql_query('ROLLBACK', false);
        alert('발권 헤더 생성 중 오류가 발생했습니다.');
    }

    $issue_no = $branch_code . '-' . $round_no . '-' . str_pad($issue_id, 8, '0', STR_PAD_LEFT);

    $update_issue = sql_query("
        UPDATE mz_issue
           SET issue_no = '".sql_real_escape_string($issue_no)."'
         WHERE issue_id = '{$issue_id}'
    ", false);

    if (!$update_issue) {
        sql_query('ROLLBACK', false);
        alert('발권번호 저장 중 오류가 발생했습니다.');
    }

    $game_index = 0;

    foreach ($rows as $row) {
        $game_index++;

        $uid = (int)$row['uid'];

        $update_result = sql_query("
            UPDATE mjtto_db
               SET chk      = 1,
                   round_no = '{$round_no}',
                   name     = '".sql_real_escape_string($issue_no)."',
                   ip       = '".sql_real_escape_string($created_ip)."',
                   used_at  = '{$now}'
             WHERE uid = '{$uid}'
               AND chk = 0
        ", false);

        if (!$update_result) {
            sql_query('ROLLBACK', false);
            alert('원본 사용 처리 중 오류가 발생했습니다.');
        }

        $ticket_no = $issue_no . '-' . str_pad($game_index, 3, '0', STR_PAD_LEFT);

        $insert_item = sql_query("
            INSERT INTO mz_issue_item
                SET issue_id       = '{$issue_id}',
                    ticket_no      = '".sql_real_escape_string($ticket_no)."',
                    round_no       = '{$round_no}',
                    num_a          = '".(int)$row['a']."',
                    num_b          = '".(int)$row['b']."',
                    num_c          = '".(int)$row['c']."',
                    num_d          = '".(int)$row['d']."',
                    num_e          = '".(int)$row['e']."',
                    num_f          = '".(int)$row['f']."',
                    item_status    = 'ISSUED',
                    customer_name  = '',
                    customer_hp    = '',
                    sent_at        = NULL,
                    created_at     = '{$now}'
        ", false);

        if (!$insert_item) {
            sql_query('ROLLBACK', false);
            alert('발권 상세 저장 중 오류가 발생했습니다.');
        }
    }

    sql_query('COMMIT', false);
    goto_url('./issue_list.php');
}

$g5['title'] = '발권하기';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);} 
h1{margin:0 0 20px;font-size:28px;}
.table-form{width:100%;border-collapse:collapse;}
.table-form th,.table-form td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;vertical-align:middle;}
.table-form th{width:180px;background:#fafafa;font-size:14px;}
.input{width:100%;max-width:240px;height:42px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;}
.readonly-box{display:inline-block;padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;min-width:220px;}
.btns{margin-top:24px;}
.btns a,.btns button{display:inline-block;min-width:110px;height:42px;line-height:42px;padding:0 18px;border:0;border-radius:8px;text-decoration:none;font-size:14px;cursor:pointer;}
.btn-submit{background:#111827;color:#fff;}
.btn-list{background:#e5e7eb;color:#111827;}
.desc{margin-top:8px;font-size:12px;color:#777;}
</style>

<div class="box">
    <h1>발권하기</h1>

    <form method="post" action="">
        <table class="table-form">
            <tr>
                <th>지점</th>
                <td><span class="readonly-box"><?php echo get_text($branch['company_code'].' / '.$branch['company_name']); ?></span></td>
            </tr>
            <tr>
                <th>쿠폰구분자</th>
                <td><span class="readonly-box"><?php echo get_text($branch['coupon_prefix']); ?></span></td>
            </tr>
            <tr>
                <th>현재 회차</th>
                <td><span class="readonly-box"><?php echo $current_round['round_no'] ? (int)$current_round['round_no'].'회' : '회차없음'; ?></span></td>
            </tr>
            <tr>
                <th>추첨일</th>
                <td><span class="readonly-box"><?php echo $current_round['draw_date'] ? $current_round['draw_date'] : '-'; ?></span></td>
            </tr>
            <tr>
                <th>지급기한</th>
                <td><span class="readonly-box"><?php echo $current_round['payout_deadline'] ? $current_round['payout_deadline'] : '-'; ?></span></td>
            </tr>
            <tr>
                <th>발권당 게임수</th>
                <td><span class="readonly-box"><?php echo number_format($issue_game_count); ?> 게임</span></td>
            </tr>
            <tr>
                <th>발권 가능 게임수</th>
                <td><span class="readonly-box"><?php echo number_format($available_cnt); ?> 게임</span></td>
            </tr>
            <tr>
                <th>발권 매수</th>
                <td>
                    <input type="number" name="issue_qty" value="1" min="1" max="100" class="input" required>
                    <div class="desc">입력한 매수만큼 발권되며, 총 <?php echo number_format($issue_game_count); ?>게임씩 생성됩니다.</div>
                    <div class="desc">발권 가능 시간: 토요일 22:00부터 다음 토요일 19:59까지 / 토요일 20:00~21:59는 발권이 중단됩니다.</div>
                </td>
            </tr>
        </table>

        <div class="btns">
            <button type="submit" class="btn-submit">발권실행</button>
            <a href="./issue_list.php" class="btn-list">발권리스트</a>
        </div>
    </form>
</div>

</div>
</body>
</html>
