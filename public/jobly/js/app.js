/**
 * Jobly — UI + navigation
 *
 * Cấu trúc:
 *  1. Store        — localStorage (applied / skipped / saved / followed)
 *  2. Helpers      — qs, escapeHtml, toast, icons
 *  3. Shell        — sidebar, bottom nav, rail (bottom sheet trên mobile)
 *  4. Rail widgets — AI profile card, quick stats, suggested jobs, career card...
 *  5. Job UI       — swipe card, job row, AI match modal
 *  6. Pages        — initHome / initExplore / initDetail / initMatch / initChat /
 *                    initApplications / initProfile / initCompany
 *
 * Kết nối Laravel sau này: thay JOBS/USER/… (data.js) bằng fetch API,
 * và thay Store.* bằng POST /api/applications, /api/saved-jobs...
 */

/* =========================================================
   1. STORE
   ========================================================= */
const Store = {
  key: {
    applied: "jobly_applied",
    skipped: "jobly_skipped",
    saved: "jobly_saved",
    followed: "jobly_followed",
  },
  read(key) {
    try {
      return JSON.parse(localStorage.getItem(key) || "[]");
    } catch {
      return [];
    }
  },
  write(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  },
  applied() {
    return this.read(this.key.applied);
  },
  skipped() {
    return this.read(this.key.skipped);
  },
  saved() {
    return this.read(this.key.saved);
  },
  addApplied(id) {
    this.write(this.key.applied, [...new Set([...this.applied(), Number(id)])]);
  },
  addSkipped(id) {
    this.write(this.key.skipped, [...new Set([...this.skipped(), Number(id)])]);
  },
  toggleSaved(id) {
    const ids = new Set(this.saved());
    const n = Number(id);
    ids.has(n) ? ids.delete(n) : ids.add(n);
    this.write(this.key.saved, [...ids]);
    return ids.has(n);
  },
  isSaved(id) {
    return this.saved().includes(Number(id));
  },
  toggleFollow(companyId) {
    const ids = new Set(this.read(this.key.followed));
    ids.has(companyId) ? ids.delete(companyId) : ids.add(companyId);
    this.write(this.key.followed, [...ids]);
    return ids.has(companyId);
  },
  isFollowing(companyId) {
    return this.read(this.key.followed).includes(companyId);
  },
};

/* =========================================================
   2. HELPERS
   ========================================================= */
const NAV = [
  { id: "home", href: "index.html", label: "Home", icon: "house" },
  { id: "explore", href: "explore.html", label: "Khám phá", icon: "compass" },
  { id: "saved", href: "explore.html?saved=1", label: "Đã lưu", icon: "heart" },
  { id: "applications", href: "applications.html", label: "Đã apply", icon: "circle-check-big" },
  { id: "chat", href: "chat.html", label: "Tin nhắn", icon: "message-circle", badge: 3 },
];

const MOBILE_NAV = [
  NAV[0],
  NAV[1],
  NAV[3],
  NAV[4],
  { id: "profile", href: "profile.html", label: "Hồ sơ", icon: "user-round" },
];

const qs = (sel, root = document) => root.querySelector(sel);
const qsa = (sel, root = document) => [...root.querySelectorAll(sel)];
const param = (name) => new URLSearchParams(location.search).get(name);
const icons = () => window.lucide?.createIcons();

function escapeHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function icon(name) {
  return `<i data-lucide="${name}"></i>`;
}

function logoHtml(company, cls = "") {
  const c = typeof company === "string" ? getCompany(company) : company;
  return `<div class="company-logo ${cls}" style="background:${c.color}">${c.initial}</div>`;
}

function matchPill(job, extra = "") {
  return `<button class="match-pill ${extra}" type="button" data-tone="${matchTone(job.match)}" data-open-match="${job.id}">
    ${icon("thumbs-up")} ${job.match}% phù hợp
  </button>`;
}

function verifiedHtml(company) {
  return company.verified ? `<span class="verified">${icon("badge-check")}</span>` : "";
}

/** Toast notification nhẹ ở đáy màn hình */
function toast(message, iconName = "check") {
  let stack = qs("#toast-stack");
  if (!stack) {
    stack = document.createElement("div");
    stack.id = "toast-stack";
    stack.className = "toast-stack";
    document.body.appendChild(stack);
  }
  const el = document.createElement("div");
  el.className = "toast";
  el.innerHTML = `${icon(iconName)}<span>${escapeHtml(message)}</span>`;
  stack.appendChild(el);
  icons();
  window.setTimeout(() => el.classList.add("is-hide"), 2200);
  window.setTimeout(() => el.remove(), 2600);
}

