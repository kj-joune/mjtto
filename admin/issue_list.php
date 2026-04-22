<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-15 04:28:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();

if (!function_exists('mjtto_issue_list_round_status_text')) {
    function mjtto_issue_list_round_status_text($round_status, $draw_date)
    {
        $round_status = strtoupper(trim((string)$round_status));
        $draw_date = trim((string)$draw_date);

        switch ($round_status) {
            case 'OPEN':
                return $draw_date ? '추첨전 · ' . $draw_date : '추첨전';
            case 'DRAWN':
                return $draw_date ? '추첨반영 · ' . $draw_date : '추첨반영';
            case 'CLOSE':
                return $draw_date ? '추첨완료 · ' . $draw_date : '추첨완료';
            default:
                return $draw_date ? '회차확인 · ' . $draw_date : '-';
        }
    }
}


if (!function_exists('mjtto_issue_list_claim_filter_text')) {
    function mjtto_issue_list_claim_filter_text($claim_filter)
    {
        switch (trim((string)$claim_filter)) {
            case 'CLAIM_WAIT':
                return '청구대기 있음';
            case 'CLAIM_DONE':
                return '수령완료 있음';
            case 'NO_CLAIM':
                return '청구이력 없음';
            default:
                return '';
        }
    }
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = 20;
$from_record = ($page - 1) * $rows;

$current_round = mjtto_get_current_issue_round();
$previous_round = mjtto_get_previous_draw_round();
$round_no = isset($_GET['round_no']) ? (int)$_GET['round_no'] : 0;
$round_select_last_no = !empty($current_round['round_no']) ? (int)$current_round['round_no'] : (int)$previous_round['round_no'];

if ($round_no < 1) {
    if (!empty($current_round['round_no'])) {
        $round_no = (int)$current_round['round_no'];
    } elseif (!empty($previous_round['round_no'])) {
        $round_no = (int)$previous_round['round_no'];
    }
}
$round_options = mjtto_get_round_select_options($round_select_last_no, 10);
$claim_filter = trim((string)($_GET['claim_filter'] ?? ''));
$stx = trim((string)($_GET['stx'] ?? ''));
$sdate = trim((string)($_GET['sdate'] ?? ''));
$edate = trim((string)($_GET['edate'] ?? ''));
$company_filter = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$branch_filter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

if ($sdate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sdate)) {
    $sdate = '';
}
if ($edate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $edate)) {
    $edate = '';
}

$normalized_filter = mjtto_normalize_issue_filters($auth, $company_filter, $branch_filter);
$company_filter = (int)$normalized_filter['company_id'];
$branch_filter = (int)$normalized_filter['branch_id'];

$scope_sql = mjtto_get_issue_scope_sql($auth, 'i');
$contract_options = mjtto_get_accessible_contracts($auth);
$branch_options = mjtto_get_accessible_branches($auth, $company_filter);
$show_contract_filter = in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true);
$show_branch_filter = in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true);

$sql_search = "
    WHERE {$scope_sql}
";

if ($company_filter > 0) {
    $sql_search .= " AND i.company_id = '{$company_filter}'
";
}

if ($branch_filter > 0) {
    $sql_search .= " AND i.branch_id = '{$branch_filter}'
";
}

if ($round_no > 0) {
    $sql_search .= " AND i.round_no = '{$round_no}'
";
}

if ($sdate !== '') {
    $sdate_sql = sql_real_escape_string($sdate);
    $sql_search .= " AND i.created_at >= '{$sdate_sql} 00:00:00'
";
}

if ($edate !== '') {
    $edate_sql = sql_real_escape_string($edate);
    $sql_search .= " AND i.created_at <= '{$edate_sql} 23:59:59'
";
}

if ($claim_filter === 'CLAIM_WAIT') {
    $sql_search .= "
      AND EXISTS (
            SELECT 1
              FROM mz_issue_item siw
             WHERE siw.issue_id = i.issue_id
               AND siw.item_status IN ('CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD')
      )
    ";
} elseif ($claim_filter === 'CLAIM_DONE') {
    $sql_search .= "
      AND EXISTS (
            SELECT 1
              FROM mz_issue_item sid
             WHERE sid.issue_id = i.issue_id
               AND sid.item_status = 'CLAIM_DONE'
      )
    ";
} elseif ($claim_filter === 'NO_CLAIM') {
    $sql_search .= "
      AND NOT EXISTS (
            SELECT 1
              FROM mz_issue_item sin
             WHERE sin.issue_id = i.issue_id
               AND sin.item_status IN ('CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_DONE', 'CLAIM_HOLD', 'CLAIM_REJECT')
      )
    ";
}

