<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-15 11:18:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alert('잘못된 요청입니다.', './index.php');
}

if (!mjtto_claim_table_exists()) {
    alert('mz_prize_claim 테이블이 없습니다. SQL 패치를 먼저 적용해 주세요.', './index.php');
}

$action = trim((string)($_POST['action'] ?? ''));
$return_url = trim((string)($_POST['return_url'] ?? './claim_list.php'));
if (
    $return_url === ''
    || strpos($return_url, 'http://') === 0
    || strpos($return_url, 'https://') === 0
    || strpos($return_url, '//') === 0
    || preg_match('#^/admin/#', $return_url)
) {
    $return_url = './claim_list.php';
}
if (!preg_match('#^(\./|\.\./win\.php(?:\?|$))#', $return_url)) {
    $return_url = './claim_list.php';
}

if (!function_exists('mjtto_alert_back')) {
    function mjtto_alert_back($msg, $url)
    {
        alert($msg, $url ?: './claim_list.php');
    }
}

if ($action === 'self_done_rank5') {
    mjtto_alert_back('5등 즉시지급 기능은 숨김 처리되었습니다. 당첨자 정보를 입력해 지급요청 후 최고관리자가 완료 처리하세요.', $return_url);
}

if ($action === 'request') {
    $issue_item_id = (int)($_POST['issue_item_id'] ?? 0);
    $request_name = trim((string)($_POST['request_name'] ?? ''));
    $request_hp = trim((string)($_POST['request_hp'] ?? ''));
    $request_memo = trim((string)($_POST['request_memo'] ?? ''));

    if ($issue_item_id < 1) {
        mjtto_alert_back('대상 티켓을 찾을 수 없습니다.', $return_url);
    }
    if ($request_name === '' || $request_hp === '') {
        mjtto_alert_back('당첨자명과 휴대폰 번호를 입력해 주세요.', $return_url);
    }

    $item = sql_fetch("\n        SELECT\n            ii.*,\n            i.issue_no,\n            i.company_id,\n            i.branch_id\n        FROM mz_issue_item ii\n        JOIN mz_issue i\n          ON ii.issue_id = i.issue_id\n        WHERE ii.issue_item_id = '{$issue_item_id}'\n          AND " . mjtto_get_issue_scope_sql($auth, 'i') . "\n        LIMIT 1\n    ");

    if (!$item) {
        mjtto_alert_back('지급 요청 대상 티켓을 찾을 수 없습니다.', $return_url);
    }

    $draw = mjtto_get_round_draw($item['round_no']);
    $result_rank = mjtto_calc_issue_item_rank($item, $draw);
    if ($result_rank < 1) {
        mjtto_alert_back('당첨 티켓만 지급 요청할 수 있습니다.', $return_url);
    }

    if (mjtto_is_payout_deadline_expired($draw['payout_deadline'] ?? '')) {
        mjtto_alert_back(mjtto_claim_deadline_message(), $return_url);
    }

    if (!mjtto_can_request_claim($auth, $item, $result_rank)) {
        mjtto_alert_back('해당 지급 요청 권한이 없습니다.', $return_url);
    }

    $prize_map = mjtto_get_prize_map($item['round_no'], $item['company_id'], $item['branch_id']);
    $prize = isset($prize_map[$result_rank]) ? $prize_map[$result_rank] : array(
        'owner_type' => 'SYSTEM',
        'owner_company_id' => 0,
        'prize_name' => $result_rank . '등 경품',
        'prize_desc' => ''
    );

    $existing = sql_fetch("SELECT * FROM mz_prize_claim WHERE issue_item_id = '{$issue_item_id}' LIMIT 1");

    $request_name_sql = sql_real_escape_string($request_name);
    $request_hp_sql = sql_real_escape_string($request_hp);
    $request_memo_sql = sql_real_escape_string($request_memo);
    $request_by_sql = sql_real_escape_string($member['mb_id']);
    $ticket_no_sql = sql_real_escape_string($item['ticket_no']);
    $owner_type_sql = sql_real_escape_string($prize['owner_type']);
    $prize_name_sql = sql_real_escape_string($prize['prize_name']);
    $prize_desc_sql = sql_real_escape_string($prize['prize_desc']);
    $owner_company_id = !empty($prize['owner_company_id']) ? (int)$prize['owner_company_id'] : 0;

    sql_query('START TRANSACTION', false);

    if ($existing && !empty($existing['claim_id'])) {
        if (strtoupper((string)$existing['claim_status']) === 'CLAIM_DONE') {
            sql_query('ROLLBACK', false);
            mjtto_alert_back('이미 지급완료 처리된 건입니다.', $return_url);
        }

        $ok = sql_query("\n            UPDATE mz_prize_claim\n               SET claim_status = 'CLAIM_REQUEST',\n                   request_name = '{$request_name_sql}',\n                   request_hp = '{$request_hp_sql}',\n                   request_memo = '{$request_memo_sql}',\n                   request_by = '{$request_by_sql}',\n                   requested_at = NOW(),\n                   approve_by = '',\n                   approved_at = NULL,\n                   paid_by = '',\n                   paid_at = NULL,\n                   reject_by = '',\n                   rejected_at = NULL,\n                   reject_reason = '',\n                   admin_memo = '',\n                   prize_owner_type = '{$owner_type_sql}',\n                   prize_owner_company_id = " . ($owner_company_id > 0 ? "'{$owner_company_id}'" : 'NULL') . ",\n                   prize_name = '{$prize_name_sql}',\n                   prize_desc = '{$prize_desc_sql}'\n             WHERE claim_id = '".(int)$existing['claim_id']."'\n        ", false);
    } else {
        $ok = sql_query("\n            INSERT INTO mz_prize_claim\n                SET issue_item_id = '{$issue_item_id}',\n                    issue_id = '".(int)$item['issue_id']."',\n                    company_id = '".(int)$item['company_id']."',\n                    branch_id = '".(int)$item['branch_id']."',\n                    round_no = '".(int)$item['round_no']."',\n                    ticket_no = '{$ticket_no_sql}',\n                    result_rank = '{$result_rank}',\n                    prize_owner_type = '{$owner_type_sql}',\n                    prize_owner_company_id = " . ($owner_company_id > 0 ? "'{$owner_company_id}'" : 'NULL') . ",\n                    prize_name = '{$prize_name_sql}',\n                    prize_desc = '{$prize_desc_sql}',\n                    claim_status = 'CLAIM_REQUEST',\n                    request_name = '{$request_name_sql}',\n                    request_hp = '{$request_hp_sql}',\n                    request_memo = '{$request_memo_sql}',\n                    request_by = '{$request_by_sql}',\n                    requested_at = NOW()\n        ", false);
    }

    if (!$ok) {
        sql_query('ROLLBACK', false);
        mjtto_alert_back('지급 요청 저장 중 오류가 발생했습니다.', $return_url);
    }

    $ok2 = sql_query("\n        UPDATE mz_issue_item\n           SET item_status = 'CLAIM_REQUEST',\n               customer_name = '{$request_name_sql}',\n               customer_hp = '{$request_hp_sql}'\n         WHERE issue_item_id = '{$issue_item_id}'\n    ", false);

    if (!$ok2) {
        sql_query('ROLLBACK', false);
        mjtto_alert_back('티켓 상태 갱신 중 오류가 발생했습니다.', $return_url);
    }

    sql_query('COMMIT', false);
    mjtto_alert_back('지급 요청이 등록되었습니다.', $return_url);
}

