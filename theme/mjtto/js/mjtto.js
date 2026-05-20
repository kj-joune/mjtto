/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-20 01:40:00 KST  */
/* 매주또 프론트 통합 JS: 메뉴 / 히어로 / 헤더 / 앵커 / reveal / 최근 5회차 */

(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var body = document.body;
    var hero = document.querySelector('.mjtto-hero');
    var menuButton = document.querySelector('[data-mjtto-menu-toggle]');
    var nav = document.getElementById('mjtto-site-nav') || document.querySelector('.mjtto-nav');
    var backdrop = document.querySelector('.mjtto-menu-backdrop');

    /* =========================
       1. Header state
    ========================= */

    if (hero) {
      body.classList.add('mjtto-home-hero');
    }

    function updateHeaderState() {
      if (!hero) return;

      if (window.pageYOffset > 20) {
        body.classList.add('mjtto-header-scrolled');
      } else {
        body.classList.remove('mjtto-header-scrolled');
      }
    }

    updateHeaderState();
    window.addEventListener('scroll', updateHeaderState, { passive: true });
    window.addEventListener('resize', updateHeaderState);

    /* =========================
       2. Mobile menu
    ========================= */

    function setMenuState(isOpen) {
      if (!menuButton || !nav) return;

      body.classList.toggle('mjtto-menu-open', isOpen);
      nav.classList.toggle('is-open', isOpen);
      menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

      var icon = menuButton.querySelector('.mjtto-mobile-toggle-icon');
      var text = menuButton.querySelector('.mjtto-mobile-toggle-text');

      if (icon) icon.textContent = isOpen ? '×' : '☰';
      if (text) text.textContent = isOpen ? 'CLOSE' : 'MENU';
    }

    function closeMenu() {
      setMenuState(false);
    }

    if (menuButton && nav) {
      menuButton.addEventListener('click', function (event) {
        event.preventDefault();
        setMenuState(!body.classList.contains('mjtto-menu-open'));
      });
    }

    if (backdrop) {
      backdrop.addEventListener('click', closeMenu);
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeMenu();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 980) {
        closeMenu();
      }
    });

    /* =========================
       3. Hero slider
    ========================= */

    var slides = Array.prototype.slice.call(document.querySelectorAll('.mjtto-hero-slide'));
    var copyItems = Array.prototype.slice.call(document.querySelectorAll('.mjtto-hero-copy-item'));
    var dots = Array.prototype.slice.call(document.querySelectorAll('[data-mjtto-hero-dot]'));
    var currentSlide = 0;
    var slideTimer = null;

    function showSlide(index) {
      if (!slides.length) return;

      if (index < 0) index = slides.length - 1;
      if (index >= slides.length) index = 0;

      currentSlide = index;

      slides.forEach(function (slide, i) {
        slide.classList.toggle('is-active', i === currentSlide);
      });

      copyItems.forEach(function (item, i) {
        item.classList.toggle('is-active', i === currentSlide);
      });

      dots.forEach(function (dot, i) {
        dot.classList.toggle('is-active', i === currentSlide);
      });
    }

    function startSlideTimer() {
      if (slideTimer) {
        clearInterval(slideTimer);
      }

      if (slides.length <= 1) {
        return;
      }

      slideTimer = setInterval(function () {
        showSlide(currentSlide + 1);
      }, 5000);
    }

    if (slides.length) {
      showSlide(0);
      startSlideTimer();

      dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
          var index = parseInt(dot.getAttribute('data-mjtto-hero-dot'), 10);

          if (!isNaN(index)) {
            showSlide(index);
            startSlideTimer();
          }
        });
      });
    }

    /* =========================
       4. Anchor scroll
    ========================= */

    function normalizePath(path) {
      path = path || '/';
      path = path.replace(/\/index\.php$/i, '/');
      path = path.replace(/\/+$/, '');

      return path === '' ? '/' : path;
    }

    function getHeaderOffset() {
      var header = document.querySelector('.mjtto-header');
      var height = header ? header.offsetHeight : 0;

      /*
        값 조정:
        - 덜 내려가면 height - 18
        - 너무 내려가면 height + 8
      */
      return Math.max(0, height - 10);
    }

    function getTargetTop(hash) {
      var target = document.querySelector(hash);

      if (!target) {
        return null;
      }

      return Math.max(
        0,
        Math.round(target.getBoundingClientRect().top + window.pageYOffset - getHeaderOffset())
      );
    }

    function instantScrollTo(top) {
      var html = document.documentElement;
      var oldBehavior = html.style.scrollBehavior;

      html.style.scrollBehavior = 'auto';
      window.scrollTo(0, top);

      requestAnimationFrame(function () {
        html.style.scrollBehavior = oldBehavior;
      });
    }

    function smoothScrollTo(top) {
      window.scrollTo({
        top: top,
        behavior: 'smooth'
      });
    }

    function moveToHash(hash, smooth) {
      if (!hash || hash === '#') return;

      var top = getTargetTop(hash);

      if (top === null) {
        return;
      }

      if (hero) {
        body.classList.add('mjtto-header-scrolled');
      }

      if (smooth) {
        smoothScrollTo(top);
      } else {
        instantScrollTo(top);
      }
    }

    function settleHash(hash) {
      if (!hash || hash === '#') return;

      closeMenu();

      if (hero) {
        body.classList.add('mjtto-header-scrolled');
      }

      moveToHash(hash, true);

      [180, 420, 850, 1400, 2100].forEach(function (delay) {
        setTimeout(function () {
          moveToHash(hash, false);
        }, delay);
      });
    }

    function bindAnchorScroll() {
      document.querySelectorAll('a[href^="#"], a[href*="/#"], [data-mjtto-anchor]').forEach(function (link) {
        link.addEventListener('click', function (event) {
          var href = link.getAttribute('href');

          if (!href) return;

          var hashIndex = href.indexOf('#');

          if (hashIndex < 0) return;

          var hash = href.substring(hashIndex);

          if (!hash || hash === '#') return;

          var linkUrl;

          try {
            linkUrl = new URL(href, window.location.origin);
          } catch (error) {
            linkUrl = null;
          }

          if (linkUrl) {
            var currentOrigin = window.location.origin;
            var currentPath = normalizePath(window.location.pathname);
            var linkPath = normalizePath(linkUrl.pathname);

            /*
              다른 페이지로 가는 링크는 브라우저 기본 이동 허용
              예: /page/mjtto_company.php
            */
            if (linkUrl.origin !== currentOrigin || linkPath !== currentPath) {
              return;
            }
          }

          if (!document.querySelector(hash)) return;

          event.preventDefault();
          event.stopPropagation();

          if (history.pushState) {
            history.pushState(null, '', hash);
          }

          settleHash(hash);
        }, true);
      });

      if (window.location.hash && document.querySelector(window.location.hash)) {
        setTimeout(function () {
          settleHash(window.location.hash);
        }, 300);

        window.addEventListener('load', function () {
          settleHash(window.location.hash);
        });
      }
    }

    bindAnchorScroll();

    /* =========================
       5. Reveal
    ========================= */

    var revealItems = Array.prototype.slice.call(document.querySelectorAll('[data-mjtto-reveal]'));

    function revealVisibleItems() {
      var triggerLine = window.innerHeight * 0.82;

      revealItems = revealItems.filter(function (item) {
        if (item.classList.contains('is-visible')) {
          return false;
        }

        var rect = item.getBoundingClientRect();

        if (rect.top <= triggerLine && rect.bottom >= 0) {
          item.classList.add('is-visible');
          return false;
        }

        return true;
      });

      if (!revealItems.length) {
        window.removeEventListener('scroll', revealVisibleItems);
        window.removeEventListener('resize', revealVisibleItems);
      }
    }

    if (revealItems.length) {
      revealVisibleItems();
      window.addEventListener('scroll', revealVisibleItems, { passive: true });
      window.addEventListener('resize', revealVisibleItems);
    }

    /* =========================
       6. Lotto recent slider
    ========================= */

    var lottoSlider = document.querySelector('[data-mjtto-lotto-slider]');

    if (lottoSlider) {
      var lottoCards = Array.prototype.slice.call(lottoSlider.querySelectorAll('[data-mjtto-lotto-card]'));
      var prevButton = lottoSlider.querySelector('[data-mjtto-lotto-prev]');
      var nextButton = lottoSlider.querySelector('[data-mjtto-lotto-next]');
      var currentText = lottoSlider.querySelector('[data-mjtto-lotto-current]');
      var currentIndex = 0;

      function showLottoCard(index) {
        if (!lottoCards.length) return;

        if (index < 0) index = lottoCards.length - 1;
        if (index >= lottoCards.length) index = 0;

        currentIndex = index;

        lottoCards.forEach(function (card, i) {
          card.classList.toggle('is-active', i === currentIndex);
        });

        if (currentText) {
          currentText.textContent = String(currentIndex + 1);
        }
      }

      if (prevButton) {
        prevButton.addEventListener('click', function () {
          showLottoCard(currentIndex - 1);
        });
      }

      if (nextButton) {
        nextButton.addEventListener('click', function () {
          showLottoCard(currentIndex + 1);
        });
      }

      showLottoCard(0);
    }
  });
})();

