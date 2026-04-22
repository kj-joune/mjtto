<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-12 23:35:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
$ym = mjtto_normalize_month_value($_GET['ym'] ?? '');
$month_range = mjtto_get_month_range($ym);
$month_options = mjtto_get_month_select_options(date('Y-m'), 12);
$month_option_map = array();
foreach ($month_options as $month_row) {
    $month_option_map[(string)$month_row['ym']] = $month_row;
}
if (!isset($month_option_map[$ym])) {
    $selected_month = mjtto_get_month_range($ym);
    $month_option_map[$ym] = array(
        'ym' => $selected_month['ym'],
        'label' => $selected_month['month_label']
    );
    uasort($month_option_map, function ($a, $b) {
        return strcmp((string)$b['ym'], (string)$a['ym']);
    });
}
$month_options = array_values($month_option_map);

$company_filter = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$branch_filter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$normalized_filter = mjtto_normalize_issue_filters($auth, $company_filter, $branch_filter);
$company_filter = (int)$normalized_filter['company_id'];
$branch_filter = (int)$normalized_filter['branch_id'];

$contract_options = mjtto_get_accessible_contracts($auth);
$branch_options = mjtto_get_accessible_branches($auth, $company_filter);
$show_contract_filter = in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true);
$show_branch_filter = in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true);
$show_contract_column = in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true);
$issue_empty_colspan = $show_contract_column ? 11 : 10;
$claim_empty_colspan = $show_contract_column ? 12 : 11;

$issue_scope_sql = mjtto_get_issue_scope_sql($auth, 'i');
$claim_scope_sql = mjtto_get_claim_scope_sql($auth, 'pc');
$start_sql = sql_real_escape_string($month_range['start_datetime']);
$end_sql = sql_real_escape_string($month_range['end_datetime']);

$issue_search = "
    WHERE {$issue_scope_sql}
      AND i.created_at >= '{$start_sql}'
      AND i.created_at < '{$end_sql}'
";
if ($company_filter > 0) {
    $issue_search .= " AND i.company_id = '{$company_filter}'
";
}
if ($branch_filter > 0) {
    $issue_search .= " AND i.branch_id = '{$branch_filter}'
";
}

$claim_search = "
    WHERE {$claim_scope_sql}
      AND COALESCE(pc.requested_at, pc.created_at) >= '{$start_sql}'
      AND COALESCE(pc.requested_at, pc.created_at) < '{$end_sql}'
";
if ($company_filter > 0) {
    $claim_search .= " AND pc.company_id = '{$company_filter}'
";
}
if ($branch_filter > 0) {
    $claim_search .= " AND pc.branch_id = '{$branch_filter}'
";
}

$issue_summary = sql_fetch("
    SELECT
        COUNT(*) AS issue_count,
        COALESCE(SUM(i.issue_qty), 0) AS issue_qty_sum,
        COALESCE(SUM(i.issue_qty * (CASE WHEN i.issue_game_count > 0 THEN i.issue_game_count ELSE 5 END)), 0) AS total_game_sum
      FROM mz_issue i
      LEFT JOIN mz_company c
             ON i.company_id = c.company_id
      LEFT JOIN mz_company b
             ON i.branch_id = b.company_id
     {$issue_search}
");

$issue_item_summary = sql_fetch("
    SELECT
        COALESCE(SUM(CASE WHEN ii.item_status = 'ISSUED' THEN 1 ELSE 0 END), 0) AS issued_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'DRAW_WIN' THEN 1 ELSE 0 END), 0) AS draw_win_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'DRAW_LOSE' THEN 1 ELSE 0 END), 0) AS draw_lose_count,
        COALESCE(SUM(CASE WHEN ii.item_status IN ('CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD') THEN 1 ELSE 0 END), 0) AS claim_wait_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'CLAIM_DONE' THEN 1 ELSE 0 END), 0) AS claim_done_count,
        COALESCE(SUM(CASE WHEN ii.item_status NOT IN ('ISSUED', 'DRAW_WIN', 'DRAW_LOSE', 'CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_DONE', 'CLAIM_HOLD') THEN 1 ELSE 0 END), 0) AS other_count
      FROM mz_issue i
      LEFT JOIN mz_company c
             ON i.company_id = c.company_id
      LEFT JOIN mz_company b
             ON i.branch_id = b.company_id
      LEFT JOIN mz_issue_item ii
             ON ii.issue_id = i.issue_id
     {$issue_search}
