<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-20 00:25:00 KST  */

include_once(__DIR__ . '/../common.php');

if (!defined('_GNUBOARD_')) {
    exit;
}

$g5['title'] = '회사 소개';

include_once(G5_THEME_PATH . '/head.php');
?>

<main class="mjtto-company-origin-page">

  <section class="mjtto-company-intro-section">
    <div class="mjtto-container">
      <div class="mjtto-company-intro" data-mjtto-reveal>
        <div class="mjtto-company-symbol">
          <img src="<?php echo G5_THEME_URL; ?>/img/4b6872210c528.png" alt="매주또">
        </div>

        <h1>
          로또 메커니즘을 결합한<br>
          <strong>B2B 리워드 마케팅 플랫폼입니다.</strong>
        </h1>

        <p>
          기업이 고객에게 단순히 사은품을 주는 것을 넘어,
          당첨의 즐거움을 제공함으로써 고객 체류 시간과 브랜드 충성도를 높이는
          차별화된 마케팅 도구로 활용됩니다.
        </p>
      </div>
    </div>
  </section>

  <section class="mjtto-company-visual-section">
    <div class="mjtto-container">
      <div class="mjtto-company-wide-image" data-mjtto-reveal>
        <img src="<?php echo G5_THEME_URL; ?>/img/fe75e5cea8699.png" alt="매주또 B2B 리워드 마케팅 플랫폼">
      </div>
    </div>
  </section>

  <section class="mjtto-company-value-section">
    <div class="mjtto-container">
      <div class="mjtto-company-value-grid">
        <article class="mjtto-company-value-card" data-mjtto-reveal>
          <h2>풍부한 현장 경험</h2>
          <p>
            2020년 유튜브 기반의 대규모 소셜 마케팅 프로젝트
            현장 촬영 180여 곳, SNS 마케팅 400여 곳 수행을 성공적으로 이끌며
            실전 역량을 증명했습니다.
          </p>
        </article>

        <article class="mjtto-company-value-card" data-mjtto-reveal>
          <h2>비전</h2>
          <p>
            “기회는 사람을 바꾸고 사람은 기업의 운명을 바꿉니다.” 라는 철학 아래,
            사람 중심의 비즈니스 가치를 창출하며 파트너사의 성장을 최우선으로 합니다.
          </p>
        </article>
      </div>
    </div>
  </section>



</main>

<?php
include_once(G5_THEME_PATH . '/tail.php');
?>