$claim_id = (int)($_POST['claim_id'] ?? 0);
if ($claim_id < 1) {
    mjtto_alert_back('지급건을 찾을 수 없습니다.', $return_url);
}

$claim = mjtto_get_claim_row($claim_id, $auth);
if (!$claim) {
    mjtto_alert_back('처리 대상 지급건을 찾을 수 없습니다.', $return_url);
}

$allowed_actions = mjtto_claim_allowed_actions($auth, $claim);
if (!in_array($action, $allowed_actions, true)) {
    mjtto_alert_back('해당 처리 권한이 없습니다.', $return_url);
}

$next_status = '';
$item_status = 'CLAIM_REQUEST';
$member_id_sql = sql_real_escape_string($member['mb_id']);
$extra_update_sql = '';
$is_rank_5 = ((int)$claim['result_rank'] === 5);
$is_5th_sms_action = ($action === 'send_5th_sms');

switch ($action) {
    case 'approve':
        if ($is_rank_5) {
            mjtto_alert_back('5등은 일반 승인 대신 경품권문자전송으로 처리해 주세요.', $return_url);
        }
        $next_status = 'CLAIM_APPROVED';
        $item_status = 'CLAIM_APPROVED';
        $extra_update_sql = "approve_by = '{$member_id_sql}', approved_at = NOW(), reject_by = '', rejected_at = NULL, reject_reason = ''";
        break;

    case 'hold':
        $next_status = 'CLAIM_HOLD';
        $item_status = 'CLAIM_HOLD';
        $extra_update_sql = "admin_memo = '보류 처리', approve_by = '{$member_id_sql}', approved_at = NOW()";
        break;

    case 'reject':
        $next_status = 'CLAIM_REJECT';
        $item_status = 'CLAIM_REJECT';
        $extra_update_sql = "reject_by = '{$member_id_sql}', rejected_at = NOW(), reject_reason = '운영자 반려'";
        break;

    case 'done':
        if ($is_rank_5) {
            mjtto_alert_back('5등은 완료 버튼을 사용하지 않습니다. 경품권문자전송으로 처리해 주세요.', $return_url);
        }
        $next_status = 'CLAIM_DONE';
        $item_status = 'CLAIM_DONE';
        break;

    case 'send_5th_sms':
        if (!$is_rank_5) {
            mjtto_alert_back('5등 지급건만 경품권문자전송 처리를 할 수 있습니다.', $return_url);
        }
        $next_status = 'CLAIM_DONE';
        $item_status = 'CLAIM_DONE';
        break;

    default:
        mjtto_alert_back('지원하지 않는 처리입니다.', $return_url);
}

