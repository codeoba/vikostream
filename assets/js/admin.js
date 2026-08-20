/* VikoStream — admin tools: import engine UI, blocks manager, server ping. */
(function () {
  "use strict";

  var A = window.VIKO_ADMIN;
  if (!A) return;

  function post(data) {
    var fd = new FormData();
    fd.append("nonce", A.nonce);
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(A.ajax, { method: "POST", body: fd }).then(function (r) { return r.json(); });
  }

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
  }

  /* =============== tabs =============== */
  var tabs = document.querySelectorAll(".viko-tabs .nav-tab");
  var panels = document.querySelectorAll(".viko-tab-panel");
  tabs.forEach(function (t) {
    t.addEventListener("click", function (e) {
      e.preventDefault();
      tabs.forEach(function (x) { x.classList.remove("nav-tab-active"); });
      t.classList.add("nav-tab-active");
      panels.forEach(function (p) { p.hidden = p.id !== t.getAttribute("href").slice(1); });
      if (t.getAttribute("href") === "#tab-log") loadLog();
    });
  });

  /* =============== results rendering =============== */
  var state = { mode: "search", page: 1, items: [], total_pages: 1 };

  function status(msg, cls) {
    var el = document.getElementById("viko-status");
    if (!el) return;
    el.textContent = msg || "";
    el.className = "viko-status" + (cls ? " " + cls : "");
  }

  function recoChecked() {
    var el = document.getElementById("viko-reco");
    return !!(el && el.checked);
  }

  function cardHtml(it, i) {
    var exists = it.exists;
    return (
      '<div class="viko-card" data-i="' + i + '">' +
      '<label class="viko-card__check"><input type="checkbox" class="viko-pick" ' + (exists ? "disabled" : "") + '></label>' +
      '<span class="viko-card__poster">' +
      (it.poster ? '<img src="' + esc(it.poster) + '" alt="" loading="lazy">' : '<span class="viko-card__ph">▶</span>') +
      '<span class="viko-card__type viko-card__type--' + esc(it.type) + '">' + esc(it.type) + "</span>" +
      "</span>" +
      '<span class="viko-card__info">' +
      '<strong class="viko-card__title">' + esc(it.title) + "</strong>" +
      '<span class="viko-card__meta">' + esc(it.year || "—") + " · ★ " + esc(it.rating || "—") + (it.origin ? " · " + esc(it.origin) : "") + "</span>" +
      '<span class="viko-card__genres">' + esc((it.genres || []).slice(0, 3).join(", ")) + "</span>" +
      '<span class="viko-card__actions">' +
      '<button class="button button-primary viko-import-one" ' + (exists ? "disabled" : "") + ">" +
      (exists ? A.i18n.exists : "Import") + "</button>" +
      "</span></span></div>"
    );
  }

  function render(targetSel, pagerSel) {
    var target = document.querySelector(targetSel);
    var pager = document.querySelector(pagerSel);
    if (!target) return;
    if (!state.items.length) {
      target.innerHTML = '<p class="viko-empty-msg">' + esc(state.emptyMsg || "Hakuna matokeo.") + "</p>";
      if (pager) pager.innerHTML = "";
      updateBulkButtons();
      return;
    }
    target.innerHTML = state.items.map(cardHtml).join("");
    /* pager */
    if (pager) {
      var html = "";
      if (state.page > 1) html += '<button class="button viko-page" data-page="' + (state.page - 1) + '">‹ Prev</button>';
      html += '<span class="viko-pageinfo">Ukurasa ' + state.page + " / " + state.total_pages + " · " + state.total + " matokeo</span>";
      if (state.page < state.total_pages) html += '<button class="button viko-page" data-page="' + (state.page + 1) + '">Next ›</button>';
      pager.innerHTML = html;
      pager.querySelectorAll(".viko-page").forEach(function (b) {
        b.addEventListener("click", function () { run(state.mode, parseInt(b.getAttribute("data-page"), 10)); });
      });
    }
    updateBulkButtons();
  }

  function updateBulkButtons() {
    var picks = document.querySelectorAll(".viko-pick:checked").length;
    var bSel = document.getElementById("viko-import-selected");
    var bPage = document.getElementById("viko-import-page");
    if (bSel) {
      bSel.disabled = picks === 0;
      bSel.textContent = "Import selected (" + picks + ")";
    }
    if (bPage) bPage.disabled = state.items.length === 0;
  }

  document.addEventListener("change", function (e) {
    if (e.target.classList && e.target.classList.contains("viko-pick")) updateBulkButtons();
  });

  /* =============== search / discover =============== */
  function run(mode, page) {
    state.mode = mode;
    state.page = page || 1;
    var data = { action: "viko_search", mode: mode, page: state.page };
    if (mode === "discover") {
      data.type = document.getElementById("viko-d-type").value;
      data.genre = document.getElementById("viko-d-genre").value;
      data.year = document.getElementById("viko-d-year").value;
    } else {
      data.q = document.getElementById("viko-q").value;
      if (!data.q.trim()) { status("Andika jina au IMDb ID.", "err"); return; }
    }
    status(A.i18n.importing.replace("…", "") + "… inatafuta…");
    var isD = mode === "discover";
    var target = isD ? "#viko-results-d" : "#viko-results";
    document.querySelector(target).innerHTML = '<p class="viko-empty-msg">Inatafuta TMDB…</p>';
    post(data).then(function (res) {
      if (!res.success) {
        status("Hitilafu: " + (res.data && res.data.msg ? res.data.msg : "—"), "err");
        document.querySelector(target).innerHTML = "";
        return;
      }
      state.items = res.data.results || [];
      state.total_pages = res.data.total_pages || 1;
      state.total = res.data.total || 0;
      status(state.total + " matokeo yamepatikana ✓");
      render(target, isD ? "#viko-pager-d" : "#viko-pager");
    });
  }

  var btnSearch = document.getElementById("viko-do-search");
  var btnDiscover = document.getElementById("viko-do-discover");
  var inpQ = document.getElementById("viko-q");
  if (btnSearch) btnSearch.addEventListener("click", function () { run("search", 1); });
  if (inpQ) inpQ.addEventListener("keydown", function (e) { if (e.key === "Enter") run("search", 1); });
  if (btnDiscover) btnDiscover.addEventListener("click", function () { run("discover", 1); });

  /* =============== single import =============== */
  document.addEventListener("click", function (e) {
    var btn = e.target.closest ? e.target.closest(".viko-import-one") : null;
    if (!btn || btn.disabled) return;
    var card = btn.closest(".viko-card");
    var i = parseInt(card.getAttribute("data-i"), 10);
    var item = state.items[i];
    btn.disabled = true;
    btn.textContent = A.i18n.importing;
    post({ action: "viko_import", item: JSON.stringify(item), recommended: recoChecked() ? "1" : "" }).then(function (res) {
      if (res.success) {
        btn.textContent = A.i18n.imported;
        btn.classList.remove("button-primary");
        btn.classList.add("viko-done");
        var pick = card.querySelector(".viko-pick");
        if (pick) pick.disabled = true;
        status("✓ " + item.title + " imeingizwa");
      } else {
        var dup = res.data && res.data.code === "viko_dup";
        btn.textContent = dup ? A.i18n.exists : A.i18n.error;
        status("Hitilafu: " + (res.data && res.data.msg ? res.data.msg : "—"), "err");
      }
    });
  });

  /* =============== bulk =============== */
  var prog = document.getElementById("viko-bulk-progress");
  var progBar = prog ? prog.querySelector(".viko-progress__bar") : null;
  var summary = document.getElementById("viko-bulk-summary");

  function selectedItems() {
    var out = [];
    document.querySelectorAll(".viko-pick:checked").forEach(function (c) {
      var card = c.closest(".viko-card");
      var i = parseInt(card.getAttribute("data-i"), 10);
      if (state.items[i]) out.push(state.items[i]);
    });
    return out;
  }

  var bSel = document.getElementById("viko-import-selected");
  var bPage = document.getElementById("viko-import-page");
  if (bSel) {
    bSel.addEventListener("click", function () {
      var items = selectedItems();
      if (!items.length) return;
      bulkSend(items, summary);
    });
  }
  if (bPage) {
    bPage.addEventListener("click", function () {
      if (!state.items.length) return;
      bulkSend(state.items, summary);
    });
  }

  function bulkSend(items, outEl) {
    if (prog) prog.hidden = false;
    if (progBar) progBar.style.width = "30%";
    status("Bulk import: " + items.length + " titles…");
    post({ action: "viko_bulk", items: JSON.stringify(items), recommended: recoChecked() ? "1" : "" }).then(function (res) {
      if (progBar) progBar.style.width = "100%";
      window.setTimeout(function () { if (prog) prog.hidden = true; }, 700);
      if (res.success) {
        var d = res.data;
        var msg = "✓ Zimeingia: " + d.ok + " · Duplikati: " + d.dup + " · Zilizoshindwa: " + d.fail;
        if (outEl) outEl.innerHTML = '<div class="notice notice-success inline"><p>' + msg + "</p></div>";
        status(msg);
      } else {
        status("Hitilafu kwenye bulk import", "err");
      }
    });
  }

  var bGo = document.getElementById("viko-bulk-go");
  var bListSummary = document.getElementById("viko-bulk-list-summary");
  if (bGo) {
    bGo.addEventListener("click", function () {
      var lines = document.getElementById("viko-bulk-list").value;
      if (!lines.trim()) return;
      bGo.disabled = true;
      bGo.textContent = "Ina-resolve + import… (inaweza kuchukua muda)";
      if (bListSummary) bListSummary.innerHTML = "";
      post({ action: "viko_bulk_resolve", lines: lines, recommended: recoChecked() ? "1" : "" }).then(function (res) {
        bGo.disabled = false;
        bGo.textContent = "Resolve + Import orodha";
        if (res.success) {
          var d = res.data;
          var html = '<div class="notice notice-success inline"><p>✓ Zimeingia: ' + d.ok + " · Duplikati: " + d.dup + " · Zilizoshindwa: " + d.fail + "</p>";
          if (d.missing && d.missing.length) {
            html += "<p><strong>Zisizopatikana:</strong> " + esc(d.missing.join(", ")) + "</p>";
          }
          html += "</div>";
          if (bListSummary) bListSummary.innerHTML = html;
          status("Bulk list imekamilika ✓");
        } else {
          status("Hitilafu: " + (res.data && res.data.msg ? res.data.msg : ""), "err");
        }
      });
    });
  }

  /* =============== direct asian drama scraper =============== */
  var bDramaGo = document.getElementById("viko-do-import-drama");
  var dramaStatus = document.getElementById("viko-drama-status");
  if (bDramaGo) {
    bDramaGo.addEventListener("click", function () {
      var urlInput = document.getElementById("viko-drama-url");
      var dramaUrl = urlInput ? urlInput.value.trim() : "";
      if (!dramaUrl) {
        alert("Tafadhali weka link ya DramaCool kwanza.");
        return;
      }
      bDramaGo.disabled = true;
      bDramaGo.textContent = "Ina-scrape drama + episodes…";
      if (dramaStatus) dramaStatus.innerHTML = '<p class="description">Inapakua taarifa za drama na orodha ya vipindi vyote…</p>';

      post({ action: "viko_import_dramacool", url: dramaUrl }).then(function (res) {
        bDramaGo.disabled = false;
        bDramaGo.textContent = "Scrape & Import Drama";
        if (res.success) {
          var d = res.data;
          if (dramaStatus) {
            dramaStatus.innerHTML = '<div class="notice notice-success inline"><p><strong>✓ Imeingizwa Kikamilifu:</strong> ' + esc(d.title) + ' (' + d.total_eps + ' Episodes) — <a href="' + esc(d.view) + '" target="_blank">Tazama Hapa ↗</a></p></div>';
          }
          if (urlInput) urlInput.value = "";
          status("Drama imeingizwa: " + d.title + " ✓");
        } else {
          if (dramaStatus) {
            dramaStatus.innerHTML = '<div class="notice notice-error inline"><p><strong>✗ Hitilafu:</strong> ' + esc(res.data && res.data.msg ? res.data.msg : "Imeshindwa ku-import drama hii.") + '</p></div>';
          }
          status("Hitilafu ya scraping", "err");
        }
      }).catch(function(err) {
        bDramaGo.disabled = false;
        bDramaGo.textContent = "Scrape & Import Drama";
        if (dramaStatus) dramaStatus.innerHTML = '<div class="notice notice-error inline"><p>Hitilafu ya mtandao: ' + esc(err.message) + '</p></div>';
      });
    });
  }

  /* =============== log =============== */
  function loadLog(clear) {
    var body = document.querySelector("#viko-log-table tbody");
    if (!body) return;
    post({ action: "viko_log", clear: clear ? "1" : "" }).then(function (res) {
      if (!res.success) return;
      var log = res.data.log || [];
      body.innerHTML = log.length
        ? log.map(function (l) {
            return (
              "<tr><td>" + esc(l.time) + "</td><td>" +
              (l.post_id ? '<a href="post.php?post=' + l.post_id + '&action=edit">' + esc(l.title) + "</a>" : esc(l.title)) +
              '</td><td>' + (l.ok ? '<span style="color:#00a32a;font-weight:700">✓ OK</span>' : '<span style="color:#d63638;font-weight:700">✗</span>') + "</td></tr>"
            );
          }).join("")
        : '<tr><td colspan="3">Log ni tupu.</td></tr>';
    });
  }
  var bLogR = document.getElementById("viko-log-refresh");
  var bLogC = document.getElementById("viko-log-clear");
  if (bLogR) bLogR.addEventListener("click", function () { loadLog(false); });
  if (bLogC) bLogC.addEventListener("click", function () { loadLog(true); });

  /* =============== blocks manager =============== */
  var blocksForm = document.getElementById("viko-blocks-form");
  if (blocksForm) {
    var actionEl = document.getElementById("viko-action");
    var idxEl = document.getElementById("viko-idx");
    var dirEl = document.getElementById("viko-dir");

    blocksForm.querySelectorAll(".viko-move").forEach(function (b) {
      b.addEventListener("click", function () {
        actionEl.value = "move";
        idxEl.value = b.getAttribute("data-idx");
        dirEl.value = b.getAttribute("data-dir");
      });
    });
    blocksForm.querySelectorAll(".viko-del").forEach(function (b) {
      b.addEventListener("click", function (e) {
        if (!window.confirm("Futa block hii?")) { e.preventDefault(); return; }
        actionEl.value = "delete";
        idxEl.value = b.getAttribute("data-idx");
      });
    });
    var addBtn = document.getElementById("viko-add-block");
    if (addBtn) addBtn.addEventListener("click", function () { actionEl.value = "add"; });
    var resetBtn = document.getElementById("viko-reset-blocks");
    if (resetBtn) {
      resetBtn.addEventListener("click", function (e) {
        if (!window.confirm("Rudisha blocks za default?")) { e.preventDefault(); return; }
        actionEl.value = "reset";
      });
    }
    /* default submit = update */
    blocksForm.addEventListener("submit", function () {
      if (!["move", "delete", "add", "reset"].includes(actionEl.value)) actionEl.value = "update";
    });
  }

  /* =============== repair all titles (types + episodes) =============== */
  var repairBtn = document.getElementById("viko-repair");
  if (repairBtn) {
    repairBtn.addEventListener("click", function () {
      if (!window.confirm("Rekebisha types + seasons/episodes za TITLES ZOTE? (inatumia TMDB — inaweza kuchukua dakika 1–3)")) return;
      repairBtn.disabled = true;
      status("Inarekebisha titles zote… subiri kidogo");
      post({ action: "viko_repair" }).then(function (res) {
        repairBtn.disabled = false;
        if (res.success) {
          var d = res.data;
          var msg =
            "✓ Repair imekamilika — Types zilizorekebishwa: " + d.types +
            " / " + d.total + " · Zilizosynciwa episodes: " + d.synced +
            " · Zilizoshindwa: " + d.failed;
          status(msg);
          if (summary) summary.innerHTML = '<div class="notice notice-success inline"><p>' + msg + "</p></div>";
        } else {
          status("Hitilafu: " + (res.data && res.data.msg ? res.data.msg : ""), "err");
        }
      });
    });
  }

  /* =============== metabox: sync seasons/episodes/cast =============== */
  var syncBtn = document.getElementById("viko-sync-eps");
  if (syncBtn) {
    syncBtn.addEventListener("click", function () {
      syncBtn.disabled = true;
      var resEl = document.getElementById("viko-sync-result");
      if (resEl) resEl.textContent = " Ina-sync kutoka TMDB…";
      post({ action: "viko_sync_eps", post_id: syncBtn.getAttribute("data-post") }).then(function (res) {
        syncBtn.disabled = false;
        if (res.success) {
          var d = res.data;
          if (resEl) {
            resEl.innerHTML =
              ' <strong style="color:#00a32a">✓ ' + d.seasons + " seasons · " + d.episodes +
              " episodes · cast " + d.cast + " · type: " + d.type + "</strong>" +
              " <em>(save post kuona kwenye watch page)</em>";
          }
        } else if (resEl) {
          resEl.innerHTML = ' <strong style="color:#d63638">✗ ' + (res.data && res.data.msg ? res.data.msg : "hitilafu") + "</strong>";
        }
      });
    });
  }

  /* =============== metabox: ping servers =============== */
  var pingBtn = document.getElementById("viko-test-servers");
  if (pingBtn) {
    pingBtn.addEventListener("click", function () {
      var urls = [];
      try { urls = JSON.parse(pingBtn.getAttribute("data-urls") || "[]"); } catch (e) { urls = []; }
      if (!urls.length) return;
      pingBtn.disabled = true;
      var resEl = document.getElementById("viko-test-result");
      if (resEl) resEl.textContent = " Inapiga ping servers…";
      post({ action: "viko_ping", urls: urls }).then(function (res) {
        pingBtn.disabled = false;
        if (res.success) {
          var ok = Object.keys(res.data).filter(function (k) { return res.data[k]; }).length;
          if (resEl) {
            resEl.innerHTML =
              ' <strong style="color:' + (ok ? "#00a32a" : "#d63638") + '">' +
              ok + "/" + urls.length + " servers ziko live</strong>";
          }
        }
      });
    });
  }
})();
