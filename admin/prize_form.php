<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-09 23:35:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
$selected_company_id = 0;
$selected_company = array();
$return_query = array();

$current_round = sql_fetch("\n    SELECT round_no, draw_date, payout_deadline, status\n      FROM mz_round\n     WHERE status = 'OPEN'\n     ORDER BY round_no DESC\n     LIMIT 1\n");

if (!$current_round || empty($current_round['round_no'])) {
    alert('현재 설정 가능한 OPEN 회차가 없습니다.', './index.php');
}

$current_round_no = (int)$current_round['round_no'];
$next_round = sql_fetch("\n    SELECT round_no, draw_date\n      FROM mz_round\n     WHERE round_no > '{$current_round_no}'\n     ORDER BY round_no ASC\n     LIMIT 1\n");
$next_round_no = !empty($next_round['round_no']) ? (int)$next_round['round_no'] : ($current_round_no + 1);
$current_issue_row = sql_fetch("SELECT COUNT(*) AS cnt FROM mz_issue WHERE round_no = '{$current_round_no}'");
$current_issue_cnt = (int)($current_issue_row['cnt'] ?? 0);
$save_round_no = $current_issue_cnt > 0 ? $next_round_no : $current_round_no;
$is_waiting_save = $save_round_no > $current_round_no;

$owner_type = 'SYSTEM';
$owner_company_id = 0;
$allowed_ranks = array();
$readonly_ranks = array();
$title_label = '경품 설정';

