<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-13 14:35:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();

$issue_id = isset($_GET['issue_id']) ? (int)$_GET['issue_id'] : 0;
if ($issue_id < 1) {
    alert('잘못된 접근입니다.');
}

$issue = mjtto_get_issue_row($issue_id, $auth);
if (!$issue || empty($issue['issue_id'])) {
    alert('발권 정보를 찾을 수 없습니다.');
}

$draw = mjtto_get_round_draw($issue['round_no']);
$prize_map = mjtto_get_prize_map($issue['round_no'], $issue['company_id'], $issue['branch_id']);
$claim_map = mjtto_get_claim_map_by_issue($issue_id, $auth);
$claim_table_ready = mjtto_claim_table_exists();

$issue_game_count = (int)$issue['issue_game_count'];
if ($issue_game_count < 1) {
    $issue_game_count = 5;
}

$stx = isset($_GET['stx']) ? trim((string)$_GET['stx']) : '';
$result_filter = isset($_GET['result_filter']) ? trim((string)$_GET['result_filter']) : '';
$result_filter_options = array('all', 'win', 'lose', 'pending', 'rank1', 'rank2', 'rank3', 'rank4', 'rank5');
if ($result_filter === '' || !in_array($result_filter, $result_filter_options, true)) {
    $result_filter = 'all';
}

$normalized_stx = preg_replace('/\s+/u', '', $stx);
$keyword_result_filter = '';
if ($normalized_stx !== '') {
    if (in_array($normalized_stx, array('당첨', '당첨자', '당첨권'), true)) {
        $keyword_result_filter = 'win';
    } elseif (in_array($normalized_stx, array('낙첨', '비당첨'), true)) {
        $keyword_result_filter = 'lose';
    } elseif (in_array($normalized_stx, array('추첨대기', '대기'), true)) {
        $keyword_result_filter = 'pending';
    } elseif (preg_match('/^([1-5])등$/u', $normalized_stx, $m)) {
        $keyword_result_filter = 'rank' . $m[1];
    }
}

$result = sql_query("\n    SELECT issue_item_id, ticket_no, round_no, num_a, num_b, num_c, num_d, num_e, num_f, item_status, customer_name, customer_hp, sent_at, created_at\n      FROM mz_issue_item\n     WHERE issue_id = '{$issue_id}'\n     ORDER BY issue_item_id ASC\n");

$rows = array();
$all_count = 0;
$match_count = 0;
$draw_ready = ($draw && !empty($draw['win_a']));
$deadline_expired = mjtto_is_payout_deadline_expired($draw['payout_deadline'] ?? '');

while ($row = sql_fetch_array($result)) {
    $all_count++;
    $page_no = (int)ceil($all_count / $issue_game_count);
    $page_game_no = (($all_count - 1) % $issue_game_count) + 1;
    $nums = sprintf('%02d, %02d, %02d, %02d, %02d, %02d', $row['num_a'], $row['num_b'], $row['num_c'], $row['num_d'], $row['num_e'], $row['num_f']);
    $result_rank = mjtto_calc_issue_item_rank($row, $draw);
    $claim = isset($claim_map[(int)$row['issue_item_id']]) ? $claim_map[(int)$row['issue_item_id']] : null;
    $prize = $result_rank > 0 && isset($prize_map[$result_rank]) ? $prize_map[$result_rank] : null;
    $can_request = $claim_table_ready && !$claim && !$deadline_expired && mjtto_can_request_claim($auth, $issue, $result_rank);
    $can_direct_done = false;
    $actions = $claim ? mjtto_claim_allowed_actions($auth, $claim) : array();

    if ($result_rank > 0) {
        $result_key = 'rank' . $result_rank;
        $result_text = mjtto_rank_text($result_rank);
    } else {
        $result_key = $draw_ready ? 'lose' : 'pending';
        $result_text = $draw_ready ? '낙첨' : '추첨대기';
    }

    $passes_result_filter = ($result_filter === 'all');
    if (!$passes_result_filter) {
        if ($result_filter === 'win') {
            $passes_result_filter = ($result_rank > 0);
        } elseif ($result_filter === 'lose') {
            $passes_result_filter = ($draw_ready && $result_rank < 1);
        } elseif ($result_filter === 'pending') {
            $passes_result_filter = (!$draw_ready && $result_rank < 1);
        } else {
            $passes_result_filter = ($result_filter === $result_key);
        }
    }

    $passes_keyword_filter = true;
    if ($stx !== '') {
        if ($keyword_result_filter !== '') {
            if ($keyword_result_filter === 'win') {
                $passes_keyword_filter = ($result_rank > 0);
            } elseif ($keyword_result_filter === 'lose') {
                $passes_keyword_filter = ($draw_ready && $result_rank < 1);
            } elseif ($keyword_result_filter === 'pending') {
                $passes_keyword_filter = (!$draw_ready && $result_rank < 1);
            } else {
                $passes_keyword_filter = ($keyword_result_filter === $result_key);
            }
        } else {
            $search_targets = array(
                $row['ticket_no'],
                $nums,
                $row['customer_name'],
                $row['customer_hp'],
                $result_text,
                mjtto_item_status_name($row['item_status']),
                $prize ? $prize['prize_name'] : ''
            );
            $passes_keyword_filter = false;
            foreach ($search_targets as $search_target) {
                $search_target = (string)$search_target;
                if ($search_target !== '' && function_exists('mb_stripos')) {
                    if (mb_stripos($search_target, $stx, 0, 'UTF-8') !== false) {
                        $passes_keyword_filter = true;
                        break;
                    }
                } elseif ($search_target !== '' && stripos($search_target, $stx) !== false) {
                    $passes_keyword_filter = true;
                    break;
                }
            }
        }
    }

    if (!$passes_result_filter || !$passes_keyword_filter) {
        continue;
    }

    $match_count++;
    $row['_row_no'] = $all_count;
    $row['_page_no'] = $page_no;
    $row['_page_game_no'] = $page_game_no;
    $row['_nums'] = $nums;
    $row['_result_rank'] = $result_rank;
    $row['_claim'] = $claim;
    $row['_prize'] = $prize;
    $row['_can_request'] = $can_request;
    $row['_can_direct_done'] = $can_direct_done;
    $row['_actions'] = $actions;
    $row['_result_text'] = $result_text;
    $row['_result_key'] = $result_key;
    $rows[] = $row;
}

