<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-19 15:30:00 KST  */
if (!defined('_GNUBOARD_')) exit;

$mjtto_inquiry_url = G5_URL.'/#mjtto-contact';
$mjtto_admin_url = 'https://adm.mjtto.com/admin';
$mjtto_inquiry_submit_url = G5_URL.'/page/mjtto_inquiry_submit.php';
$mjtto_company_url = G5_URL.'/page/mjtto_company.php';
$mjtto_guide_url = G5_URL.'/#mjtto-guide';

$mjtto_inquiry_form_token = get_session('ss_mjtto_inquiry_form_token');
if (!$mjtto_inquiry_form_token) {
    $mjtto_inquiry_form_token = md5(uniqid('', true));
    set_session('ss_mjtto_inquiry_form_token', $mjtto_inquiry_form_token);
}

$mjtto_inquiry_success = (isset($_GET['mjtto_inquiry']) && $_GET['mjtto_inquiry'] === 'success');

include_once(G5_THEME_PATH.'/head.php');
?>
<section id="mjtto-intro" class="mjtto-hero" data-mjtto-hero>
  <div class="mjtto-hero-slides" aria-hidden="true">
    <div class="mjtto-hero-slide is-active" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/e405179902973.png')"></div>
    <div class="mjtto-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/d5c7d7a049575.png')"></div>
    <div class="mjtto-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/34b1ed2f7435a.png')"></div>
  </div>
  <div class="mjtto-hero-overlay"></div>
  <div class="mjtto-container mjtto-hero-grid">
    <div class="mjtto-hero-copy">
<div class="mjtto-hero-copy-item is-active" data-mjtto-hero-copy>
<h1 class="mjtto-hero-title">고객 참여를 만드는<br class="mjtto-br-pc"><span class="mjtto-hero-title-line">가장 공정한</span> <br class="mjtto-br-mobile"><span class="mjtto-hero-title-line">기브어웨이 마케팅</span></h1>
        <p class="mjtto-lead">로또기반 연동시스템 기브어웨이 마케팅 솔루션으로 고객 참여와 방문을 자연스럽게 유도하세요.</p>
      </div>
      <div class="mjtto-hero-copy-item" data-mjtto-hero-copy>
<h1 class="mjtto-hero-title">고객 참여를 만드는<br class="mjtto-br-pc"><span class="mjtto-hero-title-line">가장 공정한</span> <br class="mjtto-br-mobile"><span class="mjtto-hero-title-line">이벤트 선물 마케팅</span></h1>
        <p class="mjtto-lead">로또기반 연동시스템 이벤트 선물마케팅 솔루션으로 고객 참여와 방문을 자연스럽게 유도하세요.</p>
      </div>
      <div class="mjtto-hero-copy-item" data-mjtto-hero-copy>
<h1 class="mjtto-hero-title">고객 참여를 만드는<br class="mjtto-br-pc"><span class="mjtto-hero-title-line">가장 공정한</span> <br class="mjtto-br-mobile"><span class="mjtto-hero-title-line">리워드 마케팅</span></h1>
        <p class="mjtto-lead">로또기반 연동시스템 리워드 마케팅 솔루션으로 고객 참여와 방문을 자연스럽게 유도하세요.</p>
      </div>
      <div class="mjtto-hero-cta">
        <a class="mjtto-btn mjtto-btn-line" href="<?php echo $mjtto_inquiry_url; ?>">지금 바로 문의하기</a>
      </div>
</div>
  </div>
  <div class="mjtto-hero-dots" aria-label="메인 슬라이드 선택">
          <button class="is-active" type="button" data-mjtto-hero-dot="0" aria-label="1번 슬라이드"></button>
          <button type="button" data-mjtto-hero-dot="1" aria-label="2번 슬라이드"></button>
          <button type="button" data-mjtto-hero-dot="2" aria-label="3번 슬라이드"></button>
        </div>
</section>

<!--  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-19 20:10:00 KST  -->

