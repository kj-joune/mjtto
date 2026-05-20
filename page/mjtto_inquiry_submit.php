<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-20 23:36:12 KST  */

$mjtto_root_path = dirname(__DIR__);
chdir($mjtto_root_path);
include_once($mjtto_root_path.'/_common.php');

if (!function_exists('mjtto_inquiry_response')) {
    function mjtto_inquiry_response($message, $url = '')
    {
        $message = (string)$message;
        $url = $url ? (string)$url : G5_URL.'/#mjtto-contact';

        if ($message === '제휴 및 공급문의가 접수되었습니다.') {
            if (!headers_sent()) {
                header('Location: '.G5_URL.'/?mjtto_inquiry=success#mjtto-contact', true, 302);
                exit;
            }

            echo '<script>location.href="'.G5_URL.'/?mjtto_inquiry=success#mjtto-contact";</script>';
            exit;
        }

        $safe_message = function_exists('get_text') ? get_text($message) : htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html lang="ko"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>문의 접수 안내</title>';
        echo '<style>body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f7f8fa;color:#111}.box{max-width:420px;margin:18vh auto;padding:32px 24px;border-radius:18px;background:#fff;box-shadow:0 18px 45px rgba(16,40,74,.1);text-align:center}.box p{margin:0 0 22px;font-size:17px;line-height:1.6}.box a{display:inline-flex;align-items:center;justify-content:center;height:46px;padding:0 22px;border-radius:999px;background:#f5a623;color:#fff;text-decoration:none;font-weight:800}</style>';
        echo '</head><body><div class="box">';
        echo '<p>'.$safe_message.'</p>';
        echo '<a href="'.$safe_url.'">돌아가기</a>';
        echo '</div></body></html>';
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mjtto_inquiry_response('잘못된 접근입니다.', G5_URL.'/#mjtto-contact');
}

if (trim((string)($_POST['mjtto_homepage'] ?? '')) !== '') {
    mjtto_inquiry_response('정상적인 접수 요청이 아닙니다.', G5_URL.'/#mjtto-contact');
}

$session_token = get_session('ss_mjtto_inquiry_form_token');
$post_token = trim((string)($_POST['mjtto_inquiry_token'] ?? ''));

if (!$session_token || !$post_token || !hash_equals($session_token, $post_token)) {
    mjtto_inquiry_response('접수 시간이 만료되었습니다. 다시 시도해주세요.', G5_URL.'/#mjtto-contact');
}

function mjtto_cut_string($value, $length)
{
    $value = trim((string)$value);

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $length, 'UTF-8');
    }

    return substr($value, 0, $length);
}

function mjtto_post_text($key, $length = 255)
{
    $value = isset($_POST[$key]) ? (string)$_POST[$key] : '';
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value);
    return mjtto_cut_string($value, $length);
}

function mjtto_post_area($key, $length = 3000)
{
    $value = isset($_POST[$key]) ? (string)$_POST[$key] : '';
    $value = strip_tags($value);
    $value = preg_replace("/\r\n|\r/", "\n", $value);
    $value = preg_replace("/\n{4,}/", "\n\n\n", $value);
    return mjtto_cut_string($value, $length);
}

function mjtto_sql_escape_value($value)
{
    if (function_exists('sql_escape_string')) {
        return sql_escape_string($value);
    }

    if (function_exists('sql_real_escape_string')) {
        return sql_real_escape_string($value);
    }

    return addslashes($value);
}

$bo_table = 'inquiry';
$write_table = $g5['write_prefix'].$bo_table;

$board = sql_fetch(" SELECT `bo_table`, `bo_subject` FROM `{$g5['board_table']}` WHERE `bo_table` = '{$bo_table}' ");

if (!$board || !$board['bo_table']) {
    mjtto_inquiry_response('문의 게시판 설정을 확인해주세요.', G5_URL.'/#mjtto-contact');
}

$column_result = sql_query(" SHOW COLUMNS FROM `{$write_table}` ", false);

if (!$column_result) {
    mjtto_inquiry_response('문의 게시판 테이블을 확인해주세요.', G5_URL.'/#mjtto-contact');
}

$columns = array();

while ($column_row = sql_fetch_array($column_result)) {
    $columns[$column_row['Field']] = true;
}