");

$claim_summary = sql_fetch("
    SELECT
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_REQUEST' THEN 1 ELSE 0 END), 0) AS request_count,
        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_APPROVED' THEN 1 ELSE 0 END), 0) AS approved_count,
        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_DONE' THEN 1 ELSE 0 END), 0) AS done_count,
        COALESCE(SUM(CASE WHEN pc.claim_status IN ('CLAIM_HOLD', 'CLAIM_REJECT') THEN 1 ELSE 0 END), 0) AS hold_reject_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 1 THEN 1 ELSE 0 END), 0) AS rank1_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 2 THEN 1 ELSE 0 END), 0) AS rank2_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 3 THEN 1 ELSE 0 END), 0) AS rank3_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 4 THEN 1 ELSE 0 END), 0) AS rank4_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 5 THEN 1 ELSE 0 END), 0) AS rank5_count
      FROM mz_prize_claim pc
      LEFT JOIN mz_company c
             ON pc.company_id = c.company_id
      LEFT JOIN mz_company b
             ON pc.branch_id = b.company_id
     {$claim_search}
");

$issue_group_rows = array();
$issue_group_map = array();
$issue_group_res = sql_query("
    SELECT
        i.company_id,
        i.branch_id,
        c.company_name AS contract_name,
        b.company_name AS branch_name,
        COUNT(*) AS issue_count,
        COALESCE(SUM(i.issue_qty), 0) AS issue_qty_sum,
        COALESCE(SUM(i.issue_qty * (CASE WHEN i.issue_game_count > 0 THEN i.issue_game_count ELSE 5 END)), 0) AS total_game_sum
      FROM mz_issue i
      LEFT JOIN mz_company c
             ON i.company_id = c.company_id
      LEFT JOIN mz_company b
             ON i.branch_id = b.company_id
     {$issue_search}
     GROUP BY i.company_id, i.branch_id, c.company_name, b.company_name
     ORDER BY c.company_name ASC, b.company_name ASC, i.company_id ASC, i.branch_id ASC
", false);
if ($issue_group_res) {
    while ($row = sql_fetch_array($issue_group_res)) {
        $key = (int)$row['company_id'] . ':' . (int)$row['branch_id'];
        $issue_group_map[$key] = array(
            'contract_name' => $row['contract_name'],
            'branch_name' => $row['branch_name'],
            'issue_count' => (int)$row['issue_count'],
            'issue_qty_sum' => (int)$row['issue_qty_sum'],
            'total_game_sum' => (int)$row['total_game_sum'],
            'draw_win_count' => 0,
            'claim_wait_count' => 0,
            'claim_done_count' => 0,
            'draw_lose_count' => 0,
            'other_count' => 0,
            'issued_count' => 0
        );
    }
}
$issue_group_item_res = sql_query("
    SELECT
        i.company_id,
        i.branch_id,
        COALESCE(SUM(CASE WHEN ii.item_status = 'ISSUED' THEN 1 ELSE 0 END), 0) AS issued_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'DRAW_WIN' THEN 1 ELSE 0 END), 0) AS draw_win_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'DRAW_LOSE' THEN 1 ELSE 0 END), 0) AS draw_lose_count,
        COALESCE(SUM(CASE WHEN ii.item_status IN ('CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD') THEN 1 ELSE 0 END), 0) AS claim_wait_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'CLAIM_DONE' THEN 1 ELSE 0 END), 0) AS claim_done_count,
        COALESCE(SUM(CASE WHEN ii.item_status NOT IN ('ISSUED', 'DRAW_WIN', 'DRAW_LOSE', 'CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_DONE', 'CLAIM_HOLD') THEN 1 ELSE 0 END), 0) AS other_count
      FROM mz_issue i
      LEFT JOIN mz_company c
             ON i.company_id = c.company_id
      LEFT JOIN mz_company b
             ON i.branch_id = b.company_id
      LEFT JOIN mz_issue_item ii
             ON ii.issue_id = i.issue_id
     {$issue_search}
     GROUP BY i.company_id, i.branch_id