if ($stx !== '') {
    $stx_sql = sql_real_escape_string($stx);
    $stx_hp = preg_replace('/[^0-9]/', '', $stx);
    $stx_hp_sql = sql_real_escape_string($stx_hp);

    $sql_search .= "
      AND (
            i.issue_no LIKE '%{$stx_sql}%'
            OR i.created_by LIKE '%{$stx_sql}%'
            OR c.company_name LIKE '%{$stx_sql}%'
            OR b.company_name LIKE '%{$stx_sql}%'
            OR EXISTS (
                SELECT 1
                  FROM mz_issue_item sik
                 WHERE sik.issue_id = i.issue_id
                   AND (
                        sik.ticket_no LIKE '%{$stx_sql}%'
                        OR sik.customer_name LIKE '%{$stx_sql}%'
                        OR sik.customer_hp LIKE '%{$stx_sql}%'
    ";

    if ($stx_hp_sql !== '') {
        $sql_search .= " OR REPLACE(REPLACE(REPLACE(sik.customer_hp, '-', ''), ' ', ''), '.', '') LIKE '%{$stx_hp_sql}%' ";
    }

    $sql_search .= "
                   )
            )
      )
    ";
}

$sql_common = "
    FROM mz_issue i
    LEFT JOIN mz_round r
           ON r.round_no = i.round_no
    LEFT JOIN mz_company c
           ON i.company_id = c.company_id
    LEFT JOIN mz_company b
           ON i.branch_id = b.company_id
    {$sql_search}
";

$total_row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common}");
$total_count = (int)($total_row['cnt'] ?? 0);
$total_page = max(1, (int)ceil($total_count / $rows));