/** Đếm số tăng dần cho AI score */
function animateNumber(el, target, suffix = "") {
  const start = performance.now();
  const dur = 900;
  const tick = (now) => {
    const p = Math.min(1, (now - start) / dur);
    const eased = 1 - Math.pow(1 - p, 3);
    el.textContent = Math.round(target * eased) + suffix;
    if (p < 1) requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
}

function scoreRing(value, id = "") {
  return `<div class="score-ring" ${id ? `id="${id}"` : ""} style="--p:0" data-score="${value}">
    <strong>0%</strong>
  </div>`;
}

function animateRings(root = document) {
  qsa(".score-ring", root).forEach((ring) => {
    const v = Number(ring.dataset.score);
    requestAnimationFrame(() => {
      ring.style.setProperty("--p", v);
      animateNumber(qs("strong", ring), v, "%");
    });
  });
}

/* =========================================================
   3. SHELL — sidebar / bottom nav / rail sheet
   ========================================================= */
function activeNavId() {
  const page = document.body.dataset.page;
  if (page === "explore" && param("saved") === "1") return "saved";
  if (page === "detail" || page === "company") return "explore";
  if (page === "match") return "applications";
  return page;
}

function renderShell() {
  const active = activeNavId();
  const sidebar = qs("#sidebar");
  const bottom = qs("#bottom-nav");

  if (sidebar) {
    sidebar.innerHTML = `
      <a class="logo" href="index.html">
        <span class="logo-mark">${icon("sparkles")}</span>
        <span>Jobly<small>Swipe • Match • Build</small></span>
      </a>
      <nav class="nav-list">
        ${NAV.map(
          (n) => `
          <a class="nav-item ${n.id === active ? "is-active" : ""}" href="${n.href}">
            ${icon(n.icon)}${n.label}
            ${n.badge ? `<span class="nav-badge">${n.badge}</span>` : ""}
          </a>`
        ).join("")}
      </nav>
      <div class="ai-card">
        <div class="ai-card-head">
          <span class="ai-bot">
            <svg viewBox="0 0 64 64" aria-hidden="true">
              <rect x="14" y="20" width="36" height="28" rx="10" fill="#6366f1"/>
              <circle cx="26" cy="33" r="4" fill="#fff"/><circle cx="38" cy="33" r="4" fill="#fff"/>
              <rect x="25" y="41" width="14" height="3" rx="1.5" fill="#2dd4bf"/>
              <rect x="30" y="10" width="4" height="10" rx="2" fill="#8b5cf6"/>
              <circle cx="32" cy="9" r="4" fill="#f472b6"/>
            </svg>
          </span>
          <strong>AI Career Assistant</strong>
        </div>
        <p>AI đang tìm việc phù hợp với bạn...</p>
        <div class="ai-progress">
          <div class="ai-progress-label"><span>CV Match Profile</span><span>${USER.profileScore}%</span></div>
          <div class="progress-bar"><span data-w="${USER.profileScore}"></span></div>
        </div>
      </div>
      <div class="sidebar-user-row">
        <a class="sidebar-user" href="profile.html">
          <img class="avatar" src="${USER.avatar}" alt="${USER.name}">
          <div><strong>${USER.name}</strong><span>${USER.year}</span></div>
        </a>
        <a class="icon-btn icon-btn--ghost" href="profile.html" aria-label="Cài đặt">${icon("settings")}</a>
      </div>`;
    requestAnimationFrame(() =>
      qsa(".progress-bar span[data-w]").forEach((s) => (s.style.width = `${s.dataset.w}%`))
    );
  }

  if (bottom) {
    bottom.innerHTML = MOBILE_NAV.map(
      (n) => `<a class="${n.id === active ? "is-active" : ""}" href="${n.href}">${icon(n.icon)}${n.label}</a>`
    ).join("");
  }

  // Rail → bottom sheet trên màn hình hẹp
  const rail = qs("#rail");
  if (rail) {
    const fab = document.createElement("button");
    fab.className = "rail-fab";
    fab.type = "button";
    fab.setAttribute("aria-label", "Mở gợi ý AI");
    fab.innerHTML = icon("sparkles");
    const backdrop = document.createElement("div");
    backdrop.className = "sheet-backdrop";
    document.body.append(fab, backdrop);
    const toggle = (open) => {
      rail.classList.toggle("is-open", open);
      backdrop.classList.toggle("is-open", open);
    };
    fab.addEventListener("click", () => toggle(!rail.classList.contains("is-open")));
    backdrop.addEventListener("click", () => toggle(false));
  }

  // Overlay AI match dùng chung
  if (!qs("#match-overlay")) {
    const ov = document.createElement("div");
    ov.className = "overlay";
    ov.id = "match-overlay";
    ov.innerHTML = `<div class="modal" id="match-overlay-body"></div>`;
    document.body.appendChild(ov);
  }
}

/* =========================================================
   4. RAIL WIDGETS (tái sử dụng)
   ========================================================= */
const Rail = {
  aiProfile() {
    return `
      <div class="ai-profile-card">
        <h3>${icon("sparkles")} Tối ưu hồ sơ của bạn</h3>
        <p>Cập nhật thêm kỹ năng để nhận được nhiều công việc phù hợp hơn.</p>
        <a class="btn" href="profile.html">Cải thiện hồ sơ ${icon("arrow-right")}</a>
      </div>`;
  },

  quickStats() {
    const s = USER.stats;
    return `
      <section>
        <h3 class="rail-title">Thống kê nhanh</h3>
        <div class="stats-grid">
          <div class="stat-card"><span class="stat-icon is-blue">${icon("send")}</span><strong>${s.applied}</strong><span>Đã ứng tuyển</span></div>
          <div class="stat-card"><span class="stat-icon is-violet">${icon("calendar-check")}</span><strong>${s.interviewed}</strong><span>Đã phỏng vấn</span></div>
          <div class="stat-card"><span class="stat-icon is-pink">${icon("briefcase")}</span><strong>${s.hired}</strong><span>Đã nhận việc</span></div>
          <div class="stat-card"><span class="stat-icon is-mint">${icon("heart")}</span><strong>${s.avgMatch}%</strong><span>Match trung bình</span></div>
        </div>
      </section>`;
  },

  suggested(title = "Gợi ý hôm nay", excludeId = null, ids = [2, 3, 5]) {
    const list = ids
      .map(getJobById)
      .filter((j) => j && j.id !== excludeId)
      .slice(0, 3);
    return `
      <section>
        <h3 class="rail-title">${title}</h3>
        <div class="suggest-list">
          ${list
            .map((job) => {
              const c = getCompany(job.companyId);
              return `
              <a class="suggest-item" href="job-detail.html?id=${job.id}">
                ${logoHtml(c)}
                <div>
                  <strong>${escapeHtml(job.title)}</strong>
                  <small>${escapeHtml(job.company)} · ${escapeHtml(job.salary)}</small>
                </div>
                <span class="match-pill" data-tone="${matchTone(job.match)}">${job.match}%</span>
              </a>`;
            })
            .join("")}
        </div>
      </section>`;
  },

  career() {
    return `
      <div class="career-card">
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=400&q=80" alt="">
        <p>Không chỉ là công việc, đó là cơ hội để bạn phát triển.
          <small>Jobly đồng hành cùng bạn trên hành trình sự nghiệp.</small>
        </p>
      </div>`;
  },

  aiMatch(job) {
    return `
      <div class="card section ai-match-card" style="margin-top:0">
        <div class="ring-row">
          ${scoreRing(job.match)}
          <div>
            <strong class="title">AI Match</strong>
            <p class="muted">Dựa trên CV và kỹ năng của bạn</p>
          </div>
        </div>
        <ul class="why-list">
          ${job.whyMatch.pros.map((p) => `<li><span class="ok">✓</span>${escapeHtml(p)}</li>`).join("")}
          ${job.whyMatch.cons.map((c) => `<li><span class="warn">!</span>Thiếu: ${escapeHtml(c)}</li>`).join("")}
        </ul>
        <div class="ai-note">${escapeHtml(job.whyMatch.comment)}</div>
      </div>`;
  },

  applicationSummary(items) {
    const viewed = items.filter((a) => a.steps.find((s) => s.key === "viewed")?.status !== "upcoming").length;
    const interview = items.filter((a) => a.steps.find((s) => s.key === "interview")?.status !== "upcoming").length;
    return `
      <section>
        <h3 class="rail-title">Application Summary</h3>
        <div class="summary-list">
          <div class="summary-item"><span class="stat-icon is-blue">${icon("send")}</span><span>Đã ứng tuyển</span><strong>${USER.stats.applied}</strong></div>
          <div class="summary-item"><span class="stat-icon is-violet">${icon("eye")}</span><span>Nhà tuyển dụng đã xem</span><strong>${Math.max(viewed, 5)}</strong></div>
          <div class="summary-item"><span class="stat-icon is-mint">${icon("video")}</span><span>Phỏng vấn</span><strong>${Math.max(interview, 2)}</strong></div>
        </div>
      </section>`;
  },

  profileScore() {
    return `
      <div class="card section" style="margin-top:0">
        <div class="ring-row" style="display:flex;align-items:center;gap:16px">
          ${scoreRing(USER.profileScore)}
          <div>
            <strong style="display:block">AI Profile Score</strong>
            <p class="muted" style="font-size:.86rem">Thêm 2 kỹ năng để tăng khả năng matching.</p>
          </div>
        </div>
        <ul class="why-list">
          <li><span class="ok">✓</span>CV đã tải lên</li>
          <li><span class="ok">✓</span>5 kỹ năng đã xác nhận</li>
          <li><span class="warn">!</span>Chưa có portfolio</li>
          <li><span class="warn">!</span>Thiếu kỹ năng: React, Motion</li>
        </ul>
      </div>`;
  },

  companyStats(company) {
    return `
      <section>
        <h3 class="rail-title">Company statistics</h3>
        <div class="stats-grid">
          <div class="stat-card"><span class="stat-icon is-violet">${icon("briefcase")}</span><strong>${company.jobsCount}</strong><span>Việc đang mở</span></div>
          <div class="stat-card"><span class="stat-icon is-pink">${icon("star")}</span><strong>${company.rating}</strong><span>Đánh giá</span></div>
          <div class="stat-card"><span class="stat-icon is-blue">${icon("message-square")}</span><strong>${company.reviews}</strong><span>Reviews</span></div>
          <div class="stat-card"><span class="stat-icon is-mint">${icon("users")}</span><strong>${company.followers}</strong><span>Theo dõi</span></div>
        </div>
      </section>`;
  },

  companyMini(company) {
    return `
      <a class="card card--hover mini-company" href="company.html?id=${company.id}">
        ${logoHtml(company)}
        <div><strong>${escapeHtml(company.name)}</strong><small>${escapeHtml(company.size)}</small></div>
        ${icon("chevron-right")}
      </a>`;
  },
};

function fillRail(html) {
  const rail = qs("#rail");
  if (!rail) return;
  rail.innerHTML = html;
  animateRings(rail);
}

/* =========================================================
   5. JOB UI — swipe card, job row, AI modal
   ========================================================= */
function cardInner(job) {
  const company = getCompany(job.companyId);
  return `
    <div class="swipe-label swipe-label--apply">APPLY ↑</div>
    <div class="swipe-label swipe-label--skip">SKIP ↓</div>
    <div class="card-cover">
      <img src="${job.image}" alt="">
      ${matchPill(job)}
      ${logoHtml(company)}
    </div>
    <div class="card-body">
      <div class="card-identity">
        <p class="company-name">${escapeHtml(job.company)} ${verifiedHtml(company)}</p>
        <p class="company-tagline">${escapeHtml(company.tagline)}</p>
      </div>
      <h2 class="job-title">${escapeHtml(job.title)} <span>(${escapeHtml(job.type)})</span></h2>
      <div class="meta-list">
        <div class="meta-item">${icon("wallet")}${escapeHtml(job.salary)}</div>
        <div class="meta-item">${icon("map-pin")}${escapeHtml(job.location)}</div>
        <div class="meta-item">${icon("clock")}${escapeHtml(job.hours)} • ${escapeHtml(job.type)}</div>
      </div>
      <div class="skill-row">${job.skills.map((s) => `<span class="chip">${escapeHtml(s)}</span>`).join("")}</div>
      <div class="why-box" data-open-match="${job.id}" role="button" tabindex="0">
        <strong>${icon("lightbulb")} Vì sao công việc này phù hợp?</strong>
        <ul>
          ${job.whyMatch.pros.slice(0, 4).map((p) => `<li class="ok">${escapeHtml(p)}</li>`).join("")}
          ${job.whyMatch.cons[0] ? `<li class="warn">Thiếu: ${escapeHtml(job.whyMatch.cons[0])}</li>` : ""}
        </ul>
      </div>
    </div>`;
}

function jobRow(job, i = 0) {
  const c = getCompany(job.companyId);
  return `
    <article class="card card--hover job-row" style="animation-delay:${i * 40}ms">
      ${logoHtml(c)}
      <div>
        <a href="job-detail.html?id=${job.id}"><h3>${escapeHtml(job.title)}</h3></a>
        <p class="company-name"><a href="company.html?id=${c.id}">${escapeHtml(job.company)}</a> ${verifiedHtml(c)}</p>
        <div class="job-row-meta">
          <span>${icon("wallet")}${escapeHtml(job.salary)}</span>
          <span>${icon("map-pin")}${escapeHtml(job.location.split(",")[0])}</span>
          <span>${icon("clock")}${escapeHtml(job.hours)}</span>
          <span>${icon("briefcase")}${escapeHtml(job.type)}</span>
        </div>
        <div class="skill-row">${job.skills.slice(0, 4).map((s) => `<span class="chip">${escapeHtml(s)}</span>`).join("")}</div>
      </div>
      <div class="job-row-side">
        ${matchPill(job)}
        <div class="job-row-actions">
          <button class="save-btn ${Store.isSaved(job.id) ? "is-saved" : ""}" data-save="${job.id}" type="button" aria-label="Lưu">${icon("heart")}</button>
          <a class="arrow-btn" href="job-detail.html?id=${job.id}" aria-label="Xem chi tiết">${icon("arrow-right")}</a>
        </div>
      </div>
    </article>`;
}

function openMatchModal(jobId) {
  const job = getJobById(jobId);
  if (!job) return;
  qs("#match-overlay-body").innerHTML = `
    <button class="modal-close" type="button" data-close-modal aria-label="Đóng">${icon("x")}</button>
    <div class="ring-row" style="display:flex;align-items:center;gap:16px">
      ${scoreRing(job.match)}
      <div>
        <strong style="display:block;font-size:1.05rem">${escapeHtml(job.title)}</strong>
        <p class="muted" style="font-size:.86rem">${escapeHtml(job.company)} · Vì sao phù hợp?</p>
      </div>
    </div>
    <ul class="why-list">
      ${job.whyMatch.pros.map((p) => `<li><span class="ok">✓</span>${escapeHtml(p)}</li>`).join("")}
      ${job.whyMatch.cons.map((c) => `<li><span class="warn">!</span>Thiếu: ${escapeHtml(c)}</li>`).join("")}
    </ul>
    <div class="ai-note"><strong>AI nhận xét.</strong> ${escapeHtml(job.whyMatch.comment)}</div>
    <a class="btn btn-primary btn-lg" style="margin-top:16px" href="job-detail.html?id=${job.id}">Xem chi tiết công việc</a>`;
  qs("#match-overlay").classList.add("is-open");
  icons();
  animateRings(qs("#match-overlay-body"));
}

function bindGlobalClicks() {
  document.addEventListener("click", (e) => {
    const open = e.target.closest("[data-open-match]");
    if (open) {
      e.preventDefault();
      e.stopPropagation();
      openMatchModal(open.dataset.openMatch);
      return;
    }
    if (e.target.closest("[data-close-modal]") || e.target.id === "match-overlay") {
      qs("#match-overlay")?.classList.remove("is-open");
    }
    const save = e.target.closest("[data-save]");
    if (save) {
      const on = Store.toggleSaved(save.dataset.save);
      save.classList.toggle("is-saved", on);
      toast(on ? "Đã lưu công việc" : "Đã bỏ lưu", on ? "heart" : "heart-off");
      document.dispatchEvent(new CustomEvent("jobly:saved-changed"));
    }
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") qs("#match-overlay")?.classList.remove("is-open");
  });
}

function goDetail(id) {
  location.href = `job-detail.html?id=${id}`;
}

function goMatch(id) {
  Store.addApplied(id);
  location.href = `match.html?id=${id}`;
}

/* =========================================================
   6. PAGES
   ========================================================= */

/* ---------- HOME ---------- */
function deckJobs() {
  const hidden = new Set([...Store.applied(), ...Store.skipped()]);
  return JOBS.filter((j) => !hidden.has(j.id)).sort((a, b) => b.match - a.match);
}

function renderStack() {
  const stack = qs("#card-stack");
  if (!stack) return;
  const jobs = deckJobs();
  if (!jobs.length) {
    stack.outerHTML = `
      <div class="card empty-deck">
        <div class="emoji">✨</div>
        <h2>Bạn đã xem hết gợi ý hôm nay</h2>
        <p>Khám phá thêm cơ hội khác, hoặc theo dõi những việc đã ứng tuyển.</p>
        <div class="match-actions">
          <a class="btn btn-primary" href="explore.html">Khám phá thêm</a>
          <button class="btn btn-ghost" type="button" id="reset-deck">Xem lại từ đầu</button>
        </div>
      </div>`;
    qs("#reset-deck")?.addEventListener("click", () => {
      localStorage.removeItem(Store.key.skipped);
      location.reload();
    });
    return;
  }
  stack.innerHTML = jobs
    .slice(0, 3)
    .map((job, i) => `<article class="job-card ${["is-front", "is-back-1", "is-back-2"][i]}" data-job-id="${job.id}">${cardInner(job)}</article>`)
    .join("");
}

function refillStack() {
  const stack = qs("#card-stack");
  if (!stack) return;
  const shown = qsa(".job-card", stack).map((el) => Number(el.dataset.jobId));
  const next = deckJobs().find((j) => !shown.includes(j.id));
  if (next && shown.length < 3) {
    const el = document.createElement("article");
    el.className = "job-card";
    el.dataset.jobId = String(next.id);
    el.innerHTML = cardInner(next);
    stack.appendChild(el);
    SwipeEngine.promoteStack();
    icons();
  }
}

function initHome() {
  fillRail(`${Rail.aiProfile()}${Rail.quickStats()}${Rail.suggested()}${Rail.career()}`);

  qs("#btn-notify")?.addEventListener("click", (e) => {
    e.stopPropagation();
    const pop = qs("#notify-pop");
    pop.hidden = !pop.hidden;
  });
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".head-tools")) qs("#notify-pop")?.setAttribute("hidden", "");
  });

  renderStack();
  const stack = qs("#card-stack");
  if (!stack) return;

  SwipeEngine.init({
    stack,
    onApply: (id) => goMatch(id),
    onSkip: (id) => {
      Store.addSkipped(id);
      if (!deckJobs().length) renderStack();
      else refillStack();
      toast("Đã bỏ qua", "arrow-down");
    },
    onClick: goDetail,
  });

  qs("#btn-apply")?.addEventListener("click", () => SwipeEngine.applyCurrent());
  qs("#btn-skip")?.addEventListener("click", () => SwipeEngine.skipCurrent());
  qs("#btn-detail")?.addEventListener("click", () => {
    const card = SwipeEngine.frontCard();
    if (card) goDetail(card.dataset.jobId);
  });
  window.addEventListener("keydown", (e) => {
    if (e.target.matches("input, textarea")) return;
    if (e.key === "ArrowUp") {
      e.preventDefault();
      SwipeEngine.applyCurrent();
    }
    if (e.key === "ArrowDown") {
      e.preventDefault();
      SwipeEngine.skipCurrent();
    }
  });
}

