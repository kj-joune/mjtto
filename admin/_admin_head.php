<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-09 14:58:00 KST  */

if (!defined('_GNUBOARD_')) exit;

$admin_name = isset($member['mb_name']) ? $member['mb_name'] : '';
$role_name  = isset($auth['role']) ? mjtto_role_name($auth['role']) : '';
$company_nm = isset($auth['company_name']) ? $auth['company_name'] : '';
$list_url   = mjtto_list_url(isset($auth['role']) ? $auth['role'] : '', basename($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title><?php echo isset($g5['title']) ? $g5['title'] : '매주또 관리자'; ?></title>
<style>
body{margin:0;padding:0;font-family:Arial,Apple SD Gothic Neo,Malgun Gothic,sans-serif;background:#f5f6f8;color:#222;}
.admin-top{position:sticky;top:0;z-index:1000;background:#111827;color:#fff;padding:14px 24px;display:flex;justify-content:space-between;align-items:center;gap:10px;box-shadow:0 2px 8px rgba(0,0,0,0.15);}
.admin-top .left{font-size:18px;font-weight:bold;white-space:nowrap;}
.admin-top .right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;font-size:13px;}
.admin-top .meta{opacity:0.9;margin-right:6px;}
.admin-top .right a{display:inline-block;color:#fff;text-decoration:none;padding:8px 12px;border:1px solid rgba(255,255,255,0.2);border-radius:8px;background:rgba(255,255,255,0.04);}
.admin-wrap{max-width:1380px;margin:24px auto;padding:0 20px 30px;}
.msg-inline{display:block;margin-top:8px;font-size:12px;}
.msg-ok{color:#166534;}
.msg-error{color:#b91c1c;}
.msg-info{color:#6b7280;}
.readonly-box{display:inline-block;padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;min-width:180px;}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;line-height:1.2;}
.badge-gray{background:#f3f4f6;color:#374151;}
.badge-blue{background:#eff6ff;color:#1d4ed8;}
.badge-indigo{background:#eef2ff;color:#4338ca;}
.badge-green{background:#ecfdf5;color:#047857;}
.badge-orange{background:#fff7ed;color:#c2410c;}
.badge-red{background:#fef2f2;color:#b91c1c;}

html {
    scrollbar-gutter: stable;
    scrollbar-width: thin;
}

html::-webkit-scrollbar {
    width: 10px;
}

html::-webkit-scrollbar-thumb {
    border-radius: 8px;
    background: #c8c8c8;
}

html::-webkit-scrollbar-track {
    background: transparent;
}
</style>
</head>
<body>
<div class="admin-top">
    <div class="left">매주또 관리자</div>
    <div class="right">
        <span class="meta"><?php echo get_text($admin_name); ?> | <?php echo get_text($role_name); ?><?php if ($company_nm) { ?> | <?php echo get_text($company_nm); ?><?php } ?></span>
        <a href="./index.php">홈</a>
        <a href="./issue_list.php">발권</a>
        <?php if (mjtto_claim_table_exists()) { ?><a href="./claim_list.php">경품지급</a><?php } ?>
        <a href="./settlement_month.php">월정산</a>
        <!-- <a href="<?php echo $list_url; ?>">목록</a> -->
        <a href="./logout.php">로그아웃</a>
    </div>
</div>
<div class="admin-wrap">
