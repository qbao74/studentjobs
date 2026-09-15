/**
 * Jobly — giao diện + điều hướng
 *
 * Tên hàm không dấu = mình viết. Đọc comment ngay trên hàm để biết dùng làm gì.
 * Tên tiếng Anh = sẵn của trình duyệt / thư viện:
 *   querySelector, addEventListener, localStorage, lucide.createIcons, JSON...
 * Class CSS (.job-card) và id HTML giữ tiếng Anh vì CSS đang dùng.
 * Dữ liệu USER / JOBS giữ tiếng Anh để sau này khớp API.
 */
/* =========================================================
   1. STORE
   Lớp mỏng trên localStorage. Mỗi key là mảng id (job hoặc company).
   Set dùng để không lưu trùng. Sau này thay bằng API + session user.
   ========================================================= */
const Kho = {
  key: {
    applied: "jobly_applied",
    skipped: "jobly_skipped",
    saved: "jobly_saved",
    followed: "jobly_followed",
  },
  /** Đọc mảng JSON; lỗi parse → [] để UI không vỡ */
  doc(key) {
    try {
      return JSON.parse(localStorage.getItem(key) || "[]");
    } catch {
      return [];
    }
  },
  /** Ghi mảng id vào localStorage. */
  ghi(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  },
  /** Danh sách job id đã ứng tuyển. */
  daUngTuyen() {
    return this.doc(this.key.applied);
  },
  /** Danh sách job id đã bỏ qua. */
  daBo() {
    return this.doc(this.key.skipped);
  },
  /** Danh sách job id đã lưu (tim). */
  daLuu() {
    return this.doc(this.key.saved);
  },
  /** Thêm job vào danh sách đã ứng tuyển. */
  themUngTuyen(id) {
    this.ghi(this.key.applied, [...new Set([...this.daUngTuyen(), Number(id)])]);
  },
  /** Thêm job vào danh sách đã bỏ qua. */
  themBo(id) {
    this.ghi(this.key.skipped, [...new Set([...this.daBo(), Number(id)])]);
  },
  /** Bật/tắt lưu job; trả về true nếu sau thao tác job đang được lưu */
  daoLuu(id) {
    const ids = new Set(this.daLuu());
    const n = Number(id);
    ids.has(n) ? ids.delete(n) : ids.add(n);
    this.ghi(this.key.saved, [...ids]);
    return ids.has(n);
  },
  /** Job này đang được lưu hay chưa. */
  dangLuu(id) {
    return this.daLuu().includes(Number(id));
  },
  /** Bật/tắt theo dõi công ty; trả về true nếu đang theo dõi. */
  daoTheoDoi(companyId) {
    const ids = new Set(this.doc(this.key.followed));
    ids.has(companyId) ? ids.delete(companyId) : ids.add(companyId);
    this.ghi(this.key.followed, [...ids]);
    return ids.has(companyId);
  },
  /** Đang theo dõi công ty này hay chưa. */
  dangTheoDoi(companyId) {
    return this.doc(this.key.followed).includes(companyId);
  },
};

/* =========================================================
   2. HELPERS
   NAV / MOBILE_NAV: menu desktop vs 5 tab dưới mobile.
   qs/qsa/param: rút gọn DOM + query string.
   thoatHtml: bắt buộc khi nhét text user/mock vào innerHTML.
   ========================================================= */
const NAV = [
  { id: "home", href: "/", label: "Home", icon: "house" },
  { id: "explore", href: "/explore", label: "Khám phá", icon: "compass" },
  { id: "saved", href: "/explore?saved=1", label: "Đã lưu", icon: "heart" },
  { id: "applications", href: "/applications", label: "Đã apply", icon: "circle-check-big" },
  { id: "chat", href: "/chat", label: "Tin nhắn", icon: "message-circle", badge: 3 },
];

// Bottom nav mobile: Home, Explore, Applications, Chat, Profile (không có mục Saved riêng)
const MOBILE_NAV = [
  NAV[0],
  NAV[1],
  NAV[3],
  NAV[4],
  { id: "profile", href: "/profile", label: "Hồ sơ", icon: "user-round" },
];

/** Chọn 1 phần tử DOM (gọn hơn querySelector). */
const chon = (sel, root = document) => root.querySelector(sel);
/** Chọn tất cả phần tử DOM khớp selector. */
const chonHet = (sel, root = document) => [...root.querySelectorAll(sel)];
/** Lấy giá trị ?ten= trên URL. */
const thamSoUrl = (name) => new URLSearchParams(location.search).get(name);
/** Vẽ lại icon Lucide sau khi innerHTML (data-lucide chỉ là placeholder) */
const veIcon = () => window.lucide?.createIcons();

/** Đổi ký tự < > & " để gán innerHTML an toàn. */
function thoatHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

/** Tạo thẻ <i data-lucide> — Lucide vẽ icon thật sau khi gọi veIcon(). */
function htmlIcon(name) {
  return `<i data-lucide="${name}"></i>`;
}

/** Ô logo công ty = chữ cái + màu brand (không dùng ảnh thật) */
function htmlLogo(company, cls = "") {
  const c = typeof company === "string" ? layCongTy(company) : company;
  return `<div class="company-logo ${cls}" style="background:${c.color}">${c.initial}</div>`;
}

/** Nút % phù hợp; data-open-match mở modal AI (ganClickToanTrang) */
function vienKhop(job, extra = "") {
  return `<button class="match-pill ${extra}" type="button" data-tone="${mucDoKhop(job.match)}" data-open-match="${job.id}">
    ${htmlIcon("thumbs-up")} ${job.match}% phù hợp
  </button>`;
}

/** Dấu tick xanh nếu công ty đã xác thực. */
function htmlXacThuc(company) {
  return company.verified ? `<span class="verified">${htmlIcon("badge-check")}</span>` : "";
}

/** Toast notification nhẹ ở đáy màn hình — tự ẩn sau ~2.2s */
function thongBao(message, iconName = "check") {
  let stack = chon("#toast-stack");
  if (!stack) {
    stack = document.createElement("div");
    stack.id = "toast-stack";
    stack.className = "toast-stack";
    document.body.appendChild(stack);
  }
  const el = document.createElement("div");
  el.className = "toast";
  el.innerHTML = `${htmlIcon(iconName)}<span>${thoatHtml(message)}</span>`;
  stack.appendChild(el);
  veIcon();
  window.setTimeout(() => el.classList.add("is-hide"), 2200);
  window.setTimeout(() => el.remove(), 2600);
}