", false);
if ($issue_group_item_res) {
    while ($row = sql_fetch_array($issue_group_item_res)) {
        $key = (int)$row['company_id'] . ':' . (int)$row['branch_id'];
        if (!isset($issue_group_map[$key])) {
            $issue_group_map[$key] = array(
                'contract_name' => '',
                'branch_name' => '',
                'issue_count' => 0,
                'issue_qty_sum' => 0,
                'total_game_sum' => 0,
                'draw_win_count' => 0,
                'claim_wait_count' => 0,
                'claim_done_count' => 0,
                'draw_lose_count' => 0,
                'other_count' => 0,
                'issued_count' => 0
            );
        }
        $issue_group_map[$key]['draw_win_count'] = (int)$row['draw_win_count'];
        $issue_group_map[$key]['claim_wait_count'] = (int)$row['claim_wait_count'];
        $issue_group_map[$key]['claim_done_count'] = (int)$row['claim_done_count'];
        $issue_group_map[$key]['draw_lose_count'] = (int)$row['draw_lose_count'];
        $issue_group_map[$key]['other_count'] = (int)$row['other_count'];
        $issue_group_map[$key]['issued_count'] = (int)$row['issued_count'];
    }
}
$issue_group_rows = array_values($issue_group_map);

$claim_group_rows = array();
$claim_group_res = sql_query("
    SELECT
        pc.company_id,
        pc.branch_id,
        c.company_name AS contract_name,
        b.company_name AS branch_name,
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_REQUEST' THEN 1 ELSE 0 END), 0) AS request_count,
        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_APPROVED' THEN 1 ELSE 0 END), 0) AS approved_count,
        COALESCE(SUM(CASE WHEN pc.claim_status = 'CLAIM_DONE' THEN 1 ELSE 0 END), 0) AS done_count,
        COALESCE(SUM(CASE WHEN pc.claim_status IN ('CLAIM_HOLD', 'CLAIM_REJECT') THEN 1 ELSE 0 END), 0) AS hold_reject_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 1 THEN 1 ELSE 0 END), 0) AS rank1_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 2 THEN 1 ELSE 0 END), 0) AS rank2_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 3 THEN 1 ELSE 0 END), 0) AS rank3_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 4 THEN 1 ELSE 0 END), 0) AS rank4_count,
        COALESCE(SUM(CASE WHEN pc.result_rank = 5 THEN 1 ELSE 0 END), 0) AS rank5_count
      FROM mz_prize_claim pc
      LEFT JOIN mz_company c
             ON pc.company_id = c.company_id
      LEFT JOIN mz_company b
             ON pc.branch_id = b.company_id
     {$claim_search}
     GROUP BY pc.company_id, pc.branch_id, c.company_name, b.company_name
     ORDER BY c.company_name ASC, b.company_name ASC, pc.company_id ASC, pc.branch_id ASC
", false);
if ($claim_group_res) {
    while ($row = sql_fetch_array($claim_group_res)) {
        $claim_group_rows[] = $row;
    }
}