if ($auth['role'] === 'SUPER_ADMIN') {
    $selected_company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
    if ($selected_company_id > 0) {
        $selected_company = sql_fetch("
            SELECT company_id, company_name, company_code
              FROM mz_company
             WHERE company_id = '{$selected_company_id}'
               AND company_type = 'CONTRACT'
               AND status = 1
             LIMIT 1
        ");

        if (!$selected_company || empty($selected_company['company_id'])) {
            alert('선택한 제휴사를 찾을 수 없습니다.', './index.php');
        }

        $owner_type = 'COMPANY';
        $owner_company_id = (int)$selected_company['company_id'];
        $allowed_ranks = array(3, 4, 5);
        $readonly_ranks = array(1, 2);
        $title_label = '제휴사별 경품 설정 - ' . trim((string)$selected_company['company_name']);
        $return_query['company_id'] = $owner_company_id;
    } else {
        $owner_type = 'SYSTEM';
        $allowed_ranks = array(1, 2, 3, 4, 5);
        $readonly_ranks = array();
        $title_label = '최고관리자 경품 설정';
    }
} elseif ($auth['role'] === 'COMPANY_ADMIN') {
    $owner_type = 'COMPANY';
    $owner_company_id = (int)$auth['company_id'];
    $allowed_ranks = array(3, 4, 5);
    $readonly_ranks = array(1, 2);
    $title_label = '제휴사 경품 설정';
    $selected_company = sql_fetch("
        SELECT company_id, company_name, company_code
          FROM mz_company
         WHERE company_id = '{$owner_company_id}'
           AND company_type = 'CONTRACT'
         LIMIT 1
    ");
} elseif ($auth['role'] === 'BRANCH_ADMIN') {
    $owner_type = 'BRANCH';
    $owner_company_id = (int)$auth['company_id'];
    $allowed_ranks = array(5);
    $readonly_ranks = array(1, 2, 3, 4);
    $title_label = '지점 경품 설정';
} else {
    alert('경품 설정 권한이 없습니다.', './index.php');
}

$return_url = './prize_form.php';
if (!empty($return_query)) {
    $return_url .= '?' . http_build_query($return_query);
}

$owner_type_sql = sql_real_escape_string($owner_type);
$owner_where_sql = $owner_type === 'SYSTEM'
    ? "owner_type = 'SYSTEM' AND owner_company_id IS NULL"
    : "owner_type = '{$owner_type_sql}' AND owner_company_id = '{$owner_company_id}'";

$latest_owned_map = array();
$res_latest = sql_query("\n    SELECT prize_id, round_no, prize_rank, prize_name, prize_desc, created_at, owner_type, owner_company_id\n      FROM mz_round_prize\n     WHERE is_active = 1\n       AND {$owner_where_sql}\n     ORDER BY prize_rank ASC, round_no DESC, created_at DESC, prize_id DESC\n", false);
while ($res_latest && ($row = sql_fetch_array($res_latest))) {
    $rank = (int)$row['prize_rank'];
    if (!isset($latest_owned_map[$rank])) {
        $latest_owned_map[$rank] = $row;
    }
}

$scope_company_id = 0;
$scope_branch_id = 0;
if ($auth['role'] === 'COMPANY_ADMIN') {
    $scope_company_id = (int)$auth['company_id'];
} elseif ($auth['role'] === 'BRANCH_ADMIN') {
    $scope_branch_id = (int)$auth['company_id'];
}

$current_live_map = mjtto_get_prize_map($current_round_no, $scope_company_id, $scope_branch_id);
$save_live_map = mjtto_get_prize_map($save_round_no, $scope_company_id, $scope_branch_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'save'));

    if ($action === 'delete_latest') {
        $delete_rank = (int)($_POST['delete_rank'] ?? 0);
        if (!in_array($delete_rank, $allowed_ranks, true)) {
            alert('삭제할 수 없는 등수입니다.', $return_url);
        }

        $latest_row = $latest_owned_map[$delete_rank] ?? array();
        if (empty($latest_row['prize_id'])) {
            alert('삭제할 최근 등록 내역이 없습니다.', $return_url);
        }

        $latest_round_no = (int)$latest_row['round_no'];
        if ($current_issue_cnt > 0 && $latest_round_no <= $current_round_no) {
            alert('현재 회차 발권이 시작된 적용중 경품은 삭제할 수 없습니다. 다음 회차 등록분만 삭제해 주세요.', $return_url);
        }

        $prize_id = (int)$latest_row['prize_id'];
        $ok = sql_query("\n            UPDATE mz_round_prize\n               SET is_active = 0\n             WHERE prize_id = '{$prize_id}'\n               AND is_active = 1\n               AND {$owner_where_sql}\n        ", false);

        if (!$ok) {
            alert('경품 삭제 중 오류가 발생했습니다.', $return_url);
        }

        alert('최근 등록 경품을 삭제했습니다.', $return_url);
    }

    $insert_count = 0;
    $created_by_sql = sql_real_escape_string($member['mb_id']);
    $owner_id_sql = $owner_type === 'SYSTEM' ? 'NULL' : "'{$owner_company_id}'";

    sql_query('START TRANSACTION', false);

    foreach ($allowed_ranks as $rank) {
        $name = trim((string)($_POST['prize_name'][$rank] ?? ''));
        $desc = trim((string)($_POST['prize_desc'][$rank] ?? ''));

        if ($name === '' && $desc === '') {
            continue;
        }

        $latest_name = isset($latest_owned_map[$rank]) ? trim((string)$latest_owned_map[$rank]['prize_name']) : '';
        $latest_desc = isset($latest_owned_map[$rank]) ? trim((string)$latest_owned_map[$rank]['prize_desc']) : '';

        if ($name === $latest_name && $desc === $latest_desc) {
            continue;
        }

        $name_sql = sql_real_escape_string($name);
        $desc_sql = sql_real_escape_string($desc);
        $ok = sql_query("\n            INSERT INTO mz_round_prize\n                SET round_no = '{$save_round_no}',\n                    owner_type = '{$owner_type_sql}',\n                    owner_company_id = {$owner_id_sql},\n                    prize_rank = '{$rank}',\n                    prize_name = '{$name_sql}',\n                    prize_desc = '{$desc_sql}',\n                    is_active = 1,\n                    created_by = '{$created_by_sql}'\n        ", false);

        if (!$ok) {
            sql_query('ROLLBACK', false);
            alert('경품 저장 중 오류가 발생했습니다.');
        }

        $insert_count++;
    }

    if ($insert_count < 1) {
        sql_query('ROLLBACK', false);
        alert('변경된 경품이 없습니다.', $return_url);
    }

    sql_query('COMMIT', false);

    if ($is_waiting_save) {
        alert('현재 회차는 발권이 시작되어 변경 내용을 다음 회차 기준으로 등록했습니다.', $return_url);
    }

    alert('경품 설정이 저장되었습니다.', $return_url);
}

