<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-18 17:35:51 KST  */
if (!defined('_GNUBOARD_')) exit;

$mjtto_inquiry_url = G5_BBS_URL.'/write.php?bo_table=inquiry';
?>
<section class="mjtto-page-hero">
  <div class="mjtto-container">
    <div class="mjtto-kicker">Guide</div>
    <h1>이용 안내</h1>
    <p class="mjtto-lead">제휴사 도입부터 운영까지 필요한 흐름을 간단하게 정리했습니다.</p>
  </div>
</section>

<section class="mjtto-section">
  <div class="mjtto-container">
    <div class="mjtto-process">
      <div class="mjtto-step"><h3>상담 접수</h3><p>문의 게시판을 통해 제휴사 정보와 운영 목적을 남깁니다.</p></div>
      <div class="mjtto-step"><h3>운영 정책 확정</h3><p>경품, 발권 조건, 지점 운영 범위를 정리합니다.</p></div>
      <div class="mjtto-step"><h3>시스템 연결</h3><p>별도 관리자 시스템에서 제휴사 계정을 구성합니다.</p></div>
      <div class="mjtto-step"><h3>운영 시작</h3><p>행운권 발권과 결과 조회를 운영합니다.</p></div>
    </div>

    <div class="mjtto-section" style="padding-bottom:0">
      <div class="mjtto-cta-band">
        <div>
          <h2>도입 상담이 필요하신가요?</h2>
          <p>문의글을 남기면 담당자가 확인할 수 있도록 구성합니다.</p>
        </div>
        <a class="mjtto-btn mjtto-btn-dark" href="<?php echo $mjtto_inquiry_url; ?>">문의글 남기기</a>
      </div>
    </div>
  </div>
</section>