<section class="mjtto-section mjtto-section-light mjtto-origin-problem">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Problem</div>
      <h2>왜 많은 기업들이<br>이벤트 마케팅에 <span class="mobile-block">어려움을 겪을까요?</span></h2>
      <p>고객 참여를 유도하는 이벤트는 많지만 지속적인 방문과 신뢰를 동시에 만드는 구조는 쉽지 않습니다.</p>
    </div>

    <div class="mjtto-origin-three-grid">
      <article class="mjtto-origin-card" data-mjtto-reveal>
        <div class="mjtto-origin-card-thumb">
          <img src="<?php echo G5_THEME_URL; ?>/img/ba662a732c861.png" alt="참여율 문제">
        </div>
        <h3>참여율 문제</h3>
        <p>이벤트는 많지만 고객 참여는 낮습니다.<br>많은 기업들이 이벤트를 진행하지만<br>고객의 실제 참여율은 기대보다 낮은 경우가 많습니다.</p>
      </article>

      <article class="mjtto-origin-card" data-mjtto-reveal>
        <div class="mjtto-origin-card-thumb">
          <img src="<?php echo G5_THEME_URL; ?>/img/11628daad2b1b.png" alt="신뢰 문제">
        </div>
        <h3>신뢰 문제</h3>
        <p>추첨 방식에 대한 신뢰가 부족합니다<br>자체 추첨 방식은 고객에게<br>공정성에 대한 의문을 만들 수 있습니다.</p>
      </article>

      <article class="mjtto-origin-card" data-mjtto-reveal>
        <div class="mjtto-origin-card-thumb">
          <img src="<?php echo G5_THEME_URL; ?>/img/44f63ccf788f7.png" alt="지속성 문제">
        </div>
        <h3>지속성 문제</h3>
        <p>이벤트는 일회성으로 끝납니다<br>대부분의 이벤트는<br>한 번 참여 후 고객과의 연결이 끊어집니다.</p>
      </article>
    </div>
  </div>
</section>

<section class="mjtto-section mjtto-origin-sector">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Eligible Sectors</div>
      <h2>이런 업종 가능해요!!</h2>
      <p>고객 참여를 유도하는 이벤트로 다양한 업종에서 사용 가능합니다.</p>
    </div>

    <div class="mjtto-origin-wide-image" data-mjtto-reveal>
      <img src="<?php echo G5_THEME_URL; ?>/img/098fb096fef34.png" alt="적용 가능 업종">
    </div>

    <div class="mjtto-origin-sector-grid">
      <article class="mjtto-origin-sector-card" data-mjtto-reveal>
        <img src="<?php echo G5_THEME_URL; ?>/img/65725168a952e.jpg" alt="식자재마트">
        <strong>식자재마트 ㅣ 중,대형 마트 사은행사</strong>
      </article>

      <article class="mjtto-origin-sector-card" data-mjtto-reveal>
        <img src="<?php echo G5_THEME_URL; ?>/img/0dac5de9fdf02.jpg" alt="정육점">
        <strong>정육점 사은행사</strong>
      </article>

      <article class="mjtto-origin-sector-card" data-mjtto-reveal>
        <img src="<?php echo G5_THEME_URL; ?>/img/1ebad4a86bd31.jpg" alt="배달업종">
        <strong>배달업종 리뷰이벤트 활용</strong>
      </article>

      <article class="mjtto-origin-sector-card" data-mjtto-reveal>
        <img src="<?php echo G5_THEME_URL; ?>/img/0fac55644c399.jpg" alt="보험사 상담">
        <strong>보험사 ㅣ 사업단 ㅣ 지점 ㅣ 보험설계사 상담시 활용</strong>
      </article>

      <article class="mjtto-origin-sector-card" data-mjtto-reveal>
        <img src="<?php echo G5_THEME_URL; ?>/img/86fa99b030fa3.png" alt="기업 행사">
        <strong>각종 기업 행사 ㅣ 지역 행사 경품행사로 활용</strong>
      </article>

      <article class="mjtto-origin-sector-card" data-mjtto-reveal>
        <img src="<?php echo G5_THEME_URL; ?>/img/0ed673de4f74a.png" alt="골프장 미용실">
        <strong>골프장 ㅣ 미용실 등 다양한 업종에 행사로 활용</strong>
      </article>
    </div>
  </div>
</section>

<!--  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-20 03:15:00 KST  -->