$company = mjtto_post_text('mjtto_company', 100);
$manager = mjtto_post_text('mjtto_manager', 50);
$phone1 = preg_replace('/[^0-9]/', '', mjtto_post_text('mjtto_phone1', 4));
$phone2 = preg_replace('/[^0-9]/', '', mjtto_post_text('mjtto_phone2', 4));
$phone3 = preg_replace('/[^0-9]/', '', mjtto_post_text('mjtto_phone3', 4));
$email = mjtto_post_text('mjtto_email', 120);
$industry = mjtto_post_text('mjtto_industry', 100);
$message = mjtto_post_area('mjtto_content', 3000);

$phone = $phone1.'-'.$phone2.'-'.$phone3;

if ($company === '' || $manager === '' || $phone1 === '' || $phone2 === '' || $phone3 === '' || $email === '' || $industry === '') {
    mjtto_inquiry_response('필수 항목을 모두 입력해주세요.', G5_URL.'/#mjtto-contact');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    mjtto_inquiry_response('이메일 형식을 확인해주세요.', G5_URL.'/#mjtto-contact');
}

if (function_exists('get_next_num')) {
    $wr_num = get_next_num($write_table);
} else {
    $num_row = sql_fetch(" SELECT MIN(`wr_num`) AS `min_wr_num` FROM `{$write_table}` ");
    $wr_num = (int)$num_row['min_wr_num'] - 1;
}

$mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
$wr_name = $manager;
$wr_password_plain = bin2hex(random_bytes(8));
$wr_password = function_exists('get_encrypt_string') ? get_encrypt_string($wr_password_plain) : md5($wr_password_plain);

$wr_subject = '[제휴문의] '.$company.' / '.$manager;

$wr_content = '';
$wr_content .= "회사명: ".$company."\n";
$wr_content .= "담당자명: ".$manager."\n";
$wr_content .= "연락처: ".$phone."\n";
$wr_content .= "이메일: ".$email."\n";
$wr_content .= "업종: ".$industry."\n";
$wr_content .= "\n";
$wr_content .= "문의 내용:\n";
$wr_content .= ($message !== '' ? $message : '문의 내용 미입력');

$data = array(
    'wr_num' => $wr_num,
    'wr_reply' => '',
    'wr_comment' => 0,
    'ca_name' => '',
    'wr_option' => 'secret',
    'wr_subject' => $wr_subject,
    'wr_content' => $wr_content,
    'wr_seo_title' => '',
    'wr_link1' => '',
    'wr_link2' => '',
    'wr_link1_hit' => 0,
    'wr_link2_hit' => 0,
    'wr_hit' => 0,
    'wr_good' => 0,
    'wr_nogood' => 0,
    'mb_id' => $mb_id,
    'wr_password' => $wr_password,
    'wr_name' => $wr_name,
    'wr_email' => $email,
    'wr_homepage' => '',
    'wr_datetime' => G5_TIME_YMDHIS,
    'wr_file' => 0,
    'wr_last' => G5_TIME_YMDHIS,
    'wr_ip' => $_SERVER['REMOTE_ADDR'],
    'wr_1' => $company,
    'wr_2' => $manager,
    'wr_3' => $phone,
    'wr_4' => $industry,
    'wr_5' => $email
);

$set_list = array();

foreach ($data as $field => $value) {
    if (isset($columns[$field])) {
        $set_list[] = "`{$field}` = '".mjtto_sql_escape_value($value)."'";
    }
}

if (empty($set_list)) {
    mjtto_inquiry_response('문의 저장 항목을 확인해주세요.', G5_URL.'/#mjtto-contact');
}

sql_query(" INSERT INTO `{$write_table}` SET ".implode(",\n", $set_list));

$wr_id = sql_insert_id();

if (isset($columns['wr_parent'])) {
    sql_query(" UPDATE `{$write_table}` SET `wr_parent` = '{$wr_id}' WHERE `wr_id` = '{$wr_id}' ");
}

sql_query("
    INSERT INTO `{$g5['board_new_table']}`
    SET `bo_table` = '{$bo_table}',
        `wr_id` = '{$wr_id}',
        `wr_parent` = '{$wr_id}',
        `bn_datetime` = '".G5_TIME_YMDHIS."',
        `mb_id` = '".mjtto_sql_escape_value($mb_id)."'
");

sql_query("
    UPDATE `{$g5['board_table']}`
    SET `bo_count_write` = `bo_count_write` + 1
    WHERE `bo_table` = '{$bo_table}'
");

if (function_exists('delete_cache_latest')) {
    delete_cache_latest($bo_table);
}

set_session('ss_mjtto_inquiry_form_token', '');

mjtto_inquiry_response('제휴 및 공급문의가 접수되었습니다.', G5_URL.'/#mjtto-contact');
