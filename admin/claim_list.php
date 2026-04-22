<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-15 04:28:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();

if (!mjtto_claim_table_exists()) {
    alert('mz_prize_claim 테이블이 없습니다. SQL 패치를 먼저 적용해 주세요.', './index.php');
}

$scope_sql = mjtto_get_claim_scope_sql($auth, 'pc');
$reward_ticket_column = '';
if (mjtto_claim_column_exists('reward_ticket_no')) {
    $reward_ticket_column = 'reward_ticket_no';
} elseif (mjtto_claim_column_exists('reward_issue_ticket_no')) {
    $reward_ticket_column = 'reward_issue_ticket_no';
}
$has_reward_ticket_column = ($reward_ticket_column !== '');
$has_sms_result_column = mjtto_claim_column_exists('sms_send_result');
$has_sms_sent_at_column = mjtto_claim_column_exists('sms_sent_at');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = 20;
$from_record = ($page - 1) * $rows;

$current_round = mjtto_get_current_issue_round();
$previous_round = mjtto_get_previous_draw_round();
$round_no_raw = isset($_GET['round_no']) ? trim((string)$_GET['round_no']) : null;
$round_no = ($round_no_raw !== null && $round_no_raw !== '') ? (int)$round_no_raw : 0;
if ($round_no_raw === null && !empty($previous_round['round_no'])) {
    $round_no = (int)$previous_round['round_no'];
}
$round_select_last_no = !empty($previous_round['round_no']) ? (int)$previous_round['round_no'] : (int)$current_round['round_no'];
$round_options = mjtto_get_round_select_options($round_select_last_no, 10);
$claim_status = trim((string)($_GET['claim_status'] ?? ''));
$result_rank = isset($_GET['result_rank']) ? (int)$_GET['result_rank'] : 0;
$stx = trim((string)($_GET['stx'] ?? ''));

$sql_search = "\n    WHERE {$scope_sql}\n";
if ($round_no > 0) {
    $sql_search .= " AND pc.round_no = '{$round_no}'\n";
}
if ($claim_status !== '') {
    $claim_status_sql = sql_real_escape_string($claim_status);
    $sql_search .= " AND pc.claim_status = '{$claim_status_sql}'\n";
}
if ($result_rank > 0) {
    $sql_search .= " AND pc.result_rank = '{$result_rank}'\n";
}
if ($stx !== '') {
    $stx_sql = sql_real_escape_string($stx);
    $stx_hp = preg_replace('/[^0-9]/', '', $stx);
    $stx_hp_sql = sql_real_escape_string($stx_hp);

    $sql_search .= "
      AND (
            pc.ticket_no LIKE '%{$stx_sql}%'
            OR pc.request_name LIKE '%{$stx_sql}%'
            OR pc.request_hp LIKE '%{$stx_sql}%'
            OR i.issue_no LIKE '%{$stx_sql}%'
            OR c.company_name LIKE '%{$stx_sql}%'
            OR b.company_name LIKE '%{$stx_sql}%'
    ";
    if ($has_reward_ticket_column) {
        $sql_search .= " OR pc.{$reward_ticket_column} LIKE '%{$stx_sql}%' ";
    } else {
        $sql_search .= " OR pc.admin_memo LIKE '%{$stx_sql}%' ";
    }
    if ($stx_hp_sql !== '') {
        $sql_search .= " OR REPLACE(REPLACE(REPLACE(pc.request_hp, '-', ''), ' ', ''), '.', '') LIKE '%{$stx_hp_sql}%' ";
    }
    $sql_search .= "
      )
    ";
}

$sql_common = "
    FROM mz_prize_claim pc
    JOIN mz_issue i
      ON pc.issue_id = i.issue_id
    LEFT JOIN mz_company c
      ON pc.company_id = c.company_id
    LEFT JOIN mz_company b
      ON pc.branch_id = b.company_id
    {$sql_search}
";

$total_row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common}");
$total_count = (int)($total_row['cnt'] ?? 0);
$total_page = max(1, (int)ceil($total_count / $rows));

$summary = sql_fetch("\n    SELECT\n        COUNT(*) AS total_count,\n        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_REQUEST' THEN 1 ELSE 0 END), 0) AS request_count,\n        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_APPROVED' THEN 1 ELSE 0 END), 0) AS approved_count,\n        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_DONE' THEN 1 ELSE 0 END), 0) AS done_count,\n        COALESCE(SUM(CASE WHEN pc.claim_status IN ('CLAIM_HOLD', 'CLAIM_REJECT') THEN 1 ELSE 0 END), 0) AS hold_reject_count\n    {$sql_common}\n");