/** Đếm số tăng dần cho AI score (ease-out cubic, 900ms) */
function chaySo(el, target, suffix = "") {
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

/** HTML vòng tròn % (AI score). CSS --p điều khiển stroke; data-score để chayVongDiem đọc. */
function vongDiem(value, id = "") {
  return `<div class="score-ring" ${id ? `id="${id}"` : ""} style="--p:0" data-score="${value}">
    <strong>0%</strong>
  </div>`;
}

/** Chạy animation stroke vòng điểm sau khi vẽ HTML. */
function chayVongDiem(root = document) {
  chonHet(".score-ring", root).forEach((ring) => {
    const v = Number(ring.dataset.score);
    requestAnimationFrame(() => {
      ring.style.setProperty("--p", v);
      chaySo(chon("strong", ring), v, "%");
    });
  });
}

/* =========================================================
   3. SHELL — sidebar / bottom nav / rail sheet
   Vẽ khung dùng chung mọi trang. body[data-page] quyết định mục nav active.
   Rail desktop = cột phải; mobile = FAB + bottom sheet.
   ========================================================= */
/** Id mục menu tương ứng trang hiện tại (data-page). */
function idMenuDangMo() {
  const page = document.body.dataset.page;
  if (page === "explore" && thamSoUrl("saved") === "1") return "saved";
  if (page === "detail" || page === "company") return "explore";
  if (page === "match") return "applications";
  return page;
}

/** Vẽ sidebar, bottom nav, rail rỗng — dùng chung mọi trang. */
function veKhung() {
  const active = idMenuDangMo();
  const sidebar = chon("#sidebar");
  const bottom = chon("#bottom-nav");

  if (sidebar) {
    sidebar.innerHTML = `
      <a class="logo" href="/">
        <span class="logo-mark">${htmlIcon("sparkles")}</span>
        <span>Jobly<small>Swipe • Match • Build</small></span>
      </a>
      <nav class="nav-list">
        ${NAV.map(
          (n) => `
          <a class="nav-item ${n.id === active ? "is-active" : ""}" href="${n.href}">
            ${htmlIcon(n.icon)}${n.label}
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
        <a class="sidebar-user" href="/profile">
          <img class="avatar" src="${USER.avatar}" alt="${USER.name}">
          <div><strong>${USER.name}</strong><span>${USER.year}</span></div>
        </a>
        <a class="icon-btn icon-btn--ghost" href="/profile" aria-label="Cài đặt">${htmlIcon("settings")}</a>
      </div>`;
    // Animate thanh progress sau khi DOM gắn (width 0 → data-w)
    requestAnimationFrame(() =>
      chonHet(".progress-bar span[data-w]").forEach((s) => (s.style.width = `${s.dataset.w}%`))
    );
  }

  if (bottom) {
    bottom.innerHTML = MOBILE_NAV.map(
      (n) => `<a class="${n.id === active ? "is-active" : ""}" href="${n.href}">${htmlIcon(n.icon)}${n.label}</a>`
    ).join("");
  }

  // Rail → bottom sheet trên màn hình hẹp (FAB sparkles + backdrop)
  const rail = chon("#rail");
  if (rail) {
    const fab = document.createElement("button");
    fab.className = "rail-fab";
    fab.type = "button";
    fab.setAttribute("aria-label", "Mở gợi ý AI");
    fab.innerHTML = htmlIcon("sparkles");
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

  // Overlay AI match dùng chung mọi trang (mở bằng [data-open-match])
  if (!chon("#match-overlay")) {
    const ov = document.createElement("div");
    ov.className = "overlay";
    ov.id = "match-overlay";
    ov.innerHTML = `<div class="modal" id="match-overlay-body"></div>`;
    document.body.appendChild(ov);
  }
}

/* =========================================================
   4. RAIL WIDGETS (tái sử dụng)
   Cột phải / bottom sheet. Mỗi trang gọi doCotPhai() với tổ hợp widget khác nhau.
   Dữ liệu vẫn từ USER / JOBS / layViecTheoId — sau này swap sang API.
   ========================================================= */
const CotPhai = {
  /** Thẻ AI Career Assistant trên cột phải. */
  hoSoAI() {
    return `
      <div class="ai-profile-card">
        <h3>${htmlIcon("sparkles")} Tối ưu hồ sơ của bạn</h3>
        <p>Cập nhật thêm kỹ năng để nhận được nhiều công việc phù hợp hơn.</p>
        <a class="btn" href="/profile">Cải thiện hồ sơ ${htmlIcon("arrow-right")}</a>
      </div>`;
  },

  /** 4 ô số: apply / phỏng vấn / hired / match. */
  thongKeNhanh() {
    const s = USER.stats;
    return `
      <section>
        <h3 class="rail-title">Thống kê nhanh</h3>
        <div class="stats-grid">
          <div class="stat-card"><span class="stat-icon is-blue">${htmlIcon("send")}</span><strong>${s.applied}</strong><span>Đã ứng tuyển</span></div>
          <div class="stat-card"><span class="stat-icon is-violet">${htmlIcon("calendar-check")}</span><strong>${s.interviewed}</strong><span>Đã phỏng vấn</span></div>
          <div class="stat-card"><span class="stat-icon is-pink">${htmlIcon("briefcase")}</span><strong>${s.hired}</strong><span>Đã nhận việc</span></div>
          <div class="stat-card"><span class="stat-icon is-mint">${htmlIcon("heart")}</span><strong>${s.avgMatch}%</strong><span>Match trung bình</span></div>
        </div>
      </section>`;
  },

  /** 3 job gợi ý; ids mặc định [2,3,5]; excludeId để ẩn job đang xem */
  viecGoiY(title = "Gợi ý hôm nay", excludeId = null, ids = [2, 3, 5]) {
    const list = ids
      .map(layViecTheoId)
      .filter((j) => j && j.id !== excludeId)
      .slice(0, 3);
    return `
      <section>
        <h3 class="rail-title">${title}</h3>
        <div class="suggest-list">
          ${list
            .map((job) => {
              const c = layCongTy(job.companyId);
              return `
              <a class="suggest-item" href="/jobs?id=${job.id}">
                ${htmlLogo(c)}
                <div>
                  <strong>${thoatHtml(job.title)}</strong>
                  <small>${thoatHtml(job.company)} · ${thoatHtml(job.salary)}</small>
                </div>
                <span class="match-pill" data-tone="${mucDoKhop(job.match)}">${job.match}%</span>
              </a>`;
            })
            .join("")}
        </div>
      </section>`;
  },

  /** Thẻ CV Match Profile / career. */
  theSuNghiep() {
    return `
      <div class="career-card">
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=400&q=80" alt="">
        <p>Không chỉ là công việc, đó là cơ hội để bạn phát triển.
          <small>Jobly đồng hành cùng bạn trên hành trình sự nghiệp.</small>
        </p>
      </div>`;
  },

  /** Khối AI Match trên trang chi tiết: vòng %, ưu/nhược, comment */
  khopAI(job) {
    return `
      <div class="card section ai-match-card" style="margin-top:0">
        <div class="ring-row">
          ${vongDiem(job.match)}
          <div>
            <strong class="title">AI Match</strong>
            <p class="muted">Dựa trên CV và kỹ năng của bạn</p>
          </div>
        </div>
        <ul class="why-list">
          ${job.whyMatch.pros.map((p) => `<li><span class="ok">✓</span>${thoatHtml(p)}</li>`).join("")}
          ${job.whyMatch.cons.map((c) => `<li><span class="warn">!</span>Thiếu: ${thoatHtml(c)}</li>`).join("")}
        </ul>
        <div class="ai-note">${thoatHtml(job.whyMatch.comment)}</div>
      </div>`;
  },

  /** Tóm tắt tiến trình đơn ứng tuyển. */
  tomTatDon(items) {
    const viewed = items.filter((a) => a.steps.find((s) => s.key === "viewed")?.status !== "upcoming").length;
    const interview = items.filter((a) => a.steps.find((s) => s.key === "interview")?.status !== "upcoming").length;
    return `
      <section>
        <h3 class="rail-title">Application Summary</h3>
        <div class="summary-list">
          <div class="summary-item"><span class="stat-icon is-blue">${htmlIcon("send")}</span><span>Đã ứng tuyển</span><strong>${USER.stats.applied}</strong></div>
          <div class="summary-item"><span class="stat-icon is-violet">${htmlIcon("eye")}</span><span>Nhà tuyển dụng đã xem</span><strong>${Math.max(viewed, 5)}</strong></div>
          <div class="summary-item"><span class="stat-icon is-mint">${htmlIcon("video")}</span><span>Phỏng vấn</span><strong>${Math.max(interview, 2)}</strong></div>
        </div>
      </section>`;
  },

  /** Vòng điểm hồ sơ AI trên rail trang Profile. */
  diemHoSo() {
    return `
      <div class="card section" style="margin-top:0">
        <div class="ring-row" style="display:flex;align-items:center;gap:16px">
          ${vongDiem(USER.profileScore)}
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

  /** Số liệu công ty trên rail. */
  thongKeCongTy(company) {
    return `
      <section>
        <h3 class="rail-title">Company statistics</h3>
        <div class="stats-grid">
          <div class="stat-card"><span class="stat-icon is-violet">${htmlIcon("briefcase")}</span><strong>${company.jobsCount}</strong><span>Việc đang mở</span></div>
          <div class="stat-card"><span class="stat-icon is-pink">${htmlIcon("star")}</span><strong>${company.rating}</strong><span>Đánh giá</span></div>
          <div class="stat-card"><span class="stat-icon is-blue">${htmlIcon("message-square")}</span><strong>${company.reviews}</strong><span>Reviews</span></div>
          <div class="stat-card"><span class="stat-icon is-mint">${htmlIcon("users")}</span><strong>${company.followers}</strong><span>Theo dõi</span></div>
        </div>
      </section>`;
  },

  /** Thẻ công ty nhỏ trên rail trang chi tiết. */
  congTyMini(company) {
    return `
      <a class="card card--hover mini-company" href="/companies?id=${company.id}">
        ${htmlLogo(company)}
        <div><strong>${thoatHtml(company.name)}</strong><small>${thoatHtml(company.size)}</small></div>
        ${htmlIcon("chevron-right")}
      </a>`;
  },
};

/** Gắn HTML vào #rail rồi animate các score-ring bên trong */
function doCotPhai(html) {
  const rail = chon("#rail");
  if (!rail) return;
  rail.innerHTML = html;
  chayVongDiem(rail);
}

/* =========================================================
   5. JOB UI — swipe card, job row, AI modal
   ruotTheViec = nội dung 1 card swipe.
   hangViec   = 1 dòng trong danh sách Explore / Company.
   Modal + save-heart dùng event delegation (ganClickToanTrang).
   ========================================================= */
/** HTML bên trong 1 thẻ vuốt (Home). */
function ruotTheViec(job) {
  const company = layCongTy(job.companyId);
  return `
    <div class="swipe-label swipe-label--apply">APPLY ↑</div>
    <div class="swipe-label swipe-label--skip">SKIP ↓</div>
    <div class="card-cover">
      <img src="${job.image}" alt="">
      ${vienKhop(job)}
      ${htmlLogo(company)}
    </div>
    <div class="card-body">
      <div class="card-identity">
        <p class="company-name">${thoatHtml(job.company)} ${htmlXacThuc(company)}</p>
        <p class="company-tagline">${thoatHtml(company.tagline)}</p>
      </div>
      <h2 class="job-title">${thoatHtml(job.title)} <span>(${thoatHtml(job.type)})</span></h2>
      <div class="meta-list">
        <div class="meta-item">${htmlIcon("wallet")}${thoatHtml(job.salary)}</div>
        <div class="meta-item">${htmlIcon("map-pin")}${thoatHtml(job.location)}</div>
        <div class="meta-item">${htmlIcon("clock")}${thoatHtml(job.hours)} • ${thoatHtml(job.type)}</div>
      </div>
      <div class="skill-row">${job.skills.map((s) => `<span class="chip">${thoatHtml(s)}</span>`).join("")}</div>
      <div class="why-box" data-open-match="${job.id}" role="button" tabindex="0">
        <strong>${htmlIcon("lightbulb")} Vì sao công việc này phù hợp?</strong>
        <ul>
          ${job.whyMatch.pros.slice(0, 4).map((p) => `<li class="ok">${thoatHtml(p)}</li>`).join("")}
          ${job.whyMatch.cons[0] ? `<li class="warn">Thiếu: ${thoatHtml(job.whyMatch.cons[0])}</li>` : ""}
        </ul>
      </div>
    </div>`;
}

/** 1 dòng việc trong danh sách Explore / company. */
function hangViec(job, i = 0) {
  const c = layCongTy(job.companyId);
  return `
    <article class="card card--hover job-row" style="animation-delay:${i * 40}ms">
      ${htmlLogo(c)}
      <div>
        <a href="/jobs?id=${job.id}"><h3>${thoatHtml(job.title)}</h3></a>
        <p class="company-name"><a href="/companies?id=${c.id}">${thoatHtml(job.company)}</a> ${htmlXacThuc(c)}</p>
        <div class="job-row-meta">
          <span>${htmlIcon("wallet")}${thoatHtml(job.salary)}</span>
          <span>${htmlIcon("map-pin")}${thoatHtml(job.location.split(",")[0])}</span>
          <span>${htmlIcon("clock")}${thoatHtml(job.hours)}</span>
          <span>${htmlIcon("briefcase")}${thoatHtml(job.type)}</span>
        </div>
        <div class="skill-row">${job.skills.slice(0, 4).map((s) => `<span class="chip">${thoatHtml(s)}</span>`).join("")}</div>
      </div>
      <div class="job-row-side">
        ${vienKhop(job)}
        <div class="job-row-actions">
          <button class="save-btn ${Kho.dangLuu(job.id) ? "is-saved" : ""}" data-save="${job.id}" type="button" aria-label="Lưu">${htmlIcon("heart")}</button>
          <a class="arrow-btn" href="/jobs?id=${job.id}" aria-label="Xem chi tiết">${htmlIcon("arrow-right")}</a>
        </div>
      </div>
    </article>`;
}

/** Mở overlay AI giải thích % match. */
function moHopKhop(jobId) {
  const job = layViecTheoId(jobId);
  if (!job) return;
  chon("#match-overlay-body").innerHTML = `
    <button class="modal-close" type="button" data-close-modal aria-label="Đóng">${htmlIcon("x")}</button>
    <div class="ring-row" style="display:flex;align-items:center;gap:16px">
      ${vongDiem(job.match)}
      <div>
        <strong style="display:block;font-size:1.05rem">${thoatHtml(job.title)}</strong>
        <p class="muted" style="font-size:.86rem">${thoatHtml(job.company)} · Vì sao phù hợp?</p>
      </div>
    </div>
    <ul class="why-list">
      ${job.whyMatch.pros.map((p) => `<li><span class="ok">✓</span>${thoatHtml(p)}</li>`).join("")}
      ${job.whyMatch.cons.map((c) => `<li><span class="warn">!</span>Thiếu: ${thoatHtml(c)}</li>`).join("")}
    </ul>
    <div class="ai-note"><strong>AI nhận xét.</strong> ${thoatHtml(job.whyMatch.comment)}</div>
    <a class="btn btn-primary btn-lg" style="margin-top:16px" href="/jobs?id=${job.id}">Xem chi tiết công việc</a>`;
  chon("#match-overlay").classList.add("is-open");
  veIcon();
  chayVongDiem(chon("#match-overlay-body"));
}

/**
 * Click toàn cục (một lần lúc boot):
 * - [data-open-match] → modal AI
 * - [data-close-modal] / click overlay / Escape → đóng
 * - [data-save] → đảo lưu job
 */
function ganClickToanTrang() {
  document.addEventListener("click", (e) => {
    const open = e.target.closest("[data-open-match]");
    if (open) {
      e.preventDefault();
      e.stopPropagation();
      moHopKhop(open.dataset.openMatch);
      return;
    }
    if (e.target.closest("[data-close-modal]") || e.target.id === "match-overlay") {
      chon("#match-overlay")?.classList.remove("is-open");
    }
    const save = e.target.closest("[data-save]");
    if (save) {
      const on = Kho.daoLuu(save.dataset.save);
      save.classList.toggle("is-saved", on);
      thongBao(on ? "Đã lưu công việc" : "Đã bỏ lưu", on ? "heart" : "heart-off");
      document.dispatchEvent(new CustomEvent("jobly:saved-changed"));
    }
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") chon("#match-overlay")?.classList.remove("is-open");
  });
}

/** Chuyển sang /jobs?id= */
function toiChiTiet(id) {
  location.href = `/jobs?id=${id}`;
}

/** Ghi applied rồi sang trang chúc mừng /match */
function toiTrangKhop(id) {
  Kho.themUngTuyen(id);
  location.href = `/match?id=${id}`;
}

/* =========================================================
   6. PAGES
   Mỗi Blade set body[data-page]; boot gọi đúng init*.
   ========================================================= */

/* ---------- HOME ----------
   Deck 3 card chồng nhau. Ẩn job đã apply/skip.
   BoVuot: ứng tuyển → /match, bỏ → đổ thẻ mới, bấm → /jobs?id=
   ---------- */
/** Job chưa apply/skip — dùng để vẽ chồng thẻ Home. */
function viecChoChongThe() {
  const hidden = new Set([...Kho.daUngTuyen(), ...Kho.daBo()]);
  return JOBS.filter((j) => !hidden.has(j.id)).sort((a, b) => b.match - a.match);
}

/** Vẽ 3 thẻ vuốt đầu tiên vào #card-stack. */
function veChongThe() {
  const stack = chon("#card-stack");
  if (!stack) return;
  const jobs = viecChoChongThe();
  if (!jobs.length) {
    // Hết bài: empty state + nút xóa skipped để xem lại (giữ applied)
    stack.outerHTML = `
      <div class="card empty-deck">
        <div class="emoji">✨</div>
        <h2>Bạn đã xem hết gợi ý hôm nay</h2>
        <p>Khám phá thêm cơ hội khác, hoặc theo dõi những việc đã ứng tuyển.</p>
        <div class="match-actions">
          <a class="btn btn-primary" href="/explore">Khám phá thêm</a>
          <button class="btn btn-ghost" type="button" id="reset-deck">Xem lại từ đầu</button>
        </div>
      </div>`;
    chon("#reset-deck")?.addEventListener("click", () => {
      localStorage.removeItem(Kho.key.skipped);
      location.reload();
    });
    return;
  }
  stack.innerHTML = jobs
    .slice(0, 3)
    .map((job, i) => `<article class="job-card ${["is-front", "is-back-1", "is-back-2"][i]}" data-job-id="${job.id}">${ruotTheViec(job)}</article>`)
    .join("");
}

/** Sau khi skip: nếu stack còn < 3 card thì nhét job tiếp theo vào đáy */
function doLaiChongThe() {
  const stack = chon("#card-stack");
  if (!stack) return;
  const shown = chonHet(".job-card", stack).map((el) => Number(el.dataset.jobId));
  const next = viecChoChongThe().find((j) => !shown.includes(j.id));
  if (next && shown.length < 3) {
    const el = document.createElement("article");
    el.className = "job-card";
    el.dataset.jobId = String(next.id);
    el.innerHTML = ruotTheViec(next);
    stack.appendChild(el);
    BoVuot.duaTheSauLenTruoc();
    veIcon();
  }
}

/** Khởi tạo trang Home: rail + chồng thẻ + BoVuot. */
function khoiTrangHome() {
  doCotPhai(`${CotPhai.hoSoAI()}${CotPhai.thongKeNhanh()}${CotPhai.viecGoiY()}${CotPhai.theSuNghiep()}`);

  chon("#btn-notify")?.addEventListener("click", (e) => {
    e.stopPropagation();
    const pop = chon("#notify-pop");
    pop.hidden = !pop.hidden;
  });
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".head-tools")) chon("#notify-pop")?.setAttribute("hidden", "");
  });

  veChongThe();
  const stack = chon("#card-stack");
  if (!stack) return;

  BoVuot.khoiTao({
    chongThe: stack,
    khiUngTuyen: (id) => toiTrangKhop(id),
    khiBo: (id) => {
      Kho.themBo(id);
      if (!viecChoChongThe().length) veChongThe();
      else doLaiChongThe();
      thongBao("Đã bỏ qua", "arrow-down");
    },
    khiBam: toiChiTiet,
  });

  chon("#btn-apply")?.addEventListener("click", () => BoVuot.ungTuyenTheHienTai());
  chon("#btn-skip")?.addEventListener("click", () => BoVuot.boTheHienTai());
  chon("#btn-detail")?.addEventListener("click", () => {
    const card = BoVuot.theMatTruoc();
    if (card) toiChiTiet(card.dataset.jobId);
  });
  // Phím ↑ apply, ↓ skip — bỏ qua khi đang gõ input
  window.addEventListener("keydown", (e) => {
    if (e.target.matches("input, textarea")) return;
    if (e.key === "ArrowUp") {
      e.preventDefault();
      BoVuot.ungTuyenTheHienTai();
    }
    if (e.key === "ArrowDown") {
      e.preventDefault();
      BoVuot.boTheHienTai();
    }
  });
}

