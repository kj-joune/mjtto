<?php
/*  chat-GPT ERP sign: sysempire@gmail.com  */

include_once __DIR__ . '/../common.php';

if (!defined('_GNUBOARD_')) exit;

if (!empty($member['mb_id'])) {
    goto_url('/admin/index.php');
}

$g5['title'] = '매주또 관리자 로그인';
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title><?php echo $g5['title']; ?></title>
<style>
body{margin:0;padding:0;font-family:Arial,Apple SD Gothic Neo,Malgun Gothic,sans-serif;background:#f5f6f8;color:#222;}
.wrap{max-width:460px;margin:100px auto;padding:0 20px;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
h1{margin:0 0 10px;font-size:28px;text-align:center;}
.desc{margin:0 0 24px;color:#666;text-align:center;}
.input{width:100%;height:46px;padding:0 14px;border:1px solid #d1d5db;border-radius:10px;box-sizing:border-box;margin-bottom:12px;}
.btn{width:100%;height:46px;border:0;border-radius:10px;background:#111827;color:#fff;font-size:15px;cursor:pointer;}
.home-link{margin-top:14px;text-align:center;}
.home-link a{text-decoration:none;color:#2563eb;}
</style>
</head>
<body>
<div class="wrap">
    <div class="box">
        <h1>관리자 로그인</h1>
        <p class="desc">권한에 따라 관리자 화면으로 이동합니다.</p>

        <form name="flogin" action="<?php echo G5_HTTPS_BBS_URL; ?>/login_check.php" method="post" autocomplete="off">
            <input type="hidden" name="url" value="<?php echo G5_URL; ?>/admin/index.php">
            <input type="text" name="mb_id" class="input" placeholder="아이디" required>
            <input type="password" name="mb_password" class="input" placeholder="비밀번호" required>
            <button type="submit" class="btn">로그인</button>
        </form>

        <div class="home-link">
            <a href="<?php echo G5_URL."/admin"; ?>">홈으로</a>
        </div>
    </div>
</div>
</body>
</html>