/* VikoStream — frontend behaviours (no dependencies). */
(function () {
  "use strict";

  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

  /* ---------------- scroll reveal ---------------- */
  if ("IntersectionObserver" in window && !reduced) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            e.target.classList.add("vk-reveal--in");
            io.unobserve(e.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: "0px 0px -4% 0px" }
    );
    $$(".vk-reveal").forEach(function (el) { io.observe(el); });
  } else {
    $$(".vk-reveal").forEach(function (el) { el.classList.add("vk-reveal--in"); });
  }

  /* ---------------- mobile menu ---------------- */
  var burger = document.getElementById("vk-burger");
  var mobile = document.getElementById("vk-mobile");
  if (burger && mobile) {
    burger.addEventListener("click", function () {
      var open = mobile.classList.toggle("vk-mobile--open");
      burger.setAttribute("aria-expanded", open ? "true" : "false");
      mobile.setAttribute("aria-hidden", open ? "false" : "true");
      document.body.style.overflow = open ? "hidden" : "";
    });
    $$("a", mobile).forEach(function (a) {
      a.addEventListener("click", function () {
        mobile.classList.remove("vk-mobile--open");
        burger.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
      });
    });
  }

  /* ---------------- now-streaming ticker ---------------- */
  var ticker = document.getElementById("vk-ticker");
  if (ticker) {
    var names = [];
    $$(".vk-slide__title").forEach(function (t) { names.push(t.textContent.trim()); });
    $$(".vk-card__title").forEach(function (t) {
      var n = t.textContent.trim();
      if (names.indexOf(n) === -1) names.push(n);
    });
    names = names.slice(0, 14);
    if (names.length) {
      var html = "";
      for (var r = 0; r < 2; r++) {
        names.forEach(function (n, i) {
          html +=
            '<span class="vk-ticker__item"><span class="vk-ticker__dot"></span>' +
            (i % 3 === 0 ? "<b>▶ NOW PLAYING</b>" : "IN LIBRARY") + " — " + n + "</span>";
        });
      }
      ticker.innerHTML = html;
    } else {
      ticker.parentNode.style.display = "none";
    }
  }

  /* ---------------- hero slider ---------------- */
  var slider = document.querySelector(".vk-slider");
  if (slider) {
    var slides = $$(".vk-slide", slider);
    var dots = $$(".vk-slider__dot", slider);
    var cur = 0;
    var timer = null;
    var delay = parseInt(slider.getAttribute("data-autoplay"), 10) || 6000;

    function show(i) {
      cur = (i + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle("vk-slide--active", k === cur); });
      dots.forEach(function (d, k) { d.classList.toggle("vk-slider__dot--active", k === cur); });
    }
    function restart() {
      if (reduced || slides.length < 2) return;
      window.clearInterval(timer);
      timer = window.setInterval(function () { show(cur + 1); }, delay);
    }
    var prev = slider.querySelector(".vk-slider__arrow--prev");
    var next = slider.querySelector(".vk-slider__arrow--next");
    if (prev) prev.addEventListener("click", function () { show(cur - 1); restart(); });
    if (next) next.addEventListener("click", function () { show(cur + 1); restart(); });
    dots.forEach(function (d, k) {
      d.addEventListener("click", function () { show(k); restart(); });
    });
    slider.addEventListener("mouseenter", function () { window.clearInterval(timer); });
    slider.addEventListener("mouseleave", restart);
    restart();
  }

  /* ---------------- row scroll arrows ---------------- */
  $$(".vk-block").forEach(function (block) {
    var row = block.querySelector(".vk-row");
    if (!row) return;
    var p = block.querySelector(".vk-sec-arrow--prev");
    var n = block.querySelector(".vk-sec-arrow--next");
    var step = function () { return Math.max(320, row.clientWidth * 0.7); };
    if (p) p.addEventListener("click", function () { row.scrollBy({ left: -step(), behavior: reduced ? "auto" : "smooth" }); });
    if (n) n.addEventListener("click", function () { row.scrollBy({ left: step(), behavior: reduced ? "auto" : "smooth" }); });
  });

  /* ---------------- A–Z block ---------------- */
  var azGrid = document.getElementById("vk-alpha-grid");
  if (azGrid) {
    var letters = $$(".vk-alpha__letter", document.querySelector(".vk-alpha__bar"));
    var typeSel = document.getElementById("vk-alpha-type");
    var countEl = document.getElementById("vk-alpha-count");
    var current = "";
    var debounce = null;

    function load(letter) {
      current = letter;
      azGrid.innerHTML = '<p class="vk-loading">… ' + (window.VIKO ? VIKO.i18n.loading : "Loading") + "</p>";
      var fd = new FormData();
      fd.append("action", "viko_alphabet");
      fd.append("nonce", window.VIKO ? VIKO.nonce : "");
      fd.append("letter", letter);
      fd.append("type", typeSel ? typeSel.value : "");
      fetch(window.VIKO ? VIKO.ajax : "/wp-admin/admin-ajax.php", { method: "POST", body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.success) {
            azGrid.innerHTML = res.data.html || '<p class="vk-loading">' + (window.VIKO ? VIKO.i18n.empty : "—") + "</p>";
            if (countEl) {
              countEl.textContent = res.data.count + (res.data.count === 1 ? " title" : " titles") + (letter ? " · " + (letter === "0" ? "#" : letter) : "");
            }
            $$(".vk-reveal", azGrid).forEach(function (el) { el.classList.add("vk-reveal--in"); });
          } else {
            azGrid.innerHTML = "";
          }
        })
        .catch(function () { azGrid.innerHTML = ""; });
    }

    letters.forEach(function (b) {
      b.addEventListener("click", function () {
        letters.forEach(function (x) { x.classList.remove("vk-alpha__letter--active"); });
        b.classList.add("vk-alpha__letter--active");
        load(b.getAttribute("data-letter"));
      });
    });
    if (typeSel) {
      typeSel.addEventListener("change", function () {
        window.clearTimeout(debounce);
        debounce = window.setTimeout(function () { load(current); }, 120);
      });
    }
    load("");
  }

  /* ---------------- watch page player + episode navigation ---------------- */
  var player = document.querySelector(".vk-player");
  if (player) {
    var iframe = document.getElementById("vk-iframe");
    var servers = $$(".vk-server", player);
    var seasonSel = document.getElementById("vk-season");
    var epSel = document.getElementById("vk-episode");
    var episodic = player.getAttribute("data-episodic") === "1";
    var activeTemplate = servers.length ? servers[0].getAttribute("data-url") : "";

    var seasonMap = [];
    try { seasonMap = JSON.parse(player.getAttribute("data-seasons") || "[]"); } catch (e) { seasonMap = []; }

    var state = {
      s: seasonMap.length ? seasonMap[0].s : 1,
      e: 1,
    };

    function fill(url) {
      return url.replace(/\{season\}/g, state.s).replace(/\{episode\}/g, state.e);
    }

    function apply() {
      if (iframe && activeTemplate) iframe.src = fill(activeTemplate);
      var now = document.getElementById("vk-eps-now");
      if (now) now.textContent = "▶ Inacheza: Season " + state.s + " · Episode " + state.e;
    }

    /* episode grid + season chips */
    var grid = document.getElementById("vk-eps-grid");
    var chipRow = document.getElementById("vk-eps-seasons");

    function currentEpCount() {
      for (var i = 0; i < seasonMap.length; i++) {
        if (seasonMap[i].s === state.s) return seasonMap[i].e;
      }
      return 12;
    }

    function renderGrid() {
      if (!grid) return;
      var count = currentEpCount();
      var html = "";
      for (var e = 1; e <= count; e++) {
        html +=
          '<button type="button" class="vk-ep' + (e === state.e ? " vk-ep--active" : "") +
          '" data-ep="' + e + '" aria-label="Episode ' + e + '"><span>' + e + "</span></button>";
      }
      grid.innerHTML = html;
      grid.querySelectorAll(".vk-ep").forEach(function (b) {
        b.addEventListener("click", function () {
          state.e = parseInt(b.getAttribute("data-ep"), 10);
          syncControls();
          renderGrid();
          apply();
        });
      });
    }

    function renderChips() {
      if (!chipRow || seasonMap.length < 2) return;
      var html = "";
      seasonMap.forEach(function (s) {
        html +=
          '<button type="button" role="tab" class="vk-eps__chip' + (s.s === state.s ? " vk-eps__chip--active" : "") +
          '" data-s="' + s.s + '">Season ' + s.s + '<small>· ' + s.e + " ep</small></button>";
      });
      chipRow.innerHTML = html;
      chipRow.querySelectorAll(".vk-eps__chip").forEach(function (c) {
        c.addEventListener("click", function () {
          state.s = parseInt(c.getAttribute("data-s"), 10);
          state.e = 1;
          renderChips();
          syncControls();
          renderGrid();
          apply();
        });
      });
    }

    function syncControls() {
      if (seasonSel) seasonSel.value = state.s;
      if (epSel) epSel.value = state.e;
    }

    function syncFromSelects() {
      state.s = parseInt(seasonSel ? seasonSel.value : state.s, 10);
      state.e = parseInt(epSel ? epSel.value : state.e, 10);
    }

    if (episodic && seasonSel && epSel) {
      seasonMap.forEach(function (s) {
        seasonSel.insertAdjacentHTML("beforeend", '<option value="' + s.s + '">Season ' + s.s + "</option>");
      });
      function fillEps() {
        var keep = parseInt(epSel.value, 10) || 1;
        epSel.innerHTML = "";
        var count = currentEpCount();
        for (var e = 1; e <= count; e++) {
          epSel.insertAdjacentHTML("beforeend", '<option value="' + e + '">Ep ' + e + "</option>");
        }
        epSel.value = Math.min(keep, count);
      }
      fillEps();
      seasonSel.addEventListener("change", function () {
        state.s = parseInt(seasonSel.value, 10);
        state.e = 1;
        fillEps();
        syncControls();
        renderChips();
        renderGrid();
        apply();
      });
      epSel.addEventListener("change", function () {
        syncFromSelects();
        renderGrid();
        apply();
      });
    }

    /* prev / next episode (auto-advances seasons) */
    function stepEp(dir) {
      var count = currentEpCount();
      var next = state.e + dir;
      if (next > count) {
        var idx = -1;
        seasonMap.forEach(function (s, i) { if (s.s === state.s) idx = i; });
        if (idx >= 0 && idx < seasonMap.length - 1) {
          state.s = seasonMap[idx + 1].s;
          state.e = 1;
          if (episodic && seasonSel) {
            var opts = seasonSel.querySelectorAll("option");
            if (opts.length) {
              seasonSel.value = String(state.s);
              epSel.innerHTML = "";
              for (var e = 1; e <= seasonMap[idx + 1].e; e++) {
                epSel.insertAdjacentHTML("beforeend", '<option value="' + e + '">Ep ' + e + "</option>");
              }
            }
            renderChips();
          }
        } else {
          return;
        }
      } else if (next < 1) {
        return;
      } else {
        state.e = next;
      }
      syncControls();
      renderGrid();
      apply();
    }
    var prevBtn = document.getElementById("vk-ep-prev");
    var nextBtn = document.getElementById("vk-ep-next");
    if (prevBtn) prevBtn.addEventListener("click", function () { stepEp(-1); });
    if (nextBtn) nextBtn.addEventListener("click", function () { stepEp(1); });

    renderChips();
    renderGrid();
    if (document.getElementById("vk-eps-now")) apply();

    servers.forEach(function (btn) {
      btn.addEventListener("click", function () {
        servers.forEach(function (b) {
          b.classList.remove("vk-server--active");
          b.setAttribute("aria-selected", "false");
        });
        btn.classList.add("vk-server--active");
        btn.setAttribute("aria-selected", "true");
        activeTemplate = btn.getAttribute("data-url");
        apply();
      });
    });
  }

  /* ---------------- live search suggest ---------------- */
  var input = document.getElementById("vk-search-input");
  var suggest = document.getElementById("vk-suggest");
  if (input && suggest) {
    var deb = null;
    input.addEventListener("input", function () {
      var q = input.value.trim();
      window.clearTimeout(deb);
      if (q.length < 2) {
        suggest.hidden = true;
        return;
      }
      deb = window.setTimeout(function () {
        var fd = new FormData();
        fd.append("action", "viko_suggest");
        fd.append("nonce", window.VIKO ? VIKO.nonce : "");
        fd.append("q", q);
        fetch(window.VIKO ? VIKO.ajax : "/wp-admin/admin-ajax.php", { method: "POST", body: fd })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (!res || !res.success || !res.data.items.length) {
              suggest.hidden = true;
              return;
            }
            var html = "";
            res.data.items.forEach(function (it) {
              html +=
                '<a class="vk-suggest__item" href="' + it.url + '">' +
                (it.poster
                  ? '<img src="' + it.poster + '" alt="" loading="lazy">'
                  : '<span class="vk-suggest__ph">▶</span>') +
                '<span><span class="vk-suggest__t">' + it.title + "</span><br>" +
                '<span class="vk-suggest__m">' + (it.type || "") + (it.year ? " · " + it.year : "") + "</span></span></a>";
            });
            html += '<a class="vk-suggest__all" href="' + (window.VIKO ? VIKO.home : "/") + "?s=" + encodeURIComponent(q) + '">Ona matokeo yote →</a>';
            suggest.innerHTML = html;
            suggest.hidden = false;
          })
          .catch(function () { suggest.hidden = true; });
      }, 220);
    });
    document.addEventListener("click", function (e) {
      if (!suggest.contains(e.target) && e.target !== input) suggest.hidden = true;
    });
  }
})();
