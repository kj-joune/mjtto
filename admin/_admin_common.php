<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-14 01:05:00 KST  */

include_once __DIR__ . '/../common.php';

if (!defined('_GNUBOARD_')) exit;

if (!function_exists('mjtto_get_admin_role')) {
    function mjtto_get_admin_role($mb_id)
    {
        if (!$mb_id) {
            return array(
                'role' => '',
                'company_id' => 0,
                'company_name' => '',
                'company_code' => ''
            );
        }

        global $member;

        if ((int)$member['mb_level'] >= 10) {
            return array(
                'role' => 'SUPER_ADMIN',
                'company_id' => 0,
                'company_name' => '본사',
                'company_code' => ''
            );
        }

        $mb_id_sql = sql_real_escape_string($mb_id);
        $row = sql_fetch("
            SELECT
                cu.role_code,
                cu.company_id,
                c.company_name,
                c.company_code
            FROM mz_company_user cu
            JOIN mz_company c
              ON cu.company_id = c.company_id
            WHERE cu.mb_id = '{$mb_id_sql}'
              AND cu.status = 1
              AND c.status = 1
              AND (
                    (cu.role_code = 'COMPANY_ADMIN' AND c.company_type = 'CONTRACT')
                 OR (cu.role_code = 'BRANCH_ADMIN' AND c.company_type = 'BRANCH')
              )
            ORDER BY
                CASE
                    WHEN cu.role_code = 'COMPANY_ADMIN' AND c.company_type = 'CONTRACT' THEN 1
                    WHEN cu.role_code = 'BRANCH_ADMIN' AND c.company_type = 'BRANCH' THEN 2
                    ELSE 9
                END ASC,
                cu.map_id DESC
            LIMIT 1
        ");

        if (!$row) {
            return array(
                'role' => '',
                'company_id' => 0,
                'company_name' => '',
                'company_code' => ''
            );
        }

        return array(
            'role' => $row['role_code'],
            'company_id' => (int)$row['company_id'],
            'company_name' => $row['company_name'],
            'company_code' => $row['company_code']
        );
    }
}

if (!function_exists('mjtto_require_login')) {
    function mjtto_require_login()
    {
        global $member;

        if (empty($member['mb_id'])) {
            goto_url('/admin/login.php');
        }
    }
}

if (!function_exists('mjtto_require_admin')) {
    function mjtto_require_admin()
    {
        global $member;

        mjtto_require_login();

        $auth = mjtto_get_admin_role($member['mb_id']);

        if (empty($auth['role'])) {
            alert('관리자 접근 권한이 없습니다.', G5_URL);
        }

        return $auth;
    }
}

if (!function_exists('mjtto_role_name')) {
    function mjtto_role_name($role)
    {
        switch ($role) {
            case 'SUPER_ADMIN': return '최고관리자';
            case 'COMPANY_ADMIN': return '제휴사관리자';
            case 'BRANCH_ADMIN': return '지점관리자';
            default: return '관리자';
        }
    }
}

if (!function_exists('mjtto_list_url')) {
    function mjtto_list_url($role, $script_name = '')
    {
        if ($script_name) {
            if (strpos($script_name, 'company_') !== false) return './company_list.php';
            if (strpos($script_name, 'branch_') !== false) {
                if ($role === 'SUPER_ADMIN') return './company_list.php';
                return './branch_list.php';
            }
            if (strpos($script_name, 'claim_') !== false) return './claim_list.php';
            if (strpos($script_name, 'issue_') !== false) return './issue_list.php';
            if (strpos($script_name, 'settlement_') !== false) return './settlement_month.php';
        }

        switch ($role) {
            case 'SUPER_ADMIN': return './company_list.php';
            case 'COMPANY_ADMIN': return './branch_list.php';
            case 'BRANCH_ADMIN':
                return './issue_list.php';
            default:
                return './index.php';
        }
    }
}

if (!function_exists('mjtto_company_admin_info')) {
    function mjtto_company_admin_info($mb_id, $role_code)
    {
        $mb_id_sql = sql_real_escape_string($mb_id);
        $role_code_sql = sql_real_escape_string($role_code);

        return sql_fetch("\n            SELECT cu.company_id, c.company_name, c.company_code\n              FROM mz_company_user cu\n              JOIN mz_company c\n                ON cu.company_id = c.company_id\n             WHERE cu.mb_id = '{$mb_id_sql}'\n               AND cu.role_code = '{$role_code_sql}'\n               AND cu.status = 1\n               AND c.status = 1\n             LIMIT 1\n        ");
    }
}

if (!function_exists('mjtto_next_contract_code')) {
    function mjtto_next_contract_code()
    {
        $result = sql_query("\n            SELECT company_code\n              FROM mz_company\n             WHERE company_type = 'CONTRACT'\n               AND company_code REGEXP '^[A-Z][0-9]{2}$'\n             ORDER BY company_code ASC\n        ", false);

        $max_serial = 0;
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $code = $row['company_code'];
                $letter_no = ord(substr($code, 0, 1)) - 64;
                $number_no = (int)substr($code, 1, 2);
                if ($letter_no < 1 || $letter_no > 26 || $number_no < 1 || $number_no > 99) {
                    continue;
                }
                $serial = (($letter_no - 1) * 99) + $number_no;
                if ($serial > $max_serial) $max_serial = $serial;
            }
        }

        $next = $max_serial + 1;
        if ($next > (26 * 99)) {
            return '';
        }

        $letter_index = (int)floor(($next - 1) / 99);
        $number = (($next - 1) % 99) + 1;
        return chr(65 + $letter_index) . str_pad($number, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('mjtto_next_branch_code')) {
    function mjtto_next_branch_code($parent_company_id, $parent_company_code)
    {
        $parent_company_id = (int)$parent_company_id;
        $parent_company_code_sql = sql_real_escape_string($parent_company_code);

        $result = sql_query("\n            SELECT company_code\n              FROM mz_company\n             WHERE parent_company_id = '{$parent_company_id}'\n               AND company_type = 'BRANCH'\n               AND company_code LIKE '{$parent_company_code_sql}%'\n             ORDER BY company_code ASC\n        ", false);

        $max_no = 0;
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $code = $row['company_code'];
                if (strpos($code, $parent_company_code) !== 0) continue;
                $suffix = substr($code, strlen($parent_company_code));
                if (!preg_match('/^[0-9]{3}$/', $suffix)) continue;
                $no = (int)$suffix;
                if ($no > $max_no) $max_no = $no;
            }
        }

        $next = $max_no + 1;
        if ($next > 999) {
            return '';
        }

        return $parent_company_code . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('mjtto_get_issue_scope_sql')) {
    function mjtto_get_issue_scope_sql($auth, $issue_alias = 'i')
    {
        $issue_alias = preg_replace('/[^a-zA-Z0-9_]/', '', $issue_alias);
        if (!$issue_alias) $issue_alias = 'i';

        switch ($auth['role']) {
            case 'SUPER_ADMIN':
                return '1=1';
            case 'COMPANY_ADMIN':
                return "{$issue_alias}.company_id = '".(int)$auth['company_id']."'";
            case 'BRANCH_ADMIN':
            default:
                return "{$issue_alias}.branch_id = '".(int)$auth['company_id']."'";
        }
    }
}