<section class="mjtto-section mjtto-section-soft mjtto-origin-prizes">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Prizes</div>
      <h2>특별한 경품 라인업</h2>
      <p>*본 제휴사별 경품이 상이할 수 있습니다</p>
    </div>

    <div class="mjtto-prize-wide-list">

      <article class="mjtto-prize-wide-card" data-mjtto-reveal>
        <div class="mjtto-prize-image">
          <img src="<?php echo G5_THEME_URL; ?>/img/f561fddb59d23.png" alt="1등 상품">
        </div>
        <div class="mjtto-prize-text">
          <span>1등 상품</span>
          <h3>레이/캐스퍼 택 1 선택가능</h3>
          <p>고객 참여를 극대화하는 최고 가치 경품</p>
          <ul>
            <li>약 1,400만원 상당의 고가 경품</li>
            <li>강력한 이벤트 참여 동기 제공</li>
            <li>높은 당첨 만족도</li>
            <li>이벤트 화제성 극대화</li>
            <li>브랜드 신뢰도 상승 효과</li>
          </ul>
        </div>
      </article>

      <article class="mjtto-prize-wide-card" data-mjtto-reveal>
        <div class="mjtto-prize-image">
          <img src="<?php echo G5_THEME_URL; ?>/img/23d5356fd9940.png" alt="2등 상품">
        </div>
        <div class="mjtto-prize-text">
          <span>2등 상품</span>
          <h3>프리미엄 가전 패키지</h3>
          <p>높은 상품 가치로 참여율 극대화</p>
          <ul>
            <li>약 500만원 이상의 고가 상품</li>
            <li>가족 모두가 선호하는 가전</li>
            <li>당첨 시 높은 만족도</li>
            <li>2등 상품으로도 충분한 매력</li>
            <li>지속적인 이벤트 참여 동기 제공</li>
          </ul>
        </div>
      </article>

      <article class="mjtto-prize-wide-card" data-mjtto-reveal>
        <div class="mjtto-prize-image">
          <img src="<?php echo G5_THEME_URL; ?>/img/249ecf3698f72.png" alt="3등 상품">
        </div>
        <div class="mjtto-prize-text">
          <span>3등 상품</span>
          <h3>LG 퓨리케어 360 공기청정기</h3>
          <p>건강과 웰빙 중심의 인기 상품</p>
          <ul>
            <li>전 연령층이 선호하는 실용 가전</li>
            <li>일상 생활 건강 관리 필수품</li>
            <li>가족 구성원 모두 사용 가능</li>
            <li>계절 관계없이 활용 가능</li>
            <li>높은 상품 만족도</li>
          </ul>
        </div>
      </article>

    </div>

    <div class="mjtto-prize-half-grid">

      <article class="mjtto-prize-half-card" data-mjtto-reveal>
        <div class="mjtto-prize-image">
          <img src="<?php echo G5_THEME_URL; ?>/img/3d5078c2cd9ba.png" alt="4등 상품">
        </div>
        <div class="mjtto-prize-text">
          <span>4등 상품</span>
          <h3>동남아 3박5일 여행권 (2인)</h3>
          <p>경험 중심의 특별한 리워드</p>
          <ul>
            <li>물질적 상품을 넘어선 경험 가치</li>
            <li>고객 만족도와 행복감 극대화</li>
            <li>SNS 공유를 통한 자연 홍보</li>
            <li>이벤트 화제성 증가</li>
            <li>브랜드 긍정 이미지 형성</li>
          </ul>
        </div>
      </article>

      <article class="mjtto-prize-half-card" data-mjtto-reveal>
        <div class="mjtto-prize-image">
          <img src="<?php echo G5_THEME_URL; ?>/img/535b5c1bd8635.png" alt="5등 상품">
        </div>
        <div class="mjtto-prize-text">
          <span>5등 상품</span>
          <h3>행운권 1장 추가 제공</h3>
          <p>지속적인 고객 참여 구조</p>
          <ul>
            <li>반복 참여로 충성도 향상</li>
            <li>행사 재방문 유도</li>
            <li>참여 고객 수 증가 효과</li>
            <li>입소문 마케팅 확대</li>
            <li>이벤트 활성화 효과</li>
          </ul>
        </div>
      </article>

    </div>
  </div>
</section>

<!--  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-19 20:40:00 KST  -->