$return_query = 'issue_id=' . (int)$issue_id;
if ($stx !== '') {
    $return_query .= '&stx=' . urlencode($stx);
}
if ($result_filter !== 'all') {
    $return_query .= '&result_filter=' . urlencode($result_filter);
}
$return_url = './issue_view.php?' . $return_query;
$g5['title'] = '발권상세';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.top h1{margin:0;font-size:28px;}
.top .btn-wrap{display:flex;gap:8px;flex-wrap:wrap;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);margin-bottom:20px;}
.info-table{width:100%;border-collapse:collapse;}
.info-table th,.info-table td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;vertical-align:top;}
.info-table th{width:180px;background:#fafafa;}
.table-wrap{overflow-x:auto;}
.table{width:100%;border-collapse:collapse;min-width:1300px;; max-width:1400px;}
.table th,.table td{padding:12px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:13px;vertical-align:top;}
.table th{background:#fafafa;white-space:nowrap;}
.btn{display:inline-block;height:38px;line-height:38px;padding:0 14px;border-radius:8px;text-decoration:none;font-size:13px;border:0;cursor:pointer;}
.btn-list{background:#111827;color:#fff;}
.btn-view{background:#f3f4f6;color:#111827;}
.btn-save{background:#111827;color:#fff;}
.btn-action{background:#e5e7eb;color:#111827;}
.empty{padding:30px 10px;text-align:center;color:#777;}
.numbers{line-height:1.7;white-space:nowrap;}
.sub{font-size:12px;color:#6b7280;line-height:1.6;}
.inline-form{display:flex;flex-wrap:wrap;gap:6px;align-items:center;}
.inline-form input{height:34px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;}
.inline-form .w-name{width:110px;}
.inline-form .w-hp{width:130px;}
.inline-form .w-memo{width:180px;}
.action-group{display:flex;flex-wrap:wrap;gap:6px;}
.notice{padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;color:#4b5563;line-height:1.7;margin-bottom:16px;}
.filter-box{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:16px;padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fafafa;}
.filter-form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.filter-form input,.filter-form select{height:38px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;background:#fff;}
.filter-form .w-search{width:220px;}
.filter-info{font-size:13px;color:#4b5563;line-height:1.6;}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;line-height:1.2;}
.badge-dark{background:#111827;color:#fff;}
.badge-gray{background:#f3f4f6;color:#374151;}
.badge-blue{background:#eff6ff;color:#1d4ed8;}
.badge-indigo{background:#eef2ff;color:#4338ca;}
.badge-green{background:#ecfdf5;color:#047857;}
.badge-orange{background:#fff7ed;color:#c2410c;}
.badge-red{background:#fef2f2;color:#b91c1c;}
</style>

<div class="top">
    <h1>발권상세</h1>
    <div class="btn-wrap">
        <a href="./claim_list.php" class="btn btn-view">경품지급 목록</a>
        <a href="./issue_list.php" class="btn btn-list">발권리스트</a>
    </div>
</div>

<?php if (!$claim_table_ready) { ?>
<div class="notice">경품 지급 화면은 <strong>mz_prize_claim</strong> 테이블이 생성된 뒤부터 동작합니다. 이번 작업에 포함된 SQL 패치를 먼저 적용하세요.</div>
<?php } else { ?>
<div class="notice">지급 처리 흐름: <strong>1~5등 모두 당첨자 이름·휴대폰번호를 입력해 지급요청</strong>하고, <strong>승인·완료·보류·반려는 최고관리자만 처리</strong>합니다. 지급기한이 지나면 새 요청은 받을 수 없습니다.</div>
<?php } ?>

<div class="box">
    <table class="info-table">
        <tr><th>발권번호</th><td><?php echo get_text($issue['issue_no']); ?></td></tr>
        <tr><th>제휴사</th><td><?php echo get_text($issue['contract_name']); ?></td></tr>
        <tr><th>지점</th><td><?php echo get_text($issue['branch_name']); ?></td></tr>
        <tr><th>회차</th><td><?php echo (int)$issue['round_no']; ?>회<?php if ($draw && !empty($draw['draw_date'])) { ?> <span class="sub">/ 추첨일 <?php echo get_text($draw['draw_date']); ?></span><?php } ?></td></tr>
        <tr><th>발권매수</th><td><?php echo number_format((int)$issue['issue_qty']); ?>매</td></tr>
        <tr><th>발권당 게임수</th><td><?php echo number_format($issue_game_count); ?>게임</td></tr>
        <tr><th>총 발권게임수</th><td><?php echo number_format(((int)$issue['issue_qty']) * $issue_game_count); ?>게임</td></tr>
        <tr><th>상태</th><td><?php echo get_text($issue['issue_status']); ?></td></tr>
        <tr><th>지급기한</th><td><?php echo $issue['payout_deadline'] ? get_text($issue['payout_deadline']) : '-'; ?></td></tr>
        <tr><th>발권자 / 일시</th><td><?php echo get_text($issue['created_by']); ?> / <?php echo get_text($issue['created_at']); ?></td></tr>
    </table>
</div>

<div class="box">
    <div class="filter-box">
        <form method="get" class="filter-form">
            <input type="hidden" name="issue_id" value="<?php echo (int)$issue_id; ?>">
            <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="w-search" placeholder="티켓번호 / 당첨자 / 휴대폰 / 당첨 / 낙첨 / 1등">
			<select name="result_filter" onchange="this.form.submit()">
                <option value="all"<?php echo $result_filter === 'all' ? ' selected' : ''; ?>>전체결과</option>
                <option value="win"<?php echo $result_filter === 'win' ? ' selected' : ''; ?>>당첨 전체</option>
                <option value="lose"<?php echo $result_filter === 'lose' ? ' selected' : ''; ?>>낙첨</option>
                <option value="pending"<?php echo $result_filter === 'pending' ? ' selected' : ''; ?>>추첨대기</option>
                <option value="rank1"<?php echo $result_filter === 'rank1' ? ' selected' : ''; ?>>1등</option>
                <option value="rank2"<?php echo $result_filter === 'rank2' ? ' selected' : ''; ?>>2등</option>
                <option value="rank3"<?php echo $result_filter === 'rank3' ? ' selected' : ''; ?>>3등</option>
                <option value="rank4"<?php echo $result_filter === 'rank4' ? ' selected' : ''; ?>>4등</option>
                <option value="rank5"<?php echo $result_filter === 'rank5' ? ' selected' : ''; ?>>5등</option>
            </select>
            <button type="submit" class="btn btn-save">검색</button>
            <a href="./issue_view.php?issue_id=<?php echo (int)$issue_id; ?>" class="btn btn-view">초기화</a>
        </form>
        <div class="filter-info">
            전체 <?php echo number_format($all_count); ?>건 / 현재 <?php echo number_format($match_count); ?>건
            <div class="sub">검색어에 <strong>당첨</strong>, <strong>낙첨</strong>, <strong>1등</strong>처럼 입력해도 바로 걸러집니다.</div>
        </div>
    </div>
</div>

<div class="box table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th width="50">순번</th>
                <th width="50">출력장</th>
                <th width="30">순번</th>
                <th width="120">티켓번호</th>
                <th width="122">번호</th>
                <th width="70">당첨결과</th>
                <th width="120">경품</th>
                <th width="70">상태</th>
                <th width="100">당첨자</th>
                <th >지급상태</th>
                <th width="110">처리</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($rows as $row) {
            $result_rank = (int)$row['_result_rank'];
            $claim = $row['_claim'];
            $prize = $row['_prize'];
            $can_request = $row['_can_request'];
            $can_direct_done = $row['_can_direct_done'];
            $actions = $row['_actions'];
            $nums = $row['_nums'];
            $page_no = (int)$row['_page_no'];
            $page_game_no = (int)$row['_page_game_no'];
        ?>
            <tr>
                <td><?php echo (int)$row['_row_no']; ?></td>
                <td><?php echo $page_no; ?>장</td>
                <td><?php echo $page_game_no; ?></td>
                <td>
                    <?php echo get_text($row['ticket_no']); ?>
                    <div class="sub"><?php echo get_text($row['created_at']); ?></div>
                </td>
                <td class="numbers"><?php echo $nums; ?></td>
                <td>
                    <?php if ($result_rank > 0) { ?>
                        <span class="badge badge-dark"><?php echo mjtto_rank_text($result_rank); ?></span>
                    <?php } else { ?>
                        <span class="badge badge-gray"><?php echo get_text($row['_result_text']); ?></span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($result_rank > 0) { ?>
                        <div><?php echo $prize ? get_text($prize['prize_name']) : (mjtto_rank_text($result_rank) . ' 경품'); ?></div>
                        <div class="sub"><?php echo $prize && trim($prize['prize_desc']) !== '' ? get_text($prize['prize_desc']) : '상세설명 없음'; ?></div>
                        <div class="sub">지급주체: <?php echo $prize ? get_text(mjtto_prize_owner_name($prize['owner_type'])) : '본사'; ?></div>
                    <?php } else { ?>
                        <span class="sub">-</span>
                    <?php } ?>
                </td>
                <td><?php echo get_text(mjtto_item_status_name($row['item_status'])); ?></td>
                <td>
                    <div><?php echo get_text($row['customer_name']); ?></div>
                    <div class="sub"><?php echo get_text($row['customer_hp']); ?></div>
                </td>
                <td>
                    <?php if ($claim) { ?>
                        <span class="badge <?php echo mjtto_claim_badge_class($claim['claim_status']); ?>"><?php echo get_text(mjtto_claim_status_name($claim['claim_status'])); ?></span>
                        <div class="sub">요청자: <?php echo get_text($claim['request_by']); ?></div>
                        <?php if ($claim['requested_at']) { ?><div class="sub">요청일: <?php echo get_text($claim['requested_at']); ?></div><?php } ?>
                        <?php if ($claim['paid_at']) { ?><div class="sub">지급일: <?php echo get_text($claim['paid_at']); ?></div><?php } ?>
                        <?php if ($claim['reject_reason']) { ?><div class="sub">사유: <?php echo get_text($claim['reject_reason']); ?></div><?php } ?>
                    <?php } else { ?>
                        <span class="badge badge-gray"><?php echo $result_rank > 0 ? '미등록' : '-'; ?></span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($can_request) { ?>
                        <form method="post" action="./claim_update.php" class="inline-form">
                            <input type="hidden" name="action" value="request">
                            <input type="hidden" name="issue_item_id" value="<?php echo (int)$row['issue_item_id']; ?>">
                            <input type="hidden" name="return_url" value="<?php echo get_text($return_url); ?>">
                            <input type="text" name="request_name" value="<?php echo get_text($row['customer_name']); ?>" class="w-name" placeholder="당첨자명" required>
                            <input type="text" name="request_hp" value="<?php echo get_text($row['customer_hp']); ?>" class="w-hp" placeholder="휴대폰" required>
                            <input type="text" name="request_memo" value="" class="w-memo" placeholder="메모(선택)">
                            <button type="submit" class="btn btn-save">지급요청 등록</button>
                        </form>                    <?php } elseif ($claim) { ?>
                        <div class="action-group">
                            <?php foreach ($actions as $action) {
                                $label = mjtto_claim_action_label($action, $claim);
                                if (!$label) continue;
                            ?>
                            <form method="post" action="./claim_update.php" onsubmit="return confirm('해당 지급건을 <?php echo $label; ?> 처리하시겠습니까?');">
                                <input type="hidden" name="action" value="<?php echo $action; ?>">
                                <input type="hidden" name="claim_id" value="<?php echo (int)$claim['claim_id']; ?>">
                                <input type="hidden" name="return_url" value="<?php echo get_text($return_url); ?>">
                                <button type="submit" class="btn btn-action"><?php echo $label; ?></button>
                            </form>
                            <?php } ?>
                            <a href="./claim_list.php?stx=<?php echo urlencode($row['ticket_no']); ?>" class="btn btn-view">목록보기</a>
                        </div>
                    <?php } else { ?>
                        <span class="sub"><?php echo $result_rank > 0 ? ($deadline_expired ? mjtto_claim_deadline_message() : '지급요청 가능') : ($draw_ready ? '지급대상 아님' : '추첨 후 처리'); ?></span>
                    <?php } ?>
                </td>
            </tr>
        <?php }
        if (empty($rows)) {
            echo '<tr><td colspan="11" class="empty">검색 조건에 맞는 발권 내역이 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

</div>
</body>
</html>
