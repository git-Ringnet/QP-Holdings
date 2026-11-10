/* ══════════════════════════════════════════
   QP Holdings — script.js
   ══════════════════════════════════════════ */

(function () {
  'use strict';

  const getLang = () => {
    const htmlLang = document.documentElement.lang || 'vi';
    if (htmlLang.startsWith('en')) return 'en';
    if (htmlLang.startsWith('zh')) return 'zh';
    return 'vi';
  };
  const lang = getLang();
  const t = (vi, en, zh) => {
    if (lang === 'en') return en;
    if (lang === 'zh') return zh;
    return vi;
  };

  /* ── Cookie consent popup ───────────────── */
  const initCookieConsent = () => {
    const banner = document.querySelector('[data-cookie-consent-banner]');
    if (!banner) return;

    const storageKey = banner.dataset.storageKey || 'qp_cookie_consent_v1';
    const cookieKey = encodeURIComponent(storageKey);
    const settings = banner.querySelector('[data-cookie-settings]');
    const customizeButton = banner.querySelector('[data-cookie-consent="customize"]');
    const customizeLabel = banner.dataset.customizeLabel || (customizeButton ? customizeButton.textContent : '');
    const saveLabel = banner.dataset.saveLabel || customizeLabel;

    const hasStoredConsent = () => {
      try {
        if (window.localStorage.getItem(storageKey)) return true;
      } catch (error) {
        // localStorage can be unavailable in strict privacy modes.
      }

      return document.cookie.split('; ').some((row) => row.startsWith(cookieKey + '='));
    };

    const persistConsent = (payload) => {
      const value = JSON.stringify(Object.assign({ updatedAt: new Date().toISOString() }, payload));
      try {
        window.localStorage.setItem(storageKey, value);
      } catch (error) {
        // Fall back to a first-party cookie when localStorage is blocked.
      }
      document.cookie = `${cookieKey}=1; max-age=15552000; path=/; SameSite=Lax`;
    };

    const dismiss = () => {
      banner.classList.add('is-dismissing');
      banner.classList.remove('is-visible');
      window.setTimeout(() => banner.remove(), 240);
    };

    const getCategory = (name) => {
      const input = banner.querySelector(`[data-cookie-category="${name}"]`);
      return Boolean(input && input.checked);
    };

    if (hasStoredConsent()) {
      banner.remove();
      return;
    }

    // Reveal the banner only after first paint settles. Keeping it hidden
    // for the first ~600 ms means the hero (not the cookie copy) wins LCP
    // on small viewports, while the banner still appears promptly enough
    // for users to interact with before scrolling.
    const reveal = () => {
      banner.hidden = false;
      window.requestAnimationFrame(() => banner.classList.add('is-visible'));
    };
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(reveal, { timeout: 1500 });
    } else {
      window.setTimeout(reveal, 600);
    }

    banner.querySelectorAll('[data-cookie-consent]').forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.dataset.cookieConsent;

        if (action === 'accept') {
          banner.querySelectorAll('[data-cookie-category]').forEach((input) => {
            input.checked = true;
          });
          persistConsent({ status: 'accepted', analytics: true, marketing: true });
          dismiss();
          return;
        }

        if (action === 'reject') {
          banner.querySelectorAll('[data-cookie-category]').forEach((input) => {
            input.checked = false;
          });
          persistConsent({ status: 'rejected', analytics: false, marketing: false });
          dismiss();
          return;
        }

        if (action === 'customize' && settings) {
          if (settings.hidden) {
            settings.hidden = false;
            banner.classList.add('has-settings');
            if (customizeButton) {
              customizeButton.textContent = saveLabel;
              customizeButton.setAttribute('aria-expanded', 'true');
            }
            return;
          }

          persistConsent({
            status: 'custom',
            analytics: getCategory('analytics'),
            marketing: getCategory('marketing'),
          });
          if (customizeButton) {
            customizeButton.textContent = customizeLabel;
            customizeButton.setAttribute('aria-expanded', 'false');
          }
          dismiss();
        }
      });
    });
  };

  initCookieConsent();

  /* ── Phone country selector (intl-tel-input) ────────────── */
  const initIntlTelInput = () => {
    if (typeof window.intlTelInput !== 'function') return;

    const phoneSelectors = '.contact-phone-input, .qp-project-consult__phone-input, .job-application-phone-input';
    document.querySelectorAll(phoneSelectors).forEach((input) => {
      const wrap = input.closest('.contact-phone-input-wrap, .qp-phone-input-wrap, .job-application-phone');
      if (!wrap) return;

      const hiddenInput = wrap.querySelector('[data-phone-country-input]');
      if (!hiddenInput) return;

      const iti = window.intlTelInput(input, {
        initialCountry: hiddenInput.value.toLowerCase() || 'vn',
        onlyCountries: ['vn', 'us', 'cn'],
        utilsScript: window.qpIntlTelInput?.utilsUrl || '',
        separateDialCode: true,
        nationalMode: false,
      });

      input.addEventListener('countrychange', () => {
        const countryData = iti.getSelectedCountryData();
        hiddenInput.value = countryData.iso2.toUpperCase();
      });
    });
  };

  if (typeof window.intlTelInput === 'function') {
    initIntlTelInput();
  } else {
    window.addEventListener('load', initIntlTelInput);
  }

  document.querySelectorAll('.contact-form-select').forEach((select) => {
    const syncSelectState = () => {
      select.classList.toggle('is-selected', Boolean(select.value));
    };

    select.addEventListener('change', syncSelectState);
    syncSelectState();
  });

  /* ── Investor submenu (mobile tap) ─────── */
  // Use capture phase so we intercept the click BEFORE the mobile-nav close handler
  // (which is bound later, in bubble phase, and would otherwise close the menu).
  document.querySelectorAll('.header-nav .has-submenu').forEach((li) => {
    const link = li.querySelector(':scope > .nav-link');
    if (!link) return;
    link.addEventListener('click', (event) => {
      // Wide viewports: hover-driven dropdown, let the link navigate.
      if (!window.matchMedia('(max-width: 900px)').matches) return;

      const isOpen = li.classList.contains('open');
      // Tap on the caret part of the link: just toggle.
      const caret = event.target.closest('.nav-caret');
      // First tap (closed) → expand and stop the mobile menu from closing.
      if (!isOpen || caret) {
        event.preventDefault();
        event.stopImmediatePropagation();
        document.querySelectorAll('.header-nav .has-submenu.open').forEach((other) => {
          if (other !== li) other.classList.remove('open');
        });
        li.classList.toggle('open');
        link.setAttribute('aria-expanded', li.classList.contains('open') ? 'true' : 'false');
      }
      // If already open and tap isn't on the caret, allow navigation to the parent page.
    }, true); // capture
  });

  /* ── Investor tabs mobile dropdown ─────── */
  const tabsTrigger = document.getElementById('investor-tabs-mobile-trigger');
  const tabsPanel   = document.getElementById('investor-tabs-mobile-panel');

  if (tabsTrigger && tabsPanel) {
    tabsTrigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = tabsPanel.classList.toggle('is-open');
      tabsTrigger.setAttribute('aria-expanded', String(isOpen));
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
      if (
        tabsPanel.classList.contains('is-open') &&
        !tabsTrigger.contains(e.target) &&
        !tabsPanel.contains(e.target)
      ) {
        tabsPanel.classList.remove('is-open');
        tabsTrigger.setAttribute('aria-expanded', 'false');
      }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && tabsPanel.classList.contains('is-open')) {
        tabsPanel.classList.remove('is-open');
        tabsTrigger.setAttribute('aria-expanded', 'false');
        tabsTrigger.focus();
      }
    });
  }

  /* ── Hamburger menu ────────────────────── */
  const hamburger = document.getElementById('hamburger');
  const mainNav   = document.getElementById('main-nav');

  if (hamburger && mainNav) {
    const labelClose = t('Đóng menu', 'Close menu', '关闭菜单');
    const labelOpen  = t('Mở menu', 'Open menu', '打开菜单');

    hamburger.addEventListener('click', () => {
      const isOpen = hamburger.classList.toggle('open');
      mainNav.classList.toggle('open', isOpen);
      hamburger.setAttribute('aria-expanded', String(isOpen));
      hamburger.setAttribute('aria-label', isOpen ? labelClose : labelOpen);
      // Prevent body scroll when nav open on mobile
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Close nav when a link inside it is clicked
    mainNav.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('open');
        mainNav.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-label', labelOpen);
        document.body.style.overflow = '';
      });
    });

    // Close nav on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && mainNav.classList.contains('open')) {
        hamburger.classList.remove('open');
        mainNav.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-label', labelOpen);
        document.body.style.overflow = '';
      }
    });

    // Close nav when clicking outside
    document.addEventListener('click', (e) => {
      if (
        mainNav.classList.contains('open') &&
        !mainNav.contains(e.target) &&
        !hamburger.contains(e.target)
      ) {
        hamburger.classList.remove('open');
        mainNav.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-label', labelOpen);
        document.body.style.overflow = '';
      }
    });
  }

  /* ── Sticky header shadow ──────────────── */
  const header = document.getElementById('site-header');
  if (header) {
    const onScroll = () => {
      header.classList.toggle('scrolled', window.scrollY > 20);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // run once on load
  }

  /* ── Scroll reveal (IntersectionObserver) ─ */
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length > 0 && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObserver.unobserve(entry.target); // fire once
          }
        });
      },
      {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px',
      }
    );
    revealEls.forEach(el => revealObserver.observe(el));
  } else {
    // Fallback: just show all elements if IntersectionObserver unsupported
    revealEls.forEach(el => el.classList.add('visible'));
  }

  /* ── Smooth active nav on scroll ──────────
     Only enabled on the front page where in-page anchor sections (#about,
     #projects, etc.) exist. On any other URL we leave the server-rendered
     active class alone so the correct top-level link stays highlighted. */
  const sections = document.querySelectorAll('section[id], footer[id]');
  const navLinks  = document.querySelectorAll('.header-nav .nav-link');

  const path = window.location.pathname.replace(/\/+$/, ''); // strip trailing slash
  const isHomePath = path === '' || path === '/index.html' || /^\/(?:en|zh)$/.test(path);
  // Only run scroll-spy when nav contains hash links and we're on home.
  const hasHashLinks = [...navLinks].some(a => (a.getAttribute('href') || '').startsWith('#'));

  if (isHomePath && hasHashLinks && sections.length > 0 && navLinks.length > 0) {
    const activateLink = () => {
      let current = '';
      const scrollY = window.scrollY + 120;

      sections.forEach(sec => {
        if (scrollY >= sec.offsetTop) {
          current = sec.getAttribute('id');
        }
      });

      navLinks.forEach(link => {
        const href = link.getAttribute('href') || '';
        // Only manipulate anchor-style links; leave full-URL nav-links untouched.
        if (!href.startsWith('#')) return;
        link.classList.remove('active');
        if (href === '#' + current) {
          link.classList.add('active');
        }
      });
    };

    window.addEventListener('scroll', activateLink, { passive: true });
    activateLink();
  }

  /* ── Newsletter form ───────────────────── */
  const newsletterForm = document.querySelector('.newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = newsletterForm.querySelector('.newsletter-input');
      if (input && input.value.trim()) {
        const btn = newsletterForm.querySelector('.newsletter-btn');
        const originalText = btn.textContent;
        btn.textContent = t('✓ Đã đăng ký', '✓ Subscribed', '✓ 已订阅');
        btn.style.background = 'linear-gradient(90deg, #4CAF50, #81C784)';
        btn.disabled = true;
        input.value = '';
        setTimeout(() => {
          btn.textContent = originalText;
          btn.style.background = '';
          btn.disabled = false;
        }, 3000);
      }
    });
  }

  /* ── Stat counter animation ────────────── */
  const statValues = document.querySelectorAll('.stat-value');
  if (statValues.length > 0 && 'IntersectionObserver' in window) {
    const parseLocalizedNumber = (numStr) => {
      if (numStr.includes('.') && numStr.includes(',')) {
        if (numStr.indexOf('.') < numStr.indexOf(',')) {
          return parseFloat(numStr.replace(/\./g, '').replace(',', '.'));
        } else {
          return parseFloat(numStr.replace(/,/g, ''));
        }
      }
      if (numStr.includes('.')) {
        const parts = numStr.split('.');
        if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3)) {
          return parseFloat(numStr.replace(/\./g, ''));
        }
        return parseFloat(numStr);
      }
      if (numStr.includes(',')) {
        const parts = numStr.split(',');
        if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3)) {
          return parseFloat(numStr.replace(/,/g, ''));
        }
        return parseFloat(numStr.replace(',', '.'));
      }
      return parseFloat(numStr);
    };

    const animateCounter = (el) => {
      const raw = el.textContent;
      const match = raw.match(/([\d,.]+)/);
      if (!match) return;

      const numStr = match[1];
      const target = parseLocalizedNumber(numStr);
      if (isNaN(target)) return;

      const matchIdx = raw.indexOf(numStr);
      const prefix = raw.substring(0, matchIdx);
      const suffix = raw.substring(matchIdx + numStr.length);

      const hasComma = numStr.includes(',');
      const hasDot = numStr.includes('.');

      const formatNum = (num) => {
        if (target === 0) return '0';
        if (hasDot && !hasComma) {
          return Math.round(num).toLocaleString('de-DE', { maximumFractionDigits: 0 });
        }
        if (hasComma && !hasDot) {
          return Math.round(num).toLocaleString('en-US', { maximumFractionDigits: 0 });
        }
        if (hasComma && hasDot) {
          if (numStr.indexOf('.') < numStr.indexOf(',')) {
            return num.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          } else {
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          }
        }
        return Math.round(num).toString();
      };

      const duration = 1600;
      const start = performance.now();

      const step = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 4);
        const current = target * eased;
        el.textContent = prefix + formatNum(current) + suffix;
        if (progress < 1) {
          requestAnimationFrame(step);
        } else {
          el.textContent = raw;
        }
      };

      requestAnimationFrame(step);
    };

    const statsObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            statsObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.5 }
    );

    statValues.forEach(el => statsObserver.observe(el));
  }


  /* ── Swiper init for mobile (≤ 640px) ────────────────────
     Initializes activity, gallery, news swipers only on mobile.
     On desktop, the carousels render as their original CSS grid via the
     `:not(.swiper-initialized)` fallback rules in styles.css. */
  const swiperConfigs = [
    {
      selector: '.activity-swiper',
      options: {
        slidesPerView: 'auto',
        spaceBetween: 16,
        grabCursor: true,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 16,
      },
    },
    {
      selector: '.gallery-swiper',
      options: {
        slidesPerView: 'auto',
        spaceBetween: 16,
        grabCursor: true,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 16,
      },
    },
    {
      selector: '.news-swiper',
      multi: true, // multiple instances on the page
      options: {
        slidesPerView: 'auto',
        spaceBetween: 24,
        grabCursor: true,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 16,
        pagination: {
          el: '.news-swiper-pagination',
          clickable: true,
        },
      },
    },
  ];

  const swiperInstances = {};
  const mobileQuery = window.matchMedia('(max-width: 1024px)');

  function setupSwipers() {
    if (typeof window.Swiper === 'undefined') return;

    const isMobile = mobileQuery.matches;
    swiperConfigs.forEach(({ selector, options, multi }) => {
      if (multi) {
        // Handle multiple elements with the same selector
        const els = document.querySelectorAll(selector);
        els.forEach((el, idx) => {
          const key = selector + '__' + idx;
          const hasInstance = !!swiperInstances[key];

          if (isMobile && !hasInstance) {
            const instanceOpts = Object.assign({}, options);
            // Prefer the pagination element that immediately follows this
            // swiper (each card row has its own dots); fall back to the first
            // pagination within the section.
            let paginationEl = el.nextElementSibling;
            if (!paginationEl || !paginationEl.classList.contains('news-swiper-pagination')) {
              const section = el.closest('.news-cat-section');
              paginationEl = section ? section.querySelector('.news-swiper-pagination') : null;
            }
            if (paginationEl) {
              instanceOpts.pagination = { el: paginationEl, clickable: true };
            }
            swiperInstances[key] = new window.Swiper(el, instanceOpts);
          } else if (!isMobile && hasInstance) {
            swiperInstances[key].destroy(true, true);
            delete swiperInstances[key];
          }
        });
      } else {
        const el = document.querySelector(selector);
        if (!el) return;

        const hasInstance = !!swiperInstances[selector];

        if (isMobile && !hasInstance) {
          swiperInstances[selector] = new window.Swiper(el, options);
        } else if (!isMobile && hasInstance) {
          swiperInstances[selector].destroy(true, true);
          delete swiperInstances[selector];
        }
      }
    });
  }

  /* ── Partners Swiper — continuous marquee (always on, all viewports) ───
     Swiper needs ≥ slidesPerView*2 slides to loop. With only ~6 partner logos
     visible at once we duplicate slides until we have enough copies. */
  function setupPartnersSwiper() {
    if (typeof window.Swiper === 'undefined') return;
    const el = document.querySelector('.partners-swiper');
    if (!el || swiperInstances['.partners-swiper']) return;

    const wrapper = el.querySelector('.swiper-wrapper');
    if (wrapper) {
      const originals = Array.from(wrapper.children);
      // Aim for at least 18 slides so loop works even at very wide viewports.
      const minSlides = 18;
      while (wrapper.children.length < minSlides && originals.length > 0) {
        originals.forEach((node) => {
          const clone = node.cloneNode(true);
          clone.setAttribute('aria-hidden', 'true');
          wrapper.appendChild(clone);
        });
      }
    }

    swiperInstances['.partners-swiper'] = new window.Swiper(el, {
      slidesPerView: 'auto',
      spaceBetween: 24,
      grabCursor: true,
      loop: true,
      loopAdditionalSlides: 4,
      // delay: 0 + slow speed = smooth continuous marquee instead of step-pause.
      speed: 4000,
      allowTouchMove: true,
      autoplay: {
        delay: 0,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      },
      breakpoints: {
        640: { spaceBetween: 32 },
        900: { spaceBetween: 40 },
        1200: { spaceBetween: 48 },
      },
    });
  }

  // Run after Swiper script loads (defer ensures DOM is ready)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      setupSwipers();
      setupPartnersSwiper();
    });
  } else {
    setupSwipers();
    setupPartnersSwiper();
  }

  // Re-evaluate on viewport changes
  if (mobileQuery.addEventListener) {
    mobileQuery.addEventListener('change', setupSwipers);
  } else if (mobileQuery.addListener) {
    // Older browsers
    mobileQuery.addListener(setupSwipers);
  }

  /* ── Shared form validation (contact + project consult) ── */
  const initFormValidation = () => {
    const i18n = window.qpFormI18n || {};
    const msg = {
      required: i18n.required || t('Vui lòng điền vào trường này.', 'Please fill in this field.', '请填写此字段。'),
      selectRequired: i18n.selectRequired || t('Vui lòng chọn một tùy chọn.', 'Please select an option.', '请选择一个选项。'),
      emailInvalid: i18n.emailInvalid || t('Địa chỉ email không hợp lệ.', 'Invalid email address.', '电子邮件地址无效。'),
    };
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Per-form config: form selector, error-message class, and how to find
    // the element that should receive the .is-invalid red border for a field.
    const configs = [
      {
        formSelector: '.contact-form',
        errorClass: 'contact-form-error',
        phoneWrap: '.contact-phone-input-wrap',
        fieldContainer: '.contact-form-field',
      },
      {
        formSelector: '.qp-project-consult__form',
        errorClass: 'qp-project-consult__error',
        phoneWrap: '.qp-phone-input-wrap',
        fieldContainer: 'label',
      },
    ];

    configs.forEach((cfg) => {
      document.querySelectorAll(cfg.formSelector).forEach((form) => {
        form.setAttribute('novalidate', '');

        const getTargets = (field) => {
          const phoneWrap = field.type === 'tel' ? field.closest(cfg.phoneWrap) : null;
          const container = field.closest(cfg.fieldContainer);
          return { display: phoneWrap || field, container: container };
        };

        const getMessage = (field, value) => {
          if (field.tagName === 'SELECT') return msg.selectRequired;
          if (field.type === 'email' && value !== '') return msg.emailInvalid;
          return msg.required;
        };

        const clearError = (field) => {
          const t2 = getTargets(field);
          t2.display.classList.remove('is-invalid');
          if (t2.container) {
            const existing = t2.container.querySelector('.' + cfg.errorClass);
            if (existing) existing.parentNode.removeChild(existing);
          }
        };

        const showError = (field, message) => {
          const t2 = getTargets(field);
          t2.display.classList.add('is-invalid');
          if (!t2.container) return;
          let existing = t2.container.querySelector('.' + cfg.errorClass);
          if (existing) existing.parentNode.removeChild(existing);
          const el = document.createElement('span');
          el.className = cfg.errorClass;
          el.setAttribute('role', 'alert');
          el.textContent = message;
          t2.container.appendChild(el);
        };

        const validateField = (field) => {
          const value = (field.value || '').trim();
          let valid = value !== '';
          if (valid && field.type === 'email') valid = emailRegex.test(value);
          return { valid: valid, value: value };
        };

        form.addEventListener('submit', (event) => {
          const fields = form.querySelectorAll('[required]');
          let firstInvalid = null;
          fields.forEach((field) => {
            if (field.classList.contains('contact-form-hp') || field.classList.contains('qp-project-consult__hp')) return;
            const result = validateField(field);
            if (!result.valid) {
              showError(field, getMessage(field, result.value));
              if (!firstInvalid) firstInvalid = field;
            } else {
              clearError(field);
            }
          });
          if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
          }
        });

        form.addEventListener('input', (event) => {
          const target = event.target;
          if (!target.hasAttribute('required')) return;
          if (validateField(target).valid) clearError(target);
        });

        form.addEventListener('change', (event) => {
          const target = event.target;
          if (!target.hasAttribute('required')) return;
          if (validateField(target).valid) clearError(target);
        });
      });
    });
  };

  initFormValidation();

  /* ── Shared lightbox (lightweight, no dependency) ──────────
     Accepts either an array of image URLs (legacy: project gallery) or an
     array of item objects { type:'image'|'video', image, embed, kind }.
     Supports keyboard nav, swipe nav on touch, pinch-to-zoom + pan on touch,
     double-tap / double-click + wheel zoom, an item counter, and embedded
     video playback (YouTube iframe or <video> for mp4). */
  /* ── Lightbox (image zoom / pan + embedded video) ──────── */
  const createLightbox = (rawItems) => {
    const items = (rawItems || []).map((it) =>
      typeof it === 'string' ? { type: 'image', image: it } : it
    );

    let current = 0;
    let overlay = null;
    let imgEl = null;
    let videoWrap = null;
    let stageEl = null;
    let counterEl = null;
    let isClosing = false;

    const clearVideo = () => {
      if (videoWrap) {
        try {
          const iframes = videoWrap.querySelectorAll('iframe');
          iframes.forEach((iframe) => {
            try { iframe.src = 'about:blank'; } catch (e) {}
          });
          const videos = videoWrap.querySelectorAll('video');
          videos.forEach((video) => {
            try { video.pause(); video.src = ''; } catch (e) {}
          });
        } catch (err) {}
        videoWrap.innerHTML = '';
        videoWrap.style.display = 'none';
      }
    };

    const show = (index) => {
      if (!items.length) return;
      current = (index + items.length) % items.length;
      const item = items[current] || {};

      if (item.type === 'video') {
        if (imgEl) {
          imgEl.style.display = 'none';
          imgEl.removeAttribute('src');
        }
        if (overlay) {
          overlay.classList.add('is-video');
          overlay.classList.remove('is-youtube', 'is-youtube-shorts', 'is-mp4', 'is-tiktok', 'is-external');
          if (item.kind) overlay.classList.add('is-' + item.kind.replace(/_/g, '-'));
        }
        if (videoWrap) {
          const kindSlug = item.kind ? item.kind.replace(/_/g, '-') : '';
          videoWrap.className = 'qp-lightbox__video-wrap' + (kindSlug ? ' qp-lightbox__video-wrap--' + kindSlug : '');
          if (item.kind === 'mp4' && item.embed) {
            videoWrap.innerHTML =
              '<video class="qp-lightbox__video" src="' + item.embed + '" controls autoplay playsinline preload="auto"></video>';
          } else if ((item.kind === 'youtube' || item.kind === 'youtube_shorts') && item.embed) {
            const isShorts = item.kind === 'youtube_shorts';
            const iframeClass = 'qp-lightbox__iframe' + (isShorts ? ' qp-lightbox__iframe--shorts' : '');
            videoWrap.innerHTML =
              '<iframe class="' + iframeClass + '" src="' + item.embed +
              '" title="video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"></iframe>';
          } else {
            const isTikTok = item.kind === 'tiktok';
            const btnLabel = isTikTok
              ? t('Mở xem trên TikTok ↗', 'Watch on TikTok ↗', '在 TikTok 上观看 ↗')
              : t('Mở xem video ↗', 'Watch Video ↗', '观看视频 ↗');
            const titleHtml = item.title ? '<h4 class="qp-lightbox__ext-title">' + item.title + '</h4>' : '';
            const thumbSrc = item.image || '';
            const thumbHtml = thumbSrc
              ? '<div class="qp-lightbox__ext-thumb-wrap"><img class="qp-lightbox__ext-thumb" src="' + thumbSrc + '" alt="" /><span class="qp-lightbox__ext-play-badge"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="11" fill="rgba(25,54,96,0.85)" stroke="#D3A049" stroke-width="1.5"/><path d="M10 8.5l6 3.5-6 3.5z" fill="#D3A049"/></svg></span></div>'
              : '<div class="qp-lightbox__ext-icon-wrap"><svg viewBox="0 0 24 24" fill="none" width="48" height="48"><circle cx="12" cy="12" r="11" fill="rgba(25,54,96,0.85)" stroke="#D3A049" stroke-width="1.5"/><path d="M10 8.5l6 3.5-6 3.5z" fill="#D3A049"/></svg></div>';

            videoWrap.innerHTML =
              '<div class="qp-lightbox__ext-card">' +
                thumbHtml +
                '<div class="qp-lightbox__ext-body">' +
                  titleHtml +
                  '<a class="qp-lightbox__ext-btn btn btn-primary-gold" href="' + (item.video || item.embed || '#') + '" target="_blank" rel="noopener noreferrer">' +
                    '<span>' + btnLabel + '</span>' +
                  '</a>' +
                '</div>' +
              '</div>';
          }
          videoWrap.style.display = 'flex';
        }
      } else {
        clearVideo();
        if (overlay) {
          overlay.classList.remove('is-video', 'is-youtube', 'is-youtube-shorts', 'is-mp4', 'is-tiktok', 'is-external');
        }
        if (imgEl) {
          imgEl.style.display = '';
          imgEl.setAttribute('src', item.image || '');
          imgEl.setAttribute('alt', item.title || '');
        }
      }

      if (counterEl) {
        counterEl.textContent = (current + 1) + ' / ' + items.length;
      }
    };

    const close = (popHistory = true) => {
      if (!overlay || isClosing) return;
      isClosing = true;
      try { clearVideo(); } catch (e) {}

      overlay.classList.remove('is-open');
      document.body.classList.remove('qp-lightbox-open');
      document.body.style.overflow = '';
      document.documentElement.style.overflow = '';

      if (window.location.hash === '#xem-anh') {
        try {
          window.history.replaceState(null, '', window.location.pathname + window.location.search);
        } catch (e) {}
      }

      setTimeout(() => {
        isClosing = false;
      }, 200);
    };

    const bindGestures = () => {
      if (!stageEl) return;

      // Touch swipe gestures
      let touchStartX = 0;
      let touchStartY = 0;

      stageEl.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
          touchStartX = e.touches[0].clientX;
          touchStartY = e.touches[0].clientY;
        }
      }, { passive: true });

      stageEl.addEventListener('touchend', (e) => {
        if (e.changedTouches.length === 1) {
          const dx = e.changedTouches[0].clientX - touchStartX;
          const dy = e.changedTouches[0].clientY - touchStartY;
          // Swipe down or up > 60px closes the lightbox immediately
          if (Math.abs(dy) > 60 && Math.abs(dy) > Math.abs(dx) * 1.3) {
            close();
          } else if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
            // Horizontal swipe navigates slides
            show(current + (dx < 0 ? 1 : -1));
          }
        }
      }, { passive: true });

      // Wheel scroll down/up dismisses or navigates
      overlay.addEventListener('wheel', (e) => {
        if (overlay && overlay.classList.contains('is-video')) return;
        if (Math.abs(e.deltaY) > 50) {
          close();
        }
      }, { passive: true });
    };

    const buildOverlay = () => {
      overlay = document.createElement('div');
      overlay.className = 'qp-lightbox';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-label', t('Thư viện ảnh', 'Gallery', '图库'));
      overlay.innerHTML =
        '<button type="button" class="qp-lightbox__close" aria-label="' + t('Đóng', 'Close', '关闭') + '">' +
          '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M6 6 18 18M18 6 6 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>' +
          '<span>' + t('ĐÓNG', 'CLOSE', '关闭') + '</span>' +
        '</button>' +
        '<button type="button" class="qp-lightbox__prev" aria-label="' + t('Trước', 'Previous', '上一个') + '">' +
          '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5 8 12l7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
        '</button>' +
        '<figure class="qp-lightbox__stage"><img class="qp-lightbox__img" src="" alt="" draggable="false"><div class="qp-lightbox__video-wrap" style="display:none"></div></figure>' +
        '<button type="button" class="qp-lightbox__next" aria-label="' + t('Sau', 'Next', '下一个') + '">' +
          '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
        '</button>' +
        '<div class="qp-lightbox__bottom-bar">' +
          '<span class="qp-lightbox__counter" aria-hidden="true"></span>' +
          '<span class="qp-lightbox__hint">' + t('Nhấp vào ảnh hoặc khoảng trống để đóng • Phím ESC để thoát', 'Click image or outside to close • ESC to exit', '点击图片或空白处关闭 • 按 ESC 退出') + '</span>' +
        '</div>';
      document.body.appendChild(overlay);

      imgEl = overlay.querySelector('.qp-lightbox__img');
      stageEl = overlay.querySelector('.qp-lightbox__stage');
      videoWrap = overlay.querySelector('.qp-lightbox__video-wrap');
      counterEl = overlay.querySelector('.qp-lightbox__counter');

      const closeBtn = overlay.querySelector('.qp-lightbox__close');
      if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          close();
        });
      }

      const prevBtn = overlay.querySelector('.qp-lightbox__prev');
      if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          show(current - 1);
        });
      }

      const nextBtn = overlay.querySelector('.qp-lightbox__next');
      if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          show(current + 1);
        });
      }

      // Any click on overlay, stage, or image (outside video controls / nav arrows) closes the lightbox
      overlay.addEventListener('click', (e) => {
        if (e.target.closest('.qp-lightbox__prev') || e.target.closest('.qp-lightbox__next')) {
          return;
        }
        if (e.target.closest('.qp-lightbox__video, .qp-lightbox__iframe, .qp-lightbox__ext-btn')) {
          return;
        }
        e.preventDefault();
        close();
      });

      // Hide prev/next when only 1 item
      if (items.length < 2) {
        overlay.classList.add('qp-lightbox--single');
      }

      bindGestures();
    };

    const open = (index) => {
      if (!overlay) buildOverlay();
      show(index);
      overlay.classList.add('is-open');
      document.body.classList.add('qp-lightbox-open');
      document.body.style.overflow = 'hidden';
      document.documentElement.style.overflow = 'hidden';
    };

    document.addEventListener('keydown', (e) => {
      if (!overlay || !overlay.classList.contains('is-open')) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') show(current - 1);
      else if (e.key === 'ArrowRight') show(current + 1);
    });

    window.addEventListener('pagehide', close);
    window.addEventListener('beforeunload', close);

    return { open };
  };

  /* ── Project gallery: Swiper carousel + lightbox ───────── */
  const initProjectGallery = () => {
    const el = document.querySelector('[data-project-gallery]');
    if (!el) return;

    const slideLinks = Array.from(el.querySelectorAll('[data-gallery-lightbox]'));
    const images = slideLinks.map((a) => a.getAttribute('href')).filter(Boolean);

    let gallerySwiper = null;

    if (typeof window.Swiper === 'function') {
      gallerySwiper = new window.Swiper(el, {
        slidesPerView: 1.1,
        spaceBetween: 16,
        speed: 650,
        loop: images.length > 1,
        loopAdditionalSlides: 3,
        slideToClickedSlide: true,
        grabCursor: true,
        watchOverflow: true,
        pagination: {
          el: el.querySelector('.swiper-pagination'),
          clickable: true,
        },
        navigation: {
          nextEl: el.querySelector('.swiper-button-next'),
          prevEl: el.querySelector('.swiper-button-prev'),
        },
        breakpoints: {
          641: {
            slidesPerView: 'auto',
            centeredSlides: true,
            spaceBetween: -29,
            allowTouchMove: true,
          },
          1025: {
            slidesPerView: 'auto',
            centeredSlides: true,
            spaceBetween: -56,
            allowTouchMove: true,
          },
        },
      });
    }

    if (!images.length) return;

    const lightbox = createLightbox(images);

    el.addEventListener('click', (e) => {
      const a = e.target.closest('[data-gallery-lightbox]');
      if (!a || !el.contains(a)) return;

      e.preventDefault();

      const slide = a.closest('.swiper-slide');
      if (gallerySwiper && slide && !slide.classList.contains('swiper-slide-active')) {
        const loopIndex = Number(slide.getAttribute('data-swiper-slide-index'));
        if (Number.isFinite(loopIndex)) {
          gallerySwiper.slideToLoop(loopIndex, 650);
        }
        return;
      }

      const index = Number(a.dataset.index || 0);
      lightbox.open(Number.isFinite(index) ? index : 0);
    });
  };

  /* ── Home gallery (Thư viện hình ảnh & video): lightbox ── */
  const initHomeGallery = () => {
    const el = document.querySelector('[data-home-gallery]');
    if (!el) return;

    // Full item list (images + videos) for the lightbox, from the data attr.
    let items = [];
    try {
      items = JSON.parse(el.getAttribute('data-gallery-items') || '[]');
    } catch (err) {
      items = [];
    }
    // Fallback: derive from the visible card links if the payload is empty.
    if (!items.length) {
      const slideLinks = Array.from(el.querySelectorAll('[data-gallery-lightbox]'));
      items = slideLinks.map((a) => ({ type: 'image', image: a.getAttribute('href') })).filter((i) => i.image);
    }
    if (!items.length) return;

    const lightbox = createLightbox(items);

    el.addEventListener('click', (e) => {
      const a = e.target.closest('[data-gallery-lightbox]');
      if (!a || !el.contains(a)) return;

      e.preventDefault();
      const index = Number(a.dataset.index || 0);
      lightbox.open(Number.isFinite(index) ? index : 0);
    });

    // "Xem tất cả" buttons open the lightbox at the first item.
    document.querySelectorAll('[data-gallery-view-all]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        lightbox.open(0);
      });
    });
  };

  if (typeof window.Swiper === 'function') {
    initProjectGallery();
  } else {
    window.addEventListener('load', initProjectGallery);
  }

  initHomeGallery();

  /* ── "Xem thêm" / "Thu gọn" toggle (mobile collapse) ───── */
  const showMoreButtons = document.querySelectorAll('.show-more-btn');
  showMoreButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetSelector = btn.getAttribute('data-target');
      const target = targetSelector
        ? document.querySelector(targetSelector)
        : btn.previousElementSibling;
      if (!target) return;

      const isExpanded = target.classList.toggle('expanded');
      btn.classList.toggle('expanded', isExpanded);
      btn.setAttribute('aria-expanded', String(isExpanded));

      const label = btn.querySelector('.show-more-label');
      if (label) {
        label.textContent = isExpanded
          ? t('Thu gọn', 'Show less', '收起')
          : t('Xem thêm', 'Read more', '查看更多');
      }
    });
  });

})();
