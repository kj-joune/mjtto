<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-19 13:15:00 KST  */
if (!defined('_GNUBOARD_')) exit;

if (!isset($g5['title']) || !$g5['title']) {
    $g5['title'] = '매주또';
}

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/css/mjtto.css?ver=20260520_234708">', 0);

include_once(G5_PATH.'/head.sub.php');

$mjtto_home_url = G5_URL;
$mjtto_company_url = G5_URL.'/page/mjtto_company.php';
$mjtto_admin_url = 'https://adm.mjtto.com/admin';
?>
<div class="mjtto-wrap">
  <header class="mjtto-header" data-mjtto-header>
    <div class="mjtto-container mjtto-header-inner">
      <a class="mjtto-logo" href="<?php echo $mjtto_home_url; ?>" aria-label="매주또 홈">
        <img class="mjtto-logo-img" src="<?php echo G5_THEME_URL; ?>/img/main_logo.png" alt="매주또">
      </a>

      <nav id="mjtto-site-nav" class="mjtto-nav" data-mjtto-nav aria-label="주요 메뉴">
        <a class="mjtto-nav-home" href="<?php echo $mjtto_home_url; ?>">메인</a>
        <a href="<?php echo $mjtto_company_url; ?>">회사소개</a>
		<a href="<?php echo G5_URL; ?>/#mjtto-guide" data-mjtto-anchor>이용안내</a>
        <a class="mjtto-nav-login" href="<?php echo $mjtto_admin_url; ?>" target="_blank" rel="noopener">제휴사 로그인</a>
      </nav>

      <div class="mjtto-header-actions">
        <a class="mjtto-login-link" href="<?php echo $mjtto_admin_url; ?>" target="_blank" rel="noopener">제휴사 로그인</a>
      </div>

      <button class="mjtto-mobile-toggle" type="button" aria-label="메뉴 열기" aria-controls="mjtto-site-nav" aria-expanded="false" data-mjtto-menu-toggle>
        <span class="mjtto-mobile-toggle-icon" aria-hidden="true">☰</span>
        <span class="mjtto-mobile-toggle-text">MENU</span>
      </button>
    </div>
  </header>
  <button class="mjtto-menu-backdrop" type="button" aria-label="메뉴 닫기" data-mjtto-menu-close></button>
  <main>
