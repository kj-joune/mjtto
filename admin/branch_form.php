<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-13 14:55:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
if (!in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true)) {
    alert('제휴사 관리자만 접근할 수 있습니다.', './index.php');
}

function mjtto_branch_query($sql) {
    $res = sql_query($sql, false);
    if ($res === false) {
        $err = function_exists('sql_error') ? trim((string)sql_error()) : '';
        error_log('[MJTTO_BRANCH_FORM] SQL ERROR: ' . $err . ' | SQL=' . preg_replace('/\s+/', ' ', trim((string)$sql)));
        sql_query("ROLLBACK", false);
        alert('DB 처리 중 오류가 발생했습니다.');
    }
    return $res;
}

function mjtto_utf8_len($text) {
    $text = (string)$text;
    if ($text === '') {
        return 0;
    }
    if (function_exists('mb_strlen')) {
        return (int)mb_strlen($text, 'UTF-8');
    }
    if (preg_match_all('/./u', $text, $matches)) {
        return count($matches[0]);
    }
    return strlen($text);
}

$company_id = isset($_REQUEST['company_id']) ? (int)$_REQUEST['company_id'] : 0;
$is_update  = ($company_id > 0);
$is_super_admin = ($auth['role'] === 'SUPER_ADMIN');
$has_prize_default_column = mjtto_company_column_exists('is_prize_issue_default');

$contract_company_id   = 0;
$contract_company_name = '';
$contract_company_code = '';

if ($is_update) {
    $select_prize_default_sql = $has_prize_default_column ? ",\n            c.is_prize_issue_default" : "";
    $row = sql_fetch("\n        SELECT\n            c.company_id,\n            c.parent_company_id,\n            c.company_name,\n            c.company_code,\n            c.status,\n            c.coupon_prefix,\n            c.issue_game_count,\n            c.print_name_1,\n            c.print_name_2,\n            c.tel_no{$select_prize_default_sql},\n            cu.mb_id AS admin_mb_id,\n            m.mb_name AS admin_name,\n            m.mb_hp AS admin_hp,\n            m.mb_email AS admin_email,\n            CASE WHEN m.mb_intercept_date <> '' OR m.mb_leave_date <> '' THEN '0' ELSE '1' END AS admin_status\n        FROM mz_company c\n        LEFT JOIN mz_company_user cu\n          ON c.company_id = cu.company_id\n         AND cu.role_code = 'BRANCH_ADMIN'\n         AND cu.status = 1\n        LEFT JOIN g5_member m\n          ON cu.mb_id = m.mb_id\n        WHERE c.company_id = '{$company_id}'\n          AND c.company_type = 'BRANCH'\n        LIMIT 1\n    ");

    if (!$row || empty($row['company_id'])) {
        alert('존재하지 않는 지점입니다.', './branch_list.php');
    }

    $contract_company_id = (int)$row['parent_company_id'];

    if (!$is_super_admin && $contract_company_id !== (int)$auth['company_id']) {
        alert('다른 제휴사 소속 지점은 수정할 수 없습니다.', './branch_list.php');
    }

    $contract = sql_fetch("\n        SELECT company_id, company_name, company_code\n          FROM mz_company\n         WHERE company_id = '{$contract_company_id}'\n           AND company_type = 'CONTRACT'\n         LIMIT 1\n    ");

    if (!$contract || empty($contract['company_id'])) {
        alert('상위 제휴사 정보가 올바르지 않습니다.', './company_list.php');
    }

    $contract_company_name = $contract['company_name'];
    $contract_company_code = $contract['company_code'];

    $write = $row;
} else {
    if ($is_super_admin) {
        $contract_company_id = isset($_REQUEST['parent_company_id']) ? (int)$_REQUEST['parent_company_id'] : 0;
        if ($contract_company_id < 1) {
            alert('상위 제휴사를 먼저 선택하세요.', './company_list.php');
        }

        $contract = sql_fetch("\n            SELECT company_id, company_name, company_code\n              FROM mz_company\n             WHERE company_id = '{$contract_company_id}'\n               AND company_type = 'CONTRACT'\n             LIMIT 1\n        ");

        if (!$contract || empty($contract['company_id'])) {
            alert('존재하지 않는 제휴사입니다.', './company_list.php');
        }

        $contract_company_name = $contract['company_name'];
        $contract_company_code = $contract['company_code'];
    } else {
        $contract_company_id   = (int)$auth['company_id'];
        $contract_company_name = $auth['company_name'];
        $contract_company_code = $auth['company_code'];
    }

    $write = array(
        'company_id'        => 0,
        'company_name'      => '',
        'company_code'      => '',
        'status'            => '1',
        'coupon_prefix'     => '',
        'issue_game_count'  => '5',
        'print_name_1'      => '',
        'print_name_2'      => '',
        'tel_no'            => '',
        'is_prize_issue_default' => '0',
        'admin_mb_id'       => '',
        'admin_name'        => '',
        'admin_hp'          => '',
        'admin_email'       => '',
        'admin_status'      => '1'
    );
}