/* ---------- EXPLORE ---------- */
function initExplore() {
  const list = qs("#job-list");
  if (!list) return;
  const savedOnly = param("saved") === "1";
  let filter = savedOnly ? "saved" : "all";
  let query = (param("q") || "").trim();
  const input = qs("#explore-search");
  if (input) input.value = query;

  fillRail(`${Rail.aiProfile()}${Rail.suggested("AI đề xuất", null, [1, 4, 6])}${Rail.career()}`);

  if (savedOnly) {
    qs("#page-title").textContent = "Đã lưu";
    qs("#page-sub").textContent = "Những cơ hội bạn muốn xem lại sau.";
  }

  const render = () => {
    const q = query.toLowerCase();
    let jobs = [...JOBS].sort((a, b) => b.match - a.match);
    if (q) {
      jobs = jobs.filter((j) =>
        [j.title, j.company, j.location, ...j.skills].join(" ").toLowerCase().includes(q)
      );
    }
    if (filter === "saved") jobs = jobs.filter((j) => Store.isSaved(j.id));
    else if (filter === "Remote") jobs = jobs.filter((j) => j.remote);
    else if (filter !== "all") jobs = jobs.filter((j) => j.type === filter);

    qs("#result-count").textContent = `${jobs.length} công việc`;
    list.innerHTML = jobs.length
      ? jobs.map(jobRow).join("")
      : `<div class="card empty-deck" style="width:100%"><div class="emoji">🔍</div><h2>Chưa có việc nào ở đây</h2><p>${
          filter === "saved" ? "Nhấn ♡ trên một công việc để lưu lại." : "Thử bộ lọc hoặc từ khóa khác."
        }</p></div>`;
    icons();
  };

  qsa(".filter-chip").forEach((chip) => {
    chip.classList.toggle("is-active", chip.dataset.filter === filter);
    chip.addEventListener("click", () => {
      qsa(".filter-chip").forEach((c) => c.classList.remove("is-active"));
      chip.classList.add("is-active");
      filter = chip.dataset.filter;
      render();
    });
  });

  input?.addEventListener("input", () => {
    query = input.value;
    render();
  });
  qs("#explore-form")?.addEventListener("submit", (e) => e.preventDefault());
  document.addEventListener("jobly:saved-changed", () => filter === "saved" && render());

  render();
}