$issue_summary = sql_fetch("
    SELECT
        COUNT(*) AS issue_count,
        COALESCE(SUM(i.issue_qty), 0) AS issue_qty_sum,
        COALESCE(SUM(i.issue_qty * (CASE WHEN i.issue_game_count > 0 THEN i.issue_game_count ELSE 5 END)), 0) AS total_game_sum
    {$sql_common}
");

$item_summary = sql_fetch("
    SELECT
        COALESCE(SUM(CASE WHEN ii.item_status = 'ISSUED' THEN 1 ELSE 0 END), 0) AS issued_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'DRAW_WIN' THEN 1 ELSE 0 END), 0) AS draw_win_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'DRAW_LOSE' THEN 1 ELSE 0 END), 0) AS draw_lose_count,
        COALESCE(SUM(CASE WHEN ii.item_status IN ('CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD') THEN 1 ELSE 0 END), 0) AS claim_wait_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'CLAIM_DONE' THEN 1 ELSE 0 END), 0) AS claim_done_count,
        COALESCE(SUM(CASE WHEN ii.item_status = 'CLAIM_REJECT' THEN 1 ELSE 0 END), 0) AS claim_reject_count,
        COALESCE(SUM(CASE WHEN ii.item_status NOT IN ('ISSUED', 'DRAW_WIN', 'DRAW_LOSE', 'CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_DONE', 'CLAIM_HOLD', 'CLAIM_REJECT') THEN 1 ELSE 0 END), 0) AS other_count,
        COALESCE(SUM(CASE WHEN (
            ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 6
        ) THEN 1 ELSE 0 END), 0) AS rank1_count,
        COALESCE(SUM(CASE WHEN (
            ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 5
            AND dr.bonus_no IN (ii.num_a, ii.num_b, ii.num_c, ii.num_d, ii.num_e, ii.num_f)
        ) THEN 1 ELSE 0 END), 0) AS rank2_count,
        COALESCE(SUM(CASE WHEN (
            ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 5
            AND dr.bonus_no NOT IN (ii.num_a, ii.num_b, ii.num_c, ii.num_d, ii.num_e, ii.num_f)
        ) THEN 1 ELSE 0 END), 0) AS rank3_count,
        COALESCE(SUM(CASE WHEN (
            ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 4
        ) THEN 1 ELSE 0 END), 0) AS rank4_count,
        COALESCE(SUM(CASE WHEN (
            ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
             (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 3
        ) THEN 1 ELSE 0 END), 0) AS rank5_count
      FROM mz_issue i
      LEFT JOIN mz_round r
             ON r.round_no = i.round_no
      LEFT JOIN mz_draw_result dr
             ON dr.round_id = r.round_id
      LEFT JOIN mz_company c
             ON i.company_id = c.company_id
      LEFT JOIN mz_company b
             ON i.branch_id = b.company_id
      LEFT JOIN mz_issue_item ii
             ON ii.issue_id = i.issue_id
     {$sql_search}
");

$result = sql_query("
    SELECT
        i.issue_id,
        i.issue_no,
        i.round_no,
        i.issue_qty,
        i.issue_game_count,
        i.issue_status,
        i.created_by,
        i.created_at,
        r.draw_date,
        r.payout_deadline,
        r.status AS round_status,
        c.company_name AS contract_name,
        b.company_name AS branch_name,
        COALESCE(s.total_item_count, 0) AS total_item_count,
        COALESCE(s.issued_count, 0) AS issued_count,
        COALESCE(s.draw_win_count, 0) AS draw_win_count,
        COALESCE(s.draw_lose_count, 0) AS draw_lose_count,
        COALESCE(s.claim_wait_count, 0) AS claim_wait_count,
        COALESCE(s.claim_done_count, 0) AS claim_done_count,
        COALESCE(s.other_count, 0) AS other_count,
        COALESCE(s.rank1_count, 0) AS rank1_count,
        COALESCE(s.rank2_count, 0) AS rank2_count,
        COALESCE(s.rank3_count, 0) AS rank3_count,
        COALESCE(s.rank4_count, 0) AS rank4_count,
        COALESCE(s.rank5_count, 0) AS rank5_count,
        COALESCE(cw.customer_name, '') AS customer_name,
        COALESCE(cw.customer_hp, '') AS customer_hp
      FROM mz_issue i
      LEFT JOIN mz_round r
             ON r.round_no = i.round_no
      LEFT JOIN mz_company c
             ON i.company_id = c.company_id
      LEFT JOIN mz_company b
             ON i.branch_id = b.company_id
      LEFT JOIN (
            SELECT
                issue_id,
                COUNT(*) AS total_item_count,
                SUM(CASE WHEN item_status = 'ISSUED' THEN 1 ELSE 0 END) AS issued_count,
                SUM(CASE WHEN item_status = 'DRAW_WIN' THEN 1 ELSE 0 END) AS draw_win_count,
                SUM(CASE WHEN item_status = 'DRAW_LOSE' THEN 1 ELSE 0 END) AS draw_lose_count,
                SUM(CASE WHEN item_status IN ('CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD') THEN 1 ELSE 0 END) AS claim_wait_count,
                SUM(CASE WHEN item_status = 'CLAIM_DONE' THEN 1 ELSE 0 END) AS claim_done_count,
                SUM(CASE WHEN item_status NOT IN ('ISSUED', 'DRAW_WIN', 'DRAW_LOSE', 'CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_DONE', 'CLAIM_HOLD') THEN 1 ELSE 0 END) AS other_count,
                SUM(CASE WHEN (
                    ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 6
                ) THEN 1 ELSE 0 END) AS rank1_count,
                SUM(CASE WHEN (
                    ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 5
                    AND dr.bonus_no IN (ii.num_a, ii.num_b, ii.num_c, ii.num_d, ii.num_e, ii.num_f)
                ) THEN 1 ELSE 0 END) AS rank2_count,
                SUM(CASE WHEN (
                    ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 5
                    AND dr.bonus_no NOT IN (ii.num_a, ii.num_b, ii.num_c, ii.num_d, ii.num_e, ii.num_f)
                ) THEN 1 ELSE 0 END) AS rank3_count,
                SUM(CASE WHEN (
                    ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 4
                ) THEN 1 ELSE 0 END) AS rank4_count,
                SUM(CASE WHEN (
                    ((ii.num_a IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_b IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_c IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_d IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_e IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f)) +
                     (ii.num_f IN (dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f))) = 3
                ) THEN 1 ELSE 0 END) AS rank5_count
              FROM mz_issue_item ii
              LEFT JOIN mz_round r
                     ON r.round_no = ii.round_no
              LEFT JOIN mz_draw_result dr
                     ON dr.round_id = r.round_id
             GROUP BY issue_id
      ) s
        ON s.issue_id = i.issue_id
      LEFT JOIN (
            SELECT x.issue_id, x.customer_name, x.customer_hp
              FROM mz_issue_item x
              INNER JOIN (
                    SELECT issue_id, MAX(issue_item_id) AS max_issue_item_id
                      FROM mz_issue_item
                     WHERE customer_name <> '' OR customer_hp <> ''
                     GROUP BY issue_id
              ) y
                ON y.issue_id = x.issue_id
               AND y.max_issue_item_id = x.issue_item_id
      ) cw
        ON cw.issue_id = i.issue_id
     {$sql_search}
     ORDER BY i.issue_id DESC
     LIMIT {$from_record}, {$rows}
");

$query_params = array();
$has_filter = false;

if ($company_filter > 0 && $auth['role'] === 'SUPER_ADMIN') {
    $query_params['company_id'] = $company_filter;
    $has_filter = true;
}
if ($branch_filter > 0 && $show_branch_filter) {
    $query_params['branch_id'] = $branch_filter;
    $has_filter = true;
}
if ($round_no > 0) {
    $query_params['round_no'] = $round_no;
    $has_filter = true;
}
if ($claim_filter !== '') {
    $query_params['claim_filter'] = $claim_filter;
    $has_filter = true;
}
if ($sdate !== '') {
    $query_params['sdate'] = $sdate;
    $has_filter = true;
}
if ($edate !== '') {
    $query_params['edate'] = $edate;
    $has_filter = true;
}
if ($stx !== '') {
    $query_params['stx'] = $stx;
    $has_filter = true;
}

$qstr = http_build_query($query_params);
$qstr_prefix = $qstr ? '&' . $qstr : '';
$can_issue = in_array($auth['role'], array('BRANCH_ADMIN'), true);

$active_filters = array();
if ($show_contract_filter && $company_filter > 0) {
    foreach ($contract_options as $contract_row) {
        if ((int)$contract_row['company_id'] === $company_filter) {
            $active_filters[] = '제휴사 ' . (string)$contract_row['company_name'];
            break;
        }
    }
}
if ($show_branch_filter && $branch_filter > 0) {
    foreach ($branch_options as $branch_row) {
        if ((int)$branch_row['company_id'] === $branch_filter) {
            $active_filters[] = '지점 ' . (string)$branch_row['company_name'];
            break;
        }
    }
}
if ($round_no > 0) $active_filters[] = $round_no . '회';
if ($claim_filter !== '') $active_filters[] = mjtto_issue_list_claim_filter_text($claim_filter);
if ($sdate !== '') $active_filters[] = '시작 ' . $sdate;
if ($edate !== '') $active_filters[] = '종료 ' . $edate;
if ($stx !== '') $active_filters[] = '키워드 ' . $stx;

$g5['title'] = '발권리스트';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;}
.top h1{margin:0;font-size:28px;}
.top .btn-wrap{display:flex;gap:8px;flex-wrap:wrap;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px;}
.summary-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.summary-label{font-size:12px;color:#6b7280;margin-bottom:8px;}
.summary-value{font-size:26px;font-weight:700;color:#111827;line-height:1.2;}
.summary-desc{margin-top:6px;font-size:12px;color:#6b7280;}
.btn{display:inline-block;height:38px;line-height:38px;padding:0 14px;border-radius:8px;text-decoration:none;font-size:13px;border:0;cursor:pointer;}
.btn-primary{background:#111827;color:#fff;}
.btn-view{background:#f3f4f6;color:#111827;}
.btn-print{background:#2563eb;color:#fff;}
.btn-search{background:#111827;color:#fff;}
.btn-reset{background:#f3f4f6;color:#111827;}
.count{margin-bottom:12px;color:#666;font-size:14px;}
.table-wrap{overflow-x:auto; width:1400px;}
.table{width:100%;border-collapse:collapse;min-width:1180px;max-width:1350px;}
.table th,.table td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;vertical-align:top;}
.table th{background:#fafafa;white-space:nowrap;}
.empty{padding:30px 10px;text-align:center;color:#777;}
.paging{margin-top:16px;text-align:center;}
.paging a,.paging strong{display:inline-block;min-width:36px;height:36px;line-height:36px;margin:0 3px;padding:0 8px;border-radius:8px;text-decoration:none;font-size:14px;}
.paging a{background:#f3f4f6;color:#111827;}
.paging strong{background:#111827;color:#fff;}
.action-group{display:flex;gap:6px;flex-wrap:wrap;}
.meta-title{font-size:12px;color:#6b7280;margin-bottom:4px;}
.meta-value{font-size:14px;color:#111827;line-height:1.6;}
.meta-sub{font-size:12px;color:#6b7280;line-height:1.6;margin-top:4px;}
.badges{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;line-height:1.2;}
.badge-dark{background:#111827;color:#fff;}
.badge-gray{background:#f3f4f6;color:#374151;}
.badge-orange{background:#fff7ed;color:#c2410c;}
.badge-green{background:#ecfdf5;color:#047857;}
.badge-blue{background:#eff6ff;color:#1d4ed8;}
.search-form{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:16px;padding:16px;border:1px solid #eef2f7;border-radius:12px;background:#f9fafb;}
.search-field{display:flex;flex-direction:column;gap:6px;}
.search-field label{font-size:12px;color:#6b7280;}
.search-field input,.search-field select{height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;min-width:130px;box-sizing:border-box;}
.search-field.keyword{flex:1;min-width:240px;}
.search-field.keyword input{width:100%;}
.search-actions{display:flex;gap:8px;flex-wrap:wrap;}
.filter-note{margin:-4px 0 14px;font-size:12px;color:#6b7280;}
.filter-summary{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:0 0 14px;}
.filter-chips{display:flex;flex-wrap:wrap;gap:8px;}
.filter-chip{display:inline-block;padding:5px 10px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:12px;font-weight:700;line-height:1.2;}
.list-meta{font-size:13px;color:#6b7280;}
@media (max-width: 900px){
    .summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
}
@media (max-width: 640px){
    .summary-grid{grid-template-columns:1fr;}
}
</style>

<div class="top">
    <h1>발권리스트</h1>
    <div class="btn-wrap">
        <a href="./claim_list.php" class="btn btn-view">경품지급 목록</a>
        <?php if ($can_issue) { ?><a href="./issue_form.php" class="btn btn-primary">발권하기</a><?php } ?>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card">
		<div class="summary-label" style="margin:0 0 14px;">
			현재 발권 가능 회차: <?php echo $current_round['round_no'] ? (int)$current_round['round_no'].'회' : '회차없음'; ?>
			<?php if (!empty($current_round['draw_date'])) { ?>· 추첨일 <?php echo get_text($current_round['draw_date']); ?><?php } ?>
		</div>
        <div class="summary-label"><?php echo $has_filter ? '검색 발권건' : '총 발권건'; ?></div>
        <div class="summary-value"><?php echo number_format((int)($issue_summary['issue_count'] ?? 0)); ?></div>
        <div class="summary-desc"><?php echo $has_filter ? '검색조건에 맞는 발권 건수' : '권한 범위 기준 누적 발권 건수'; ?></div>
    </div>
    <div class="summary-card">
        <div class="summary-label"><?php echo $has_filter ? '검색 발권장수' : '총 발권장수'; ?></div>
        <div class="summary-value"><?php echo number_format((int)($issue_summary['issue_qty_sum'] ?? 0)); ?></div>
        <div class="summary-desc"><?php echo $has_filter ? '검색조건 기준 출력 장수 합계' : '출력 기준 장수 합계'; ?></div>
    </div>
    <div class="summary-card">
        <div class="summary-label"><?php echo $has_filter ? '검색 발권게임수' : '총 발권게임수'; ?></div>
        <div class="summary-value"><?php echo number_format((int)($issue_summary['total_game_sum'] ?? 0)); ?></div>
        <div class="summary-desc"><?php echo $has_filter ? '검색조건 기준 전체 게임 합계' : '전체 게임 누적 합계'; ?><br>당첨 <?php echo number_format((int)($item_summary['draw_win_count'] ?? 0)); ?> · 청구대기 <?php echo number_format((int)($item_summary['claim_wait_count'] ?? 0)); ?> · 완료 <?php echo number_format((int)($item_summary['claim_done_count'] ?? 0)); ?><br>낙첨 <?php echo number_format((int)($item_summary['draw_lose_count'] ?? 0)); ?> · 반려 <?php echo number_format((int)($item_summary['claim_reject_count'] ?? 0)); ?> · 기타 <?php echo number_format((int)($item_summary['other_count'] ?? 0)); ?> · 미추첨/발권 <?php echo number_format((int)($item_summary['issued_count'] ?? 0)); ?>건</div>
    </div>
    <div class="summary-card">
        <div class="summary-label"><?php echo $has_filter ? '검색 당첨 진행' : '당첨 진행 현황'; ?></div>
        <div class="summary-value"><?php echo number_format((int)(($item_summary['draw_win_count'] ?? 0) + ($item_summary['claim_wait_count'] ?? 0) + ($item_summary['claim_done_count'] ?? 0))); ?></div>
        <div class="summary-desc">미청구 당첨 <?php echo number_format((int)($item_summary['draw_win_count'] ?? 0)); ?>건 · 청구대기 <?php echo number_format((int)($item_summary['claim_wait_count'] ?? 0)); ?>건 · 수령완료 <?php echo number_format((int)($item_summary['claim_done_count'] ?? 0)); ?>건<br>당첨 등수 1등 <?php echo number_format((int)($item_summary['rank1_count'] ?? 0)); ?> / 2등 <?php echo number_format((int)($item_summary['rank2_count'] ?? 0)); ?> / 3등 <?php echo number_format((int)($item_summary['rank3_count'] ?? 0)); ?> / 4등 <?php echo number_format((int)($item_summary['rank4_count'] ?? 0)); ?> / 5등 <?php echo number_format((int)($item_summary['rank5_count'] ?? 0)); ?></div>
    </div>
</div>

<div class="box">
    <form method="get" class="search-form">
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

        <div class="search-field">
            <label for="round_no">회차</label>
            <select name="round_no" id="round_no" class="auto-submit">
                <?php foreach ($round_options as $round_row) { ?>
                <option value="<?php echo (int)$round_row['round_no']; ?>" <?php echo $round_no === (int)$round_row['round_no'] ? 'selected' : ''; ?>><?php echo (int)$round_row['round_no']; ?>회</option>
                <?php } ?>
            </select>
        </div>
        <div class="search-field">
            <label for="claim_filter">청구상태</label>
            <select name="claim_filter" id="claim_filter" class="auto-submit">
                <option value="">전체</option>
                <option value="CLAIM_WAIT" <?php echo $claim_filter === 'CLAIM_WAIT' ? 'selected' : ''; ?>>청구대기 있음</option>
                <option value="CLAIM_DONE" <?php echo $claim_filter === 'CLAIM_DONE' ? 'selected' : ''; ?>>수령완료 있음</option>
                <option value="NO_CLAIM" <?php echo $claim_filter === 'NO_CLAIM' ? 'selected' : ''; ?>>청구이력 없음</option>
            </select>
        </div>
        <div class="search-field">
            <label for="sdate">발권일 시작</label>
            <input type="date" name="sdate" id="sdate" value="<?php echo get_text($sdate); ?>">
        </div>
        <div class="search-field">
            <label for="edate">발권일 종료</label>
            <input type="date" name="edate" id="edate" value="<?php echo get_text($edate); ?>">
        </div>
        <div class="search-field keyword">
            <label for="stx">기타 키워드</label>
            <input type="text" name="stx" id="stx" value="<?php echo get_text($stx); ?>" placeholder="발권번호, 티켓번호, 당첨자명, 휴대폰, 발권자, 제휴사명, 지점명">
        </div>
        <div class="search-actions">
            <button type="submit" class="btn btn-search">검색</button>
            <a href="./issue_list.php" class="btn btn-reset">초기화</a>
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

    <div class="filter-note">
        <?php
        if ($auth['role'] === 'SUPER_ADMIN') {
            echo '관리자: 전체 발권리스트에서 제휴사와 지점 기준으로 바로 검색할 수 있습니다.';
        } elseif ($auth['role'] === 'COMPANY_ADMIN') {
            echo '제휴사: 내 제휴사 범위에서 하위 지점별로 발권내역을 검색할 수 있습니다.';
        } else {
            echo '지점: 내 발권내역만 표시됩니다.';
        }
        ?>
    </div>

    <div class="count">총 <?php echo number_format($total_count); ?>건<?php if ($has_filter) { ?> · 검색조건 적용<?php } ?></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th width="50">번호</th>
                    <th width="160">발권정보</th>
                    <th width="160">소속</th>
                    <th width="140">회차상태</th>
                    <th width="160">발권요약</th>
                    <th width="220">청구요약</th>
                    <th width="160">발권자/일시</th>
                    <th >처리</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $number = $total_count - $from_record;
            $i = 0;
            while ($row = sql_fetch_array($result)) {
                $i++;
                $issue_qty = (int)$row['issue_qty'];
                $issue_game_count = (int)$row['issue_game_count'];
                if ($issue_game_count < 1) $issue_game_count = 5;
                $game_qty = $issue_qty * $issue_game_count;
            ?>
                <tr>
                    <td><?php echo $number; ?></td>
                    <td>
                        <div class="meta-title">발권번호</div>
                        <div class="meta-value"><?php echo get_text($row['issue_no']); ?></div>
                        <div class="meta-sub">상태: <?php echo get_text($row['issue_status']); ?></div>
                        <?php if ($row['customer_name'] || $row['customer_hp']) { ?>
                        <div class="meta-sub">최근 당첨자: <?php echo get_text($row['customer_name']); ?><?php echo $row['customer_hp'] ? ' (' . get_text($row['customer_hp']) . ')' : ''; ?></div>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="meta-title">제휴사 / 지점</div>
                        <div class="meta-value"><?php echo get_text($row['contract_name']); ?></div>
                        <div class="meta-sub"><?php echo get_text($row['branch_name']); ?></div>
                    </td>
                    <td>
                        <div class="meta-title">회차</div>
                        <div class="meta-value"><?php echo (int)$row['round_no']; ?>회</div>
                        <div class="meta-sub"><?php echo get_text(mjtto_issue_list_round_status_text($row['round_status'], $row['draw_date'])); ?></div>
                        <?php if ($row['payout_deadline']) { ?><div class="meta-sub">지급기한: <?php echo get_text($row['payout_deadline']); ?></div><?php } ?>
                    </td>
                    <td>
                        <div class="badges">
                            <span class="badge badge-dark">발권 <?php echo number_format($issue_qty); ?>매</span>
                            <span class="badge badge-gray"><?php echo number_format($game_qty); ?>게임</span>
                        </div>
                        <div class="meta-sub">장당 <?php echo number_format($issue_game_count); ?>게임 · 생성티켓 <?php echo number_format((int)$row['total_item_count']); ?>건</div>
                    </td>
                    <td>
                        <div class="badges">
                            <span class="badge badge-dark">당첨 <?php echo number_format((int)$row['draw_win_count']); ?></span>
                            <span class="badge badge-orange">청구대기 <?php echo number_format((int)$row['claim_wait_count']); ?></span>
                            <span class="badge badge-green">수령완료 <?php echo number_format((int)$row['claim_done_count']); ?></span>
                            <span class="badge badge-gray">낙첨 <?php echo number_format((int)$row['draw_lose_count']); ?></span>
                            <span class="badge badge-blue">기타 <?php echo number_format((int)$row['other_count']); ?></span>
                        </div>
                        <div class="meta-sub">미추첨/발권 <?php echo number_format((int)$row['issued_count']); ?>건</div>
                        <div class="meta-sub">당첨 등수 1등 <?php echo number_format((int)$row['rank1_count']); ?> / 2등 <?php echo number_format((int)$row['rank2_count']); ?> / 3등 <?php echo number_format((int)$row['rank3_count']); ?> / 4등 <?php echo number_format((int)$row['rank4_count']); ?> / 5등 <?php echo number_format((int)$row['rank5_count']); ?></div>
                    </td>
                    <td>
                        <div class="meta-value"><?php echo get_text($row['created_by']); ?></div>
                        <div class="meta-sub"><?php echo get_text($row['created_at']); ?></div>
                    </td>
                    <td>
                        <div class="action-group">
                            <a href="./issue_view.php?issue_id=<?php echo (int)$row['issue_id']; ?>" class="btn btn-view">보기</a>
                            <a href="./issue_print.php?issue_id=<?php echo (int)$row['issue_id']; ?>" class="btn btn-print" target="_blank">인쇄</a>
                        </div>
                    </td>
                </tr>
            <?php
                $number--;
            }

            if ($i === 0) {
                echo '<tr><td colspan="8" class="empty">발권 내역이 없습니다.</td></tr>';
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
                echo '<a href="./issue_list.php?page='.$p.$qstr_prefix.'">'.$p.'</a>';
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