/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-20 02:55:00 KST  */
/* Weekly Prizes 제휴사 로고 자동 스크롤 + 화살표 */

(function () {
  function initMjttoPartnerAutoSlider() {
    var slider = document.querySelector('[data-mjtto-partner-slider]');
    if (!slider) return;

    var viewport = slider.querySelector('[data-mjtto-partner-viewport]');
    var prevButton = slider.querySelector('[data-mjtto-partner-prev]');
    var nextButton = slider.querySelector('[data-mjtto-partner-next]');
    var autoTimer = null;
    var isPaused = false;

    if (!viewport) return;

    function getScrollAmount() {
      var firstItem = viewport.querySelector('.mjtto-origin-partner-item');
      if (!firstItem) return 180;

      return firstItem.offsetWidth + 10;
    }

    function getMaxScrollLeft() {
      return viewport.scrollWidth - viewport.clientWidth;
    }

    function movePartner(direction) {
      var amount = getScrollAmount();
      var maxScrollLeft = getMaxScrollLeft();

      if (direction > 0) {
        if (viewport.scrollLeft + amount >= maxScrollLeft - 8) {
          viewport.scrollTo({
            left: 0,
            behavior: 'smooth'
          });
        } else {
          viewport.scrollBy({
            left: amount,
            behavior: 'smooth'
          });
        }
      } else {
        if (viewport.scrollLeft <= 8) {
          viewport.scrollTo({
            left: maxScrollLeft,
            behavior: 'smooth'
          });
        } else {
          viewport.scrollBy({
            left: -amount,
            behavior: 'smooth'
          });
        }
      }
    }

    function startAutoScroll() {
      stopAutoScroll();

      autoTimer = setInterval(function () {
        if (!isPaused) {
          movePartner(1);
        }
      }, 2600);
    }

    function stopAutoScroll() {
      if (autoTimer) {
        clearInterval(autoTimer);
        autoTimer = null;
      }
    }

    if (prevButton) {
      prevButton.addEventListener('click', function () {
        movePartner(-1);
        startAutoScroll();
      });
    }

    if (nextButton) {
      nextButton.addEventListener('click', function () {
        movePartner(1);
        startAutoScroll();
      });
    }

    slider.addEventListener('mouseenter', function () {
      isPaused = true;
    });

    slider.addEventListener('mouseleave', function () {
      isPaused = false;
    });

    slider.addEventListener('touchstart', function () {
      isPaused = true;
    }, { passive: true });

    slider.addEventListener('touchend', function () {
      setTimeout(function () {
        isPaused = false;
      }, 1200);
    }, { passive: true });

    startAutoScroll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMjttoPartnerAutoSlider);
  } else {
    initMjttoPartnerAutoSlider();
  }
})();

/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-20 03:45:00 KST  */
/* 도입문의 글쓰기 페이지 전용 표시 */

(function () {
  function initMjttoInquiryWritePage() {
    var params = new URLSearchParams(window.location.search);

    if (
      window.location.pathname.indexOf('/bbs/write.php') >= 0 &&
      params.get('bo_table') === 'inquiry'
    ) {
      document.body.classList.add('mjtto-inquiry-write-page');
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMjttoInquiryWritePage);
  } else {
    initMjttoInquiryWritePage();
  }
})();