$g5['title'] = $title_label;
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);} 
h1{margin:0 0 20px;font-size:28px;}
.table-form{width:100%;border-collapse:collapse;}
.table-form th,.table-form td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;vertical-align:middle;}
.table-form th{width:140px;background:#fafafa;font-size:14px;}
.input{width:100%;max-width:360px;height:42px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;}
.desc{font-size:12px;color:#777;line-height:1.6;}
.info-card{margin:0 0 18px;padding:14px 16px;border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb;}
.info-card strong{display:block;margin-bottom:4px;color:#111827;}
.rank-box{margin-bottom:18px;padding:16px;border:1px solid #ececec;border-radius:12px;background:#fff;}
.rank-title{font-size:16px;font-weight:700;margin-bottom:10px;color:#111827;}
.rank-meta{margin-bottom:8px;}
.rank-actions{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;}
.btns{margin-top:24px;}
.btns a,.btns button,.rank-actions button{display:inline-block;min-width:110px;height:42px;line-height:42px;padding:0 18px;border:0;border-radius:8px;text-decoration:none;font-size:14px;cursor:pointer;}
.btn-submit{background:#111827;color:#fff;}
.btn-delete{background:#b91c1c;color:#fff;}
.btn-list{background:#e5e7eb;color:#111827;}
.text-live{margin-bottom:6px;color:#111827;}
.text-pending{color:#9a3412;}
.readonly-box{margin-bottom:18px;padding:14px 16px;border-radius:12px;background:#f9fafb;border:1px solid #e5e7eb;}
</style>
<div class="box">
    <h1><?php echo get_text($title_label); ?></h1>

    <div class="info-card">
        <strong>현재 적용 기준</strong>
        <div class="desc">현재 OPEN 회차: <?php echo $current_round_no; ?>회 / 추첨일: <?php echo get_text($current_round['draw_date']); ?></div>
        <div class="desc">현재 회차 발권건수: <?php echo number_format($current_issue_cnt); ?>건</div>
        <?php if ($is_waiting_save): ?>
        <div class="desc text-pending">현재 회차는 발권이 시작되어 이번 저장은 <?php echo $save_round_no; ?>회차부터 적용됩니다.</div>
        <?php else: ?>
        <div class="desc">현재 회차 발권이 없어 이번 저장은 <?php echo $save_round_no; ?>회차에 즉시 적용됩니다.</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($readonly_ranks)): ?>
    <div class="readonly-box">
        <strong>상위 권한 자동 적용 경품</strong>
        <?php foreach ($readonly_ranks as $rank): $ro = $current_live_map[$rank] ?? null; ?>
        <div class="desc" style="margin-top:6px;"><?php echo $rank; ?>등: <?php echo $ro ? get_text($ro['prize_name']) : '설정 없음'; ?><?php if ($ro && trim((string)$ro['prize_desc']) !== ''): ?> / <?php echo get_text($ro['prize_desc']); ?><?php endif; ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($selected_company['company_id']) && $owner_type === 'COMPANY'): ?>
    <div class="readonly-box">
        <strong>설정 대상 제휴사</strong>
        <div class="desc"><?php echo get_text($selected_company['company_name']); ?><?php if (!empty($selected_company['company_code'])): ?> (<?php echo get_text($selected_company['company_code']); ?>)<?php endif; ?></div>
    </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="action" value="save">
        <?php foreach ($allowed_ranks as $rank):
            $latest_owned = $latest_owned_map[$rank] ?? array('prize_id'=>0, 'round_no'=>'', 'prize_name'=>'', 'prize_desc'=>'', 'created_at'=>'');
            $current_live = $current_live_map[$rank] ?? null;
            $save_live = $save_live_map[$rank] ?? null;
            $can_delete_latest = !empty($latest_owned['prize_id']) && !($current_issue_cnt > 0 && (int)$latest_owned['round_no'] <= $current_round_no);
        ?>
        <div class="rank-box">
            <div class="rank-title"><?php echo $rank; ?>등 경품</div>
            <div class="rank-meta text-live">
                현재 사용중: <?php echo $current_live ? get_text($current_live['prize_name']) : '설정 없음'; ?>
                <?php if ($current_live && trim((string)$current_live['prize_desc']) !== ''): ?> / <?php echo get_text($current_live['prize_desc']); ?><?php endif; ?>
            </div>
            <div class="rank-meta desc">
                저장 후 기준: <?php echo $save_round_no; ?>회차
                <?php if ($save_live): ?>
                    / 현재 조회상 적용값: <?php echo get_text($save_live['prize_name']); ?><?php if (trim((string)$save_live['prize_desc']) !== ''): ?> / <?php echo get_text($save_live['prize_desc']); ?><?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($latest_owned['created_at'])): ?>
            <div class="rank-meta desc">
                내 최근 등록: <?php echo get_text($latest_owned['prize_name']); ?><?php if (trim((string)$latest_owned['prize_desc']) !== ''): ?> / <?php echo get_text($latest_owned['prize_desc']); ?><?php endif; ?> / 적용시작 <?php echo (int)$latest_owned['round_no']; ?>회 / 등록 <?php echo get_text($latest_owned['created_at']); ?>
            </div>
            <?php endif; ?>
            <div style="margin-bottom:8px;"><input type="text" name="prize_name[<?php echo $rank; ?>]" value="<?php echo get_text($latest_owned['prize_name']); ?>" class="input" placeholder="경품명"></div>
            <div><input type="text" name="prize_desc[<?php echo $rank; ?>]" value="<?php echo get_text($latest_owned['prize_desc']); ?>" class="input" placeholder="상세설명"></div>
            <div class="rank-actions">
                <?php if ($can_delete_latest): ?>
                <button type="submit" class="btn-delete" formaction="" formmethod="post" name="action" value="delete_latest" onclick="return confirm('최근 등록 경품을 삭제하시겠습니까?');">최근 등록 삭제</button>
                <input type="hidden" name="delete_rank" value="">
                <script>
                (function(){
                    var buttons = document.querySelectorAll('.rank-box .btn-delete');
                    if(!buttons.length) return;
                })();
                </script>
                <?php else: ?>
                <span class="desc">적용중인 현재 회차 경품은 삭제할 수 없습니다.</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="btns">
            <button type="submit" class="btn-submit">저장</button>
            <a href="./index.php" class="btn-list">관리자 홈</a>
        </div>
    </form>

    <form id="deleteLatestForm" method="post" action="" style="display:none;">
        <input type="hidden" name="action" value="delete_latest">
        <input type="hidden" name="delete_rank" id="delete_rank_field" value="0">
    </form>
</div>
<script>
document.addEventListener('click', function(e){
    var btn = e.target.closest('.btn-delete');
    if(!btn){ return; }
    e.preventDefault();
    var box = btn.closest('.rank-box');
    if(!box){ return; }
    var title = box.querySelector('.rank-title');
    var rank = title ? parseInt(title.textContent, 10) : 0;
    if(!rank){ return; }
    if(!confirm('최근 등록 경품을 삭제하시겠습니까?')){ return; }
    document.getElementById('delete_rank_field').value = rank;
    document.getElementById('deleteLatestForm').submit();
});
</script>
</div>
</body>
</html>
