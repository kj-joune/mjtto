<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-06-14 00:00:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();

if (!in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true)) {
    alert('엑셀 다운로드 권한이 없습니다.', './claim_list.php');
}

if (!mjtto_claim_table_exists()) {
    alert('mz_prize_claim 테이블이 없습니다. SQL 패치를 먼저 적용해 주세요.', './index.php');
}

if (!function_exists('mjtto_claim_excel_processed_at')) {
    function mjtto_claim_excel_processed_at($row)
    {
        foreach (array('paid_at', 'rejected_at', 'approved_at', 'requested_at') as $key) {
            $value = trim((string)($row[$key] ?? ''));
            if ($value !== '' && $value !== '0000-00-00 00:00:00') {
                return $value;
            }
        }

        return '';
    }
}

$scope_sql = mjtto_get_claim_scope_sql($auth, 'pc');
$reward_ticket_column = '';
if (mjtto_claim_column_exists('reward_ticket_no')) {
    $reward_ticket_column = 'reward_ticket_no';
} elseif (mjtto_claim_column_exists('reward_issue_ticket_no')) {
    $reward_ticket_column = 'reward_issue_ticket_no';
}
$has_reward_ticket_column = ($reward_ticket_column !== '');
$has_request_birth_column = mjtto_claim_column_exists('request_birth');

$previous_round = mjtto_get_previous_draw_round();
$round_no_raw = isset($_GET['round_no']) ? trim((string)$_GET['round_no']) : null;
$round_no = ($round_no_raw !== null && $round_no_raw !== '') ? (int)$round_no_raw : 0;
if ($round_no_raw === null && !empty($previous_round['round_no'])) {
    $round_no = (int)$previous_round['round_no'];
}
$claim_status = trim((string)($_GET['claim_status'] ?? ''));
$result_rank = isset($_GET['result_rank']) ? (int)$_GET['result_rank'] : 0;
$stx = trim((string)($_GET['stx'] ?? ''));

$sql_search = "
    WHERE {$scope_sql}
";
if ($round_no > 0) {
    $sql_search .= " AND pc.round_no = '{$round_no}'
";
}
if ($claim_status !== '') {
    $claim_status_sql = sql_real_escape_string($claim_status);
    $sql_search .= " AND pc.claim_status = '{$claim_status_sql}'
";
}
if ($result_rank > 0) {
    $sql_search .= " AND pc.result_rank = '{$result_rank}'
";
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
            " . ($has_request_birth_column ? "OR pc.request_birth LIKE '%{$stx_sql}%'
            " : '') . "
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

$result = sql_query("
    SELECT
        pc.*,
        i.issue_no,
        c.company_name AS contract_name,
        b.company_name AS branch_name
    {$sql_common}
    ORDER BY pc.claim_id DESC
", false);

$filename_parts = array('claim_winners');
if ($round_no > 0) {
    $filename_parts[] = 'round_' . $round_no;
}
if ($claim_status !== '') {
    $claim_status_filename = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($claim_status));
    if ($claim_status_filename !== '') {
        $filename_parts[] = $claim_status_filename;
    }
}
if ($result_rank > 0) {
    $filename_parts[] = 'rank_' . $result_rank;
}
$filename_parts[] = date('Ymd_His');
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
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #cfcfcf;padding:8px 10px;font-size:12px;text-align:left;vertical-align:top;}
th{background:#f5f5f5;font-weight:bold;}
.text{mso-number-format:"\@";}
</style>
</head>
<body>
<table>
    <thead>
    <tr>
        <th>처리일</th>
        <th>제휴사</th>
        <th>지점</th>
        <th>당첨자명</th>
        <th>핸드폰</th>
        <th>생년월일</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $processed_at = mjtto_claim_excel_processed_at($row);
            $request_birth = $has_request_birth_column ? (string)($row['request_birth'] ?? '') : '';
    ?>
    <tr>
        <td class="text"><?php echo get_text($processed_at); ?></td>
        <td><?php echo get_text($row['contract_name']); ?></td>
        <td><?php echo get_text($row['branch_name']); ?></td>
        <td><?php echo get_text($row['request_name']); ?></td>
        <td class="text"><?php echo get_text($row['request_hp']); ?></td>
        <td class="text"><?php echo get_text($request_birth); ?></td>
    </tr>
    <?php
        }
    }
    ?>
    </tbody>
</table>
</body>
</html>