/* ---------- EXPLORE ----------
   Danh sách job + chip lọc (all / saved / Remote / type).
   ?saved=1 mở thẳng tab Đã lưu. Search lọc theo title/company/location/skills.
   ---------- */
/** Khởi tạo trang Explore: lọc, tìm, danh sách việc. */
function khoiTrangKhamPha() {
  const list = chon("#job-list");
  if (!list) return;
  const savedOnly = thamSoUrl("saved") === "1";
  let filter = savedOnly ? "saved" : "all";
  let query = (thamSoUrl("q") || "").trim();
  const input = chon("#explore-search");
  if (input) input.value = query;

  doCotPhai(`${CotPhai.hoSoAI()}${CotPhai.viecGoiY("AI đề xuất", null, [1, 4, 6])}${CotPhai.theSuNghiep()}`);

  if (savedOnly) {
    chon("#page-title").textContent = "Đã lưu";
    chon("#page-sub").textContent = "Những cơ hội bạn muốn xem lại sau.";
  }

  const veDanhSachViec = () => {
    const q = query.toLowerCase();
    let jobs = [...JOBS].sort((a, b) => b.match - a.match);
    if (q) {
      jobs = jobs.filter((j) =>
        [j.title, j.company, j.location, ...j.skills].join(" ").toLowerCase().includes(q)
      );
    }
    if (filter === "saved") jobs = jobs.filter((j) => Kho.dangLuu(j.id));
    else if (filter === "Remote") jobs = jobs.filter((j) => j.remote);
    else if (filter !== "all") jobs = jobs.filter((j) => j.type === filter);

    chon("#result-count").textContent = `${jobs.length} công việc`;
    list.innerHTML = jobs.length
      ? jobs.map(hangViec).join("")
      : `<div class="card empty-deck" style="width:100%"><div class="emoji">🔍</div><h2>Chưa có việc nào ở đây</h2><p>${
          filter === "saved" ? "Nhấn ♡ trên một công việc để lưu lại." : "Thử bộ lọc hoặc từ khóa khác."
        }</p></div>`;
    veIcon();
  };

  chonHet(".filter-chip").forEach((chip) => {
    chip.classList.toggle("is-active", chip.dataset.filter === filter);
    chip.addEventListener("click", () => {
      chonHet(".filter-chip").forEach((c) => c.classList.remove("is-active"));
      chip.classList.add("is-active");
      filter = chip.dataset.filter;
      veDanhSachViec();
    });
  });

  input?.addEventListener("input", () => {
    query = input.value;
    veDanhSachViec();
  });
  chon("#explore-form")?.addEventListener("submit", (e) => e.preventDefault());
  document.addEventListener("jobly:saved-changed", () => filter === "saved" && veDanhSachViec());

  veDanhSachViec();
}