$list_href = './branch_list.php';
if ($is_super_admin) {
    $list_href .= '?contract_company_id=' . $contract_company_id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name      = trim((string)($_POST['company_name'] ?? ''));
    $status            = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $issue_game_count  = isset($_POST['issue_game_count']) ? (int)$_POST['issue_game_count'] : 5;
    $print_name_1      = trim((string)($_POST['print_name_1'] ?? ''));
    $print_name_2      = trim((string)($_POST['print_name_2'] ?? ''));
    $tel_no            = trim((string)($_POST['tel_no'] ?? ''));
    $admin_mb_id       = trim((string)($_POST['admin_mb_id'] ?? ''));
    $admin_pass        = (string)($_POST['admin_pass'] ?? '');
    $admin_pass2       = (string)($_POST['admin_pass2'] ?? '');
    $admin_name        = trim((string)($_POST['admin_name'] ?? ''));
    $admin_hp          = trim((string)($_POST['admin_hp'] ?? ''));
    $admin_email       = trim((string)($_POST['admin_email'] ?? ''));
    $admin_status      = isset($_POST['admin_status']) ? (int)$_POST['admin_status'] : 1;
    $is_prize_issue_default = 0;
    if ($has_prize_default_column) {
        if ($is_super_admin) {
            $is_prize_issue_default = !empty($_POST['is_prize_issue_default']) ? 1 : 0;
        } else {
            $is_prize_issue_default = (int)($write['is_prize_issue_default'] ?? 0);
        }
    }

    if ($company_name === '') alert('지점명을 입력하세요.');
    if (mjtto_utf8_len($print_name_1) > 9) alert('복권 표기명 1행은 한글 기준 9자까지만 입력할 수 있습니다.');
    if (mjtto_utf8_len($print_name_2) > 9) alert('복권 표기명 2행은 한글 기준 9자까지만 입력할 수 있습니다.');
    if ($admin_mb_id === '') alert('관리자 아이디를 입력하세요.');
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $admin_mb_id)) alert('관리자 아이디는 영문, 숫자, _ 만 사용할 수 있습니다.');
    if ($admin_name === '') alert('관리자 이름을 입력하세요.');
    if (!in_array($issue_game_count, array(1,2,3,4,5), true)) alert('발권당 게임수는 1~5만 선택할 수 있습니다.');
    if ($admin_hp === '') alert('관리자 휴대폰을 입력하세요.');

    if ($is_update) {
        if ($admin_mb_id !== $write['admin_mb_id']) alert('관리자 아이디는 수정할 수 없습니다.');
        if ($admin_pass !== '' || $admin_pass2 !== '') {
            if ($admin_pass !== $admin_pass2) alert('비밀번호 확인이 일치하지 않습니다.');
        }
    } else {
        if ($admin_pass === '') alert('비밀번호를 입력하세요.');
        if ($admin_pass !== $admin_pass2) alert('비밀번호 확인이 일치하지 않습니다.');
    }

    $company_name_sql   = sql_real_escape_string($company_name);
    $print_name_1_sql   = sql_real_escape_string($print_name_1);
    $print_name_2_sql   = sql_real_escape_string($print_name_2);
    $tel_no_sql         = sql_real_escape_string($tel_no);
    $admin_mb_id_sql    = sql_real_escape_string($admin_mb_id);
    $admin_name_sql     = sql_real_escape_string($admin_name);
    $admin_hp_sql       = sql_real_escape_string($admin_hp);
    $admin_email_sql    = sql_real_escape_string($admin_email);
    $status_sql         = $status ? 1 : 0;
    $admin_status_sql   = $admin_status ? 1 : 0;
    $today              = G5_TIME_YMD;
    $now                = G5_TIME_YMDHIS;
    $ip                 = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!$is_update) {
        $dup_member = sql_fetch(" SELECT mb_id FROM g5_member WHERE mb_id = '{$admin_mb_id_sql}' ");
        if (!empty($dup_member['mb_id'])) alert('이미 사용 중인 관리자 아이디입니다.');

        $new_branch_code = mjtto_next_branch_code($contract_company_id, $contract_company_code);
        if ($new_branch_code === '') alert('지점코드 자동생성 범위를 초과했습니다.');
        $new_branch_code_sql = sql_real_escape_string($new_branch_code);

        sql_query("START TRANSACTION", false);

        if ($has_prize_default_column && $is_prize_issue_default) {
            mjtto_branch_query(" UPDATE mz_company SET is_prize_issue_default = '0' WHERE company_type = 'BRANCH' ");
        }

        $insert_company_parts = array(
            "parent_company_id = '{$contract_company_id}'",
            "company_name = '{$company_name_sql}'",
            "company_code = '{$new_branch_code_sql}'",
            "company_type = 'BRANCH'",
            "coupon_prefix = '{$new_branch_code_sql}'",
            "issue_game_count = '{$issue_game_count}'",
            "print_name_1 = '{$print_name_1_sql}'",
            "print_name_2 = '{$print_name_2_sql}'",
            "tel_no = '{$tel_no_sql}'"
        );

        if ($has_prize_default_column) {
            $insert_company_parts[] = "is_prize_issue_default = '{$is_prize_issue_default}'";
        }

        $insert_company_parts[] = "status = '{$status_sql}'";

        mjtto_branch_query("
            INSERT INTO mz_company
            SET
                " . implode(",
                ", $insert_company_parts) . "
        " );

        $new_company_id = sql_insert_id();
        if (!$new_company_id) {
            sql_query("ROLLBACK", false);
            alert('지점 생성에 실패했습니다.');
        }

        mjtto_branch_query("\n            INSERT INTO g5_member\n            SET\n                mb_id               = '{$admin_mb_id_sql}',\n                mb_password         = '".get_encrypt_string($admin_pass)."',\n                mb_name             = '{$admin_name_sql}',\n                mb_nick             = '{$admin_name_sql}',\n                mb_nick_date        = '{$today}',\n                mb_email            = '{$admin_email_sql}',\n                mb_homepage         = '',\n                mb_level            = '2',\n                mb_sex              = '',\n                mb_birth            = '',\n                mb_tel              = '',\n                mb_hp               = '{$admin_hp_sql}',\n                mb_certify          = '',\n                mb_adult            = '0',\n                mb_dupinfo          = '',\n                mb_zip1             = '',\n                mb_zip2             = '',\n                mb_addr1            = '',\n                mb_addr2            = '',\n                mb_addr3            = '',\n                mb_addr_jibeon      = '',\n                mb_signature        = '',\n                mb_recommend        = '',\n                mb_point            = '0',\n                mb_today_login      = '{$now}',\n                mb_login_ip         = '{$ip}',\n                mb_datetime         = '{$now}',\n                mb_ip               = '{$ip}',\n                mb_leave_date       = '',\n                mb_intercept_date   = '',\n                mb_email_certify    = '{$now}',\n                mb_email_certify2   = '',\n                mb_memo             = '',\n                mb_lost_certify     = '',\n                mb_mailling         = '0',\n                mb_mailling_date    = '0000-00-00 00:00:00',\n                mb_sms              = '0',\n                mb_sms_date         = '0000-00-00 00:00:00',\n                mb_open             = '0',\n                mb_open_date        = '0000-00-00',\n                mb_profile          = '',\n                mb_memo_call        = '',\n                mb_memo_cnt         = '0',\n                mb_scrap_cnt        = '0',\n                mb_marketing_agree  = '0',\n                mb_marketing_date   = '0000-00-00 00:00:00',\n                mb_thirdparty_agree = '0',\n                mb_thirdparty_date  = '0000-00-00 00:00:00',\n                mb_agree_log        = '',\n                mb_1                = '',\n                mb_2                = '',\n                mb_3                = '',\n                mb_4                = '',\n                mb_5                = '',\n                mb_6                = '',\n                mb_7                = '',\n                mb_8                = '',\n                mb_9                = '',\n                mb_10               = ''\n        ");

        if (!$admin_status_sql) {
            mjtto_branch_query(" UPDATE g5_member SET mb_intercept_date = '".date('Ymd')."' WHERE mb_id = '{$admin_mb_id_sql}' ");
        }

        mjtto_branch_query("\n            INSERT INTO mz_company_user\n            SET\n                company_id = '{$new_company_id}',\n                mb_id      = '{$admin_mb_id_sql}',\n                role_code  = 'BRANCH_ADMIN',\n                status     = '1'\n        ");

        sql_query("COMMIT", false);
        alert('지점과 관리자 계정이 등록되었습니다.', $list_href);
    } else {
        sql_query("START TRANSACTION", false);

        if ($has_prize_default_column && $is_prize_issue_default) {
            mjtto_branch_query(" UPDATE mz_company SET is_prize_issue_default = '0' WHERE company_type = 'BRANCH' ");
        }

        $update_prize_default_sql = $has_prize_default_column ? ",
                   is_prize_issue_default = '{$is_prize_issue_default}'" : "";

        mjtto_branch_query("
            UPDATE mz_company
               SET company_name      = '{$company_name_sql}',
                   issue_game_count  = '{$issue_game_count}',
                   print_name_1      = '{$print_name_1_sql}',
                   print_name_2      = '{$print_name_2_sql}',
                   tel_no            = '{$tel_no_sql}',
                   status            = '{$status_sql}'{$update_prize_default_sql}
             WHERE company_id = '{$company_id}'
               AND parent_company_id = '{$contract_company_id}'
               AND company_type = 'BRANCH'
        " );

        mjtto_branch_query("\n            UPDATE g5_member\n               SET mb_name  = '{$admin_name_sql}',\n                   mb_nick  = '{$admin_name_sql}',\n                   mb_hp    = '{$admin_hp_sql}',\n                   mb_email = '{$admin_email_sql}'\n             WHERE mb_id = '{$admin_mb_id_sql}'\n        ");

        if ($admin_pass !== '') {
            mjtto_branch_query(" UPDATE g5_member SET mb_password = '".get_encrypt_string($admin_pass)."' WHERE mb_id = '{$admin_mb_id_sql}' ");
        }

        if ($admin_status_sql) {
            mjtto_branch_query(" UPDATE g5_member SET mb_intercept_date = '', mb_leave_date = '' WHERE mb_id = '{$admin_mb_id_sql}' ");
        } else {
            mjtto_branch_query(" UPDATE g5_member SET mb_intercept_date = '".date('Ymd')."' WHERE mb_id = '{$admin_mb_id_sql}' ");
        }

        $map_exists = sql_fetch("\n            SELECT map_id\n              FROM mz_company_user\n             WHERE company_id = '{$company_id}'\n               AND mb_id = '{$admin_mb_id_sql}'\n               AND role_code = 'BRANCH_ADMIN'\n             LIMIT 1\n        ");

        if (!empty($map_exists['map_id'])) {
            mjtto_branch_query(" UPDATE mz_company_user SET status = '1' WHERE map_id = '".(int)$map_exists['map_id']."' ");
        } else {
            mjtto_branch_query("\n                INSERT INTO mz_company_user\n                SET company_id = '{$company_id}',\n                    mb_id      = '{$admin_mb_id_sql}',\n                    role_code  = 'BRANCH_ADMIN',\n                    status     = '1'\n            ");
        }

        sql_query("COMMIT", false);
        alert('지점 정보가 수정되었습니다.', $list_href);
    }
}

$g5['title'] = $is_update ? '지점 수정' : '지점 등록';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,0.03);} h1{margin:0 0 24px;font-size:28px;} h2{margin:28px 0 12px;font-size:18px;}
.table-form{width:100%;border-collapse:collapse;} .table-form th,.table-form td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;vertical-align:middle;} .table-form th{width:180px;background:#fafafa;font-size:14px;}
.input{width:100%;height:42px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;} .radio-wrap label{margin-right:18px;} .desc{margin-top:8px;font-size:12px;color:#777;} .btns{margin-top:24px;}
.btns a,.btns button{display:inline-block;min-width:110px;height:42px;line-height:42px;padding:0 18px;border:0;border-radius:8px;text-decoration:none;font-size:14px;cursor:pointer;} .btn-submit{background:#111827;color:#fff;} .btn-list{background:#e5e7eb;color:#111827;}
</style>
<div class="box">
    <h1><?php echo $g5['title']; ?></h1>
    <form method="post" action="" autocomplete="off">
        <input type="hidden" name="company_id" value="<?php echo (int)$write['company_id']; ?>">
        <?php if ($is_super_admin) { ?><input type="hidden" name="parent_company_id" value="<?php echo $contract_company_id; ?>"><?php } ?>
        <h2>지점 정보</h2>
        <table class="table-form">
            <tr><th>상위 제휴사</th><td><span class="readonly-box"><?php echo get_text($contract_company_name); ?> (<?php echo get_text($contract_company_code); ?>)</span></td></tr>
            <tr><th>지점명</th><td><textarea name="company_name" class="input" style="height:72px;padding:10px 12px;" maxlength="100" required><?php echo get_text($write['company_name']); ?></textarea><div class="desc">관리용 지점명입니다. 줄바꿈 입력 가능합니다.</div></td></tr>
            <tr><th>발권당 게임수</th><td><select name="issue_game_count" class="input" style="max-width:140px;" required><?php for ($g = 5; $g >= 1; $g--) { ?><option value="<?php echo $g; ?>" <?php echo ((int)$write['issue_game_count'] === $g) ? 'selected' : ''; ?>><?php echo $g; ?>게임</option><?php } ?></select><div class="desc">실제 발권 시 1장당 생성할 게임 수입니다.</div></td></tr>
            <tr><th>복권 표기명 1행</th><td><input type="text" name="print_name_1" value="<?php echo get_text($write['print_name_1']); ?>" class="input" maxlength="8"><div class="desc">복권 상단 첫 줄에 출력됩니다. 한글 기준 9자까지만 입력할 수 있습니다.</div></td></tr>
            <tr><th>복권 표기명 2행</th><td><input type="text" name="print_name_2" value="<?php echo get_text($write['print_name_2']); ?>" class="input" maxlength="8"><div class="desc">한글 기준 9자까지만 입력할 수 있습니다.</div></td></tr>
            <tr><th>복권 연락처</th><td><input type="text" name="tel_no" value="<?php echo get_text($write['tel_no']); ?>" class="input" maxlength="30"></td></tr>
            <tr><th>지점코드</th><td><span class="readonly-box"><?php echo $is_update ? get_text($write['company_code']) : '저장 시 자동 생성'; ?></span></td></tr>
            <tr><th>쿠폰구분자</th><td><span class="readonly-box"><?php echo $is_update ? get_text($write['coupon_prefix']) : '지점코드와 동일하게 자동 생성'; ?></span></td></tr>
            <?php if ($has_prize_default_column) { ?>
            <tr>
                <th>5등 기본발권지점</th>
                <td>
                    <?php if ($is_super_admin) { ?>
                        <label><input type="checkbox" name="is_prize_issue_default" value="1" <?php echo ((int)$write['is_prize_issue_default'] === 1) ? 'checked' : ''; ?>> 이 지점을 5등 경품권 기본발권지점으로 사용</label>
                        <div class="desc">최고관리자만 설정할 수 있으며, 지정 시 다른 지점의 기본설정은 자동 해제됩니다.</div>
                    <?php } else { ?>
                        <span class="readonly-box"><?php echo ((int)$write['is_prize_issue_default'] === 1) ? '기본발권지점' : '미지정'; ?></span>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
            <tr>
                <th>지점 상태</th>
                <td class="radio-wrap">
                    <label><input type="radio" name="status" value="1" <?php echo ((string)$write['status'] === '1') ? 'checked' : ''; ?>> 사용</label>
                    <label><input type="radio" name="status" value="0" <?php echo ((string)$write['status'] === '0') ? 'checked' : ''; ?>> 중지</label>
                </td>
            </tr>
        </table>

        <h2>지점 관리자 계정</h2>
        <table class="table-form">
            <tr>
                <th>관리자 아이디</th>
                <td>
                    <input type="text" name="admin_mb_id" id="admin_mb_id" value="<?php echo get_text($write['admin_mb_id']); ?>" class="input" maxlength="20" required <?php echo $is_update ? 'readonly' : ''; ?>>
                    <span id="mb_id_check_msg" class="msg-inline msg-info"><?php echo $is_update ? '수정 시 아이디 변경 불가' : '입력 후 자동으로 중복확인됩니다.'; ?></span>
                </td>
            </tr>
            <tr><th>비밀번호</th><td><input type="password" name="admin_pass" value="" class="input" maxlength="100" <?php echo $is_update ? '' : 'required'; ?>><div class="desc"><?php echo $is_update ? '비워두면 기존 비밀번호 유지' : '지점 관리자 로그인 비밀번호'; ?></div></td></tr>
            <tr><th>비밀번호 확인</th><td><input type="password" name="admin_pass2" value="" class="input" maxlength="100" <?php echo $is_update ? '' : 'required'; ?>></td></tr>
            <tr><th>관리자 이름</th><td><input type="text" name="admin_name" value="<?php echo get_text($write['admin_name']); ?>" class="input" maxlength="100" required></td></tr>
            <tr><th>휴대폰</th><td><input type="text" name="admin_hp" value="<?php echo get_text($write['admin_hp']); ?>" class="input" maxlength="20" required></td></tr>
            <tr><th>이메일</th><td><input type="text" name="admin_email" value="<?php echo get_text($write['admin_email']); ?>" class="input" maxlength="100"></td></tr>
            <tr>
                <th>계정 상태</th>
                <td class="radio-wrap">
                    <label><input type="radio" name="admin_status" value="1" <?php echo ((string)$write['admin_status'] === '1') ? 'checked' : ''; ?>> 사용</label>
                    <label><input type="radio" name="admin_status" value="0" <?php echo ((string)$write['admin_status'] === '0') ? 'checked' : ''; ?>> 중지</label>
                </td>
            </tr>
        </table>
        <div class="btns">
            <button type="submit" class="btn-submit"><?php echo $is_update ? '수정저장' : '등록하기'; ?></button>
            <a href="<?php echo $list_href; ?>" class="btn-list">목록</a>
        </div>
    </form>
</div>
<script>
(function(){
    ['print_name_1', 'print_name_2'].forEach(function(name){
        var field = document.querySelector('[name="' + name + '"]');
        if (!field) return;
        field.addEventListener('input', function(){
            if (field.value.length > 8) {
                field.value = field.value.slice(0, 8);
            }
        });
    });

    var input = document.getElementById('admin_mb_id');
    var msg = document.getElementById('mb_id_check_msg');
    if (!input || input.hasAttribute('readonly')) return;
    var timer = null;
    function setMsg(text, cls){ msg.className = 'msg-inline ' + cls; msg.textContent = text; }
    function checkId(){
        var v = input.value.trim();
        if (!v) { setMsg('아이디를 입력하세요.', 'msg-info'); return; }
        fetch('./ajax_check_mb_id.php?mb_id=' + encodeURIComponent(v), {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(res){ setMsg(res.message, res.ok ? 'msg-ok' : 'msg-error'); })
            .catch(function(){ setMsg('중복확인 중 오류가 발생했습니다.', 'msg-error'); });
    }
    input.addEventListener('blur', function(){ clearTimeout(timer); timer = setTimeout(checkId, 100); });
})();
</script>
</div>
</body>
</html>