<section id="mjtto-strength" class="mjtto-section mjtto-section-light mjtto-origin-features">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Key Features</div>
      <h2>매주또만의 3대 핵심 강점</h2>
      <p>신뢰, 반복, 합법 - 세 가지가 완벽하게 갖춰진 리워드 마케팅 솔루션!</p>
    </div>

    <div class="mjtto-origin-feature-grid">
      <article class="mjtto-origin-feature-card" data-mjtto-reveal>
        <span>Trust</span>
        <h3>절대적 공정성</h3>
        <strong>주최 측 개입 불가!</strong>
        <p>✔ 매주 토요일 생중계되는 '동행복권(나눔로또)' 번호와 100% 동기화됩니다.</p>
        <p>✔ 자체 추첨 조작 의혹을 원천 차단하여 고객 신뢰를 극대화합니다.</p>
      </article>

      <article class="mjtto-origin-feature-card" data-mjtto-reveal>
        <span>Loop</span>
        <h3>강력한 루프 형성</h3>
        <strong>고객이 매주 돌아옵니다!</strong>
        <p>✔ 고객은 매주 당첨을 확인하기 위해 모바일 플랫폼에 접속하고, 자연스럽게 제휴사를 재인지합니다.</p>
        <p>✔ 5등 당첨 시스템은 고객이 다시 매장을 방문하거나 상담을 받게 하는 강력한 유입 기폭제가 됩니다.</p>
      </article>

      <article class="mjtto-origin-feature-card" data-mjtto-reveal>
        <span>Legal</span>
        <h3>법적 안정성 확보</h3>
        <strong>완전히 합법적인 구조!</strong>
        <p>✔ '소비자 현장경품' 형태로, 2016년 경품 가액 한도 폐지에 따라 고가 경품 제공에 법적 제한이 없습니다.</p>
        <p>✔ 무상 사은품 제공 방식으로 사행성 유발이나 도박죄에 해당하지 않습니다.</p>
      </article>
    </div>
  </div>
</section>

<!--  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-19 22:55:00 KST  -->

<section id="mjtto-guide" class="mjtto-section mjtto-origin-join">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">How To Join</div>
      <h2><span>매주또 리워드</span> &nbsp; <span>이용안내</span></h2>
      <p>복잡한 절차 없이 제휴사 상담(방문)을<br class="pc_hidden"> 통하여 이벤트 참여가 가능합니다.</p>
    </div>

    <div class="mjtto-origin-step-grid">
      <article class="mjtto-origin-step-card" data-mjtto-reveal>
        <div class="mjtto-origin-step-icon">
          <img src="<?php echo G5_THEME_URL; ?>/img/22ec52b856acb.png" alt="제휴사 상담 및 행운권 획득">
        </div>
        <span>STEP 01</span>
        <h3>제휴사 상담 및 행운권 획득</h3>
        <p>제휴사 방문 및 상담을 통하여 행운권 획득</p>
      </article>

      <article class="mjtto-origin-step-card" data-mjtto-reveal>
        <div class="mjtto-origin-step-icon">
          <img src="<?php echo G5_THEME_URL; ?>/img/60edbd2682513.png" alt="로또 추첨 확인">
        </div>
        <span>STEP 02</span>
        <h3>로또 추첨 확인</h3>
        <p>매주 토요일 로또 추첨 확인(공정성 보장)</p>
      </article>

      <article class="mjtto-origin-step-card" data-mjtto-reveal>
        <div class="mjtto-origin-step-icon">
          <img src="<?php echo G5_THEME_URL; ?>/img/a03cc4e5f535e.png" alt="당첨자 정보등록 및 경품수령">
        </div>
        <span>STEP 03</span>
        <h3>당첨자 정보등록 및 경품수령</h3>
        <p>당첨 및 경품 수령(QR코드 접수)</p>
      </article>

      <article class="mjtto-origin-step-card" data-mjtto-reveal>
        <div class="mjtto-origin-step-icon">
          <img src="<?php echo G5_THEME_URL; ?>/img/c661f4546d48b.png" alt="제휴사 연락 방문">
        </div>
        <span>STEP 04</span>
        <h3>제휴사 연락(방문)</h3>
        <p>고객 재방문 및 시스템 선순환</p>
      </article>
    </div>
  </div>
</section>

<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-19 22:45:00 KST  */

if (!function_exists('mjtto_lotto_ball_class')) {
    function mjtto_lotto_ball_class($number)
    {
        $number = (int)$number;

        if ($number >= 1 && $number <= 10) {
            return 'ball-yellow';
        }

        if ($number >= 11 && $number <= 20) {
            return 'ball-blue';
        }

        if ($number >= 21 && $number <= 30) {
            return 'ball-red';
        }

        if ($number >= 31 && $number <= 40) {
            return 'ball-gray';
        }

        return 'ball-green';
    }
}

$mjtto_draw_result_list = array();