/* ---------- JOB DETAIL ---------- */
function initDetail() {
  const root = qs("#detail-root");
  if (!root) return;
  const job = getJobById(param("id")) || JOBS[0];
  const company = getCompany(job.companyId);
  const applied = Store.applied().includes(job.id);
  document.title = `${job.title} — ${job.company} | Jobly`;

  root.innerHTML = `
    <article class="card detail-hero">
      <div class="detail-cover">
        <img src="${job.image}" alt="">
        ${matchPill(job)}
      </div>
      <div class="detail-body">
        <div class="detail-top">
          ${logoHtml(company)}
          <div class="job-row-actions">
            <button class="save-btn ${Store.isSaved(job.id) ? "is-saved" : ""}" data-save="${job.id}" type="button" aria-label="Lưu">${icon("heart")}</button>
            <button class="icon-btn" type="button" id="share-btn" aria-label="Chia sẻ">${icon("share-2")}</button>
          </div>
        </div>
        <h1 class="detail-title">${escapeHtml(job.title)} <span>· ${escapeHtml(job.type)}</span></h1>
        <p class="detail-company"><a href="company.html?id=${company.id}">${escapeHtml(job.company)}</a> ${verifiedHtml(company)} <span>· ${escapeHtml(company.tagline)}</span></p>
        <div class="detail-meta">
          <div class="meta-item">${icon("wallet")}${escapeHtml(job.salary)}</div>
          <div class="meta-item">${icon("map-pin")}${escapeHtml(job.location)}</div>
          <div class="meta-item">${icon("clock")}${escapeHtml(job.hours)}</div>
          <div class="meta-item">${icon("briefcase")}${escapeHtml(job.type)}</div>
        </div>
      </div>
    </article>
    <section class="card section"><h2>Mô tả công việc</h2><p>${escapeHtml(job.description)}</p></section>
    <section class="card section"><h2>Yêu cầu</h2><ul class="check-list">${job.requirements.map((r) => `<li>${escapeHtml(r)}</li>`).join("")}</ul></section>
    <section class="card section"><h2>Quyền lợi</h2><ul class="check-list">${job.benefits.map((b) => `<li>${escapeHtml(b)}</li>`).join("")}</ul></section>
    <section class="card section"><h2>Skills</h2><div class="skill-row">${job.skills.map((s) => `<span class="skill-tag">${escapeHtml(s)}</span>`).join("")}</div></section>`;

  fillRail(`
    <div class="sticky-cta" style="display:flex;flex-direction:column;gap:16px">
      ${Rail.aiMatch(job)}
      <button class="btn btn-primary btn-lg ${applied ? "is-done" : ""}" id="apply-now" type="button">
        ${applied ? `${icon("check")} Đã ứng tuyển` : `${icon("send")} Ứng tuyển ngay`}
      </button>
      ${Rail.companyMini(company)}
      ${Rail.suggested("Việc tương tự", job.id, [2, 7, 1, 4])}
    </div>`);

  qs("#apply-now")?.addEventListener("click", (e) => {
    const btn = e.currentTarget;
    if (btn.classList.contains("is-done")) {
      location.href = "applications.html";
      return;
    }
    btn.classList.add("is-done");
    btn.innerHTML = `${icon("check")} Đã ứng tuyển`;
    icons();
    window.setTimeout(() => goMatch(job.id), 450);
  });
  qs("#share-btn")?.addEventListener("click", () => {
    navigator.clipboard?.writeText(location.href);
    toast("Đã sao chép link công việc", "link");
  });
  icons();
}

