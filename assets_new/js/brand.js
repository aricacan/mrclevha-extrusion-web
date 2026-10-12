/* Cappara — header davranışları (bağımlılıksız) */
(function () {
  var cookieConsent = document.getElementById('cookieConsent');
  if (cookieConsent) {
    var consentKey = 'mrc_cookie_consent_v1';
    var consent = null;
    try { consent = localStorage.getItem(consentKey); } catch (e) {}
    if (!consent) cookieConsent.hidden = false;
    Array.prototype.forEach.call(cookieConsent.querySelectorAll('[data-cookie-accept], [data-cookie-reject]'), function (button) {
      button.addEventListener('click', function () {
        try { localStorage.setItem(consentKey, button.hasAttribute('data-cookie-accept') ? 'accepted' : 'rejected'); } catch (e) {}
        cookieConsent.hidden = true;
      });
    });
  }

  'use strict';

  var header = document.getElementById('brandHeader');
  var nav    = document.getElementById('brandNav');
  var toggle = document.getElementById('brandNavToggle');

  var isMobile = function () { return window.innerWidth <= 991; };

  /* Mobil menüyü aç/kapat */
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* Mega menüsü olan başlık: masaüstünde hover, mobilde dokunmayla açılır */
  if (nav) {
    Array.prototype.forEach.call(nav.querySelectorAll('li.has-mega'), function (item) {
      var link = item.querySelector('a');
      if (!link) return;

      link.addEventListener('click', function (e) {
        if (!isMobile()) return;
        e.preventDefault();
        var wasOpen = item.classList.contains('is-open');
        // Aynı anda tek panel açık kalsın
        Array.prototype.forEach.call(nav.querySelectorAll('li.is-open'), function (el) {
          el.classList.remove('is-open');
        });
        if (!wasOpen) item.classList.add('is-open');
      });
    });
  }

  /* Hero göstergeleri.
     Tıklama Bootstrap'in data-api'si ile zaten çalışıyor; ancak aktif sınıfı
     yalnızca .carousel-indicators kabında senkronlanıyor. O sınıfın kendi
     stili numaraları gizlediği için senkronu burada yapıyoruz. */
  var hero = document.getElementById('heroCarousel');
  if (hero && window.bootstrap && bootstrap.Carousel) {
    var dots = hero.querySelectorAll('.hero__nums button');

    // Eski tema JS'i data-api ile çakıştığı için carousel'i açıkça başlatıyoruz.
    // Yavaş ilerlesin diye 7,5 sn; son slayttan sonra başa döner (wrap).
    if (!bootstrap.Carousel.getInstance(hero)) {
      new bootstrap.Carousel(hero, {
        interval: 7500,
        ride: 'carousel',
        pause: 'hover',
        wrap: true
      });
    }
    // Aktif indeksi olay özelliklerinden değil, DOM'dan okuyoruz — sürümler
    // arasında slid olayının alanları değişebiliyor.
    hero.addEventListener('slid.bs.carousel', function () {
      var items  = hero.querySelectorAll('.carousel-item');
      var active = hero.querySelector('.carousel-item.active');
      var index  = Array.prototype.indexOf.call(items, active);

      Array.prototype.forEach.call(dots, function (dot, i) {
        dot.classList.toggle('active', i === index);
      });
    });
  }

  /* Duyuru kartları: ok tuşlarıyla 4'erli sayfalama */
  var newsGrid = document.getElementById('newsGrid');
  if (newsGrid) {
    var items   = newsGrid.querySelectorAll('.news-item');
    var perPage = 4;
    var pages   = Math.ceil(items.length / perPage);
    var page    = 0;

    var render = function () {
      Array.prototype.forEach.call(items, function (item, i) {
        var visible = i >= page * perPage && i < (page + 1) * perPage;
        item.classList.toggle('d-none', !visible);
      });
    };

    var move = function (step) {
      page = (page + step + pages) % pages;
      render();
    };

    var prev = document.getElementById('newsPrev');
    var next = document.getElementById('newsNext');
    if (prev) prev.addEventListener('click', function () { move(-1); });
    if (next) next.addEventListener('click', function () { move(1); });
  }

  /* Kaydırınca header'a gölge + alt kenarda ilerleme çizgisi */
  if (header) {
    var progress = document.getElementById('scrollProgress');

    var onScroll = function () {
      header.classList.toggle('is-stuck', window.scrollY > 10);

      if (progress) {
        var alinabilir = document.documentElement.scrollHeight - window.innerHeight;
        var oran = alinabilir > 0 ? (window.scrollY / alinabilir) * 100 : 0;
        progress.style.width = Math.min(100, Math.max(0, oran)) + '%';
      }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    onScroll();
  }

  /* Asistan mesaj kutusu → Sohbet Simülasyonu */
  var assistantPanel = document.getElementById('assistantPanel');
  var assistantBody  = document.getElementById('assistantBody');
  var waForm          = document.getElementById('waForm');
  
  if (waForm && assistantPanel && assistantBody) {
    waForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = document.getElementById('waText');
      if (!input) return;
      var text = input.value.trim();
      if (!text) return;
      
      // 1) Kullanıcı baloncuğunu ekle
      var userBubble = document.createElement('div');
      userBubble.className = 'assistant__msg assistant__msg--user';
      userBubble.textContent = text;
      assistantBody.appendChild(userBubble);
      
      // Girişi temizle
      input.value = '';
      assistantBody.scrollTop = assistantBody.scrollHeight;
      
      // 2) Yazıyor... animasyon baloncuğu ekle
      var typingBubble = document.createElement('div');
      typingBubble.className = 'assistant__msg assistant__msg--bot assistant__msg--typing';
      typingBubble.innerHTML = '<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>';
      assistantBody.appendChild(typingBubble);
      assistantBody.scrollTop = assistantBody.scrollHeight;
      
      // 3) 1000ms sonra cevabı yaz
      setTimeout(function () {
        if (typingBubble.parentNode) {
          typingBubble.parentNode.removeChild(typingBubble);
        }
        
        var replyText = assistantPanel.getAttribute('data-auto-reply') || '';
        var botBubble = document.createElement('div');
        botBubble.className = 'assistant__msg assistant__msg--bot';
        botBubble.textContent = replyText;
        assistantBody.appendChild(botBubble);
        
        assistantBody.scrollTop = assistantBody.scrollHeight;
      }, 1000);
    });
  }

  /* SSS akordeonu — aynı anda tek soru açık */
  var faq = document.getElementById('faqList');
  if (faq) {
    Array.prototype.forEach.call(faq.querySelectorAll('.faq__q'), function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.parentElement;
        var wasOpen = item.classList.contains('is-open');

        Array.prototype.forEach.call(faq.querySelectorAll('.faq__item'), function (el) {
          el.classList.remove('is-open');
          var q = el.querySelector('.faq__q');
          if (q) q.setAttribute('aria-expanded', 'false');
        });

        if (!wasOpen) {
          item.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  /* Başa dön */
  var toTop = document.getElementById('toTop');
  if (toTop) {
    var toggleTop = function () {
      toTop.classList.toggle('is-visible', window.scrollY > 400);
    };
    window.addEventListener('scroll', toggleTop, { passive: true });
    toggleTop();

    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* Asistan paneli */
  var aToggle = document.getElementById('assistantToggle');
  var aPanel  = document.getElementById('assistantPanel');
  if (aToggle && aPanel) {
    aToggle.addEventListener('click', function () {
      var open = aPanel.classList.toggle('is-open');
      aToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      aToggle.innerHTML = open ? '&#10005;' : '&#128172;';
    });

    // Panel dışına tıklayınca kapat
    document.addEventListener('click', function (e) {
      if (!aPanel.classList.contains('is-open')) return;
      if (aPanel.contains(e.target) || aToggle.contains(e.target)) return;
      aPanel.classList.remove('is-open');
      aToggle.setAttribute('aria-expanded', 'false');
      aToggle.innerHTML = '&#128172;';
    });
  }

  /* ------------------------------------------------------------------
     Kaydırdıkça belirme + daktilo efekti
     ------------------------------------------------------------------ */
  // .reveal → aşağıdan yukarı (kurumsal), .slide-in → yanlardan (sektörler)
  var reveals = document.querySelectorAll('.reveal, .slide-in');

  if (reveals.length) {
    // Kullanıcı hareket azaltma istediyse yazım efekti uygulanmaz
    var reduceMotion = window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var typeHeading = function (el) {
      var text = el.getAttribute('data-text') || el.textContent.trim();
      if (el.dataset.typed) return;
      el.dataset.typed = '1';

      if (reduceMotion) {
        el.textContent = text;
        return;
      }

      el.textContent = '';
      el.classList.add('is-typing');

      var i = 0;
      var step = function () {
        el.textContent = text.slice(0, ++i);
        if (i < text.length) {
          setTimeout(step, 28);
        } else {
          el.classList.remove('is-typing');
        }
      };
      step();
    };

    var showBlock = function (block) {
      block.classList.add('is-in');
      var heading = block.querySelector('.typewriter');
      if (heading) setTimeout(function () { typeHeading(heading); }, 260);
    };

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          showBlock(entry.target);
          io.unobserve(entry.target);
        });
      }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });

      Array.prototype.forEach.call(reveals, function (el) { io.observe(el); });
    } else {
      // Eski tarayıcılarda efektsiz göster
      Array.prototype.forEach.call(reveals, showBlock);
    }
  }

  /* ------------------------------------------------------------------
     Kurumsal sayfa: kaydırırken aktif bölümü yan menüde işaretle
     ------------------------------------------------------------------ */
  var corpNav = document.getElementById('corpNav');

  if (corpNav) {
    var links = corpNav.querySelectorAll('.corp__nav-link');

    var blocks = Array.prototype.map.call(links, function (link) {
      return document.getElementById(link.getAttribute('data-target'));
    }).filter(Boolean);

    var setActive = function (id) {
      Array.prototype.forEach.call(links, function (link) {
        link.classList.toggle('is-active', link.getAttribute('data-target') === id);
      });
    };

    if ('IntersectionObserver' in window && blocks.length) {
      var spy = new IntersectionObserver(function (entries) {
        // Ekranda görünenler arasında en yukarıdakini aktif say
        var visible = entries.filter(function (e) { return e.isIntersecting; });
        if (!visible.length) return;
        visible.sort(function (a, b) {
          return a.boundingClientRect.top - b.boundingClientRect.top;
        });
        setActive(visible[0].target.id);
      }, { rootMargin: '-30% 0px -55% 0px', threshold: 0 });

      blocks.forEach(function (b) { spy.observe(b); });
    }

    // Menüye tıklayınca sayfayı yenilemeden ilgili bölüme kay, URL'i güncelle
    Array.prototype.forEach.call(links, function (link) {
      link.addEventListener('click', function (e) {
        var target = document.getElementById(link.getAttribute('data-target'));
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setActive(target.id);
        if (window.history && history.pushState) {
          history.pushState(null, '', link.getAttribute('href'));
        }
      });
    });

    // URL'deki bölümle açıldıysa oraya konumlan
    var initial = corpNav.querySelector('.corp__nav-link.is-active');
    if (initial) {
      var firstLink = corpNav.querySelector('.corp__nav-link');
      var target = document.getElementById(initial.getAttribute('data-target'));
      if (target && initial !== firstLink) {
        setTimeout(function () {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 400);
      }
    }
  }

  /* ------------------------------------------------------------------
     Teklif Al penceresi
     ------------------------------------------------------------------ */
  var quote = document.getElementById('quoteModal');

  if (quote) {
    var lastFocus = null;

    var openQuote = function () {
      lastFocus = document.activeElement;
      quote.hidden = false;
      document.body.style.overflow = 'hidden';
      var first = quote.querySelector('input, select');
      if (first) first.focus();
    };

    var closeQuote = function () {
      quote.hidden = true;
      document.body.style.overflow = '';
      if (lastFocus) lastFocus.focus();
    };

    Array.prototype.forEach.call(document.querySelectorAll('[data-quote-open]'), function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        openQuote();
        // Asistan paneli açıksa kapansın, iki katman üst üste gelmesin
        if (aPanel && aPanel.classList.contains('is-open')) aToggle.click();
      });
    });

    Array.prototype.forEach.call(quote.querySelectorAll('[data-quote-close]'), function (btn) {
      btn.addEventListener('click', closeQuote);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !quote.hidden) closeQuote();
    });

    // Gönderim sonrası sonucu göstermek için pencereyi açık aç
    if (/[?&]teklif=/.test(window.location.search)) openQuote();
  }

  /* Masaüstüne dönünce mobil durumları sıfırla */
  window.addEventListener('resize', function () {
    if (isMobile() || !nav) return;
    nav.classList.remove('is-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
    Array.prototype.forEach.call(nav.querySelectorAll('li.is-open'), function (el) {
      el.classList.remove('is-open');
    });
  });

  /* Typewriter Efekti (Neden Biz Bölümü) */
  var whyText = document.getElementById('whyTypewriter');
  if (whyText) {
    var isTr = document.documentElement.lang === 'tr';
    var text = whyText.getAttribute(isTr ? 'data-text-tr' : 'data-text-en') || '';
    whyText.innerHTML = '';
    
    var idx = 0;
    var speed = 25; // ms per char
    
    var type = function () {
      if (idx < text.length) {
        whyText.innerHTML = text.substring(0, idx + 1) + '<span class="why__cursor">|</span>';
        idx++;
        setTimeout(type, speed);
      } else {
        whyText.innerHTML = text; // remove cursor at the end
      }
    };
    
    if ('IntersectionObserver' in window) {
      var typewriterObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            typewriterObserver.unobserve(whyText);
            setTimeout(type, 300);
          }
        });
      }, { threshold: 0.3 });
      typewriterObserver.observe(whyText);
    } else {
      whyText.innerHTML = text;
    }
  }
})();