/* ---------- JOB DETAIL ----------
   /jobs?id= — mô tả, yêu cầu, quyền lợi, CTA apply.
   Đã apply thì nút chuyển sang /applications.
   ---------- */
/** Khởi tạo trang chi tiết 1 job. */
function khoiTrangChiTiet() {
  const root = chon("#detail-root");
  if (!root) return;
  const job = layViecTheoId(thamSoUrl("id")) || JOBS[0];
  const company = layCongTy(job.companyId);
  const applied = Kho.daUngTuyen().includes(job.id);
  document.title = `${job.title} — ${job.company} | Jobly`;

  root.innerHTML = `
    <article class="card detail-hero">
      <div class="detail-cover">
        <img src="${job.image}" alt="">
        ${vienKhop(job)}
      </div>
      <div class="detail-body">
        <div class="detail-top">
          ${htmlLogo(company)}
          <div class="job-row-actions">
            <button class="save-btn ${Kho.dangLuu(job.id) ? "is-saved" : ""}" data-save="${job.id}" type="button" aria-label="Lưu">${htmlIcon("heart")}</button>
            <button class="icon-btn" type="button" id="share-btn" aria-label="Chia sẻ">${htmlIcon("share-2")}</button>
          </div>
        </div>
        <h1 class="detail-title">${thoatHtml(job.title)} <span>· ${thoatHtml(job.type)}</span></h1>
        <p class="detail-company"><a href="/companies?id=${company.id}">${thoatHtml(job.company)}</a> ${htmlXacThuc(company)} <span>· ${thoatHtml(company.tagline)}</span></p>
        <div class="detail-meta">
          <div class="meta-item">${htmlIcon("wallet")}${thoatHtml(job.salary)}</div>
          <div class="meta-item">${htmlIcon("map-pin")}${thoatHtml(job.location)}</div>
          <div class="meta-item">${htmlIcon("clock")}${thoatHtml(job.hours)}</div>
          <div class="meta-item">${htmlIcon("briefcase")}${thoatHtml(job.type)}</div>
        </div>
      </div>
    </article>
    <section class="card section"><h2>Mô tả công việc</h2><p>${thoatHtml(job.description)}</p></section>
    <section class="card section"><h2>Yêu cầu</h2><ul class="check-list">${job.requirements.map((r) => `<li>${thoatHtml(r)}</li>`).join("")}</ul></section>
    <section class="card section"><h2>Quyền lợi</h2><ul class="check-list">${job.benefits.map((b) => `<li>${thoatHtml(b)}</li>`).join("")}</ul></section>
    <section class="card section"><h2>Skills</h2><div class="skill-row">${job.skills.map((s) => `<span class="skill-tag">${thoatHtml(s)}</span>`).join("")}</div></section>`;

  doCotPhai(`
    <div class="sticky-cta" style="display:flex;flex-direction:column;gap:16px">
      ${CotPhai.khopAI(job)}
      <button class="btn btn-primary btn-lg ${applied ? "is-done" : ""}" id="apply-now" type="button">
        ${applied ? `${htmlIcon("check")} Đã ứng tuyển` : `${htmlIcon("send")} Ứng tuyển ngay`}
      </button>
      ${CotPhai.congTyMini(company)}
      ${CotPhai.viecGoiY("Việc tương tự", job.id, [2, 7, 1, 4])}
    </div>`);

  chon("#apply-now")?.addEventListener("click", (e) => {
    const btn = e.currentTarget;
    if (btn.classList.contains("is-done")) {
      location.href = "/applications";
      return;
    }
    btn.classList.add("is-done");
    btn.innerHTML = `${htmlIcon("check")} Đã ứng tuyển`;
    veIcon();
    window.setTimeout(() => toiTrangKhop(job.id), 450);
  });
  chon("#share-btn")?.addEventListener("click", () => {
    navigator.clipboard?.writeText(location.href);
    thongBao("Đã sao chép link công việc", "link");
  });
  veIcon();
}