$download_mode = trim((string)($_GET['download'] ?? ''));
if ($download_mode === 'excel') {
    $filename_parts = array('settlement', $month_range['ym']);
    if ($company_filter > 0) {
        $filename_parts[] = 'company_' . $company_filter;
    }
    if ($branch_filter > 0) {
        $filename_parts[] = 'branch_' . $branch_filter;
    }
    $download_filename = implode('_', $filename_parts) . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    ?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<style>
table{border-collapse:collapse;width:100%;margin-bottom:20px;}
th,td{border:1px solid #cfcfcf;padding:8px 10px;font-size:12px;text-align:left;vertical-align:top;}
th{background:#f5f5f5;}
h1,h2{margin:0 0 12px;}
.meta{margin:0 0 14px;font-size:12px;color:#333;}
</style>
</head>
<body>
<h1>월별 정산</h1>
<div class="meta">기준월: <?php echo get_text($month_range['month_label']); ?></div>

<table>
    <thead>
    <tr>
        <th>발권건수</th>
        <th>발권장수</th>
        <th>발권게임수</th>
        <th>지급요청건</th>
        <th>지급완료건</th>
        <th>등수별 요청합계</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><?php echo number_format((int)($issue_summary['issue_count'] ?? 0)); ?></td>
        <td><?php echo number_format((int)($issue_summary['issue_qty_sum'] ?? 0)); ?></td>
        <td><?php echo number_format((int)($issue_summary['total_game_sum'] ?? 0)); ?></td>
        <td><?php echo number_format((int)($claim_summary['request_count'] ?? 0)); ?></td>
        <td><?php echo number_format((int)($claim_summary['done_count'] ?? 0)); ?></td>
        <td><?php echo number_format((int)($claim_summary['total_count'] ?? 0)); ?></td>
    </tr>
    </tbody>
</table>

<h2>발권월 정산</h2>
<table>
    <thead>
    <tr>
        <?php if ($show_contract_column) { ?><th>제휴사</th><?php } ?>
        <th>지점</th>
        <th>발권건수</th>
        <th>발권장수</th>
        <th>발권게임수</th>
        <th>당첨</th>
        <th>청구대기</th>
        <th>완료</th>
        <th>낙첨</th>
        <th>기타</th>
        <th>미추첨/발권</th>
    </tr>
    </thead>
    <tbody>
    <?php if (!empty($issue_group_rows)) { foreach ($issue_group_rows as $row) { ?>
    <tr>
        <?php if ($show_contract_column) { ?><td><?php echo get_text($row['contract_name']); ?></td><?php } ?>
        <td><?php echo get_text($row['branch_name']); ?></td>
        <td><?php echo number_format((int)$row['issue_count']); ?></td>
        <td><?php echo number_format((int)$row['issue_qty_sum']); ?></td>
        <td><?php echo number_format((int)$row['total_game_sum']); ?></td>
        <td><?php echo number_format((int)$row['draw_win_count']); ?></td>
        <td><?php echo number_format((int)$row['claim_wait_count']); ?></td>
        <td><?php echo number_format((int)$row['claim_done_count']); ?></td>
        <td><?php echo number_format((int)$row['draw_lose_count']); ?></td>
        <td><?php echo number_format((int)$row['other_count']); ?></td>
        <td><?php echo number_format((int)$row['issued_count']); ?></td>
    </tr>
    <?php }} ?>
    </tbody>
</table>

<?php if (mjtto_claim_table_exists()) { ?>
<h2>지급요청월 정산</h2>
<table>
    <thead>
    <tr>
        <?php if ($show_contract_column) { ?><th>제휴사</th><?php } ?>
        <th>지점</th>
        <th>총 요청</th>
        <th>1등</th>
        <th>2등</th>
        <th>3등</th>
        <th>4등</th>
        <th>5등</th>
        <th>요청중</th>
        <th>승인</th>
        <th>완료</th>
        <th>보류/반려</th>
    </tr>
    </thead>
    <tbody>
    <?php if (!empty($claim_group_rows)) { foreach ($claim_group_rows as $row) { ?>
    <tr>
        <?php if ($show_contract_column) { ?><td><?php echo get_text($row['contract_name']); ?></td><?php } ?>
        <td><?php echo get_text($row['branch_name']); ?></td>
        <td><?php echo number_format((int)$row['total_count']); ?></td>
        <td><?php echo number_format((int)$row['rank1_count']); ?></td>
        <td><?php echo number_format((int)$row['rank2_count']); ?></td>
        <td><?php echo number_format((int)$row['rank3_count']); ?></td>
        <td><?php echo number_format((int)$row['rank4_count']); ?></td>
        <td><?php echo number_format((int)$row['rank5_count']); ?></td>
        <td><?php echo number_format((int)$row['request_count']); ?></td>
        <td><?php echo number_format((int)$row['approved_count']); ?></td>
        <td><?php echo number_format((int)$row['done_count']); ?></td>
        <td><?php echo number_format((int)$row['hold_reject_count']); ?></td>
    </tr>
    <?php }} ?>
    </tbody>
</table>
<?php } ?>
</body>
</html>
<?php
    exit;
}

$g5['title'] = '월별 정산';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.top h1{margin:0;font-size:28px;}
.top .btn-wrap{display:flex;gap:8px;flex-wrap:wrap;}
.btn{display:inline-block;height:38px;line-height:38px;padding:0 14px;border-radius:8px;text-decoration:none;font-size:13px;border:0;cursor:pointer;}
.btn-primary{background:#111827;color:#fff;}
.btn-light{background:#f3f4f6;color:#111827;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);margin-bottom:20px;}
.summary-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:20px;}
.summary-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.summary-label{font-size:12px;color:#6b7280;margin-bottom:8px;}
.summary-value{font-size:26px;font-weight:700;color:#111827;line-height:1.2;}
.summary-desc{margin-top:6px;font-size:12px;color:#6b7280;line-height:1.6;}
.search-form{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:16px;padding:16px;border:1px solid #eef2f7;border-radius:12px;background:#f9fafb;}
.search-field{display:flex;flex-direction:column;gap:6px;}
.search-field label{font-size:12px;color:#6b7280;}
.search-field select{height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;min-width:140px;box-sizing:border-box;}
.table-wrap{overflow-x:auto; width:1400px;}
.table{width:100%;border-collapse:collapse;min-width:1180px;max-width:1350px;}
.table th,.table td{padding:12px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:13px;vertical-align:top;}
.table th{background:#fafafa;white-space:nowrap;}
.section-title{display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:12px;}
.section-title h2{margin:0;font-size:20px;}
.section-title .sub{font-size:12px;color:#6b7280;line-height:1.6;}
.empty{padding:30px 10px;text-align:center;color:#777;}
@media (max-width:1200px){.summary-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
@media (max-width:640px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
</style>
<div class="top">
    <h1>월별 정산</h1>
    <div class="btn-wrap">
        <a href="./issue_list.php" class="btn btn-light">발권리스트</a>
        <?php if (mjtto_claim_table_exists()) { ?><a href="./claim_list.php" class="btn btn-light">경품지급 목록</a><?php } ?>
    </div>
</div>
<div class="box">
    <form method="get" class="search-form" id="settlementSearchForm">
        <div class="search-field">
            <label for="ym">정산월</label>
            <select name="ym" id="ym" class="auto-submit">
                <?php foreach ($month_options as $month_row) { ?>
                <option value="<?php echo get_text($month_row['ym']); ?>" <?php echo $ym === $month_row['ym'] ? 'selected' : ''; ?>><?php echo get_text($month_row['label']); ?></option>
                <?php } ?>
            </select>
        </div>
        <?php if ($show_contract_filter) { ?>
        <div class="search-field">
            <label for="company_id"><?php echo $auth['role'] === 'SUPER_ADMIN' ? '제휴사' : '내 제휴사'; ?></label>
            <select name="company_id" id="company_id" class="auto-submit">
                <?php if ($auth['role'] === 'SUPER_ADMIN') { ?><option value="">전체 제휴사</option><?php } ?>
                <?php foreach ($contract_options as $contract_row) { ?>
                <option value="<?php echo (int)$contract_row['company_id']; ?>" <?php echo $company_filter === (int)$contract_row['company_id'] ? 'selected' : ''; ?>><?php echo get_text($contract_row['company_name']); ?></option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>
        <?php if ($show_branch_filter) { ?>
        <div class="search-field">
            <label for="branch_id"><?php echo $auth['role'] === 'SUPER_ADMIN' ? '지점' : '하위 지점'; ?></label>
            <select name="branch_id" id="branch_id" class="auto-submit">
                <option value="">전체 지점</option>
                <?php foreach ($branch_options as $branch_row) { ?>
                <option value="<?php echo (int)$branch_row['company_id']; ?>" <?php echo $branch_filter === (int)$branch_row['company_id'] ? 'selected' : ''; ?>><?php echo get_text($branch_row['company_name']); ?></option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>
        <div class="search-field"><a href="./settlement_month.php" class="btn btn-light">초기화</a></div>
        <div class="search-field"><button type="submit" name="download" value="excel" class="btn btn-light">엑셀저장</button></div>
    </form>
    <div class="summary-desc"><?php echo get_text($month_range['month_label']); ?> 기준으로 발권일과 지급요청일을 분리해 보여줍니다.</div>
</div>
<div class="summary-grid">
    <div class="summary-card"><div class="summary-label">발권건수</div><div class="summary-value"><?php echo number_format((int)($issue_summary['issue_count'] ?? 0)); ?></div><div class="summary-desc">선택월 발권번호 건수</div></div>
    <div class="summary-card"><div class="summary-label">발권장수</div><div class="summary-value"><?php echo number_format((int)($issue_summary['issue_qty_sum'] ?? 0)); ?></div><div class="summary-desc">출력 기준 장수 합계</div></div>
    <div class="summary-card"><div class="summary-label">발권게임수</div><div class="summary-value"><?php echo number_format((int)($issue_summary['total_game_sum'] ?? 0)); ?></div><div class="summary-desc">당첨 <?php echo number_format((int)($issue_item_summary['draw_win_count'] ?? 0)); ?> · 낙첨 <?php echo number_format((int)($issue_item_summary['draw_lose_count'] ?? 0)); ?> · 미추첨/발권 <?php echo number_format((int)($issue_item_summary['issued_count'] ?? 0)); ?></div></div>
    <div class="summary-card"><div class="summary-label">지급요청건</div><div class="summary-value"><?php echo number_format((int)($claim_summary['request_count'] ?? 0)); ?></div><div class="summary-desc">선택월 요청 등록 기준</div></div>
    <div class="summary-card"><div class="summary-label">지급완료건</div><div class="summary-value"><?php echo number_format((int)($claim_summary['done_count'] ?? 0)); ?></div><div class="summary-desc">요청월 기준 완료 상태</div></div>
    <div class="summary-card"><div class="summary-label">등수별 요청합계</div><div class="summary-value"><?php echo number_format((int)($claim_summary['total_count'] ?? 0)); ?></div><div class="summary-desc">1등 <?php echo number_format((int)($claim_summary['rank1_count'] ?? 0)); ?> · 2등 <?php echo number_format((int)($claim_summary['rank2_count'] ?? 0)); ?> · 3등 <?php echo number_format((int)($claim_summary['rank3_count'] ?? 0)); ?><br>4등 <?php echo number_format((int)($claim_summary['rank4_count'] ?? 0)); ?> · 5등 <?php echo number_format((int)($claim_summary['rank5_count'] ?? 0)); ?></div></div>
</div>
<div class="box">
    <div class="section-title"><h2>발권월 정산</h2><div class="sub"><?php echo get_text($month_range['month_label']); ?> 발권일 기준 · 상태는 현재 티켓 상태 기준</div></div>
    <div class="table-wrap">
        <table class="table">
            <thead>
			<tr>
				<?php if ($show_contract_column) { ?><th width="150">제휴사</th><?php } ?>
				<th width="150">지점</th>
				<th width="80">발권건수</th>
				<th width="80">발권장수</th>
				<th width="80">발권게임수</th>
				<th width="80">당첨</th>
				<th width="80">청구대기</th>
				<th width="80">완료</th>
				<th width="80">낙첨</th>
				<th width="80">기타</th>
				<th >미추첨/발권</th>
			</tr>
			</thead>
            <tbody>
            <?php if (!empty($issue_group_rows)) { foreach ($issue_group_rows as $row) { ?>
                <tr>
                    <?php if ($show_contract_column) { ?><td><?php echo get_text($row['contract_name']); ?></td><?php } ?>
                    <td><?php echo get_text($row['branch_name']); ?></td>
                    <td><?php echo number_format((int)$row['issue_count']); ?></td>
                    <td><?php echo number_format((int)$row['issue_qty_sum']); ?></td>
                    <td><?php echo number_format((int)$row['total_game_sum']); ?></td>
                    <td><?php echo number_format((int)$row['draw_win_count']); ?></td>
                    <td><?php echo number_format((int)$row['claim_wait_count']); ?></td>
                    <td><?php echo number_format((int)$row['claim_done_count']); ?></td>
                    <td><?php echo number_format((int)$row['draw_lose_count']); ?></td>
                    <td><?php echo number_format((int)$row['other_count']); ?></td>
                    <td><?php echo number_format((int)$row['issued_count']); ?></td>
                </tr>
            <?php }} else { ?>
                <tr><td colspan="<?php echo $issue_empty_colspan; ?>" class="empty">선택한 월의 발권 정산 데이터가 없습니다.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php if (mjtto_claim_table_exists()) { ?>
<div class="box">
    <div class="section-title"><h2>지급요청월 정산</h2><div class="sub"><?php echo get_text($month_range['month_label']); ?> 지급요청일 기준 · 등수와 현재 지급상태 합계</div></div>
    <div class="table-wrap">
        <table class="table">
            <thead>
			<tr>
				<?php if ($show_contract_column) { ?><th width="150">제휴사</th><?php } ?>
				<th width="150">지점</th>
				<th width="80">총 요청</th>
				<th width="60">1등</th>
				<th width="60">2등</th>
				<th width="60">3등</th>
				<th width="60">4등</th>
				<th width="60">5등</th>
				<th width="80">요청중</th>
				<th width="80">승인</th>
				<th width="80">완료</th>
				<th >보류/반려</th>
			</tr>
			</thead>
            <tbody>
            <?php if (!empty($claim_group_rows)) { foreach ($claim_group_rows as $row) { ?>
                <tr>
                    <?php if ($show_contract_column) { ?><td><?php echo get_text($row['contract_name']); ?></td><?php } ?>
                    <td><?php echo get_text($row['branch_name']); ?></td>
                    <td><?php echo number_format((int)$row['total_count']); ?></td>
                    <td><?php echo number_format((int)$row['rank1_count']); ?></td>
                    <td><?php echo number_format((int)$row['rank2_count']); ?></td>
                    <td><?php echo number_format((int)$row['rank3_count']); ?></td>
                    <td><?php echo number_format((int)$row['rank4_count']); ?></td>
                    <td><?php echo number_format((int)$row['rank5_count']); ?></td>
                    <td><?php echo number_format((int)$row['request_count']); ?></td>
                    <td><?php echo number_format((int)$row['approved_count']); ?></td>
                    <td><?php echo number_format((int)$row['done_count']); ?></td>
                    <td><?php echo number_format((int)$row['hold_reject_count']); ?></td>
                </tr>
            <?php }} else { ?>
                <tr><td colspan="<?php echo $claim_empty_colspan; ?>" class="empty">선택한 월의 지급 정산 데이터가 없습니다.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('settlementSearchForm');
    if(!form) return;
    form.querySelectorAll('select.auto-submit').forEach(function(el){
        el.addEventListener('change', function(){ form.submit(); });
    });
});
</script>
</div>
</body>
</html>