/* Üretim süreci — yatay şerit + hammadde→ürün şekil dönüşümü.
   Scroll'dan bağımsız: kendi kendine döngüyle ilerler, sadece bölüm
   ekrandayken çalışır. Bağımsız IIFE: sadece anasayfada çalışır. */
(function () {
  'use strict';

  var rail = document.getElementById('processRail');
  if (!rail) return;

  var fill     = document.getElementById('processLineFill');
  var scene    = document.getElementById('processScene');
  var label    = document.getElementById('processMaterialLabel');
  var steps    = rail.querySelectorAll('.process__step');
  var rings    = rail.querySelectorAll('.process__ring-fill');
  var details  = rail.querySelectorAll('.process__detail-item');
  var total    = steps.length;

  var INTERVAL = 2600; // ms — her aşamanın ekranda kalma süresi
  rail.style.setProperty('--process-interval', INTERVAL + 'ms');

  var reduceMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (reduceMotion) return; // CSS zaten tamamlanmış son hâli sabit gösteriyor

  var currentStage = 0;
  var timer        = null;

  var goToStage = function (stage) {
    currentStage = stage;

    if (fill) fill.style.width = ((stage - 1) / (total - 1) * 100) + '%';

    Array.prototype.forEach.call(steps, function (step) {
      var n = parseInt(step.getAttribute('data-step'), 10);
      step.classList.toggle('is-done', n < stage);
      step.classList.toggle('is-active', n === stage);
    });

    if (scene) scene.setAttribute('data-stage', stage);
    if (label) label.textContent = label.getAttribute('data-label-' + stage) || '';

    Array.prototype.forEach.call(details, function (item) {
      item.classList.toggle('is-active', parseInt(item.getAttribute('data-detail'), 10) === stage);
    });
  };

  /* Son aşamadan ilkine dönerken çizgi/halka geriye doğru "boşalıyormuş" gibi
     görünmesin diye anında (geçişsiz) sıfırlanır, bir kare sonra 1. aşama
     normal şekilde (animasyonlu) başlar. Sahne zaten çapraz solduğu için
     (opacity) onun sıfırlanmaya ihtiyacı yok. */
  var resetLoop = function () {
    if (fill) fill.classList.add('is-resetting');
    Array.prototype.forEach.call(steps, function (step) {
      step.classList.remove('is-active', 'is-done');
    });
    Array.prototype.forEach.call(rings, function (ring) {
      ring.classList.add('is-resetting');
    });
    if (fill) fill.style.width = '0%';

    void rail.offsetWidth; // reflow'u zorla — sıfırlanmış hâl gerçekten boyansın

    requestAnimationFrame(function () {
      if (fill) fill.classList.remove('is-resetting');
      Array.prototype.forEach.call(rings, function (ring) {
        ring.classList.remove('is-resetting');
      });
      goToStage(1);
    });
  };

  var tick = function () {
    if (currentStage >= total) {
      resetLoop();
    } else {
      goToStage(currentStage + 1);
    }
  };

  var startLoop = function () {
    if (timer) return;
    goToStage(currentStage || 1);
    timer = setInterval(tick, INTERVAL);
  };

  var stopLoop = function () {
    if (!timer) return;
    clearInterval(timer);
    timer = null;
  };

  /* Bölüm ekrandayken oynat, ekran dışına çıkınca duraklat (gereksiz iş yapmasın) */
  var visObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        Array.prototype.forEach.call(steps, function (step) { step.classList.add('is-visible'); });
        startLoop();
      } else {
        stopLoop();
      }
    });
  }, { threshold: .3 });

  visObserver.observe(rail);
})();