/* ---------- MATCH ---------- */
function burstConfetti() {
  const colors = ["#6366f1", "#2dd4bf", "#f472b6", "#f59e0b", "#8b5cf6", "#3b82f6"];
  for (let i = 0; i < 44; i += 1) {
    const el = document.createElement("i");
    el.className = "confetti-piece";
    el.style.left = `${Math.random() * 100}vw`;
    el.style.background = colors[i % colors.length];
    el.style.animationDelay = `${Math.random() * 0.35}s`;
    el.style.transform = `rotate(${Math.random() * 180}deg)`;
    document.body.appendChild(el);
    window.setTimeout(() => el.remove(), 2000);
  }
}

function initMatch() {
  const root = qs("#match-root");
  if (!root) return;
  const job = getJobById(param("id")) || JOBS[0];
  const company = getCompany(job.companyId);
  Store.addApplied(job.id);
  burstConfetti();

  root.innerHTML = `
    <div class="card match-hero">
      <div class="match-emoji">🎉</div>
      <h1>Tuyệt vời!</h1>
      <p>Bạn đã ứng tuyển thành công.<br><strong>${escapeHtml(job.company)}</strong> đã nhận được CV của bạn.</p>
      <a class="card card--hover match-mini" href="job-detail.html?id=${job.id}">
        <img src="${job.image}" alt="">
        <div><strong>${escapeHtml(job.title)}</strong><small>${escapeHtml(job.company)} · ${escapeHtml(job.location.split(",")[0])}</small></div>
        <span class="match-pill" data-tone="${matchTone(job.match)}">${job.match}% phù hợp</span>
      </a>
      <div class="match-actions">
        <a class="btn btn-primary" href="applications.html">${icon("route")} Xem tiến trình</a>
        <a class="btn btn-ghost" href="index.html">${icon("house")} Về trang chủ</a>
      </div>
    </div>
    <div class="match-steps">
      <div class="card match-step is-done"><span class="stat-icon is-mint">${icon("send")}</span>Đã gửi CV</div>
      <div class="card match-step"><span class="stat-icon is-blue">${icon("eye")}</span>NTD xem hồ sơ</div>
      <div class="card match-step"><span class="stat-icon is-violet">${icon("message-circle")}</span>Trao đổi</div>
      <div class="card match-step"><span class="stat-icon is-pink">${icon("calendar-check")}</span>Phỏng vấn</div>
    </div>`;

  fillRail(`
    <div class="card section" style="margin-top:0">
      <h2>Điều gì xảy ra tiếp theo?</h2>
      <ul class="why-list" style="margin-top:0">
        <li><span class="ok">✓</span>CV của bạn đã được gửi tới ${escapeHtml(company.name)}</li>
        <li><span class="ok">✓</span>AI đã đính kèm bản tóm tắt điểm mạnh của bạn</li>
        <li><span class="warn">2</span>Nhà tuyển dụng thường phản hồi trong 2–3 ngày</li>
        <li><span class="warn">3</span>Bạn sẽ nhận thông báo khi được mời phỏng vấn</li>
      </ul>
      <div class="ai-note">
        <strong>Mẹo từ AI.</strong> Bổ sung ${escapeHtml(job.whyMatch.cons[0] || "portfolio")} vào hồ sơ để tăng cơ hội được shortlist.
      </div>
      <div class="match-actions" style="margin-top:14px;justify-content:flex-start">
        <a class="btn btn-soft btn-sm" href="chat.html?c=${company.id}">${icon("message-circle")} Mở tin nhắn</a>
        <a class="btn btn-soft btn-sm" href="company.html?id=${company.id}">${icon("building-2")} Xem công ty</a>
      </div>
    </div>
    ${Rail.quickStats()}
    ${Rail.suggested("Tiếp tục khám phá", job.id, [2, 3, 5, 4])}
    ${Rail.career()}`);
  icons();
}

