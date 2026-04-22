<?php
/*  chat-GPT ERP sign: sysempire@gmail.com  */

include_once __DIR__ . '/_admin_common.php';

header('Content-Type: application/json; charset=utf-8');

$auth = mjtto_require_admin();

$mb_id = trim((string)($_GET['mb_id'] ?? ''));
$current_mb_id = trim((string)($_GET['current_mb_id'] ?? ''));

if ($mb_id === '') {
    echo json_encode(array('ok' => false, 'message' => '아이디를 입력하세요.'));
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $mb_id)) {
    echo json_encode(array('ok' => false, 'message' => '영문, 숫자, _ 만 사용할 수 있습니다.'));
    exit;
}

if ($current_mb_id !== '' && $mb_id === $current_mb_id) {
    echo json_encode(array('ok' => true, 'message' => '현재 사용 중인 아이디입니다.'));
    exit;
}

$row = sql_fetch(" SELECT mb_id FROM g5_member WHERE mb_id = '".sql_real_escape_string($mb_id)."' ");
if (!empty($row['mb_id'])) {
    echo json_encode(array('ok' => false, 'message' => '이미 사용 중인 아이디입니다.'));
    exit;
}

echo json_encode(array('ok' => true, 'message' => '사용 가능한 아이디입니다.'));