$result = sql_query("\n    SELECT\n        pc.*,\n        i.issue_no,\n        i.created_by AS issue_created_by,\n        i.created_at AS issue_created_at,\n        c.company_name AS contract_name,\n        b.company_name AS branch_name\n    {$sql_common}\n    ORDER BY pc.claim_id DESC\n    LIMIT {$from_record}, {$rows}\n");

$query_params = array();
if ($round_no > 0) $query_params['round_no'] = $round_no;
if ($claim_status !== '') $query_params['claim_status'] = $claim_status;
if ($result_rank > 0) $query_params['result_rank'] = $result_rank;
if ($stx !== '') $query_params['stx'] = $stx;
$qstr = http_build_query($query_params);
$qstr_prefix = $qstr ? '&' . $qstr : '';
$return_url = './claim_list.php' . ($qstr ? '?' . $qstr . '&page=' . $page : '?page=' . $page);

$active_filters = array();
if ($round_no > 0) $active_filters[] = $round_no . '회';
if ($claim_status !== '') $active_filters[] = mjtto_claim_status_name($claim_status);
if ($result_rank > 0) $active_filters[] = $result_rank . '등';
if ($stx !== '') $active_filters[] = '키워드 ' . $stx;

$g5['title'] = '경품 지급 목록';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.top h1{margin:0;font-size:28px;}
.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px;}
.summary-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.summary-label{font-size:12px;color:#6b7280;margin-bottom:8px;}
.summary-value{font-size:26px;font-weight:700;color:#111827;line-height:1.2;}
.summary-desc{margin-top:6px;font-size:12px;color:#6b7280;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.search-form{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:16px;padding:16px;border:1px solid #eef2f7;border-radius:12px;background:#f9fafb;}
.search-field{display:flex;flex-direction:column;gap:6px;}
.search-field label{font-size:12px;color:#6b7280;}
.search-field input,.search-field select{height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;min-width:130px;box-sizing:border-box;}
.search-field.keyword{flex:1;min-width:220px;}
.search-field.keyword input{width:100%;}
.btn{display:inline-block;height:38px;line-height:38px;padding:0 14px;border-radius:8px;text-decoration:none;font-size:13px;border:0;cursor:pointer;}
.btn-primary{background:#111827;color:#fff;}
.btn-light{background:#f3f4f6;color:#111827;}
.table-wrap{overflow-x:auto; width:1400px;}
.table{width:100%;border-collapse:collapse;min-width:1100px;max-width:1350px;}
.table th,.table td{padding:12px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:13px;vertical-align:top;}
.table th{background:#fafafa;white-space:nowrap;}
.sub{font-size:12px;color:#6b7280;line-height:1.6;}
.paging{margin-top:16px;text-align:center;}
.paging a,.paging strong{display:inline-block;min-width:36px;height:36px;line-height:36px;margin:0 3px;padding:0 8px;border-radius:8px;text-decoration:none;font-size:14px;}
.paging a{background:#f3f4f6;color:#111827;}
.paging strong{background:#111827;color:#fff;}
.action-group{display:flex;gap:6px;flex-wrap:wrap;}
.empty{padding:30px 10px;text-align:center;color:#777;}
.notice{padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;color:#4b5563;line-height:1.7;margin-bottom:16px;}
.filter-summary{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:0 0 14px;}
.filter-chips{display:flex;flex-wrap:wrap;gap:8px;}
.filter-chip{display:inline-block;padding:5px 10px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:12px;font-weight:700;line-height:1.2;}
.count{margin-bottom:12px;color:#666;font-size:14px;}
.list-meta{font-size:13px;color:#6b7280;}
@media (max-width: 900px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media (max-width: 640px){.summary-grid{grid-template-columns:1fr;}}
</style>

<div class="top">
    <h1>경품 지급 목록</h1>
    <div>
        <a href="./issue_list.php" class="btn btn-light">발권리스트</a>
    </div>
</div>
<div class="notice">제휴사와 지점은 당첨자 정보로 <strong>지급요청</strong>을 등록 그 외 처리는 최고관리자만 가능. 5등은 일반 완료 대신 경품권문자전송으로 처리, 문자 전송 성공 시 <strong>지급완료</strong> 처리합니다.</div>

<div class="summary-grid">
    <div class="summary-card"><div class="summary-label"><?php echo !empty($active_filters) ? '검색 지급건' : '총 지급건'; ?></div><div class="summary-value"><?php echo number_format((int)$summary['total_count']); ?></div><div class="summary-desc"><?php echo !empty($active_filters) ? '현재 검색조건에 맞는 지급 이력' : '현재 권한 범위 기준 전체 지급 이력'; ?></div></div>
    <div class="summary-card"><div class="summary-label">지급요청</div><div class="summary-value"><?php echo number_format((int)$summary['request_count']); ?></div><div class="summary-desc">현재 승인 대기 중인 요청 건수</div></div>
    <div class="summary-card"><div class="summary-label">지급승인</div><div class="summary-value"><?php echo number_format((int)$summary['approved_count']); ?></div><div class="summary-desc">승인되었고 완료 대기 중인 건수</div></div>
    <div class="summary-card"><div class="summary-label">지급완료</div><div class="summary-value"><?php echo number_format((int)$summary['done_count']); ?></div><div class="summary-desc">완료 <?php echo number_format((int)$summary['done_count']); ?>건 · 보류/반려 <?php echo number_format((int)$summary['hold_reject_count']); ?>건</div></div>
</div>

<div class="box">
    <form method="get" class="search-form">
        <div class="search-field">
            <label for="round_no">회차</label>
            <select name="round_no" id="round_no" class="auto-submit">
                <option value="">전체</option>
                <?php foreach ($round_options as $round_row) { ?>
                <option value="<?php echo (int)$round_row['round_no']; ?>" <?php echo $round_no === (int)$round_row['round_no'] ? 'selected' : ''; ?>><?php echo (int)$round_row['round_no']; ?>회</option>
                <?php } ?>
            </select>
        </div>
        <div class="search-field">
            <label for="claim_status">지급상태</label>
            <select name="claim_status" id="claim_status" class="auto-submit">
                <option value="">전체</option>
                <option value="CLAIM_REQUEST" <?php echo $claim_status === 'CLAIM_REQUEST' ? 'selected' : ''; ?>>지급요청</option>
                <option value="CLAIM_APPROVED" <?php echo $claim_status === 'CLAIM_APPROVED' ? 'selected' : ''; ?>>지급승인</option>
                <option value="CLAIM_DONE" <?php echo $claim_status === 'CLAIM_DONE' ? 'selected' : ''; ?>>지급완료</option>
                <option value="CLAIM_HOLD" <?php echo $claim_status === 'CLAIM_HOLD' ? 'selected' : ''; ?>>보류</option>
                <option value="CLAIM_REJECT" <?php echo $claim_status === 'CLAIM_REJECT' ? 'selected' : ''; ?>>반려</option>
            </select>
        </div>
        <div class="search-field">
            <label for="result_rank">등수</label>
            <select name="result_rank" id="result_rank" class="auto-submit">
                <option value="0">전체</option>
                <?php for ($rank = 1; $rank <= 5; $rank++) { ?>
                <option value="<?php echo $rank; ?>" <?php echo $result_rank === $rank ? 'selected' : ''; ?>><?php echo $rank; ?>등</option>
                <?php } ?>
            </select>
        </div>
        <div class="search-field keyword">
            <label for="stx">기타 키워드</label>
            <input type="text" name="stx" id="stx" value="<?php echo get_text($stx); ?>" placeholder="티켓번호, 당첨자명, 휴대폰, 발권번호, 제휴사명, 지점명">
        </div>
        <div class="action-group">
            <button type="submit" class="btn btn-primary">검색</button>
            <a href="./claim_list.php" class="btn btn-light">초기화</a>
        </div>
    </form>

    <div class="filter-summary">
        <div class="filter-chips">
            <?php if (!empty($active_filters)) { ?>
                <?php foreach ($active_filters as $active_filter) { ?>
                    <span class="filter-chip"><?php echo get_text($active_filter); ?></span>
                <?php } ?>
            <?php } else { ?>
                <span class="filter-chip">전체 조건</span>
            <?php } ?>
        </div>
        <div class="list-meta">페이지 <?php echo $page; ?> / <?php echo $total_page; ?></div>
    </div>

    <div class="count">총 <?php echo number_format($total_count); ?>건<?php if (!empty($active_filters)) { ?> · 검색조건 적용<?php } ?></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th width="40">번호</th>
                    <th width="180">티켓 / 발권</th>
                    <th width="120">소속</th>
                    <th width="40">등수</th>
                    <th width="160">경품</th>
                    <th width="80">지급주체</th>
                    <th width="120">당첨자</th>
                    <th width="120">지급상태</th>
                    <th width="120">요청/처리</th>
                    <th >처리</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $number = $total_count - $from_record;
            $i = 0;
            while ($row = sql_fetch_array($result)) {
                $i++;
                $actions = mjtto_claim_allowed_actions($auth, $row);
            ?>
                <tr>
                    <td><?php echo $number; ?></td>
                    <td>
                        <div><?php echo get_text($row['ticket_no']); ?></div>
                        <div class="sub">발권번호: <a href="./issue_view.php?issue_id=<?php echo (int)$row['issue_id']; ?>"><?php echo get_text($row['issue_no']); ?></a></div>
                        <?php $reward_issue_ticket_no = mjtto_claim_get_reward_issue_ticket_no($row); ?>
                        <?php if ($reward_issue_ticket_no !== '') { ?><div class="sub">5등 문자권: <?php echo get_text($reward_issue_ticket_no); ?></div><?php } ?>
                        <div class="sub"><?php echo get_text($row['issue_created_by']); ?> / <?php echo get_text($row['issue_created_at']); ?></div>
                    </td>
                    <td>
                        <div><?php echo get_text($row['contract_name']); ?></div>
                        <div class="sub"><?php echo get_text($row['branch_name']); ?></div>
                    </td>
                    <td><span class="badge badge-dark"><?php echo mjtto_rank_text($row['result_rank']); ?></span></td>
                    <td>
                        <div><?php echo get_text($row['prize_name']); ?></div>
                        <div class="sub"><?php echo get_text($row['prize_desc']); ?></div>
                    </td>
                    <td>
                        <div><?php echo get_text(mjtto_prize_owner_name($row['prize_owner_type'])); ?></div>
                        <?php if ((int)$row['prize_owner_company_id'] > 0) { ?><div class="sub">소속ID: <?php echo (int)$row['prize_owner_company_id']; ?></div><?php } ?>
                    </td>
                    <td>
                        <div><?php echo get_text($row['request_name']); ?></div>
                        <div class="sub"><?php echo get_text($row['request_hp']); ?></div>
                    </td>
                    <td>
                        <span class="badge <?php echo mjtto_claim_badge_class($row['claim_status']); ?>"><?php echo get_text(mjtto_claim_status_name($row['claim_status'])); ?></span>
                        <?php if ($row['paid_at']) { ?><div class="sub">완료일: <?php echo get_text($row['paid_at']); ?></div><?php } ?>
                        <?php if ($has_sms_sent_at_column && !empty($row['sms_sent_at'])) { ?><div class="sub">문자발송: <?php echo get_text($row['sms_sent_at']); ?></div><?php } ?>
                        <?php if ($has_sms_result_column && !empty($row['sms_send_result'])) { ?><div class="sub">문자결과: <?php echo get_text($row['sms_send_result']); ?></div><?php } ?>
                        <?php if ($row['reject_reason']) { ?><div class="sub">사유: <?php echo get_text($row['reject_reason']); ?></div><?php } ?>
                    </td>
                    <td>
                        <div class="sub">요청: <?php echo get_text($row['request_by']); ?><?php if ($row['requested_at']) echo ' / ' . get_text($row['requested_at']); ?></div>
                        <?php if ($row['approve_by']) { ?><div class="sub">승인: <?php echo get_text($row['approve_by']); ?><?php if ($row['approved_at']) echo ' / ' . get_text($row['approved_at']); ?></div><?php } ?>
                        <?php if ($row['paid_by']) { ?><div class="sub">완료: <?php echo get_text($row['paid_by']); ?></div><?php } ?>
                    </td>
                    <td>
                        <div class="action-group">
                            <?php foreach ($actions as $action) {
                                $label = mjtto_claim_action_label($action, $row);
                                if (!$label) continue;
                                $confirm_text = '해당 지급건을 ' . $label . ' 처리하시겠습니까?';
                                if ($action === 'send_5th_sms') {
                                    $confirm_text = '5등 당첨자에게 경품권 문자를 전송하고 지급완료 처리하시겠습니까?';
                                }
                            ?>
                            <form method="post" action="./claim_update.php" onsubmit="return confirm('<?php echo get_text($confirm_text); ?>');">
                                <input type="hidden" name="action" value="<?php echo $action; ?>">
                                <input type="hidden" name="claim_id" value="<?php echo (int)$row['claim_id']; ?>">
                                <input type="hidden" name="return_url" value="<?php echo get_text($return_url); ?>">
                                <button type="submit" class="btn btn-light"><?php echo $label; ?></button>
                            </form>
                            <?php } ?>
                            <a href="./issue_view.php?issue_id=<?php echo (int)$row['issue_id']; ?>" class="btn btn-light">상세</a>
                        </div>
                    </td>
                </tr>
            <?php
                $number--;
            }
            if ($i === 0) {
                echo '<tr><td colspan="10" class="empty">지급 이력이 없습니다.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <div class="paging">
        <?php
        for ($p = 1; $p <= $total_page; $p++) {
            if ($p == $page) {
                echo '<strong>'.$p.'</strong>';
            } else {
                echo '<a href="./claim_list.php?page='.$p.$qstr_prefix.'">'.$p.'</a>';
            }
        }
        ?>
    </div>
</div>

</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.querySelector('.search-form');
    if(!form) return;
    var selects = form.querySelectorAll('select.auto-submit');
    selects.forEach(function(el){
        el.addEventListener('change', function(){ form.submit(); });
    });
});
</script>
</body>
</html>