/* ---------- CHAT ---------- */
function initChat() {
  const layout = qs("#chat-layout");
  if (!layout) return;
  const convItems = qs("#conv-items");
  const thread = qs("#chat-thread");
  let activeId = param("c") || CONVERSATIONS[0].id;
  const messagesById = {};

  const buildMessages = (conv) => {
    if (conv.id === CHAT_THREAD.companyId) return [...CHAT_THREAD.messages];
    const job = getJobById(conv.jobId);
    return [
      { id: 1, from: "recruiter", text: `Chào Bảo! Cảm ơn bạn đã ứng tuyển vị trí ${job.title}.`, time: "10:02" },
      { id: 2, from: "user", text: "Dạ vâng, em rất mong được trao đổi thêm với team.", time: "10:10" },
      { id: 3, from: "recruiter", text: conv.last, time: "10:12" },
    ];
  };

  const renderConvs = () => {
    convItems.innerHTML = CONVERSATIONS.map((conv) => {
      const c = getCompany(conv.companyId);
      return `
        <button class="conv-item ${conv.id === activeId ? "is-active" : ""}" type="button" data-conv="${conv.id}">
          ${logoHtml(c)}
          <div><strong>${escapeHtml(c.name)}</strong><p>${escapeHtml(conv.last)}</p></div>
          <div class="conv-meta"><span>${conv.time}</span>${conv.unread ? `<span class="unread">${conv.unread}</span>` : ""}</div>
        </button>`;
    }).join("");
    qsa(".conv-item", convItems).forEach((el) => {
      const conv = CONVERSATIONS.find((c) => c.id === el.dataset.conv);
      if (conv?.online) qs(".company-logo", el).insertAdjacentHTML("beforeend", '<span class="online-dot"></span>');
    });
    icons();
  };

  const paint = () => {
    const msgs = messagesById[activeId];
    const conv = CONVERSATIONS.find((c) => c.id === activeId);
    const job = getJobById(conv.jobId);
    const c = getCompany(conv.companyId);
    // Gom tin nhắn liên tiếp cùng người gửi thành 1 nhóm (kiểu Messenger)
    const groups = [];
    msgs.forEach((m) => {
      const last = groups[groups.length - 1];
      if (last && last.from === m.from) last.items.push(m);
      else groups.push({ from: m.from, items: [m] });
    });
    const groupHtml = (g) => {
      const out = g.from === "user";
      const lastTime = g.items[g.items.length - 1].time;
      return `
        <div class="msg-group ${out ? "msg-group--out" : "msg-group--in"}">
          ${out ? "" : `<span class="msg-avatar" style="background:${c.color}">${c.initial}</span>`}
          ${g.items.map((m) => `<div class="bubble ${out ? "bubble--out" : "bubble--in"}">${escapeHtml(m.text)}</div>`).join("")}
          <span class="msg-time">${escapeHtml(lastTime)}</span>
        </div>`;
    };
    thread.innerHTML =
      `<div class="thread-pin">
        ${logoHtml(c)}
        <div><strong>${escapeHtml(job.title)}</strong><small>Đang trao đổi về vị trí này · ${escapeHtml(job.salary)}</small></div>
        <a class="btn btn-soft btn-sm" href="job-detail.html?id=${job.id}">Xem job</a>
      </div>
      <span class="day-sep">Hôm nay</span>` + groups.map(groupHtml).join("");
    thread.scrollTop = thread.scrollHeight;
    icons();
  };

  /* Panel thông tin hội thoại (cột phải) */
  // Bảng màu chủ đề sáng, rực kiểu Messenger — mỗi hội thoại nhớ màu riêng
  const THEMES = [
    { name: "Jobly Violet", color: "#7c5cff" },
    { name: "Messenger Blue", color: "#0a84ff" },
    { name: "Purple", color: "#a033ff" },
    { name: "Pink", color: "#ff2e93" },
    { name: "Red", color: "#ff3b4e" },
    { name: "Orange", color: "#ff7a1a" },
    { name: "Yellow", color: "#ffb800" },
    { name: "Green", color: "#1ed760" },
    { name: "Teal", color: "#00c2b2" },
    { name: "Cyan", color: "#1ecbe1" },
    { name: "Lime", color: "#a3d900" },
    { name: "Navy", color: "#3457d5" },
  ];
  const THEME_KEY = "jobly_chat_theme";
  const themeMap = (() => {
    try {
      return JSON.parse(localStorage.getItem(THEME_KEY) || "{}");
    } catch {
      return {};
    }
  })();
  // Mặc định: mỗi hội thoại một màu khác nhau cho sinh động
  const DEFAULT_THEME = { "may-creative": 0, techwind: 1, datanest: 8, novastack: 3 };
  const themeOf = (id) => themeMap[id] ?? DEFAULT_THEME[id] ?? 0;
  const setTheme = (id, idx) => {
    themeMap[id] = idx;
    localStorage.setItem(THEME_KEY, JSON.stringify(themeMap));
  };

  const renderInfo = (conv) => {
    const c = getCompany(conv.companyId);
    const job = getJobById(conv.jobId);
    const app = APPLICATIONS.find((a) => a.jobId === job.id);
    const status = app?.steps.find((s) => s.status === "current")?.label || "Đã ứng tuyển";
    const photos = JOBS.filter((j) => j.id !== job.id).slice(0, 6);
    qs("#chat-info").innerHTML = `
      <div class="chat-info-head">
        ${logoHtml(c)}
        <h3>${escapeHtml(c.name)} ${verifiedHtml(c)}</h3>
        <p>${conv.online ? "Đang hoạt động" : `Hoạt động ${conv.time.toLowerCase()}`} · ${escapeHtml(c.location)}</p>
        <div class="chat-info-actions">
          <a href="company.html?id=${c.id}"><span class="icon-btn">${icon("building-2")}</span>Công ty</a>
          <a href="job-detail.html?id=${job.id}"><span class="icon-btn">${icon("briefcase")}</span>Xem job</a>
          <button type="button" id="mute-btn"><span class="icon-btn">${icon("bell")}</span>Thông báo</button>
        </div>
      </div>

      <div class="info-block">
        <div class="search-bar">${icon("search")}<input type="search" id="thread-search" placeholder="Tìm trong hội thoại" /></div>
      </div>

      <div class="info-block">
        <h4>Ứng tuyển ${icon("chevron-right")}</h4>
        <a class="job-brief" href="applications.html">
          <div><strong>${escapeHtml(job.title)}</strong><small>${escapeHtml(job.salary)} · ${escapeHtml(job.type)}</small></div>
          <span class="status-tag">${escapeHtml(status)}</span>
        </a>
      </div>

      <div class="info-block">
        <h4>Màu hội thoại</h4>
        <div class="theme-dots" id="theme-dots">
          ${THEMES.map(
            (t, i) =>
              `<button class="theme-dot ${i === themeOf(conv.id) ? "is-active" : ""}" type="button" data-theme="${i}" style="background:${t.color}" aria-label="${t.name}" title="${t.name}"></button>`
          ).join("")}
        </div>
      </div>

      <div class="info-block">
        <h4>Ảnh đã chia sẻ <span class="muted" style="font-weight:600">${photos.length}</span></h4>
        <div class="media-grid">
          ${photos.map((j) => `<a href="job-detail.html?id=${j.id}"><img src="${j.image}" alt="" loading="lazy"></a>`).join("")}
        </div>
      </div>

      <div class="info-block">
        <h4>File đã chia sẻ</h4>
        <div class="file-item"><span class="cv-icon">${icon("file-text")}</span><div><strong>${USER.cvFile}</strong><small>PDF · 1.2 MB · Bạn đã gửi</small></div></div>
        <div class="file-item"><span class="cv-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">${icon("file")}</span><div><strong>JD_${escapeHtml(job.title.replace(/\s+/g, "_"))}.pdf</strong><small>PDF · 340 KB · ${escapeHtml(c.name)}</small></div></div>
      </div>

      <div class="info-block">
        <h4>Tùy chọn</h4>
        <button class="info-row" type="button" id="notif-row">${icon("bell-off")}Tắt thông báo<span class="switch" id="notif-switch"></span></button>
        <button class="info-row" type="button" data-toast="Đã ghim hội thoại">${icon("pin")}Ghim hội thoại</button>
        <button class="info-row is-danger" type="button" data-toast="Đã gửi báo cáo tới Jobly">${icon("flag")}Báo cáo</button>
      </div>`;
    icons();
  };

  const applyTheme = () => {
    const t = THEMES[themeOf(activeId)];
    const shell = qs(".chat-shell");
    shell.style.setProperty("--chat-accent", t.color);
    // Gradient rất nhẹ (màu chính → sáng hơn 10%) để bubble tươi nhưng không tối
    shell.style.setProperty(
      "--chat-accent-grad",
      `linear-gradient(135deg, ${t.color} 0%, color-mix(in srgb, ${t.color} 82%, white) 100%)`
    );
  };

  const openConv = (id) => {
    activeId = id;
    const conv = CONVERSATIONS.find((c) => c.id === id);
    const c = getCompany(conv.companyId);
    const job = getJobById(conv.jobId);
    conv.unread = 0;
    if (!messagesById[id]) messagesById[id] = buildMessages(conv);
    renderInfo(conv);
    applyTheme();
    qs("#chat-logo").style.background = c.color;
    qs("#chat-logo").textContent = c.initial;
    qs("#chat-name").innerHTML = `${escapeHtml(c.name)} ${verifiedHtml(c)}`;
    qs("#chat-status").innerHTML = conv.online
      ? `<span class="online-dot"></span>Đang hoạt động · ${escapeHtml(job.title)}`
      : `Hoạt động ${conv.time.toLowerCase()} · ${escapeHtml(job.title)}`;
    qs("#chat-company-link").href = `company.html?id=${c.id}`;
    qs("#quick-replies").style.display = id === CHAT_THREAD.companyId ? "" : "none";
    layout.classList.remove("show-list");
    renderConvs();
    paint();
  };

  const now = () => new Date().toTimeString().slice(0, 5);

  const send = (text) => {
    const t = text.trim();
    if (!t) return;
    messagesById[activeId].push({ id: Date.now(), from: "user", text: t, time: now() });
    paint();
    qs("#chat-input").value = "";
    thread.insertAdjacentHTML("beforeend", `<div class="typing" id="typing"><i></i><i></i><i></i></div>`);
    thread.scrollTop = thread.scrollHeight;
    window.setTimeout(() => {
      qs("#typing")?.remove();
      messagesById[activeId].push({
        id: Date.now() + 1,
        from: "recruiter",
        text: "Cảm ơn bạn! Mình đã ghi nhận và sẽ gửi lịch chi tiết qua email. Hẹn gặp bạn 💜",
        time: now(),
      });
      paint();
    }, 1100);
  };

  qs("#chat-form").addEventListener("submit", (e) => {
    e.preventDefault();
    send(qs("#chat-input").value);
  });
  qs("#quick-replies").addEventListener("click", (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;
    send(`Em chọn ${btn.textContent.trim()} ạ.`);
    btn.remove();
    toast("Đã gửi lựa chọn lịch phỏng vấn", "calendar-check");
  });
  convItems.addEventListener("click", (e) => {
    const item = e.target.closest("[data-conv]");
    if (item) openConv(item.dataset.conv);
  });
  qs("#chat-back")?.addEventListener("click", () => layout.classList.add("show-list"));

  // Panel thông tin: đổi màu chủ đề, tìm trong hội thoại, toggle drawer, toast
  const info = qs("#chat-info");
  const infoBackdrop = qs("#chat-info-backdrop");
  const toggleInfo = (open) => {
    info.classList.toggle("is-open", open);
    infoBackdrop.classList.toggle("is-open", open);
  };
  qs("#chat-info-toggle")?.addEventListener("click", () => toggleInfo(!info.classList.contains("is-open")));
  infoBackdrop?.addEventListener("click", () => toggleInfo(false));

  info.addEventListener("click", (e) => {
    const dot = e.target.closest("[data-theme]");
    if (dot) {
      setTheme(activeId, Number(dot.dataset.theme));
      qsa(".theme-dot", info).forEach((d) => d.classList.toggle("is-active", d === dot));
      applyTheme();
      toast(`Đã đổi màu hội thoại: ${THEMES[themeOf(activeId)].name}`, "palette");
      return;
    }
    if (e.target.closest("#notif-row") || e.target.closest("#mute-btn")) {
      const sw = qs("#notif-switch");
      const on = sw.classList.toggle("is-on");
      toast(on ? "Đã tắt thông báo hội thoại" : "Đã bật thông báo", on ? "bell-off" : "bell");
      return;
    }
    const t = e.target.closest("[data-toast]");
    if (t) toast(t.dataset.toast, "check");
  });
  info.addEventListener("input", (e) => {
    if (e.target.id !== "thread-search") return;
    const q = e.target.value.toLowerCase();
    qsa(".bubble", thread).forEach((b) => {
      const hit = !q || b.textContent.toLowerCase().includes(q);
      b.style.opacity = hit ? "" : "0.25";
    });
  });
  qs(".chat-header-actions")?.addEventListener("click", (e) => {
    const t = e.target.closest("[data-toast]");
    if (t) toast(t.dataset.toast, "phone");
  });
  qs("#conv-search")?.addEventListener("input", (e) => {
    const q = e.target.value.toLowerCase();
    qsa(".conv-item", convItems).forEach((el) => {
      el.style.display = el.textContent.toLowerCase().includes(q) ? "" : "none";
    });
  });

  openConv(activeId);
  if (window.matchMedia("(max-width: 960px)").matches && !param("c")) layout.classList.add("show-list");
}