sql_query('START TRANSACTION', false);

if ($is_5th_sms_action) {
    $rank5_issue_item = mjtto_get_reward_issue_item_by_claim($claim);
    $rank5_issue_result = array();

    if (empty($rank5_issue_item['issue_item_id'])) {
        $default_branch = mjtto_get_default_prize_issue_branch();
        if (empty($default_branch['company_id'])) {
            sql_query('ROLLBACK', false);
            mjtto_alert_back('5등 경품권 기본발권지점이 지정되지 않았습니다. 지점관리에서 먼저 지정해 주세요.', $return_url);
        }

        $rank5_issue_result = mjtto_issue_one_prize_ticket(
            (int)$default_branch['company_id'],
            $member['mb_id'],
            '5등 당첨 경품권 자동발권 / claim_id='.(int)$claim['claim_id']
        );

        if (empty($rank5_issue_result['ok'])) {
            sql_query('ROLLBACK', false);
            mjtto_alert_back($rank5_issue_result['error'] ?? '5등 경품권 발권 중 오류가 발생했습니다.', $return_url);
        }

        $pre_memo_sql = sql_real_escape_string('5등 경품권 발권 '.$rank5_issue_result['ticket_no'].' / 문자대기');
        $ok_pre = sql_query("
            UPDATE mz_prize_claim
               SET admin_memo = '{$pre_memo_sql}'
             WHERE claim_id = '{$claim_id}'
        ", false);

        if (!$ok_pre || !mjtto_claim_store_reward_issue_meta($claim_id, $rank5_issue_result, 'PENDING', '')) {
            sql_query('ROLLBACK', false);
            mjtto_alert_back('5등 경품권 발권 정보 저장 중 오류가 발생했습니다.', $return_url);
        }

        sql_query('COMMIT', false);
        $claim = mjtto_get_claim_row($claim_id, $auth);
        $rank5_issue_item = mjtto_get_reward_issue_item_by_claim($claim);
    } else {
        sql_query('COMMIT', false);
        $rank5_issue_result = array(
            'issue_id' => (int)$rank5_issue_item['issue_id'],
            'issue_item_id' => (int)$rank5_issue_item['issue_item_id'],
            'ticket_no' => (string)$rank5_issue_item['ticket_no'],
            'round_no' => (int)$rank5_issue_item['round_no'],
            'branch_id' => (int)($claim['reward_issue_branch_id'] ?? 0),
            'company_id' => 0,
        );
    }

    if (empty($rank5_issue_item['issue_item_id']) || empty($rank5_issue_result['ticket_no'])) {
        mjtto_alert_back('5등 경품권 발권 정보를 찾을 수 없습니다.', $return_url);
    }

    $sms_message = "축5등 매주또행운권

" . mjtto_build_ticket_win_url($rank5_issue_result['ticket_no']);
    $sms_send = mjtto_sms_send_one_logged($claim, $sms_message, array(
        'reward_ticket_no' => $rank5_issue_result['ticket_no'],
        'reward_issue_item_id' => (int)$rank5_issue_item['issue_item_id']
    ));

    $sms_result = (string)($sms_send['result_text'] ?? (empty($sms_send['ok']) ? '문자 전송 실패' : 'OK'));
    $sms_sent_at_now = date('Y-m-d H:i:s');
    $sms_log_id = (int)($sms_send['log_id'] ?? 0);
    $sms_log_warning = '';

    if ($sms_log_id < 1 && mjtto_sms_log_table_exists()) {
        $point_info = is_array($sms_send['point_info'] ?? null) ? $sms_send['point_info'] : array();
        $sms_log_id = mjtto_sms_log_insert(array(
            'claim_id' => (int)$claim['claim_id'],
            'issue_item_id' => (int)$claim['issue_item_id'],
            'reward_issue_item_id' => (int)$rank5_issue_item['issue_item_id'],
            'result_rank' => (int)$claim['result_rank'],
            'ticket_no' => (string)($claim['ticket_no'] ?? ''),
            'reward_ticket_no' => (string)$rank5_issue_result['ticket_no'],
            'recv_hp' => (string)($sms_send['recv_hp'] ?? ($claim['request_hp'] ?? '')),
            'send_hp' => (string)($sms_send['send_hp'] ?? mjtto_sms_get_sender_number()),
            'sms_type' => (string)($sms_send['sms_type'] ?? 'SMS'),
            'send_status' => !empty($sms_send['ok']) ? 'SUCCESS' : 'FAIL',
            'result_text' => $sms_result,
            'message_text' => (string)$sms_message,
            'point_balance' => !empty($point_info['ok']) ? (string)($point_info['point'] ?? '') : '',
            'payment_type' => !empty($point_info['ok']) ? (string)($point_info['payment'] ?? '') : '',
            'created_by' => (string)($member['mb_id'] ?? '')
        ));
    }

    if ($sms_log_id < 1 && mjtto_sms_log_table_exists()) {
        $sms_log_warning = ' / 문자로그저장실패';
    }

    if (empty($sms_send['ok'])) {
        $fail_memo_sql = sql_real_escape_string('5등 경품권 발권 '.$rank5_issue_result['ticket_no'].' / 문자실패 '.$sms_result.$sms_log_warning);
        sql_query("
            UPDATE mz_prize_claim
               SET admin_memo = '{$fail_memo_sql}'
             WHERE claim_id = '{$claim_id}'
        ", false);
        mjtto_claim_store_reward_issue_meta($claim_id, $rank5_issue_result, 'FAIL: '.$sms_result, '');
        mjtto_alert_back('문자 전송 실패: '.$sms_result.' / 재시도 시 기존 발권번호를 재사용합니다.', $return_url);
    }

    sql_query('START TRANSACTION', false);

    $claim_request_name_sql = sql_real_escape_string((string)$claim['request_name']);
    $claim_request_hp_sql = sql_real_escape_string((string)$claim['request_hp']);
    $claim_admin_memo_sql = sql_real_escape_string('5등 경품권 발권 '.$rank5_issue_result['ticket_no'].' / '.$sms_result.$sms_log_warning);

    $ok_ticket = sql_query("
        UPDATE mz_issue_item
           SET sent_at = NOW(),
               customer_name = '{$claim_request_name_sql}',
               customer_hp = '{$claim_request_hp_sql}'
         WHERE issue_item_id = '".(int)$rank5_issue_item['issue_item_id']."'
    ", false);

    if (!$ok_ticket) {
        sql_query('ROLLBACK', false);
        mjtto_alert_back('경품권 발권 이력 저장 중 오류가 발생했습니다.', $return_url);
    }

    if (!mjtto_claim_store_reward_issue_meta($claim_id, $rank5_issue_result, $sms_result, $sms_sent_at_now)) {
        sql_query('ROLLBACK', false);
        mjtto_alert_back('문자 전송 결과 저장 중 오류가 발생했습니다.', $return_url);
    }

    $extra_update_sql = "approve_by = '{$member_id_sql}', approved_at = COALESCE(approved_at, NOW()), paid_by = '{$member_id_sql}', paid_at = NOW(), reject_by = '', rejected_at = NULL, reject_reason = '', admin_memo = '{$claim_admin_memo_sql}'";
} elseif ($action === 'done') {
    $extra_update_sql = "paid_by = '{$member_id_sql}', paid_at = NOW()";
}

$ok = sql_query("\n    UPDATE mz_prize_claim\n       SET claim_status = '{$next_status}',\n           {$extra_update_sql}\n     WHERE claim_id = '{$claim_id}'\n", false);

if (!$ok) {
    sql_query('ROLLBACK', false);
    mjtto_alert_back('지급 상태 저장 중 오류가 발생했습니다.', $return_url);
}

$ok2 = sql_query("\n    UPDATE mz_issue_item\n       SET item_status = '{$item_status}'\n     WHERE issue_item_id = '".(int)$claim['issue_item_id']."'\n", false);

if (!$ok2) {
    sql_query('ROLLBACK', false);
    mjtto_alert_back('티켓 상태 갱신 중 오류가 발생했습니다.', $return_url);
}

sql_query('COMMIT', false);

if ($is_5th_sms_action) {
    $success_tail = '';
    if (!empty($sms_log_warning)) {
        $success_tail = ' 다만 문자로그 저장은 확인이 필요합니다.';
    }
    mjtto_alert_back('5등 경품권 문자 전송 후 지급완료 처리되었습니다.' . $success_tail, $return_url);
}

mjtto_alert_back('지급 상태가 변경되었습니다.', $return_url);