if (!function_exists('mjtto_get_accessible_contracts')) {
    function mjtto_get_accessible_contracts($auth)
    {
        $rows = array();

        if (empty($auth['role'])) {
            return $rows;
        }

        if ($auth['role'] === 'SUPER_ADMIN') {
            $res = sql_query("
                SELECT company_id, company_name, company_code
                  FROM mz_company
                 WHERE company_type = 'CONTRACT'
                   AND status = 1
                 ORDER BY company_name ASC, company_id ASC
            ", false);

            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }

            return $rows;
        }

        if ($auth['role'] === 'COMPANY_ADMIN') {
            $row = sql_fetch("
                SELECT company_id, company_name, company_code
                  FROM mz_company
                 WHERE company_id = '".(int)$auth['company_id']."'
                   AND company_type = 'CONTRACT'
                   AND status = 1
                 LIMIT 1
            ");

            if ($row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('mjtto_get_accessible_branches')) {
    function mjtto_get_accessible_branches($auth, $company_id = 0)
    {
        $rows = array();
        $company_id = (int)$company_id;

        if (empty($auth['role'])) {
            return $rows;
        }

        if ($auth['role'] === 'SUPER_ADMIN') {
            $where = " company_type = 'BRANCH' AND status = 1 ";
            if ($company_id > 0) {
                $where .= " AND parent_company_id = '{$company_id}' ";
            }

            $res = sql_query("
                SELECT company_id, parent_company_id, company_name, company_code
                  FROM mz_company
                 WHERE {$where}
                 ORDER BY company_name ASC, company_id ASC
            ", false);

            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }

            return $rows;
        }

        if ($auth['role'] === 'COMPANY_ADMIN') {
            $company_id = (int)$auth['company_id'];
            $res = sql_query("
                SELECT company_id, parent_company_id, company_name, company_code
                  FROM mz_company
                 WHERE parent_company_id = '{$company_id}'
                   AND company_type = 'BRANCH'
                   AND status = 1
                 ORDER BY company_name ASC, company_id ASC
            ", false);

            if ($res) {
                while ($row = sql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }

            return $rows;
        }

        if ($auth['role'] === 'BRANCH_ADMIN') {
            $row = sql_fetch("
                SELECT company_id, parent_company_id, company_name, company_code
                  FROM mz_company
                 WHERE company_id = '".(int)$auth['company_id']."'
                   AND company_type = 'BRANCH'
                   AND status = 1
                 LIMIT 1
            ");

            if ($row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('mjtto_normalize_issue_filters')) {
    function mjtto_normalize_issue_filters($auth, $company_id = 0, $branch_id = 0)
    {
        $company_id = (int)$company_id;
        $branch_id = (int)$branch_id;
        $normalized = array(
            'company_id' => 0,
            'branch_id' => 0
        );

        switch ($auth['role']) {
            case 'SUPER_ADMIN':
                if ($company_id > 0) {
                    $company_row = sql_fetch("
                        SELECT company_id
                          FROM mz_company
                         WHERE company_id = '{$company_id}'
                           AND company_type = 'CONTRACT'
                           AND status = 1
                         LIMIT 1
                    ");
                    if ($company_row) {
                        $normalized['company_id'] = (int)$company_row['company_id'];
                    }
                }

                if ($branch_id > 0) {
                    $branch_where = " company_id = '{$branch_id}' AND company_type = 'BRANCH' AND status = 1 ";
                    if ($normalized['company_id'] > 0) {
                        $branch_where .= " AND parent_company_id = '".$normalized['company_id']."' ";
                    }

                    $branch_row = sql_fetch("
                        SELECT company_id, parent_company_id
                          FROM mz_company
                         WHERE {$branch_where}
                         LIMIT 1
                    ");

                    if ($branch_row) {
                        $normalized['branch_id'] = (int)$branch_row['company_id'];
                        if ($normalized['company_id'] < 1) {
                            $normalized['company_id'] = (int)$branch_row['parent_company_id'];
                        }
                    }
                }
                break;

            case 'COMPANY_ADMIN':
                $normalized['company_id'] = (int)$auth['company_id'];

                if ($branch_id > 0) {
                    $branch_row = sql_fetch("
                        SELECT company_id
                          FROM mz_company
                         WHERE company_id = '{$branch_id}'
                           AND parent_company_id = '".$normalized['company_id']."'
                           AND company_type = 'BRANCH'
                           AND status = 1
                         LIMIT 1
                    ");

                    if ($branch_row) {
                        $normalized['branch_id'] = (int)$branch_row['company_id'];
                    }
                }
                break;

            case 'BRANCH_ADMIN':
            default:
                $branch_row = sql_fetch("
                    SELECT company_id, parent_company_id
                      FROM mz_company
                     WHERE company_id = '".(int)$auth['company_id']."'
                       AND company_type = 'BRANCH'
                       AND status = 1
                     LIMIT 1
                ");

                if ($branch_row) {
                    $normalized['branch_id'] = (int)$branch_row['company_id'];
                    $normalized['company_id'] = (int)$branch_row['parent_company_id'];
                }
                break;
        }

        return $normalized;
    }
}

if (!function_exists('mjtto_get_issue_row')) {
    function mjtto_get_issue_row($issue_id, $auth)
    {
        $issue_id = (int)$issue_id;
        if ($issue_id < 1) {
            return array();
        }

        $scope_sql = mjtto_get_issue_scope_sql($auth, 'i');

        return sql_fetch("\n            SELECT\n                i.*,\n                c.company_name AS contract_name,\n                c.print_name_1 AS contract_print_name_1,\n                c.print_name_2 AS contract_print_name_2,\n                c.company_code AS contract_code,\n                c.tel_no AS contract_tel_no,\n                b.company_name AS branch_name,\n                b.print_name_1 AS branch_print_name_1,\n                b.print_name_2 AS branch_print_name_2,\n                b.company_code AS branch_code,\n                b.coupon_prefix AS branch_coupon_prefix,\n                b.tel_no AS branch_tel_no,\n                r.draw_date,\n                r.payout_deadline,\n                r.status AS round_status\n            FROM mz_issue i\n            LEFT JOIN mz_company c\n              ON i.company_id = c.company_id\n            LEFT JOIN mz_company b\n              ON i.branch_id = b.company_id\n            LEFT JOIN mz_round r\n              ON i.round_no = r.round_no\n            WHERE i.issue_id = '{$issue_id}'\n              AND {$scope_sql}\n            LIMIT 1\n        ");
    }
}

if (!function_exists('mjtto_get_prize_map')) {
    function mjtto_get_prize_map($round_no, $company_id = 0, $branch_id = 0)
    {
        $round_no = (int)$round_no;
        $company_id = (int)$company_id;
        $branch_id = (int)$branch_id;

        $rows = array();
        $res = sql_query("
            SELECT owner_type, owner_company_id, prize_rank, prize_name, prize_desc, round_no, created_at, prize_id
              FROM mz_round_prize
             WHERE round_no <= '{$round_no}'
               AND is_active = 1
               AND (
                    owner_type = 'SYSTEM'
                    OR (owner_type = 'COMPANY' AND owner_company_id = '{$company_id}')
                    OR (owner_type = 'BRANCH' AND owner_company_id = '{$branch_id}')
               )
             ORDER BY FIELD(owner_type, 'BRANCH', 'COMPANY', 'SYSTEM'), prize_rank ASC, round_no DESC, created_at DESC, prize_id DESC
        ", false);

        if ($res) {
            while ($row = sql_fetch_array($res)) {
                $rank = (int)$row['prize_rank'];
                if ($rank < 1 || $rank > 5) {
                    continue;
                }
                if (!isset($rows[$rank])) {
                    $rows[$rank] = $row;
                }
            }
        }

        return $rows;
    }
}

if (!function_exists('mjtto_claim_table_exists')) {
    function mjtto_claim_table_exists()
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        $row = sql_fetch("SHOW TABLES LIKE 'mz_prize_claim'");
        $exists = $row ? true : false;
        return $exists;
    }
}

if (!function_exists('mjtto_claim_status_name')) {
    function mjtto_claim_status_name($status)
    {
        switch (strtoupper(trim((string)$status))) {
            case 'CLAIM_REQUEST': return '지급요청';
            case 'CLAIM_APPROVED': return '지급승인';
            case 'CLAIM_DONE': return '지급완료';
            case 'CLAIM_HOLD': return '보류';
            case 'CLAIM_REJECT': return '반려';
            case 'CLAIM_WAIT': return '청구대기';
            default: return $status ? $status : '-';
        }
    }
}

if (!function_exists('mjtto_claim_badge_class')) {
    function mjtto_claim_badge_class($status)
    {
        switch (strtoupper(trim((string)$status))) {
            case 'CLAIM_REQUEST': return 'badge-blue';
            case 'CLAIM_APPROVED': return 'badge-indigo';
            case 'CLAIM_DONE': return 'badge-green';
            case 'CLAIM_HOLD': return 'badge-orange';
            case 'CLAIM_REJECT': return 'badge-red';
            default: return 'badge-gray';
        }
    }
}

if (!function_exists('mjtto_item_status_name')) {
    function mjtto_item_status_name($status)
    {
        switch (strtoupper(trim((string)$status))) {
            case 'ISSUED': return '발권완료';
            case 'DRAW_WIN': return '당첨';
            case 'DRAW_LOSE': return '낙첨';
            case 'CLAIM_WAIT': return '청구대기';
            case 'CLAIM_REQUEST': return '지급요청';
            case 'CLAIM_APPROVED': return '지급승인';
            case 'CLAIM_DONE': return '지급완료';
            case 'CLAIM_HOLD': return '보류';
            case 'CLAIM_REJECT': return '반려';
            default: return $status ? $status : '-';
        }
    }
}

if (!function_exists('mjtto_prize_owner_name')) {
    function mjtto_prize_owner_name($owner_type)
    {
        switch (strtoupper(trim((string)$owner_type))) {
            case 'SYSTEM': return '본사';
            case 'COMPANY': return '제휴사';
            case 'BRANCH': return '지점';
            default: return $owner_type ? $owner_type : '-';
        }
    }
}

if (!function_exists('mjtto_get_claim_scope_sql')) {
    function mjtto_get_claim_scope_sql($auth, $claim_alias = 'pc')
    {
        $claim_alias = preg_replace('/[^a-zA-Z0-9_]/', '', $claim_alias);
        if (!$claim_alias) $claim_alias = 'pc';

        switch ($auth['role']) {
            case 'SUPER_ADMIN':
                return '1=1';
            case 'COMPANY_ADMIN':
                return "{$claim_alias}.company_id = '".(int)$auth['company_id']."'";
            case 'BRANCH_ADMIN':
            default:
                return "{$claim_alias}.branch_id = '".(int)$auth['company_id']."'";
        }
    }
}

if (!function_exists('mjtto_can_request_claim')) {
    function mjtto_can_request_claim($auth, $issue_row, $result_rank)
    {
        $result_rank = (int)$result_rank;
        if ($result_rank < 1 || $result_rank > 5) {
            return false;
        }

        if (empty($auth['role']) || empty($issue_row['issue_id'])) {
            return false;
        }

        if ($auth['role'] === 'SUPER_ADMIN') {
            return true;
        }

        if ($auth['role'] === 'COMPANY_ADMIN') {
            return (int)$issue_row['company_id'] === (int)$auth['company_id'];
        }

        if ($auth['role'] === 'BRANCH_ADMIN') {
            return (int)$issue_row['branch_id'] === (int)$auth['company_id'];
        }

        return false;
    }
}

if (!function_exists('mjtto_claim_allowed_actions')) {
    function mjtto_claim_allowed_actions($auth, $claim_row)
    {
        if (!$claim_row || empty($claim_row['claim_id'])) {
            return array();
        }

        if (($auth['role'] ?? '') !== 'SUPER_ADMIN') {
            return array();
        }

        $status = strtoupper(trim((string)$claim_row['claim_status']));
        $result_rank = (int)($claim_row['result_rank'] ?? 0);
        $actions = array();

        if ($result_rank === 5) {
            if (in_array($status, array('CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD', 'CLAIM_REJECT'), true)) {
                $actions[] = 'send_5th_sms';
            }
            if (in_array($status, array('CLAIM_REQUEST', 'CLAIM_APPROVED'), true)) {
                $actions[] = 'hold';
            }
            if (in_array($status, array('CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD'), true)) {
                $actions[] = 'reject';
            }

            return array_values(array_unique($actions));
        }

        if (in_array($status, array('CLAIM_REQUEST', 'CLAIM_HOLD', 'CLAIM_REJECT'), true)) {
            $actions[] = 'approve';
        }
        if (in_array($status, array('CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_HOLD'), true)) {
            $actions[] = 'done';
            $actions[] = 'reject';
        }
        if (in_array($status, array('CLAIM_REQUEST', 'CLAIM_APPROVED'), true)) {
            $actions[] = 'hold';
        }

        return array_values(array_unique($actions));
    }
}

if (!function_exists('mjtto_claim_action_label')) {
    function mjtto_claim_action_label($action, $claim_row = array())
    {
        $result_rank = (int)($claim_row['result_rank'] ?? 0);
        $status = strtoupper(trim((string)($claim_row['claim_status'] ?? '')));

        if ($action === 'send_5th_sms' && $result_rank === 5) {
            $ticket_no = '';
            if (!empty($claim_row['reward_ticket_no'])) {
                $ticket_no = trim((string)$claim_row['reward_ticket_no']);
            } elseif (!empty($claim_row['reward_issue_ticket_no'])) {
                $ticket_no = trim((string)$claim_row['reward_issue_ticket_no']);
            } elseif (!empty($claim_row['admin_memo']) && preg_match('/5등 경품권 발권\s+([A-Z0-9\-]+)/u', (string)$claim_row['admin_memo'], $m)) {
                $ticket_no = trim((string)$m[1]);
            }

            if ($ticket_no !== '' && $status !== 'CLAIM_DONE') {
                return '재전송';
            }

            return '문자전송';
        }

        if ($action === 'approve') return '승인';
        if ($action === 'done') return '완료';
        if ($action === 'hold') return '보류';
        if ($action === 'reject') return '반려';

        return '';
    }
}

if (!function_exists('mjtto_get_claim_row')) {
    function mjtto_get_claim_row($claim_id, $auth)
    {
        $claim_id = (int)$claim_id;
        if ($claim_id < 1) {
            return array();
        }

        $scope_sql = mjtto_get_claim_scope_sql($auth, 'pc');

        return sql_fetch("\n            SELECT\n                pc.*,\n                i.issue_no,\n                i.created_by AS issue_created_by,\n                i.created_at AS issue_created_at,\n                c.company_name AS contract_name,\n                b.company_name AS branch_name\n            FROM mz_prize_claim pc\n            JOIN mz_issue i\n              ON pc.issue_id = i.issue_id\n            LEFT JOIN mz_company c\n              ON pc.company_id = c.company_id\n            LEFT JOIN mz_company b\n              ON pc.branch_id = b.company_id\n            WHERE pc.claim_id = '{$claim_id}'\n              AND {$scope_sql}\n            LIMIT 1\n        ");
    }
}

if (!function_exists('mjtto_get_claim_map_by_issue')) {
    function mjtto_get_claim_map_by_issue($issue_id, $auth)
    {
        $issue_id = (int)$issue_id;
        $rows = array();

        if ($issue_id < 1 || !mjtto_claim_table_exists()) {
            return $rows;
        }

        $scope_sql = mjtto_get_claim_scope_sql($auth, 'pc');
        $res = sql_query("\n            SELECT pc.*\n              FROM mz_prize_claim pc\n             WHERE pc.issue_id = '{$issue_id}'\n               AND {$scope_sql}\n        ", false);

        if ($res) {
            while ($row = sql_fetch_array($res)) {
                $rows[(int)$row['issue_item_id']] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('mjtto_get_round_draw')) {
    function mjtto_get_round_draw($round_no)
    {
        $round_no = (int)$round_no;
        if ($round_no < 1) {
            return array();
        }

        return sql_fetch("\n            SELECT\n                r.round_no,\n                r.draw_date,\n                r.payout_deadline,\n                r.status AS round_status,\n                dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f, dr.bonus_no\n            FROM mz_round r\n            LEFT JOIN mz_draw_result dr\n              ON r.round_id = dr.round_id\n            WHERE r.round_no = '{$round_no}'\n            LIMIT 1\n        ");
    }
}

if (!function_exists('mjtto_calc_issue_item_rank')) {
    function mjtto_calc_issue_item_rank($item_row, $draw_row)
    {
        if (!$item_row || !$draw_row || empty($draw_row['win_a'])) {
            return 0;
        }

        $my = array(
            (int)$item_row['num_a'],
            (int)$item_row['num_b'],
            (int)$item_row['num_c'],
            (int)$item_row['num_d'],
            (int)$item_row['num_e'],
            (int)$item_row['num_f']
        );

        $win = array(
            (int)$draw_row['win_a'],
            (int)$draw_row['win_b'],
            (int)$draw_row['win_c'],
            (int)$draw_row['win_d'],
            (int)$draw_row['win_e'],
            (int)$draw_row['win_f']
        );

        $match_count = 0;
        foreach ($my as $n) {
            if (in_array($n, $win, true)) {
                $match_count++;
            }
        }

        $bonus_match = in_array((int)$draw_row['bonus_no'], $my, true);

        if ($match_count === 6) return 1;
        if ($match_count === 5 && $bonus_match) return 2;
        if ($match_count === 5) return 3;
        if ($match_count === 4) return 4;
        if ($match_count === 3) return 5;
        return 0;
    }
}

if (!function_exists('mjtto_rank_text')) {
    function mjtto_rank_text($rank)
    {
        $rank = (int)$rank;
        if ($rank < 1 || $rank > 5) {
            return '낙첨';
        }
        return $rank . '등';
    }
}


if (!function_exists('mjtto_get_current_issue_round')) {
    function mjtto_get_current_issue_round()
    {
        $row = sql_fetch("
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

        if (!$row || empty($row['round_id'])) {
            return array(
                'round_id' => 0,
                'round_no' => 0,
                'draw_date' => '',
                'payout_deadline' => '',
                'status' => ''
            );
        }

        return $row;
    }
}

if (!function_exists('mjtto_get_previous_draw_round')) {
    function mjtto_get_previous_draw_round()
    {
        $row = sql_fetch("
            SELECT r.round_id, r.round_no, r.draw_date, r.payout_deadline, r.status
              FROM mz_round r
              INNER JOIN mz_draw_result d
                      ON r.round_id = d.round_id
             ORDER BY r.round_no DESC
             LIMIT 1
        ");

        if (!$row || empty($row['round_id'])) {
            return array(
                'round_id' => 0,
                'round_no' => 0,
                'draw_date' => '',
                'payout_deadline' => '',
                'status' => ''
            );
        }

        return $row;
    }
}

if (!function_exists('mjtto_get_round_select_options')) {
    function mjtto_get_round_select_options($last_round_no, $window_size = 15)
    {
        $last_round_no = (int)$last_round_no;
        $window_size = max(1, (int)$window_size);
        $rows = array();

        if ($last_round_no < 1) {
            return $rows;
        }

        $start_round_no = max(1, $last_round_no - $window_size);
        $res = sql_query("
            SELECT round_no, draw_date, payout_deadline, status
              FROM mz_round
             WHERE round_no BETWEEN '{$start_round_no}' AND '{$last_round_no}'
             ORDER BY round_no DESC
        ", false);

        if ($res) {
            while ($row = sql_fetch_array($res)) {
                $rows[] = $row;
            }
        }

        if (empty($rows)) {
            for ($round_no = $last_round_no; $round_no >= $start_round_no; $round_no--) {
                $rows[] = array(
                    'round_no' => $round_no,
                    'draw_date' => '',
                    'payout_deadline' => '',
                    'status' => ''
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('mjtto_can_self_pay_rank5')) {
    function mjtto_can_self_pay_rank5($auth, $issue_row, $result_rank)
    {
        return false;
    }
}


if (!function_exists('mjtto_is_payout_deadline_expired')) {
    function mjtto_is_payout_deadline_expired($payout_deadline)
    {
        $payout_deadline = trim((string)$payout_deadline);
        if ($payout_deadline === '' || $payout_deadline === '0000-00-00') {
            return false;
        }

        $deadline_ts = strtotime($payout_deadline . ' 23:59:59');
        if (!$deadline_ts) {
            return false;
        }

        return time() > $deadline_ts;
    }
}

if (!function_exists('mjtto_claim_deadline_message')) {
    function mjtto_claim_deadline_message()
    {
        return '지급가능기간이 지났습니다.';
    }
}

if (!function_exists('mjtto_company_column_exists')) {
    function mjtto_company_column_exists($column_name)
    {
        static $cache = array();

        $column_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column_name);
        if ($column_name === '') {
            return false;
        }

        if (array_key_exists($column_name, $cache)) {
            return $cache[$column_name];
        }

        $row = sql_fetch("SHOW COLUMNS FROM mz_company LIKE '" . sql_real_escape_string($column_name) . "'");
        $cache[$column_name] = $row ? true : false;
        return $cache[$column_name];
    }
}

if (!function_exists('mjtto_claim_column_exists')) {
    function mjtto_claim_column_exists($column_name)
    {
        static $cache = array();
        $column_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column_name);
        if ($column_name === '') return false;
        if (array_key_exists($column_name, $cache)) return $cache[$column_name];
        $row = sql_fetch("SHOW COLUMNS FROM mz_prize_claim LIKE '" . sql_real_escape_string($column_name) . "'");
        $cache[$column_name] = $row ? true : false;
        return $cache[$column_name];
    }
}

if (!function_exists('mjtto_sms_log_table_exists')) {
    function mjtto_sms_log_table_exists()
    {
        static $checked = null;
        if ($checked !== null) return $checked;
        $row = sql_fetch("SHOW TABLES LIKE 'mz_sms_send_log'");
        $checked = $row ? true : false;
        return $checked;
    }
}

if (!function_exists('mjtto_get_default_prize_issue_branch')) {
    function mjtto_get_default_prize_issue_branch()
    {
        if (!mjtto_company_column_exists('is_prize_issue_default')) {
            return array();
        }

        $row = sql_fetch("
            SELECT
                b.company_id,
                b.parent_company_id,
                b.company_name,
                b.company_code,
                b.coupon_prefix,
                b.issue_game_count,
                b.status,
                c.company_name AS contract_name
            FROM mz_company b
            LEFT JOIN mz_company c
              ON b.parent_company_id = c.company_id
            WHERE b.company_type = 'BRANCH'
              AND b.status = 1
              AND b.is_prize_issue_default = 1
            ORDER BY b.company_id ASC
            LIMIT 1
        ");

        return $row ? $row : array();
    }
}

if (!function_exists('mjtto_issue_one_prize_ticket')) {
    function mjtto_issue_one_prize_ticket($branch_id, $created_by = '', $issue_memo = '')
    {
        $branch_id = (int)$branch_id;
        $created_by = trim((string)$created_by);
        $issue_memo = trim((string)$issue_memo);

        if ($branch_id < 1) {
            return array('ok' => false, 'error' => '기본 발권 지점이 지정되지 않았습니다.');
        }

        $branch = sql_fetch("
            SELECT company_id, parent_company_id, company_name, company_code, coupon_prefix, status
              FROM mz_company
             WHERE company_id = '{$branch_id}'
               AND company_type = 'BRANCH'
               AND status = 1
             LIMIT 1
        ");

        if (!$branch || empty($branch['company_id'])) {
            return array('ok' => false, 'error' => '기본 발권 지점 정보를 찾을 수 없습니다.');
        }

        $current_round = mjtto_get_current_issue_round();
        if (empty($current_round['round_id']) || empty($current_round['round_no'])) {
            return array('ok' => false, 'error' => '현재 발권 가능한 회차가 없습니다.');
        }

        $prize_game_count = 5;

        $res = sql_query("
            SELECT uid, a, b, c, d, e, f
              FROM mjtto_db
             WHERE chk = 0
             ORDER BY draw_order ASC, uid ASC
             LIMIT {$prize_game_count}
             FOR UPDATE
        ", false);

        $rows = array();
        if ($res) {
            while ($tmp = sql_fetch_array($res)) {
                $rows[] = $tmp;
            }
        }

        if (count($rows) < $prize_game_count) {
            return array('ok' => false, 'error' => '발권 가능한 번호가 부족합니다.');
        }

        $round_no = (int)$current_round['round_no'];
        $contract_id = (int)$branch['parent_company_id'];
        $branch_code = trim((string)$branch['company_code']);
        $created_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $now = G5_TIME_YMDHIS;
        $issue_memo_sql = sql_real_escape_string($issue_memo);

        $ok = sql_query("
            INSERT INTO mz_issue
                SET issue_no = '',
                    company_id = '{$contract_id}',
                    branch_id = '{$branch_id}',
                    round_no = '{$round_no}',
                    issue_qty = '1',
                    issue_game_count = '{$prize_game_count}',
                    issue_status = 'ISSUED',
                    issue_memo = '{$issue_memo_sql}',
                    created_by = '" . sql_real_escape_string($created_by) . "',
                    created_ip = '" . sql_real_escape_string($created_ip) . "',
                    created_at = '{$now}'
        ", false);

        if (!$ok) {
            return array('ok' => false, 'error' => '경품권 발권 헤더 생성 중 오류가 발생했습니다.');
        }

        $issue_id = (int)sql_insert_id();
        if ($issue_id < 1) {
            return array('ok' => false, 'error' => '경품권 발권번호 생성에 실패했습니다.');
        }

        $issue_no = $branch_code . '-' . $round_no . '-' . str_pad($issue_id, 8, '0', STR_PAD_LEFT);
        $issue_no_sql = sql_real_escape_string($issue_no);

        $ok = sql_query("
            UPDATE mz_issue
               SET issue_no = '{$issue_no_sql}'
             WHERE issue_id = '{$issue_id}'
        ", false);

        if (!$ok) {
            return array('ok' => false, 'error' => '경품권 발권번호 저장 중 오류가 발생했습니다.');
        }

        $first_issue_item_id = 0;
        $first_ticket_no = '';
        $created_item_ids = array();
        $created_ticket_nos = array();
        $game_index = 0;

        foreach ($rows as $row) {
            $game_index++;
            $uid = (int)$row['uid'];

            $ok = sql_query("
                UPDATE mjtto_db
                   SET chk = 1,
                       round_no = '{$round_no}',
                       name = '{$issue_no_sql}',
                       ip = '" . sql_real_escape_string($created_ip) . "',
                       used_at = '{$now}'
                 WHERE uid = '{$uid}'
                   AND chk = 0
            ", false);

            $locked_row = array();
            if ($ok) {
                $locked_row = sql_fetch("
                    SELECT uid, chk, round_no, name
                      FROM mjtto_db
                     WHERE uid = '{$uid}'
                     LIMIT 1
                ", false);
            }

            if (
                !$ok
                || empty($locked_row['uid'])
                || (int)$locked_row['chk'] !== 1
                || (int)$locked_row['round_no'] !== $round_no
                || (string)$locked_row['name'] !== $issue_no
            ) {
                return array('ok' => false, 'error' => '경품권 원본 번호 선점에 실패했습니다.');
            }

            $ticket_no = $issue_no . '-' . str_pad($game_index, 3, '0', STR_PAD_LEFT);
            $ticket_no_sql = sql_real_escape_string($ticket_no);

            $ok = sql_query("
                INSERT INTO mz_issue_item
                    SET issue_id = '{$issue_id}',
                        ticket_no = '{$ticket_no_sql}',
                        round_no = '{$round_no}',
                        num_a = '" . (int)$row['a'] . "',
                        num_b = '" . (int)$row['b'] . "',
                        num_c = '" . (int)$row['c'] . "',
                        num_d = '" . (int)$row['d'] . "',
                        num_e = '" . (int)$row['e'] . "',
                        num_f = '" . (int)$row['f'] . "',
                        item_status = 'ISSUED',
                        customer_name = '',
                        customer_hp = '',
                        sent_at = NULL,
                        created_at = '{$now}'
            ", false);

            if (!$ok) {
                return array('ok' => false, 'error' => '경품권 상세 생성 중 오류가 발생했습니다.');
            }

            $issue_item_id = (int)sql_insert_id();
            $created_item_ids[] = $issue_item_id;
            $created_ticket_nos[] = $ticket_no;

            if ($first_issue_item_id < 1) {
                $first_issue_item_id = $issue_item_id;
                $first_ticket_no = $ticket_no;
            }
        }

        return array(
            'ok' => true,
            'issue_id' => $issue_id,
            'issue_no' => $issue_no,
            'issue_item_id' => $first_issue_item_id,
            'ticket_no' => $first_ticket_no,
            'round_no' => $round_no,
            'branch_id' => $branch_id,
            'company_id' => $contract_id,
            'issue_game_count' => $prize_game_count,
            'created_item_ids' => $created_item_ids,
            'created_ticket_nos' => $created_ticket_nos
        );
    }
}

if (!function_exists('mjtto_send_sms_message')) {
    function mjtto_send_sms_message($recv_hp, $message, &$result_text = '')
    {
        global $config;

        $result_text = '';

        $recv_number = preg_replace('/[^0-9]/', '', (string)$recv_hp);
		$send_number = mjtto_sms_get_sender_number();

        if ($recv_number === '' || strlen($recv_number) < 10 || strlen($recv_number) > 11) {
            $result_text = '수신 휴대폰 번호가 올바르지 않습니다.';
            return false;
        }

        if ($send_number === '' || strlen($send_number) < 10 || strlen($send_number) > 11) {
            $result_text = '발신번호가 설정되지 않았습니다.';
            return false;
        }

        if (!defined('G5_PLUGIN_PATH')) {
            $result_text = 'G5_PLUGIN_PATH 가 정의되지 않았습니다.';
            return false;
        }

        $sms_lib = G5_PLUGIN_PATH . '/sms5/sms5.lib.php';
        if (!file_exists($sms_lib)) {
            $result_text = 'SMS5 라이브러리를 찾을 수 없습니다.';
            return false;
        }

        include_once $sms_lib;

        if (!class_exists('SMS5') && !class_exists('SMS')) {
            $result_text = 'SMS 발송 클래스를 불러오지 못했습니다.';
            return false;
        }

        $sms_class = class_exists('SMS5') ? 'SMS5' : 'SMS';
        $SMS = new $sms_class();

        if (!method_exists($SMS, 'SMS_con') || !method_exists($SMS, 'Add') || !method_exists($SMS, 'Send')) {
            $result_text = 'SMS 발송 함수 구성이 올바르지 않습니다.';
            return false;
        }

        $port = $config['cf_icode_server_port'] ?? '';
        $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $port);
        $add_ok = $SMS->Add($recv_number, $send_number, $config['cf_icode_id'], $message, '');

        if (!$add_ok) {
            $result_text = 'SMS 발송 데이터 생성에 실패했습니다.';
            return false;
        }

        $send_ok = $SMS->Send();
        if (!$send_ok) {
            $result_text = 'SMS 전송 처리에 실패했습니다.';
            return false;
        }

        $result_line = '';
        if (isset($SMS->Result) && is_array($SMS->Result) && !empty($SMS->Result)) {
            $result_line = (string)reset($SMS->Result);
        }

        if ($result_line !== '') {
            $result_text = $result_line;
            if (stripos($result_line, 'Error(') !== false) {
                return false;
            }
        } else {
            $result_text = 'OK';
        }

        return true;
    }
}


if (!function_exists('mjtto_sms_load_bridge')) {
    function mjtto_sms_load_bridge()
    {
		global $config;
        if (function_exists('sysk_sms_send_one')) return true;
        if (!defined('G5_PLUGIN_PATH')) return false;
        $sms_lib = G5_PLUGIN_PATH . '/sms5/sms5.lib.php';
        if (!file_exists($sms_lib)) return false;
        include_once $sms_lib;
        return function_exists('sysk_sms_send_one');
    }
}

if (!function_exists('mjtto_sms_get_sender_number')) {
    function mjtto_sms_get_sender_number()
    {
        global $g5, $config, $sms5;

        $candidates = array();

        if (!empty($sms5['cf_phone'])) {
            $candidates[] = $sms5['cf_phone'];
        }

        if (!empty($config['cf_phone'])) {
            $candidates[] = $config['cf_phone'];
        }

        if (isset($g5['sms5_config_table']) && $g5['sms5_config_table']) {
            $row = sql_fetch(" SELECT cf_phone FROM {$g5['sms5_config_table']} LIMIT 1 ", false);
            if (!empty($row['cf_phone'])) {
                $candidates[] = $row['cf_phone'];
            }
        }

        foreach ($candidates as $phone) {
            $phone = preg_replace('/[^0-9]/', '', (string)$phone);

            if ($phone === '') {
                continue;
            }

            if (function_exists('check_vaild_callback')) {
                if (check_vaild_callback($phone)) {
                    return $phone;
                }
            } else {
                return $phone;
            }
        }

        return '';
    }
}

if (!function_exists('mjtto_sms_is_lms_message')) {
    function mjtto_sms_is_lms_message($message, $title = '')
    {
        global $config;
        if (strtoupper((string)($config['cf_sms_type'] ?? '')) === 'LMS') return true;
        if (trim((string)$title) !== '') return true;
        $body = (string)$message;
        if (function_exists('iconv')) {
            $euckr = @iconv('UTF-8', 'EUC-KR//IGNORE', $body);
            if ($euckr !== false) return strlen($euckr) > 80;
        }
        return strlen($body) > 90;
    }
}

if (!function_exists('mjtto_sms_get_point_info')) {
    function mjtto_sms_get_point_info()
    {
        global $config;
        if (!function_exists('get_icode_userinfo')) return array('ok' => false, 'error' => 'get_icode_userinfo 함수가 없습니다.');
        $icode_id = trim((string)($config['cf_icode_id'] ?? ''));
        $icode_pw = trim((string)($config['cf_icode_pw'] ?? ''));
        if ($icode_id === '' || $icode_pw === '') return array('ok' => false, 'error' => '문자 계정 설정이 비어 있습니다.');
        $userinfo = get_icode_userinfo($icode_id, $icode_pw);
        $code = (string)($userinfo['code'] ?? '');
        if ($code !== '0' && $code !== '0.0' && $code !== '') return array('ok' => false, 'error' => '문자 포인트 조회 실패', 'raw' => $userinfo);
        $point = (float)($userinfo['coin'] ?? 0);
        return array('ok' => true, 'point' => $point, 'display_point' => number_format($point), 'payment' => (string)($userinfo['payment'] ?? 'A'), 'gpay' => (float)($userinfo['gpay'] ?? 0), 'raw' => $userinfo);
    }
}

if (!function_exists('mjtto_sms_log_insert')) {
    function mjtto_sms_log_insert($row)
    {
        if (!mjtto_sms_log_table_exists()) return 0;
        $claim_id = (int)($row['claim_id'] ?? 0);
        $issue_item_id = (int)($row['issue_item_id'] ?? 0);
        $reward_issue_item_id = (int)($row['reward_issue_item_id'] ?? 0);
        $result_rank = (int)($row['result_rank'] ?? 0);
        $ticket_no = sql_real_escape_string((string)($row['ticket_no'] ?? ''));
        $reward_ticket_no = sql_real_escape_string((string)($row['reward_ticket_no'] ?? ($row['reward_issue_ticket_no'] ?? '')));
        $recv_hp = sql_real_escape_string((string)($row['recv_hp'] ?? ''));
        $send_hp = sql_real_escape_string((string)($row['send_hp'] ?? ''));
        $sms_type = sql_real_escape_string((string)($row['sms_type'] ?? 'SMS'));
        $send_status = sql_real_escape_string((string)($row['send_status'] ?? ''));
        $result_text = sql_real_escape_string((string)($row['result_text'] ?? ''));
        $message_text = sql_real_escape_string((string)($row['message_text'] ?? ''));
        $created_by = sql_real_escape_string((string)($row['created_by'] ?? ''));
        $point_balance = isset($row['point_balance']) && $row['point_balance'] !== '' ? (float)$row['point_balance'] : null;
        $payment_type = sql_real_escape_string((string)($row['payment_type'] ?? ''));
        $sql = "
            INSERT INTO mz_sms_send_log
                SET claim_id = '{$claim_id}',
                    issue_item_id = '{$issue_item_id}',
                    reward_issue_item_id = '{$reward_issue_item_id}',
                    result_rank = '{$result_rank}',
                    ticket_no = '{$ticket_no}',
                    reward_ticket_no = '{$reward_ticket_no}',
                    recv_hp = '{$recv_hp}',
                    send_hp = '{$send_hp}',
                    sms_type = '{$sms_type}',
                    send_status = '{$send_status}',
                    result_text = '{$result_text}',
                    message_text = '{$message_text}',
                    point_balance = " . ($point_balance === null ? "NULL" : "'{$point_balance}'") . ",
                    payment_type = '{$payment_type}',
                    created_by = '{$created_by}',
                    created_at = NOW()
        ";
        $ok = sql_query($sql, false);
        if (!$ok) {
            $err = function_exists('sql_error') ? sql_error() : '';
            error_log('[MJTTO_SMS_LOG] SQL ERROR: ' . $err . ' | SQL=' . preg_replace('/\s+/', ' ', trim($sql)));
            return 0;
        }
        return (int)sql_insert_id();
    }
}

if (!function_exists('mjtto_sms_get_send_list')) {
    function mjtto_sms_get_send_list($params = array())
    {
        $rows = array();
        if (!mjtto_sms_log_table_exists()) return array('ok' => false, 'error' => 'mz_sms_send_log 테이블이 없습니다.', 'list' => $rows);
        $limit = max(1, (int)($params['limit'] ?? 20));
        $where = array('1=1');
        if (!empty($params['claim_id'])) $where[] = "claim_id = '" . (int)$params['claim_id'] . "'";
        if (!empty($params['to'])) {
            $to = preg_replace('/[^0-9]/', '', (string)$params['to']);
            if ($to !== '') $where[] = "REPLACE(REPLACE(REPLACE(recv_hp, '-', ''), ' ', ''), '.', '') LIKE '%" . sql_real_escape_string($to) . "%'";
        }
        $res = sql_query("
            SELECT *
              FROM mz_sms_send_log
             WHERE " . implode(' AND ', $where) . "
             ORDER BY sms_log_id DESC
             LIMIT {$limit}
        ", false);
        if (!$res) return array('ok' => false, 'error' => '문자 로그 조회 중 오류가 발생했습니다.', 'list' => $rows);
        while ($row = sql_fetch_array($res)) $rows[] = $row;
        return array('ok' => true, 'list' => $rows);
    }
}

if (!function_exists('mjtto_claim_get_reward_issue_ticket_no')) {
    function mjtto_claim_get_reward_issue_ticket_no($claim)
    {
        if (is_array($claim) && !empty($claim['reward_ticket_no'])) return trim((string)$claim['reward_ticket_no']);
        if (is_array($claim) && !empty($claim['reward_issue_ticket_no'])) return trim((string)$claim['reward_issue_ticket_no']);
        $memo = is_array($claim) ? (string)($claim['admin_memo'] ?? '') : '';
        if ($memo !== '' && preg_match('/5등 경품권 발권\s+([A-Z0-9\-]+)/u', $memo, $m)) return trim((string)$m[1]);
        return '';
    }
}

if (!function_exists('mjtto_get_reward_issue_item_by_claim')) {
    function mjtto_get_reward_issue_item_by_claim($claim)
    {
        if (!is_array($claim)) return array();
        if (!empty($claim['reward_issue_item_id'])) {
            $row = sql_fetch("
                SELECT ii.*, i.issue_no
                  FROM mz_issue_item ii
                  JOIN mz_issue i
                    ON ii.issue_id = i.issue_id
                 WHERE ii.issue_item_id = '" . (int)$claim['reward_issue_item_id'] . "'
                 LIMIT 1
            ");
            if ($row) return $row;
        }
        $ticket_no = mjtto_claim_get_reward_issue_ticket_no($claim);
        if ($ticket_no === '') return array();
        return sql_fetch("
            SELECT ii.*, i.issue_no
              FROM mz_issue_item ii
              JOIN mz_issue i
                ON ii.issue_id = i.issue_id
             WHERE ii.ticket_no = '" . sql_real_escape_string($ticket_no) . "'
             LIMIT 1
        ") ?: array();
    }
}

if (!function_exists('mjtto_claim_store_reward_issue_meta')) {
    function mjtto_claim_store_reward_issue_meta($claim_id, $issue_result = array(), $sms_result = '', $sms_sent_at = '')
    {
        $claim_id = (int)$claim_id;
        if ($claim_id < 1) return false;
        $updates = array();
        if (!empty($issue_result)) {
            if (mjtto_claim_column_exists('reward_issue_branch_id')) $updates[] = "reward_issue_branch_id = '" . (int)($issue_result['branch_id'] ?? 0) . "'";
            if (mjtto_claim_column_exists('reward_issue_round_no')) $updates[] = "reward_issue_round_no = '" . (int)($issue_result['round_no'] ?? 0) . "'";
            if (mjtto_claim_column_exists('reward_issue_id')) $updates[] = "reward_issue_id = '" . (int)($issue_result['issue_id'] ?? 0) . "'";
            if (mjtto_claim_column_exists('reward_issue_item_id')) $updates[] = "reward_issue_item_id = '" . (int)($issue_result['issue_item_id'] ?? 0) . "'";
            if (mjtto_claim_column_exists('reward_ticket_no')) $updates[] = "reward_ticket_no = '" . sql_real_escape_string((string)($issue_result['ticket_no'] ?? '')) . "'";
            else if (mjtto_claim_column_exists('reward_issue_ticket_no')) $updates[] = "reward_issue_ticket_no = '" . sql_real_escape_string((string)($issue_result['ticket_no'] ?? '')) . "'";
        }
        if (mjtto_claim_column_exists('sms_send_result')) $updates[] = "sms_send_result = '" . sql_real_escape_string((string)$sms_result) . "'";
        if ($sms_sent_at !== '' && mjtto_claim_column_exists('sms_sent_at')) $updates[] = "sms_sent_at = '" . sql_real_escape_string((string)$sms_sent_at) . "'";
        if (empty($updates)) return true;
        return (bool)sql_query("UPDATE mz_prize_claim SET " . implode(', ', $updates) . " WHERE claim_id = '{$claim_id}'", false);
    }
}

if (!function_exists('mjtto_sms_send_one_logged')) {
    function mjtto_sms_send_one_logged($claim_row, $message, $options = array())
    {
        global $member;
        $claim_id = (int)($claim_row['claim_id'] ?? 0);
        $recv_hp = preg_replace('/[^0-9]/', '', (string)($claim_row['request_hp'] ?? ''));
        $send_hp = mjtto_sms_get_sender_number();
        $title = trim((string)($options['title'] ?? ''));
        $reserve_at = trim((string)($options['reserve_at'] ?? ''));
        $reward_ticket_no = (string)($options['reward_ticket_no'] ?? ($options['reward_issue_ticket_no'] ?? ''));
        $reward_issue_item_id = (int)($options['reward_issue_item_id'] ?? 0);
        $point_info = mjtto_sms_get_point_info();
        $is_lms = mjtto_sms_is_lms_message($message, $title);
        $result_text = '';
        $ok = false;

		$send_hp_digits = preg_replace('/[^0-9]/', '', (string)$send_hp);

		$is_valid_sender =
			preg_match('/^(15|16|18)\d{6}$/', $send_hp_digits) ||
			(strlen($send_hp_digits) >= 9 && strlen($send_hp_digits) <= 11);

		if ($send_hp_digits === '' || !$is_valid_sender) {
			$result_text = '발신번호가 설정되지 않았습니다.';
		} elseif ($recv_hp === '' || strlen($recv_hp) < 10 || strlen($recv_hp) > 11) {
			$result_text = '수신 휴대폰 번호가 올바르지 않습니다.';
		} elseif (function_exists('is_sms_send') && !is_sms_send()) {
			$result_text = '문자 발송 가능 포인트가 부족하거나 계정 설정 확인이 필요합니다.';
		} elseif (!mjtto_sms_load_bridge()) {
			$result_text = 'SMS5 라이브러리를 찾을 수 없습니다.';
		} elseif (!function_exists('sysk_sms_send_one')) {
			$result_text = '직접 문자 전송 함수를 찾을 수 없습니다.';
		} else {
			list($ok, $reason) = sysk_sms_send_one($recv_hp, $send_hp, (string)$message, $title, $is_lms, $reserve_at);
			$result_text = $ok ? 'OK' : (string)$reason;
		}

        $log_id = mjtto_sms_log_insert(array(
            'claim_id' => $claim_id,
            'issue_item_id' => (int)($claim_row['issue_item_id'] ?? 0),
            'reward_issue_item_id' => $reward_issue_item_id,
            'result_rank' => (int)($claim_row['result_rank'] ?? 0),
            'ticket_no' => (string)($claim_row['ticket_no'] ?? ''),
            'reward_ticket_no' => $reward_ticket_no,
            'recv_hp' => $recv_hp,
            'send_hp' => $send_hp,
            'sms_type' => $is_lms ? 'LMS' : 'SMS',
            'send_status' => $ok ? 'SUCCESS' : 'FAIL',
            'result_text' => $result_text,
            'message_text' => (string)$message,
            'point_balance' => !empty($point_info['ok']) ? (string)$point_info['point'] : '',
            'payment_type' => !empty($point_info['ok']) ? (string)($point_info['payment'] ?? '') : '',
            'created_by' => (string)($member['mb_id'] ?? '')
        ));
        return array('ok' => $ok, 'result_text' => $result_text, 'send_hp' => $send_hp, 'recv_hp' => $recv_hp, 'sms_type' => $is_lms ? 'LMS' : 'SMS', 'log_id' => $log_id, 'point_info' => $point_info);
    }
}

if (!function_exists('mjtto_build_ticket_win_url')) {
    function mjtto_build_ticket_win_url($ticket_no)
    {
        $base_url = defined('G5_URL') ? rtrim(G5_URL, '/') : '';
        return $base_url . '/win.php?no=' . rawurlencode((string)$ticket_no);
    }
}

if (!function_exists('mjtto_normalize_month_value')) {
    function mjtto_normalize_month_value($ym = '')
    {
        $ym = trim((string)$ym);
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $ym = date('Y-m');
        }

        $ts = strtotime($ym . '-01');
        if (!$ts) {
            $ts = time();
        }

        return date('Y-m', $ts);
    }
}

if (!function_exists('mjtto_get_month_range')) {
    function mjtto_get_month_range($ym = '')
    {
        $ym = mjtto_normalize_month_value($ym);
        $start_ts = strtotime($ym . '-01 00:00:00');
        $end_ts = strtotime('+1 month', $start_ts);

        return array(
            'ym' => date('Y-m', $start_ts),
            'month_label' => date('Y년 n월', $start_ts),
            'start_date' => date('Y-m-01', $start_ts),
            'start_datetime' => date('Y-m-d 00:00:00', $start_ts),
            'end_datetime' => date('Y-m-d 00:00:00', $end_ts)
        );
    }
}

if (!function_exists('mjtto_get_month_select_options')) {
    function mjtto_get_month_select_options($base_ym = '', $count = 12)
    {
        $count = max(1, (int)$count);
        $base = mjtto_get_month_range($base_ym);
        $base_ts = strtotime($base['ym'] . '-01 00:00:00');
        $rows = array();

        for ($i = 0; $i < $count; $i++) {
            $ts = strtotime('-' . $i . ' month', $base_ts);
            $rows[] = array(
                'ym' => date('Y-m', $ts),
                'label' => date('Y년 n월', $ts)
            );
        }

        return $rows;
    }
}