/* ---------- MATCH ----------
   Màn hình "đã apply thành công" + confetti.
   Đảm bảo jobId nằm trong Kho.applied dù vào thẳng URL.
   ---------- */
/** Hiệu ứng confetti trang Match. */
function phaoGiay() {
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

/** Khởi tạo trang Match (ứng tuyển thành công). */
function khoiTrangKhop() {
  const root = chon("#match-root");
  if (!root) return;
  const job = layViecTheoId(thamSoUrl("id")) || JOBS[0];
  const company = layCongTy(job.companyId);
  Kho.themUngTuyen(job.id);
  phaoGiay();

  root.innerHTML = `
    <div class="card match-hero">
      <div class="match-emoji">🎉</div>
      <h1>Tuyệt vời!</h1>
      <p>Bạn đã ứng tuyển thành công.<br><strong>${thoatHtml(job.company)}</strong> đã nhận được CV của bạn.</p>
      <a class="card card--hover match-mini" href="/jobs?id=${job.id}">
        <img src="${job.image}" alt="">
        <div><strong>${thoatHtml(job.title)}</strong><small>${thoatHtml(job.company)} · ${thoatHtml(job.location.split(",")[0])}</small></div>
        <span class="match-pill" data-tone="${mucDoKhop(job.match)}">${job.match}% phù hợp</span>
      </a>
      <div class="match-actions">
        <a class="btn btn-primary" href="/applications">${htmlIcon("route")} Xem tiến trình</a>
        <a class="btn btn-ghost" href="/">${htmlIcon("house")} Về trang chủ</a>
      </div>
    </div>
    <div class="match-steps">
      <div class="card match-step is-done"><span class="stat-icon is-mint">${htmlIcon("send")}</span>Đã gửi CV</div>
      <div class="card match-step"><span class="stat-icon is-blue">${htmlIcon("eye")}</span>NTD xem hồ sơ</div>
      <div class="card match-step"><span class="stat-icon is-violet">${htmlIcon("message-circle")}</span>Trao đổi</div>
      <div class="card match-step"><span class="stat-icon is-pink">${htmlIcon("calendar-check")}</span>Phỏng vấn</div>
    </div>`;

  doCotPhai(`
    <div class="card section" style="margin-top:0">
      <h2>Điều gì xảy ra tiếp theo?</h2>
      <ul class="why-list" style="margin-top:0">
        <li><span class="ok">✓</span>CV của bạn đã được gửi tới ${thoatHtml(company.name)}</li>
        <li><span class="ok">✓</span>AI đã đính kèm bản tóm tắt điểm mạnh của bạn</li>
        <li><span class="warn">2</span>Nhà tuyển dụng thường phản hồi trong 2–3 ngày</li>
        <li><span class="warn">3</span>Bạn sẽ nhận thông báo khi được mời phỏng vấn</li>
      </ul>
      <div class="ai-note">
        <strong>Mẹo từ AI.</strong> Bổ sung ${thoatHtml(job.whyMatch.cons[0] || "portfolio")} vào hồ sơ để tăng cơ hội được shortlist.
      </div>
      <div class="match-actions" style="margin-top:14px;justify-content:flex-start">
        <a class="btn btn-soft btn-sm" href="/chat?c=${company.id}">${htmlIcon("message-circle")} Mở tin nhắn</a>
        <a class="btn btn-soft btn-sm" href="/companies?id=${company.id}">${htmlIcon("building-2")} Xem công ty</a>
      </div>
    </div>
    ${CotPhai.thongKeNhanh()}
    ${CotPhai.viecGoiY("Tiếp tục khám phá", job.id, [2, 3, 5, 4])}
    ${CotPhai.theSuNghiep()}`);
  veIcon();
}

/* ---------- CHAT ----------
   3 cột: danh sách hội thoại | thread | panel info.
   messagesById giữ tin trong RAM (mất khi reload). Recruiter reply giả sau 1.1s.
   Màu chủ đề lưu localStorage theo conv id.
   ---------- */
/** Khởi tạo trang Chat 3 cột. */
function khoiTrangChat() {
  const layout = chon("#chat-layout");
  if (!layout) return;
  const convItems = chon("#conv-items");
  const thread = chon("#chat-thread");
  let activeId = thamSoUrl("c") || CONVERSATIONS[0].id;
  const messagesById = {};

  /** Thread đầy đủ cho Mây Creative; hội thoại khác = 3 tin giả từ conv.last */
  const dungTinNhan = (conv) => {
    if (conv.id === CHAT_THREAD.companyId) return [...CHAT_THREAD.messages];
    const job = layViecTheoId(conv.jobId);
    return [
      { id: 1, from: "recruiter", text: `Chào Bảo! Cảm ơn bạn đã ứng tuyển vị trí ${job.title}.`, time: "10:02" },
      { id: 2, from: "user", text: "Dạ vâng, em rất mong được trao đổi thêm với team.", time: "10:10" },
      { id: 3, from: "recruiter", text: conv.last, time: "10:12" },
    ];
  };

  const veDanhSachHoiThoai = () => {
    convItems.innerHTML = CONVERSATIONS.map((conv) => {
      const c = layCongTy(conv.companyId);
      return `
        <button class="conv-item ${conv.id === activeId ? "is-active" : ""}" type="button" data-conv="${conv.id}">
          ${htmlLogo(c)}
          <div><strong>${thoatHtml(c.name)}</strong><p>${thoatHtml(conv.last)}</p></div>
          <div class="conv-meta"><span>${conv.time}</span>${conv.unread ? `<span class="unread">${conv.unread}</span>` : ""}</div>
        </button>`;
    }).join("");
    chonHet(".conv-item", convItems).forEach((el) => {
      const conv = CONVERSATIONS.find((c) => c.id === el.dataset.conv);
      if (conv?.online) chon(".company-logo", el).insertAdjacentHTML("beforeend", '<span class="online-dot"></span>');
    });
    veIcon();
  };

  const veLuongTin = () => {
    const msgs = messagesById[activeId];
    const conv = CONVERSATIONS.find((c) => c.id === activeId);
    const job = layViecTheoId(conv.jobId);
    const c = layCongTy(conv.companyId);
    // Gom tin nhắn liên tiếp cùng người gửi thành 1 nhóm (kiểu Messenger)
    const groups = [];
    msgs.forEach((m) => {
      const last = groups[groups.length - 1];
      if (last && last.from === m.from) last.items.push(m);
      else groups.push({ from: m.from, items: [m] });
    });
    const htmlNhomTin = (g) => {
      const out = g.from === "user";
      const lastTime = g.items[g.items.length - 1].time;
      return `
        <div class="msg-group ${out ? "msg-group--out" : "msg-group--in"}">
          ${out ? "" : `<span class="msg-avatar" style="background:${c.color}">${c.initial}</span>`}
          ${g.items.map((m) => `<div class="bubble ${out ? "bubble--out" : "bubble--in"}">${thoatHtml(m.text)}</div>`).join("")}
          <span class="msg-time">${thoatHtml(lastTime)}</span>
        </div>`;
    };
    thread.innerHTML =
      `<div class="thread-pin">
        ${htmlLogo(c)}
        <div><strong>${thoatHtml(job.title)}</strong><small>Đang trao đổi về vị trí này · ${thoatHtml(job.salary)}</small></div>
        <a class="btn btn-soft btn-sm" href="/jobs?id=${job.id}">Xem job</a>
      </div>
      <span class="day-sep">Hôm nay</span>` + groups.map(htmlNhomTin).join("");
    thread.scrollTop = thread.scrollHeight;
    veIcon();
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
  const chiSoMau = (id) => themeMap[id] ?? DEFAULT_THEME[id] ?? 0;
  const datMau = (id, idx) => {
    themeMap[id] = idx;
    localStorage.setItem(THEME_KEY, JSON.stringify(themeMap));
  };

  const veCotThongTin = (conv) => {
    const c = layCongTy(conv.companyId);
    const job = layViecTheoId(conv.jobId);
    const app = APPLICATIONS.find((a) => a.jobId === job.id);
    const status = app?.steps.find((s) => s.status === "current")?.label || "Đã ứng tuyển";
    const photos = JOBS.filter((j) => j.id !== job.id).slice(0, 6);
    chon("#chat-info").innerHTML = `
      <div class="chat-info-head">
        ${htmlLogo(c)}
        <h3>${thoatHtml(c.name)} ${htmlXacThuc(c)}</h3>
        <p>${conv.online ? "Đang hoạt động" : `Hoạt động ${conv.time.toLowerCase()}`} · ${thoatHtml(c.location)}</p>
        <div class="chat-info-actions">
          <a href="/companies?id=${c.id}"><span class="icon-btn">${htmlIcon("building-2")}</span>Công ty</a>
          <a href="/jobs?id=${job.id}"><span class="icon-btn">${htmlIcon("briefcase")}</span>Xem job</a>
          <button type="button" id="mute-btn"><span class="icon-btn">${htmlIcon("bell")}</span>Thông báo</button>
        </div>
      </div>

      <div class="info-block">
        <div class="search-bar">${htmlIcon("search")}<input type="search" id="thread-search" placeholder="Tìm trong hội thoại" /></div>
      </div>

      <div class="info-block">
        <h4>Ứng tuyển ${htmlIcon("chevron-right")}</h4>
        <a class="job-brief" href="/applications">
          <div><strong>${thoatHtml(job.title)}</strong><small>${thoatHtml(job.salary)} · ${thoatHtml(job.type)}</small></div>
          <span class="status-tag">${thoatHtml(status)}</span>
        </a>
      </div>

      <div class="info-block">
        <h4>Màu hội thoại</h4>
        <div class="theme-dots" id="theme-dots">
          ${THEMES.map(
            (t, i) =>
              `<button class="theme-dot ${i === chiSoMau(conv.id) ? "is-active" : ""}" type="button" data-theme="${i}" style="background:${t.color}" aria-label="${t.name}" title="${t.name}"></button>`
          ).join("")}
        </div>
      </div>

      <div class="info-block">
        <h4>Ảnh đã chia sẻ <span class="muted" style="font-weight:600">${photos.length}</span></h4>
        <div class="media-grid">
          ${photos.map((j) => `<a href="/jobs?id=${j.id}"><img src="${j.image}" alt="" loading="lazy"></a>`).join("")}
        </div>
      </div>

      <div class="info-block">
        <h4>File đã chia sẻ</h4>
        <div class="file-item"><span class="cv-icon">${htmlIcon("file-text")}</span><div><strong>${USER.cvFile}</strong><small>PDF · 1.2 MB · Bạn đã gửi</small></div></div>
        <div class="file-item"><span class="cv-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">${htmlIcon("file")}</span><div><strong>JD_${thoatHtml(job.title.replace(/\s+/g, "_"))}.pdf</strong><small>PDF · 340 KB · ${thoatHtml(c.name)}</small></div></div>
      </div>

      <div class="info-block">
        <h4>Tùy chọn</h4>
        <button class="info-row" type="button" id="notif-row">${htmlIcon("bell-off")}Tắt thông báo<span class="switch" id="notif-switch"></span></button>
        <button class="info-row" type="button" data-toast="Đã ghim hội thoại">${htmlIcon("pin")}Ghim hội thoại</button>
        <button class="info-row is-danger" type="button" data-toast="Đã gửi báo cáo tới Jobly">${htmlIcon("flag")}Báo cáo</button>
      </div>`;
    veIcon();
  };

  const apMauChat = () => {
    const t = THEMES[chiSoMau(activeId)];
    const shell = chon(".chat-shell");
    shell.style.setProperty("--chat-accent", t.color);
    // Gradient rất nhẹ (màu chính → sáng hơn 10%) để bubble tươi nhưng không tối
    shell.style.setProperty(
      "--chat-accent-grad",
      `linear-gradient(135deg, ${t.color} 0%, color-mix(in srgb, ${t.color} 82%, white) 100%)`
    );
  };

  const moHoiThoai = (id) => {
    activeId = id;
    const conv = CONVERSATIONS.find((c) => c.id === id);
    const c = layCongTy(conv.companyId);
    const job = layViecTheoId(conv.jobId);
    conv.unread = 0;
    if (!messagesById[id]) messagesById[id] = dungTinNhan(conv);
    veCotThongTin(conv);
    apMauChat();
    chon("#chat-logo").style.background = c.color;
    chon("#chat-logo").textContent = c.initial;
    chon("#chat-name").innerHTML = `${thoatHtml(c.name)} ${htmlXacThuc(c)}`;
    chon("#chat-status").innerHTML = conv.online
      ? `<span class="online-dot"></span>Đang hoạt động · ${thoatHtml(job.title)}`
      : `Hoạt động ${conv.time.toLowerCase()} · ${thoatHtml(job.title)}`;
    chon("#chat-company-link").href = `/companies?id=${c.id}`;
    chon("#quick-replies").style.display = id === CHAT_THREAD.companyId ? "" : "none";
    layout.classList.remove("show-list");
    veDanhSachHoiThoai();
    veLuongTin();
  };

  const gioHienTai = () => new Date().toTimeString().slice(0, 5);

  const guiTin = (text) => {
    const t = text.trim();
    if (!t) return;
    messagesById[activeId].push({ id: Date.now(), from: "user", text: t, time: gioHienTai() });
    veLuongTin();
    chon("#chat-input").value = "";
    thread.insertAdjacentHTML("beforeend", `<div class="typing" id="typing"><i></i><i></i><i></i></div>`);
    thread.scrollTop = thread.scrollHeight;
    window.setTimeout(() => {
      chon("#typing")?.remove();
      messagesById[activeId].push({
        id: Date.now() + 1,
        from: "recruiter",
        text: "Cảm ơn bạn! Mình đã ghi nhận và sẽ gửi lịch chi tiết qua email. Hẹn gặp bạn 💜",
        time: gioHienTai(),
      });
      veLuongTin();
    }, 1100);
  };

  chon("#chat-form").addEventListener("submit", (e) => {
    e.preventDefault();
    guiTin(chon("#chat-input").value);
  });
  chon("#quick-replies").addEventListener("click", (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;
    guiTin(`Em chọn ${btn.textContent.trim()} ạ.`);
    btn.remove();
    thongBao("Đã gửi lựa chọn lịch phỏng vấn", "calendar-check");
  });
  convItems.addEventListener("click", (e) => {
    const item = e.target.closest("[data-conv]");
    if (item) moHoiThoai(item.dataset.conv);
  });
  chon("#chat-back")?.addEventListener("click", () => layout.classList.add("show-list"));

  // Panel thông tin: đổi màu chủ đề, tìm trong hội thoại, toggle drawer, toast
  const info = chon("#chat-info");
  const infoBackdrop = chon("#chat-info-backdrop");
  const batTatCotThongTin = (open) => {
    info.classList.toggle("is-open", open);
    infoBackdrop.classList.toggle("is-open", open);
  };
  chon("#chat-info-toggle")?.addEventListener("click", () => batTatCotThongTin(!info.classList.contains("is-open")));
  infoBackdrop?.addEventListener("click", () => batTatCotThongTin(false));

  info.addEventListener("click", (e) => {
    const dot = e.target.closest("[data-theme]");
    if (dot) {
      datMau(activeId, Number(dot.dataset.theme));
      chonHet(".theme-dot", info).forEach((d) => d.classList.toggle("is-active", d === dot));
      apMauChat();
      thongBao(`Đã đổi màu hội thoại: ${THEMES[chiSoMau(activeId)].name}`, "palette");
      return;
    }
    if (e.target.closest("#notif-row") || e.target.closest("#mute-btn")) {
      const sw = chon("#notif-switch");
      const on = sw.classList.toggle("is-on");
      thongBao(on ? "Đã tắt thông báo hội thoại" : "Đã bật thông báo", on ? "bell-off" : "bell");
      return;
    }
    const t = e.target.closest("[data-toast]");
    if (t) thongBao(t.dataset.toast, "check");
  });
  info.addEventListener("input", (e) => {
    if (e.target.id !== "thread-search") return;
    const q = e.target.value.toLowerCase();
    chonHet(".bubble", thread).forEach((b) => {
      const hit = !q || b.textContent.toLowerCase().includes(q);
      b.style.opacity = hit ? "" : "0.25";
    });
  });
  chon(".chat-header-actions")?.addEventListener("click", (e) => {
    const t = e.target.closest("[data-toast]");
    if (t) thongBao(t.dataset.toast, "phone");
  });
  chon("#conv-search")?.addEventListener("input", (e) => {
    const q = e.target.value.toLowerCase();
    chonHet(".conv-item", convItems).forEach((el) => {
      el.style.display = el.textContent.toLowerCase().includes(q) ? "" : "none";
    });
  });

  moHoiThoai(activeId);
  // Mobile: không có ?c= thì hiện list hội thoại trước, ẩn thread
  if (window.matchMedia("(max-width: 960px)").matches && !thamSoUrl("c")) layout.classList.add("show-list");
}

/* ---------- APPLICATIONS ----------
   Timeline mock từ APPLICATIONS (data.js) + job user vừa apply (Store) chưa có trong mock.
   ---------- */
/** Khởi tạo trang timeline đơn ứng tuyển. */
function khoiTrangDon() {
  const list = chon("#app-list");
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
  const extra = Kho.daUngTuyen()
    .filter((id) => !APPLICATIONS.some((a) => a.jobId === id))
    .map(blank);
  const items = [...extra, ...APPLICATIONS];

  list.innerHTML = items
    .map((app, i) => {
      const job = layViecTheoId(app.jobId);
      if (!job) return "";
      const c = layCongTy(job.companyId);
      const current = app.steps.find((s) => s.status === "current")?.label || "Chờ phản hồi";
      return `
        <article class="card card--hover app-card" style="animation:pageIn .4s ${i * 60}ms var(--ease) both">
          <div class="app-card-head">
            ${htmlLogo(c)}
            <div>
              <h2><a href="/jobs?id=${job.id}">${thoatHtml(job.title)}</a></h2>
              <small>${thoatHtml(job.company)} · ${thoatHtml(job.salary)}</small>
            </div>
            <span class="status-tag">${thoatHtml(current)}</span>
          </div>
          <div class="timeline">
            ${app.steps
              .map(
                (s) => `<div class="t-step is-${s.status}"><span class="t-dot"></span><div><strong>${thoatHtml(s.label)}</strong>${
                  s.status === "current" ? "<small>Đang diễn ra</small>" : ""
                }</div></div>`
              )
              .join("")}
          </div>
        </article>`;
    })
    .join("");

  doCotPhai(`${CotPhai.tomTatDon(items)}${CotPhai.viecGoiY("Cơ hội tiếp theo", null, [7, 3, 8])}${CotPhai.theSuNghiep()}`);
  veIcon();
}

/* ---------- PROFILE ----------
   Hồ sơ USER từ data.js. Nút sửa / thêm skill / CV mới chỉ toast (chưa API).
   ---------- */
/** Khởi tạo trang hồ sơ USER. */
function khoiTrangHoSo() {
  const root = chon("#profile-root");
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
            <p>${htmlIcon("map-pin")} ${USER.location}</p>
          </div>
        </div>
        <button class="btn btn-primary" type="button" id="edit-profile">${htmlIcon("pencil")} Chỉnh sửa hồ sơ</button>
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
        ${USER.skills.map((s) => `<span class="skill-tag">${htmlIcon("check")}${s}</span>`).join("")}
        <button class="skill-tag add" type="button" id="add-skill">${htmlIcon("plus")} Thêm kỹ năng</button>
      </div>
    </section>

    <section class="card section">
      <h2>CV</h2>
      <div class="cv-card">
        <span class="cv-icon">${htmlIcon("file-text")}</span>
        <div><strong>${USER.cvFile}</strong><small>${USER.cvUpdated} · PDF · 1.2 MB</small></div>
        <div class="cv-actions">
          <button class="btn btn-soft btn-sm" type="button" data-toast="Đang mở CV…">${htmlIcon("eye")} Xem CV</button>
          <button class="btn btn-ghost btn-sm" type="button" data-toast="Chọn file để cập nhật CV">${htmlIcon("upload")} Cập nhật</button>
        </div>
      </div>
    </section>

    <section class="card section">
      <h2>Thông tin</h2>
      <div class="info-grid">
        <div class="info-item">${htmlIcon("graduation-cap")}<div><small>Trường</small>${USER.school}</div></div>
        <div class="info-item">${htmlIcon("book-open")}<div><small>Ngành</small>${USER.major}</div></div>
        <div class="info-item">${htmlIcon("mail")}<div><small>Email</small>${USER.email}</div></div>
        <div class="info-item">${htmlIcon("phone")}<div><small>Điện thoại</small>${USER.phone}</div></div>
      </div>
    </section>`;

  doCotPhai(`${CotPhai.diemHoSo()}${CotPhai.thongKeNhanh()}${CotPhai.viecGoiY("Việc phù hợp với bạn", null, [1, 4, 2])}`);

  root.addEventListener("click", (e) => {
    const t = e.target.closest("[data-toast]");
    if (t) thongBao(t.dataset.toast, "info");
    if (e.target.closest("#edit-profile")) thongBao("Chế độ chỉnh sửa sẽ có trong bản kết nối API", "pencil");
    if (e.target.closest("#add-skill")) thongBao("Gợi ý: React, Motion Design", "sparkles");
  });
  veIcon();
}

/* ---------- COMPANY ----------
   /companies?id= — hero, tab Giới thiệu / Việc làm / Đánh giá, follow (Store).
   ---------- */
/** Khởi tạo trang công ty. */
function khoiTrangCongTy() {
  const root = chon("#company-root");
  if (!root) return;
  const company = layCongTy(thamSoUrl("id")) || layCongTy("may-creative");
  const jobs = viecCuaCongTy(company.id);
  const following = Kho.dangTheoDoi(company.id);
  document.title = `${company.name} — Jobly`;

  root.innerHTML = `
    <article class="card company-hero">
      <div class="company-cover"><img src="${company.cover}" alt=""></div>
      <div class="company-body">
        <div class="company-id">
          ${htmlLogo(company)}
          <div>
            <h1>${thoatHtml(company.name)} ${htmlXacThuc(company)}</h1>
            <p>${thoatHtml(company.tagline)} · ${thoatHtml(company.size)} · ${thoatHtml(company.location)}</p>
          </div>
        </div>
        <div class="job-row-actions">
          <button class="btn btn-primary follow-btn ${following ? "is-following" : ""}" id="follow-btn" type="button">
            ${following ? `${htmlIcon("check")} Đang theo dõi` : `${htmlIcon("plus")} Theo dõi`}
          </button>
          <a class="icon-btn" href="/chat?c=${company.id}" aria-label="Nhắn tin">${htmlIcon("message-circle")}</a>
        </div>
      </div>
    </article>

    <div class="tabs" role="tablist">
      <button class="tab is-active" data-tab="about" type="button">Giới thiệu</button>
      <button class="tab" data-tab="jobs" type="button">Việc làm <span class="chip" style="padding:2px 8px;margin-left:4px">${jobs.length}</span></button>
      <button class="tab" data-tab="reviews" type="button">Đánh giá</button>
    </div>

    <div class="tab-panel is-active" data-panel="about">
      <section class="card section"><h2>About company</h2><p>${thoatHtml(company.about)}</p></section>
      <section class="card section">
        <h2>Open positions</h2>
        <div class="job-list">${jobs.map(hangViec).join("")}</div>
      </section>
    </div>
    <div class="tab-panel" data-panel="jobs">
      <div class="job-list" style="margin-top:16px">${jobs.map(hangViec).join("")}</div>
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
              <strong>${thoatHtml(r.name)}</strong> <span class="muted" style="font-size:.8rem">· ${thoatHtml(r.role)}</span>
              <div class="stars">${"★".repeat(r.stars)}${"☆".repeat(5 - r.stars)}</div>
              <p>${thoatHtml(r.text)}</p>
            </div>
          </div>`
        ).join("")}
      </section>
    </div>`;

  doCotPhai(`${CotPhai.thongKeCongTy(company)}${CotPhai.viecGoiY("Việc nổi bật", null, jobs.map((j) => j.id).concat([2, 6]))}${CotPhai.theSuNghiep()}`);

  root.addEventListener("click", (e) => {
    const tab = e.target.closest("[data-tab]");
    if (tab) {
      chonHet(".tab", root).forEach((t) => t.classList.toggle("is-active", t === tab));
      chonHet(".tab-panel", root).forEach((p) => p.classList.toggle("is-active", p.dataset.panel === tab.dataset.tab));
      veIcon();
    }
    if (e.target.closest("#follow-btn")) {
      const on = Kho.daoTheoDoi(company.id);
      const btn = chon("#follow-btn");
      btn.classList.toggle("is-following", on);
      btn.innerHTML = on ? `${htmlIcon("check")} Đang theo dõi` : `${htmlIcon("plus")} Theo dõi`;
      veIcon();
      thongBao(on ? `Đang theo dõi ${company.name}` : "Đã bỏ theo dõi", on ? "bell-ring" : "bell-off");
    }
  });
  veIcon();
}

/* =========================================================
   BOOT
   layout/app.blade.php set data-page trên <body>.
   Thứ tự: shell → click toàn cục → init trang hiện tại → vẽ icon Lucide.
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {
  veKhung();
  ganClickToanTrang();
  const pages = {
    home: khoiTrangHome,
    explore: khoiTrangKhamPha,
    detail: khoiTrangChiTiet,
    match: khoiTrangKhop,
    chat: khoiTrangChat,
    applications: khoiTrangDon,
    profile: khoiTrangHoSo,
    company: khoiTrangCongTy,
  };
  pages[document.body.dataset.page]?.();
  veIcon();
});