$mjtto_draw_result_sql = "
    SELECT
        `mz_round`.`round_id`,
        `mz_round`.`round_no`,
        `mz_round`.`draw_date`,
        `mz_draw_result`.`win_a`,
        `mz_draw_result`.`win_b`,
        `mz_draw_result`.`win_c`,
        `mz_draw_result`.`win_d`,
        `mz_draw_result`.`win_e`,
        `mz_draw_result`.`win_f`,
        `mz_draw_result`.`bonus_no`
    FROM `mz_draw_result`
    INNER JOIN `mz_round`
        ON `mz_draw_result`.`round_id` = `mz_round`.`round_id`
    WHERE `mz_round`.`draw_date` <= CURDATE()
    ORDER BY `mz_round`.`round_no` DESC
    LIMIT 10
";

$mjtto_draw_result_query = sql_query($mjtto_draw_result_sql);

while ($mjtto_draw_result_row = sql_fetch_array($mjtto_draw_result_query)) {
    $mjtto_draw_result_list[] = $mjtto_draw_result_row;
}
?>

<section class="mjtto-section mjtto-origin-draw">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Draw Results</div>
      <h2>당첨번호 발표</h2>
      <p>매주 토요일 8시35분 TV방송<br>로또번호 동일하게 확인 가능합니다.</p>
    </div>

    <?php if (!empty($mjtto_draw_result_list)) { ?>
      <div class="mjtto-lotto-slider" data-mjtto-lotto-slider data-mjtto-reveal>
        <button type="button" class="mjtto-lotto-arrow mjtto-lotto-arrow-prev" data-mjtto-lotto-prev aria-label="이전 회차 보기">
          ‹
        </button>

        <div class="mjtto-lotto-slider-viewport">
          <?php foreach ($mjtto_draw_result_list as $mjtto_draw_result_index => $mjtto_draw_result_row) { ?>
            <?php
            $mjtto_draw_numbers = array(
                (int)$mjtto_draw_result_row['win_a'],
                (int)$mjtto_draw_result_row['win_b'],
                (int)$mjtto_draw_result_row['win_c'],
                (int)$mjtto_draw_result_row['win_d'],
                (int)$mjtto_draw_result_row['win_e'],
                (int)$mjtto_draw_result_row['win_f']
            );

            $mjtto_bonus_number = (int)$mjtto_draw_result_row['bonus_no'];
            $mjtto_is_active = ($mjtto_draw_result_index === 0) ? ' is-active' : '';
            ?>
            <div class="mjtto-lotto-result-card<?php echo $mjtto_is_active; ?>" data-mjtto-lotto-card>
              <img class="mjtto-lotto-logo" src="<?php echo G5_THEME_URL; ?>/img/main_logo.png" alt="매주또">

              <strong class="mjtto-lotto-round">
                <?php echo (int)$mjtto_draw_result_row['round_no']; ?>회
              </strong>

              <span class="mjtto-lotto-date">
                <?php echo date('Y.m.d', strtotime($mjtto_draw_result_row['draw_date'])); ?> 추첨
              </span>

              <div class="mjtto-lotto-balls">
                <?php foreach ($mjtto_draw_numbers as $mjtto_draw_number) { ?>
                  <span class="ball <?php echo mjtto_lotto_ball_class($mjtto_draw_number); ?>">
                    <?php echo $mjtto_draw_number; ?>
                  </span>
                <?php } ?>

                <em>+</em>

                <span class="ball <?php echo mjtto_lotto_ball_class($mjtto_bonus_number); ?>">
                  <?php echo $mjtto_bonus_number; ?>
                </span>
              </div>

              <p class="mjtto-lotto-source">※ 당첨번호 데이터 출처: 매주또 동기화 DB</p>
            </div>
          <?php } ?>
        </div>

        <button type="button" class="mjtto-lotto-arrow mjtto-lotto-arrow-next" data-mjtto-lotto-next aria-label="다음 회차 보기">
          ›
        </button>

        <div class="mjtto-lotto-slider-count">
          <span data-mjtto-lotto-current>1</span> / <span><?php echo count($mjtto_draw_result_list); ?></span>
        </div>
      </div>
    <?php } else { ?>
      <div class="mjtto-lotto-result-card" data-mjtto-reveal>
        <img class="mjtto-lotto-logo" src="<?php echo G5_THEME_URL; ?>/img/main_logo.png" alt="매주또">
        <strong class="mjtto-lotto-round">당첨번호 준비중</strong>
        <p class="mjtto-lotto-source">아직 동기화된 당첨번호가 없습니다.</p>
      </div>
    <?php } ?>
  </div>
</section>

<section class="mjtto-section mjtto-origin-weekly">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head mjtto-weekly-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Weekly Prizes</div>
      <h2>함께하는 제휴사</h2>
    </div>