/* ---------- APPLICATIONS ---------- */
function initApplications() {
  const list = qs("#app-list");
  if (!list) return;

  const blank = (jobId) => ({
    jobId,
    steps: [
      { key: "applied", label: "Đã ứng tuyển", status: "done" },
      { key: "viewed", label: "NTD đã xem", status: "upcoming" },
      { key: "shortlist", label: "Shortlist", status: "upcoming" },
      { key: "interview", label: "Phỏng vấn", status: "upcoming" },
      { key: "result", label: "Kết quả", status: "upcoming" },
    ],
  });
  const extra = Store.applied()
    .filter((id) => !APPLICATIONS.some((a) => a.jobId === id))
    .map(blank);
  const items = [...extra, ...APPLICATIONS];

  list.innerHTML = items
    .map((app, i) => {
      const job = getJobById(app.jobId);
      if (!job) return "";
      const c = getCompany(job.companyId);
      const current = app.steps.find((s) => s.status === "current")?.label || "Chờ phản hồi";
      return `
        <article class="card card--hover app-card" style="animation:pageIn .4s ${i * 60}ms var(--ease) both">
          <div class="app-card-head">
            ${logoHtml(c)}
            <div>
              <h2><a href="job-detail.html?id=${job.id}">${escapeHtml(job.title)}</a></h2>
              <small>${escapeHtml(job.company)} · ${escapeHtml(job.salary)}</small>
            </div>
            <span class="status-tag">${escapeHtml(current)}</span>
          </div>
          <div class="timeline">
            ${app.steps
              .map(
                (s) => `<div class="t-step is-${s.status}"><span class="t-dot"></span><div><strong>${escapeHtml(s.label)}</strong>${
                  s.status === "current" ? "<small>Đang diễn ra</small>" : ""
                }</div></div>`
              )
              .join("")}
          </div>
        </article>`;
    })
    .join("");

  fillRail(`${Rail.applicationSummary(items)}${Rail.suggested("Cơ hội tiếp theo", null, [7, 3, 8])}${Rail.career()}`);
  icons();
}