<div class="mjtto-partner-slider" data-mjtto-partner-slider>
  <button type="button" class="mjtto-partner-arrow mjtto-partner-prev" data-mjtto-partner-prev>‹</button>

  <div class="mjtto-partner-viewport" data-mjtto-partner-viewport>
    <div class="mjtto-partner-track">

          <?php
          $mjtto_partner_images = array(
            '1c3bc525496a2.png',
            '872424cdef107.png',
            '4de5e615c2487.png',
            '6af8f7006f179.png',
            '94cf8078dbf39.png',
            '56492f37996ac.png',
            'cb6788a6a2d6b.png',
            '9844fd32a6ca2.png',
            '6dc4b5663305e.png',
            '4389fcf6e9dec.png',
            '22009f0f8dd28.png',
            '8452c53de646b.png'
          );

          foreach ($mjtto_partner_images as $mjtto_partner_image) {
          ?>
            <div class="mjtto-origin-partner-item">
              <img src="<?php echo G5_THEME_URL; ?>/img/<?php echo $mjtto_partner_image; ?>" alt="함께하는 제휴사">
            </div>
          <?php } ?>

    </div>
  </div>

  <button type="button" class="mjtto-partner-arrow mjtto-partner-next" data-mjtto-partner-next>›</button>
</div></section>

<section id="mjtto-contact" class="mjtto-section mjtto-inquiry-section">
  <!--  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-20 23:36:12 KST  -->
  <div class="mjtto-container">
    <div class="mjtto-inquiry-layout">

      <div class="mjtto-inquiry-left">
        <div class="mjtto-inquiry-title-card">
          <span>제휴문의</span>
          <h2>지금 바로<br>문의를 남겨주세요</h2>
          <p>담당자가 <strong>24시간 내</strong> 연락드립니다</p>
        </div>

        <div class="mjtto-inquiry-phone-card">
          <span>전화 상담</span>
          <p>평일09:00~18:00</p>
          <strong>1661-5022</strong>
        </div>
      </div>

      <form class="mjtto-inquiry-form-card" method="post" action="<?php echo $mjtto_inquiry_submit_url; ?>" autocomplete="off">
        <input type="hidden" name="mjtto_inquiry_token" value="<?php echo get_text($mjtto_inquiry_form_token); ?>">
        <input type="text" name="mjtto_homepage" value="" class="mjtto-inquiry-hp" tabindex="-1" autocomplete="off" aria-hidden="true">

        <?php if (!empty($mjtto_inquiry_success)) { ?>
        <div class="mjtto-inquiry-success">
          <strong>제휴 및 공급문의가 접수되었습니다.</strong>
          <span>담당자가 확인 후 빠르게 연락드리겠습니다.</span>
        </div>
        <?php } ?>

        <div class="mjtto-inquiry-form-grid">
          <label>
            <span>회사명 <em>*</em></span>
            <input type="text" name="mjtto_company" required>
          </label>

          <label>
            <span>담당자명 <em>*</em></span>
            <input type="text" name="mjtto_manager" required>
          </label>

          <label>
            <span>연락처 <em>*</em></span>
            <div class="mjtto-phone-row">
              <input type="tel" name="mjtto_phone1" inputmode="numeric" maxlength="4" required>
              <b>-</b>
              <input type="tel" name="mjtto_phone2" inputmode="numeric" maxlength="4" required>
              <b>-</b>
              <input type="tel" name="mjtto_phone3" inputmode="numeric" maxlength="4" required>
            </div>
          </label>

          <label>
            <span>이메일 <em>*</em></span>
            <input type="email" name="mjtto_email" required>
          </label>

          <label class="mjtto-inquiry-wide">
            <span>업종 <em>*</em></span>
            <select name="mjtto_industry" required>
              <option value="">(선택)</option>
              <option value="보험회사">보험회사</option>
              <option value="기업 영업조직">기업 영업조직</option>
              <option value="지자체/공공기관">지자체/공공기관</option>
              <option value="소상공인">소상공인</option>
              <option value="쇼핑몰/온라인사업자">쇼핑몰/온라인사업자</option>
              <option value="기타">기타</option>
            </select>
          </label>

          <label class="mjtto-inquiry-wide">
            <span>문의 내용</span>
            <textarea name="mjtto_content"></textarea>
          </label>
        </div>

        <button type="submit" class="mjtto-inquiry-submit">제휴 및 공급문의</button>
      </form>

    </div>
  </div>
</section>
<?php
include_once(G5_THEME_PATH.'/tail.php');