/* ---------- PROFILE ---------- */
function initProfile() {
  const root = qs("#profile-root");
  if (!root) return;
  root.innerHTML = `
    <article class="card profile-hero">
      <div class="profile-cover"></div>
      <div class="profile-body">
        <div class="profile-id">
          <img class="profile-avatar" src="${USER.avatar}" alt="${USER.name}">
          <div>
            <h1>${USER.name}</h1>
            <p>${USER.year} · ${USER.school}</p>
            <p>${icon("map-pin")} ${USER.location}</p>
          </div>
        </div>
        <button class="btn btn-primary" type="button" id="edit-profile">${icon("pencil")} Chỉnh sửa hồ sơ</button>
      </div>
    </article>

    <div class="profile-stats">
      <div class="card profile-stat"><strong>${USER.stats.applied}</strong><span>Applications</span></div>
      <div class="card profile-stat"><strong>${USER.stats.interviewed}</strong><span>Interviews</span></div>
      <div class="card profile-stat"><strong>${USER.stats.hired}</strong><span>Offers</span></div>
    </div>

    <section class="card section"><h2>About me</h2><p>${USER.bio}</p></section>

    <section class="card section">
      <h2>Kỹ năng</h2>
      <div class="skill-row">
        ${USER.skills.map((s) => `<span class="skill-tag">${icon("check")}${s}</span>`).join("")}
        <button class="skill-tag add" type="button" id="add-skill">${icon("plus")} Thêm kỹ năng</button>
      </div>
    </section>

    <section class="card section">
      <h2>CV</h2>
      <div class="cv-card">
        <span class="cv-icon">${icon("file-text")}</span>
        <div><strong>${USER.cvFile}</strong><small>${USER.cvUpdated} · PDF · 1.2 MB</small></div>
        <div class="cv-actions">
          <button class="btn btn-soft btn-sm" type="button" data-toast="Đang mở CV…">${icon("eye")} Xem CV</button>
          <button class="btn btn-ghost btn-sm" type="button" data-toast="Chọn file để cập nhật CV">${icon("upload")} Cập nhật</button>
        </div>
      </div>
    </section>

    <section class="card section">
      <h2>Thông tin</h2>
      <div class="info-grid">
        <div class="info-item">${icon("graduation-cap")}<div><small>Trường</small>${USER.school}</div></div>
        <div class="info-item">${icon("book-open")}<div><small>Ngành</small>${USER.major}</div></div>
        <div class="info-item">${icon("mail")}<div><small>Email</small>${USER.email}</div></div>
        <div class="info-item">${icon("phone")}<div><small>Điện thoại</small>${USER.phone}</div></div>
      </div>
    </section>`;

  fillRail(`${Rail.profileScore()}${Rail.quickStats()}${Rail.suggested("Việc phù hợp với bạn", null, [1, 4, 2])}`);

  root.addEventListener("click", (e) => {
    const t = e.target.closest("[data-toast]");
    if (t) toast(t.dataset.toast, "info");
    if (e.target.closest("#edit-profile")) toast("Chế độ chỉnh sửa sẽ có trong bản kết nối API", "pencil");
    if (e.target.closest("#add-skill")) toast("Gợi ý: React, Motion Design", "sparkles");
  });
  icons();
}

/* ---------- COMPANY ---------- */
function initCompany() {
  const root = qs("#company-root");
  if (!root) return;
  const company = getCompany(param("id")) || getCompany("may-creative");
  const jobs = jobsByCompany(company.id);
  const following = Store.isFollowing(company.id);
  document.title = `${company.name} — Jobly`;

  root.innerHTML = `
    <article class="card company-hero">
      <div class="company-cover"><img src="${company.cover}" alt=""></div>
      <div class="company-body">
        <div class="company-id">
          ${logoHtml(company)}
          <div>
            <h1>${escapeHtml(company.name)} ${verifiedHtml(company)}</h1>
            <p>${escapeHtml(company.tagline)} · ${escapeHtml(company.size)} · ${escapeHtml(company.location)}</p>
          </div>
        </div>
        <div class="job-row-actions">
          <button class="btn btn-primary follow-btn ${following ? "is-following" : ""}" id="follow-btn" type="button">
            ${following ? `${icon("check")} Đang theo dõi` : `${icon("plus")} Theo dõi`}
          </button>
          <a class="icon-btn" href="chat.html?c=${company.id}" aria-label="Nhắn tin">${icon("message-circle")}</a>
        </div>
      </div>
    </article>

    <div class="tabs" role="tablist">
      <button class="tab is-active" data-tab="about" type="button">Giới thiệu</button>
      <button class="tab" data-tab="jobs" type="button">Việc làm <span class="chip" style="padding:2px 8px;margin-left:4px">${jobs.length}</span></button>
      <button class="tab" data-tab="reviews" type="button">Đánh giá</button>
    </div>

    <div class="tab-panel is-active" data-panel="about">
      <section class="card section"><h2>About company</h2><p>${escapeHtml(company.about)}</p></section>
      <section class="card section">
        <h2>Open positions</h2>
        <div class="job-list">${jobs.map(jobRow).join("")}</div>
      </section>
    </div>
    <div class="tab-panel" data-panel="jobs">
      <div class="job-list" style="margin-top:16px">${jobs.map(jobRow).join("")}</div>
    </div>
    <div class="tab-panel" data-panel="reviews">
      <section class="card section">
        <div class="rating-big">
          <strong>${company.rating}</strong>
          <div><div class="stars" style="color:#f59e0b">★★★★★</div><p class="muted">${company.reviews} đánh giá từ sinh viên đã làm việc</p></div>
        </div>
      </section>
      <section class="card section">
        ${REVIEWS.map(
          (r) => `
          <div class="review">
            <span class="review-avatar">${r.name[0]}</span>
            <div>
              <strong>${escapeHtml(r.name)}</strong> <span class="muted" style="font-size:.8rem">· ${escapeHtml(r.role)}</span>
              <div class="stars">${"★".repeat(r.stars)}${"☆".repeat(5 - r.stars)}</div>
              <p>${escapeHtml(r.text)}</p>
            </div>
          </div>`
        ).join("")}
      </section>
    </div>`;

  fillRail(`${Rail.companyStats(company)}${Rail.suggested("Việc nổi bật", null, jobs.map((j) => j.id).concat([2, 6]))}${Rail.career()}`);

  root.addEventListener("click", (e) => {
    const tab = e.target.closest("[data-tab]");
    if (tab) {
      qsa(".tab", root).forEach((t) => t.classList.toggle("is-active", t === tab));
      qsa(".tab-panel", root).forEach((p) => p.classList.toggle("is-active", p.dataset.panel === tab.dataset.tab));
      icons();
    }
    if (e.target.closest("#follow-btn")) {
      const on = Store.toggleFollow(company.id);
      const btn = qs("#follow-btn");
      btn.classList.toggle("is-following", on);
      btn.innerHTML = on ? `${icon("check")} Đang theo dõi` : `${icon("plus")} Theo dõi`;
      icons();
      toast(on ? `Đang theo dõi ${company.name}` : "Đã bỏ theo dõi", on ? "bell-ring" : "bell-off");
    }
  });
  icons();
}

/* =========================================================
   BOOT
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {
  renderShell();
  bindGlobalClicks();
  const pages = {
    home: initHome,
    explore: initExplore,
    detail: initDetail,
    match: initMatch,
    chat: initChat,
    applications: initApplications,
    profile: initProfile,
    company: initCompany,
  };
  pages[document.body.dataset.page]?.();
  icons();
});
