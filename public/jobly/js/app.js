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
   Đơn, việc đã lưu, công ty theo dõi: nằm trên server (JOBLY + API).
   Riêng "bỏ qua" chỉ là lựa chọn hiển thị nên để localStorage, tách theo từng tài khoản.
   ========================================================= */
const Kho = {
  /** Key localStorage của danh sách bỏ qua — mỗi tài khoản (hoặc khách) một key riêng. */
  keyBo() {
    return `jobly_skipped_${USER?.id ?? "guest"}`;
  },
  /** Danh sách job id đã ứng tuyển (theo đơn thật). */
  daUngTuyen() {
    return APPLICATIONS.map((a) => a.jobId);
  },
  /** Danh sách job id đã bỏ qua. Lỗi parse → [] để UI không vỡ. */
  daBo() {
    try {
      return JSON.parse(localStorage.getItem(this.keyBo()) || "[]");
    } catch {
      return [];
    }
  },
  /** Thêm job vào danh sách đã bỏ qua. */
  themBo(id) {
    localStorage.setItem(this.keyBo(), JSON.stringify([...new Set([...this.daBo(), Number(id)])]));
  },
  /** Xóa danh sách bỏ qua để xem lại từ đầu. */
  xoaBo() {
    localStorage.removeItem(this.keyBo());
  },
  /** Job này đang được lưu hay chưa. */
  dangLuu(id) {
    return JOBLY.saved.includes(Number(id));
  },
  /** Bật/tắt lưu job qua API; trả về trạng thái mới (true = đang lưu). */
  async daoLuu(id) {
    const n = Number(id);
    const data = await Api.goi("POST", `/api/jobs/${n}/save`);
    JOBLY.saved = data.saved ? [...JOBLY.saved, n] : JOBLY.saved.filter((x) => x !== n);
    return data.saved;
  },
  /** Đang theo dõi công ty này hay chưa. */
  dangTheoDoi(slug) {
    return JOBLY.following.includes(slug);
  },
  /** Bật/tắt theo dõi công ty qua API; trả về { following, followers }. */
  async daoTheoDoi(slug) {
    const data = await Api.goi("POST", `/api/companies/${encodeURIComponent(slug)}/follow`);
    JOBLY.following = data.following ? [...JOBLY.following, slug] : JOBLY.following.filter((x) => x !== slug);
    return data;
  },
  /** Ứng tuyển qua API, thêm đơn mới vào APPLICATIONS rồi trả về đơn đó. */
  async ungTuyen(id) {
    const data = await Api.goi("POST", `/api/jobs/${Number(id)}/apply`);
    APPLICATIONS.unshift(data.application);
    return data.application;
  },
};

/* =========================================================
   2. HELPERS
   NAV / MOBILE_NAV: menu desktop vs 5 tab dưới mobile.
   qs/qsa/param: rút gọn DOM + query string.
   thoatHtml: bắt buộc khi nhét text người dùng nhập vào innerHTML.
   auth: true = mục cần đăng nhập; khách bấm vào sẽ mở popup thay vì chuyển trang.
   ========================================================= */
const NAV = [
  { id: "home", href: "/", label: "Home", icon: "house" },
  { id: "explore", href: "/explore", label: "Khám phá", icon: "compass" },
  { id: "saved", href: "/explore?saved=1", label: "Đã lưu", icon: "heart", auth: true },
  { id: "applications", href: "/applications", label: "Đã apply", icon: "circle-check-big", auth: true },
  { id: "chat", href: "/chat", label: "Tin nhắn", icon: "message-circle", auth: true, badge: JOBLY.unread },
];

// Bottom nav mobile: Home, Explore, Applications, Chat, Profile (không có mục Saved riêng)
const MOBILE_NAV = [
  NAV[0],
  NAV[1],
  NAV[3],
  NAV[4],
  { id: "profile", href: "/profile", label: "Hồ sơ", icon: "user-round", auth: true },
];

/** Thuộc tính cho link cần đăng nhập — ganClickToanTrang bắt data-auth-link. */
const thuocTinhLink = (n) => (n.auth && !laSinhVien() ? `data-auth-link="${n.href}"` : "");

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

/** Nút % phù hợp; data-open-match mở modal AI (ganClickToanTrang). Chưa có điểm (khách) → mời đăng nhập. */
function vienKhop(job, extra = "") {
  if (job.match == null) {
    return `<button class="match-pill ${extra}" type="button" data-tone="mid" data-open-match="${job.id}">
      ${htmlIcon("sparkles")} ${laSinhVien() ? "Đang tính điểm" : "Xem độ phù hợp"}
    </button>`;
  }
  return `<button class="match-pill ${extra}" type="button" data-tone="${mucDoKhop(job.match)}" data-open-match="${job.id}">
    ${htmlIcon("thumbs-up")} ${job.match}% phù hợp
  </button>`;
}

/** Nhãn % nhỏ (không bấm được) cho danh sách gợi ý; không có điểm thì không hiện. */
function nhanKhop(job, suffix = "") {
  return job.match == null ? "" : `<span class="match-pill" data-tone="${mucDoKhop(job.match)}">${job.match}%${suffix}</span>`;
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
          <a class="nav-item ${n.id === active ? "is-active" : ""}" href="${n.href}" ${thuocTinhLink(n)}>
            ${htmlIcon(n.icon)}${n.label}
            ${n.badge ? `<span class="nav-badge">${n.badge}</span>` : ""}
          </a>`
        ).join("")}
      </nav>
      ${USER ? theAISidebar() : theKhachSidebar()}`;
    // Animate thanh progress sau khi DOM gắn (width 0 → data-w)
    requestAnimationFrame(() =>
      chonHet(".progress-bar span[data-w]").forEach((s) => (s.style.width = `${s.dataset.w}%`))
    );
  }

  if (bottom) {
    bottom.innerHTML = MOBILE_NAV.map(
      (n) => `<a class="${n.id === active ? "is-active" : ""}" href="${n.href}" ${thuocTinhLink(n)}>${htmlIcon(n.icon)}${n.label}</a>`
    ).join("");
  }
  veRailVaOverlay();
}

/** Khối cuối sidebar cho khách: giới thiệu + nút đăng nhập / đăng ký (mở popup). */
function theKhachSidebar() {
  return `
      <div class="ai-card">
        <div class="ai-card-head"><strong>${htmlIcon("sparkles")} Jobly cho sinh viên</strong></div>
        <p>Tải CV lên để AI chấm độ phù hợp với từng công việc.</p>
        <div class="guest-actions">
          <button class="btn btn-primary btn-sm" type="button" data-open-auth="login">Đăng nhập</button>
          <button class="btn btn-ghost btn-sm" type="button" data-open-auth="register">Tạo tài khoản</button>
        </div>
      </div>`;
}

/** Khối cuối sidebar cho sinh viên: điểm hồ sơ + tên + nút đăng xuất. */
function theAISidebar() {
  return `
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
        <p>${USER.cv ? "Điểm hồ sơ càng cao, gợi ý càng chính xác." : "Tải CV lên để AI hiểu kỹ năng của bạn."}</p>
        <div class="ai-progress">
          <div class="ai-progress-label"><span>Điểm hồ sơ</span><span>${USER.profileScore}%</span></div>
          <div class="progress-bar"><span data-w="${USER.profileScore}"></span></div>
        </div>
      </div>
      <div class="sidebar-user-row">
        <a class="sidebar-user" href="/profile">
          <img class="avatar" src="${anhDaiDien(USER)}" alt="">
          <div><strong>${thoatHtml(USER.name)}</strong><span>${thoatHtml(USER.year || USER.school || "Sinh viên")}</span></div>
        </a>
        <form method="POST" action="/logout">
          <input type="hidden" name="_token" value="${Api.token()}">
          <button class="icon-btn icon-btn--ghost" type="submit" aria-label="Đăng xuất" title="Đăng xuất">${htmlIcon("log-out")}</button>
        </form>
      </div>`;
}

/** Rail → bottom sheet trên màn hình hẹp + overlay AI match dùng chung. */
function veRailVaOverlay() {
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
    if (!USER) {
      return `
      <div class="ai-profile-card">
        <h3>${htmlIcon("sparkles")} AI chấm độ phù hợp</h3>
        <p>Đăng nhập và tải CV lên để biết bạn hợp với việc nào, còn thiếu kỹ năng gì.</p>
        <button class="btn" type="button" data-open-auth="register">Tạo tài khoản ${htmlIcon("arrow-right")}</button>
      </div>`;
    }
    const goiY = USER.profileMissing[0];
    return `
      <div class="ai-profile-card">
        <h3>${htmlIcon("sparkles")} Tối ưu hồ sơ của bạn</h3>
        <p>${goiY ? `Gợi ý: ${thoatHtml(goiY)}.` : "Hồ sơ đã đầy đủ. Cập nhật kỹ năng mới khi bạn học thêm."}</p>
        <a class="btn" href="/profile">Cải thiện hồ sơ ${htmlIcon("arrow-right")}</a>
      </div>`;
  },

  /** 4 ô số: apply / phỏng vấn / hired / match. Khách không có số liệu → bỏ khối. */
  thongKeNhanh() {
    if (!USER) return "";
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

  /** 3 job gợi ý (điểm cao nhất, hoặc mới nhất với khách); excludeId để ẩn job đang xem. list tự truyền nếu cần. */
  viecGoiY(title = "Gợi ý hôm nay", excludeId = null, list = viecGoiY(3, excludeId)) {
    if (!list.length) return "";
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
                ${nhanKhop(job)}
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

  /** Khối AI Match trên trang chi tiết: vòng %, điểm từng tiêu chí, ưu/nhược, nhận xét. */
  khopAI(job) {
    if (job.match == null) {
      return `
      <div class="card section ai-match-card" style="margin-top:0">
        <strong class="title">${htmlIcon("sparkles")} AI Match</strong>
        <p class="muted">${laSinhVien() ? "Hệ thống đang tính điểm cho tin này, hãy tải lại trang sau ít phút." : "Đăng nhập để xem bạn hợp với việc này bao nhiêu phần trăm và còn thiếu gì."}</p>
        ${laSinhVien() ? "" : `<button class="btn btn-soft btn-sm" type="button" data-open-auth="login">Đăng nhập để xem</button>`}
      </div>`;
    }
    return `
      <div class="card section ai-match-card" style="margin-top:0">
        <div class="ring-row">
          ${vongDiem(job.match)}
          <div>
            <strong class="title">AI Match</strong>
            <p class="muted">Dựa trên CV và kỹ năng của bạn</p>
          </div>
        </div>
        ${htmlGiaiThich(job.whyMatch)}
      </div>`;
  },

  /** Tóm tắt tiến trình đơn ứng tuyển (đếm từ đơn thật). */
  tomTatDon(items) {
    const daXem = items.filter((a) => a.status !== "pending").length;
    return `
      <section>
        <h3 class="rail-title">Tóm tắt ứng tuyển</h3>
        <div class="summary-list">
          <div class="summary-item"><span class="stat-icon is-blue">${htmlIcon("send")}</span><span>Đã ứng tuyển</span><strong>${items.length}</strong></div>
          <div class="summary-item"><span class="stat-icon is-violet">${htmlIcon("eye")}</span><span>Nhà tuyển dụng đã xem</span><strong>${daXem}</strong></div>
          <div class="summary-item"><span class="stat-icon is-mint">${htmlIcon("video")}</span><span>Phỏng vấn</span><strong>${USER.stats.interviewed}</strong></div>
        </div>
      </section>`;
  },

  /** Vòng điểm hồ sơ trên rail trang Profile + những mục còn thiếu (tính ở server). */
  diemHoSo() {
    const thieu = USER.profileMissing;
    return `
      <div class="card section" style="margin-top:0">
        <div class="ring-row" style="display:flex;align-items:center;gap:16px">
          ${vongDiem(USER.profileScore)}
          <div>
            <strong style="display:block">Điểm hồ sơ</strong>
            <p class="muted" style="font-size:.86rem">${thieu.length ? "Hoàn thiện các mục dưới để tăng độ chính xác khi so khớp." : "Hồ sơ đã đầy đủ."}</p>
          </div>
        </div>
        <ul class="why-list">
          ${USER.cv ? `<li><span class="ok">✓</span>CV đã tải lên</li>` : ""}
          ${USER.skills.length ? `<li><span class="ok">✓</span>${USER.skills.length} kỹ năng trong hồ sơ</li>` : ""}
          ${thieu.map((m) => `<li><span class="warn">!</span>${thoatHtml(m)}</li>`).join("")}
        </ul>
      </div>`;
  },

  /** Số liệu công ty trên rail. */
  thongKeCongTy(company) {
    return `
      <section>
        <h3 class="rail-title">Thông tin công ty</h3>
        <div class="stats-grid">
          <div class="stat-card"><span class="stat-icon is-violet">${htmlIcon("briefcase")}</span><strong>${company.jobsCount}</strong><span>Việc đang mở</span></div>
          <div class="stat-card"><span class="stat-icon is-mint">${htmlIcon("users")}</span><strong>${thoatHtml(company.followers)}</strong><span>Người theo dõi</span></div>
        </div>
      </section>`;
  },

  /** Thẻ công ty nhỏ trên rail trang chi tiết. */
  congTyMini(company) {
    return `
      <a class="card card--hover mini-company" href="/companies?id=${encodeURIComponent(company.id)}">
        ${htmlLogo(company)}
        <div><strong>${thoatHtml(company.name)}</strong><small>${thoatHtml(company.size)}</small></div>
        ${htmlIcon("chevron-right")}
      </a>`;
  },
};

/**
 * Lời giải thích điểm khớp (tính ở MatchExplainer.php):
 * điểm từng tiêu chí (breakdown), điểm mạnh (pros), điểm thiếu (cons), nhận xét (comment).
 */
function htmlGiaiThich(why) {
  const tieuChi = (why.breakdown || [])
    .map((b) =>
      b.applicable
        ? `<div class="crit-row" title="${thoatHtml(b.detail)}">
            <span>${thoatHtml(b.label)} <small>(${b.weight}%)</small></span>
            <div class="progress-bar"><span style="width:${b.score}%"></span></div>
            <strong>${b.score}</strong>
          </div>`
        : `<div class="crit-row is-na"><span>${thoatHtml(b.label)}</span><small>${thoatHtml(b.detail)}</small></div>`
    )
    .join("");
  return `
    ${tieuChi ? `<div class="crit-list">${tieuChi}</div>` : ""}
    <ul class="why-list">
      ${why.pros.map((p) => `<li><span class="ok">✓</span>${thoatHtml(p)}</li>`).join("")}
      ${why.cons.map((c) => `<li><span class="warn">!</span>${thoatHtml(c)}</li>`).join("")}
    </ul>
    ${why.comment ? `<div class="ai-note"><strong>AI nhận xét.</strong> ${thoatHtml(why.comment)}</div>` : ""}`;
}

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
        ${
          job.whyMatch
            ? `<ul>
          ${job.whyMatch.pros.slice(0, 3).map((p) => `<li class="ok">${thoatHtml(p)}</li>`).join("")}
          ${job.whyMatch.cons[0] ? `<li class="warn">${thoatHtml(job.whyMatch.cons[0])}</li>` : ""}
        </ul>`
            : `<p class="muted">${laSinhVien() ? "Đang tính điểm cho tin này." : "Đăng nhập để AI so khớp CV của bạn với công việc này."}</p>`
        }
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
  if (!job.whyMatch) {
    if (!laSinhVien()) Popup.moDangNhap({ lyDo: "Đăng nhập để xem độ phù hợp của bạn với công việc này." });
    else thongBao("Hệ thống đang tính điểm cho tin này", "loader");
    return;
  }
  chon("#match-overlay-body").innerHTML = `
    <button class="modal-close" type="button" data-close-modal aria-label="Đóng">${htmlIcon("x")}</button>
    <div class="ring-row" style="display:flex;align-items:center;gap:16px">
      ${vongDiem(job.match)}
      <div>
        <strong style="display:block;font-size:1.05rem">${thoatHtml(job.title)}</strong>
        <p class="muted" style="font-size:.86rem">${thoatHtml(job.company)} · Vì sao phù hợp?</p>
      </div>
    </div>
    ${htmlGiaiThich(job.whyMatch)}
    <a class="btn btn-primary btn-lg" style="margin-top:16px" href="/jobs?id=${job.id}">Xem chi tiết công việc</a>`;
  chon("#match-overlay").classList.add("is-open");
  veIcon();
  chayVongDiem(chon("#match-overlay-body"));
}

/**
 * Click toàn cục (một lần lúc boot):
 * - [data-open-match] → modal AI
 * - [data-close-modal] / click overlay / Escape → đóng
 * - [data-save] → đảo lưu job (API)
 * - [data-open-auth] → popup đăng nhập / đăng ký
 * - [data-auth-link] → khách bấm link cần đăng nhập: mở popup, đăng nhập xong mới chuyển trang
 */
function ganClickToanTrang() {
  document.addEventListener("click", async (e) => {
    const authBtn = e.target.closest("[data-open-auth]");
    if (authBtn) {
      Popup.moDangNhap({ tab: authBtn.dataset.openAuth, lyDo: "Đăng nhập để nhận gợi ý việc hợp với CV của bạn." });
      return;
    }
    const authLink = e.target.closest("[data-auth-link]");
    if (authLink) {
      e.preventDefault();
      Popup.moDangNhap({ lyDo: "Mục này dành cho sinh viên đã đăng nhập.", viecCho: { type: "goto", url: authLink.dataset.authLink } });
      return;
    }
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
    if (save && !save.disabled) {
      const id = Number(save.dataset.save);
      if (!canDangNhap("Đăng nhập để lưu công việc và xem lại sau.", { type: "save", jobId: id })) return;
      save.disabled = true;
      try {
        const on = await Kho.daoLuu(id);
        chonHet(`[data-save="${id}"]`).forEach((b) => b.classList.toggle("is-saved", on));
        thongBao(on ? "Đã lưu công việc" : "Đã bỏ lưu", on ? "heart" : "heart-off");
        document.dispatchEvent(new CustomEvent("jobly:saved-changed"));
      } catch (ex) {
        if (ex.status !== 401) thongBao(ex.message, "circle-alert");
      } finally {
        save.disabled = false;
      }
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

/** Trạng thái rỗng khi id trên URL không tồn tại. */
function veKhongTimThay(root, tieuDe, moTa) {
  root.innerHTML = `
    <div class="card empty-deck" style="margin:40px auto">
      <div class="emoji">🔍</div>
      <h2>${thoatHtml(tieuDe)}</h2>
      <p>${thoatHtml(moTa)}</p>
      <a class="btn btn-primary" href="/explore">Xem việc đang tuyển</a>
    </div>`;
}

/** Việc tương tự: nhiều kỹ năng chung nhất, cùng số thì ưu tiên điểm khớp cao. */
function viecTuongTu(job, soLuong = 3) {
  const kyNang = new Set(job.skills);
  const applied = new Set(Kho.daUngTuyen());
  return JOBS.filter((j) => j.id !== job.id && !applied.has(j.id) && !daDongTuyen(j))
    .map((j) => ({ j, chung: j.skills.filter((s) => kyNang.has(s)).length }))
    .sort((a, b) => b.chung - a.chung || (b.j.match ?? -1) - (a.j.match ?? -1))
    .slice(0, soLuong)
    .map((x) => x.j);
}

/**
 * Ứng tuyển thật qua API. Thành công → trang chúc mừng /match.
 * Trả về true nếu đã gửi được đơn (hoặc đơn đã có từ trước), false nếu lỗi / chưa đăng nhập.
 */
async function ungTuyenViec(id) {
  const job = layViecTheoId(id);
  if (!canDangNhap(`Đăng nhập để ứng tuyển${job ? ` vị trí ${job.title}` : ""}.`, { type: "apply", jobId: Number(id) })) return false;
  try {
    await Kho.ungTuyen(id);
    location.href = `/match?id=${id}`;
    return true;
  } catch (ex) {
    if (ex.status === 409) {
      thongBao("Bạn đã ứng tuyển việc này rồi", "info");
      location.href = "/applications";
      return true;
    }
    if (ex.status === 401) return false;
    thongBao(ex.message, "circle-alert");
    return false;
  }
}

/** Chạy tiếp việc khách đang làm dở trước khi đăng nhập (lưu trong ViecCho). */
async function chayViecCho() {
  const viec = ViecCho.lay();
  if (!viec || !laSinhVien()) return;
  if (viec.type === "goto" && /^\/(?!\/)/.test(viec.url)) location.href = viec.url;
  if (viec.type === "apply" && !Kho.daUngTuyen().includes(viec.jobId)) await ungTuyenViec(viec.jobId);
  if (viec.type === "save" && !Kho.dangLuu(viec.jobId)) {
    await Kho.daoLuu(viec.jobId).catch(() => null);
    chonHet(`[data-save="${viec.jobId}"]`).forEach((b) => b.classList.add("is-saved"));
    thongBao("Đã lưu công việc", "heart");
  }
  if (viec.type === "follow" && !Kho.dangTheoDoi(viec.slug)) {
    await Kho.daoTheoDoi(viec.slug).catch(() => null);
    document.dispatchEvent(new CustomEvent("jobly:follow-changed"));
  }
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
  return viecGoiY(JOBS.length).filter((j) => !hidden.has(j.id));
}

/** Vẽ 3 thẻ vuốt đầu tiên vào #card-stack. */
function veChongThe() {
  const stack = chon("#card-stack");
  if (!stack) return;
  const jobs = viecChoChongThe();
  if (!jobs.length) {
    // Hết bài: empty state + nút xóa skipped để xem lại (giữ applied)
    const coTheXemLai = Kho.daBo().length > 0;
    stack.innerHTML = `
      <div class="card empty-deck">
        <div class="emoji">✨</div>
        <h2>${JOBS.length ? "Bạn đã xem hết gợi ý hôm nay" : "Chưa có tin tuyển dụng nào"}</h2>
        <p>${JOBS.length ? "Khám phá thêm cơ hội khác, hoặc theo dõi những việc đã ứng tuyển." : "Quay lại sau nhé, nhà tuyển dụng đang đăng tin mới."}</p>
        <div class="match-actions">
          <a class="btn btn-primary" href="/explore">Khám phá thêm</a>
          ${coTheXemLai ? `<button class="btn btn-ghost" type="button" id="reset-deck">Xem lại từ đầu</button>` : ""}
        </div>
      </div>`;
    chon("#reset-deck")?.addEventListener("click", () => {
      Kho.xoaBo();
      veChongThe();
      veIcon();
    });
    chonHet(".swipe-actions button").forEach((b) => (b.disabled = true));
    return;
  }
  chonHet(".swipe-actions button").forEach((b) => (b.disabled = false));
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

/** Hộp thông báo (chuông) trên Home: dựng từ đơn đã được xử lý, tin nhắn chưa đọc và việc khớp cao. */
function veThongBaoHome() {
  const pop = chon("#notify-pop");
  if (!pop) return;
  const dong = [];
  APPLICATIONS.filter((a) => a.status !== "pending")
    .slice(0, 3)
    .forEach((a) => {
      const job = layViecTheoId(a.jobId);
      if (job) dong.push(`<p><strong>${thoatHtml(job.company)}</strong> · ${thoatHtml(job.title)}: ${thoatHtml(a.statusLabel)}</p>`);
    });
  if (JOBLY.unread) dong.push(`<p>Bạn có <strong>${JOBLY.unread}</strong> tin nhắn chưa đọc</p>`);
  const khopCao = JOBS.filter((j) => j.match >= 80 && !Kho.daUngTuyen().includes(j.id) && !daDongTuyen(j)).length;
  if (khopCao) dong.push(`<p>AI tìm thấy <strong>${khopCao}</strong> việc khớp từ 80% trở lên</p>`);

  chon(".notify-dot")?.toggleAttribute("hidden", !dong.length);
  pop.innerHTML = dong.length
    ? `${dong.join("")}${laSinhVien() ? `<a href="/chat">Mở tin nhắn →</a>` : ""}`
    : `<p class="muted">${laSinhVien() ? "Chưa có thông báo mới." : "Đăng nhập để nhận thông báo về đơn ứng tuyển."}</p>`;
}

/** Khởi tạo trang Home: rail + chồng thẻ + BoVuot. */
function khoiTrangHome() {
  doCotPhai(`${CotPhai.hoSoAI()}${CotPhai.thongKeNhanh()}${CotPhai.viecGoiY()}${CotPhai.theSuNghiep()}`);
  veThongBaoHome();

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
    // Vuốt lên: gửi đơn thật. Khách / lỗi → thẻ đã bay mất nên vẽ lại chồng thẻ.
    khiUngTuyen: async (id) => {
      if (!(await ungTuyenViec(id))) {
        veChongThe();
        veIcon();
      }
    },
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

  doCotPhai(`${CotPhai.hoSoAI()}${CotPhai.viecGoiY(laSinhVien() ? "AI đề xuất" : "Tin mới nhất")}${CotPhai.theSuNghiep()}`);

  if (savedOnly) {
    chon("#page-title").textContent = "Đã lưu";
    chon("#page-sub").textContent = "Những cơ hội bạn muốn xem lại sau.";
  }

  const veDanhSachViec = () => {
    const q = query.toLowerCase();
    let jobs = JOBS.filter((j) => !daDongTuyen(j)).sort(
      (a, b) => (b.match ?? -1) - (a.match ?? -1) || String(b.postedAt).localeCompare(String(a.postedAt))
    );
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
          filter !== "saved"
            ? "Thử bộ lọc hoặc từ khóa khác."
            : laSinhVien()
              ? "Nhấn ♡ trên một công việc để lưu lại."
              : "Đăng nhập để xem những việc bạn đã lưu."
        }</p></div>`;
    veIcon();
  };

  chonHet(".filter-chip").forEach((chip) => {
    chip.classList.toggle("is-active", chip.dataset.filter === filter);
    chip.addEventListener("click", () => {
      if (chip.dataset.filter === "saved" && !canDangNhap("Đăng nhập để xem những việc bạn đã lưu.", { type: "goto", url: "/explore?saved=1" })) return;
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
  const job = layViecTheoId(thamSoUrl("id"));
  if (!job) {
    veKhongTimThay(root, "Không tìm thấy công việc", "Tin có thể đã ngừng tuyển hoặc đường dẫn không đúng.");
    doCotPhai(`${CotPhai.viecGoiY("Việc đang tuyển")}`);
    return;
  }
  const company = layCongTy(job.companyId);
  const applied = Kho.daUngTuyen().includes(job.id);
  const closed = daDongTuyen(job);
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
      ${
        closed && !applied
          ? `<button class="btn btn-lg" type="button" disabled>${htmlIcon("lock")} Tin đã ngừng tuyển</button>`
          : `<button class="btn btn-primary btn-lg ${applied ? "is-done" : ""}" id="apply-now" type="button">
        ${applied ? `${htmlIcon("check")} Đã ứng tuyển · xem tiến trình` : `${htmlIcon("send")} Ứng tuyển ngay`}
      </button>`
      }
      ${CotPhai.congTyMini(company)}
      ${CotPhai.viecGoiY("Việc tương tự", job.id, viecTuongTu(job))}
    </div>`);

  chon("#apply-now")?.addEventListener("click", async (e) => {
    const btn = e.currentTarget;
    if (btn.classList.contains("is-done")) {
      location.href = "/applications";
      return;
    }
    btn.disabled = true;
    btn.classList.add("is-loading");
    if (!(await ungTuyenViec(job.id))) {
      btn.disabled = false;
      btn.classList.remove("is-loading");
    }
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
  const job = layViecTheoId(thamSoUrl("id"));
  const app = job && layDonTheoViec(job.id);
  // Chỉ chúc mừng khi đơn thật sự tồn tại; gõ thẳng URL với việc chưa nộp → về trang chi tiết.
  if (!app) {
    location.replace(job ? `/jobs?id=${job.id}` : "/applications");
    return;
  }
  const company = layCongTy(job.companyId);
  phaoGiay();

  root.innerHTML = `
    <div class="card match-hero">
      <div class="match-emoji">🎉</div>
      <h1>Tuyệt vời!</h1>
      <p>Bạn đã ứng tuyển thành công.<br><strong>${thoatHtml(job.company)}</strong> đã nhận được ${USER.cv ? "CV" : "hồ sơ"} của bạn.</p>
      <a class="card card--hover match-mini" href="/jobs?id=${job.id}">
        <img src="${job.image}" alt="">
        <div><strong>${thoatHtml(job.title)}</strong><small>${thoatHtml(job.company)} · ${thoatHtml(job.location.split(",")[0])}</small></div>
        ${nhanKhop(job, " phù hợp")}
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
        <li><span class="ok">✓</span>${USER.cv ? `CV ${thoatHtml(USER.cv.name)} đã được gửi tới ${thoatHtml(company.name)}` : `Hồ sơ của bạn đã được gửi tới ${thoatHtml(company.name)}`}</li>
        <li><span class="warn">2</span>Trạng thái đơn cập nhật trong mục Tiến trình mỗi khi nhà tuyển dụng xử lý</li>
        <li><span class="warn">3</span>Nhà tuyển dụng có thể nhắn tin trực tiếp cho bạn trong mục Tin nhắn</li>
      </ul>
      ${
        USER.cv
          ? job.whyMatch?.cons[0]
            ? `<div class="ai-note"><strong>Mẹo từ AI.</strong> ${thoatHtml(job.whyMatch.cons[0])} Bổ sung vào hồ sơ để tăng cơ hội.</div>`
            : ""
          : `<div class="ai-note"><strong>Mẹo.</strong> Bạn chưa có CV. <a href="/profile">Tải CV lên</a> để nhà tuyển dụng xem được kinh nghiệm của bạn.</div>`
      }
      <div class="match-actions" style="margin-top:14px;justify-content:flex-start">
        <a class="btn btn-soft btn-sm" href="/chat?c=${app.id}">${htmlIcon("message-circle")} Mở tin nhắn</a>
        <a class="btn btn-soft btn-sm" href="/companies?id=${encodeURIComponent(company.id)}">${htmlIcon("building-2")} Xem công ty</a>
      </div>
    </div>
    ${CotPhai.thongKeNhanh()}
    ${CotPhai.viecGoiY("Tiếp tục khám phá", job.id)}
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
  if (!CONVERSATIONS.length) {
    layout.innerHTML = `<div class="card empty-deck" style="margin:40px auto"><div class="emoji">💬</div><h2>Chưa có hội thoại</h2><p>Mỗi đơn ứng tuyển là một hội thoại với nhà tuyển dụng.</p><a class="btn btn-primary" href="/">Tìm việc</a></div>`;
    return;
  }
  CONVERSATIONS.forEach((c) => (c.id = String(c.id)));
  let activeId = CONVERSATIONS.some((c) => c.id === thamSoUrl("c")) ? thamSoUrl("c") : CONVERSATIONS[0].id;
  const messagesById = {};

  const dungTinNhan = (conv) => {
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
        <div class="file-item"><span class="cv-icon">${htmlIcon("file-text")}</span><div><strong>${thoatHtml(USER.cv?.name || "Chưa có CV")}</strong><small>Bạn đã gửi</small></div></div>
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
    chon("#quick-replies").style.display = "none";
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
   Đơn thật (APPLICATIONS). steps do server tính từ trạng thái đơn (ApplicationStatus::timeline).
   Rút đơn được khi canWithdraw (nhà tuyển dụng chưa đưa vào vòng trong).
   ---------- */
/** Khởi tạo trang timeline đơn ứng tuyển. */
function khoiTrangDon() {
  const list = chon("#app-list");
  if (!list) return;

  const veDanhSachDon = () => {
    if (!APPLICATIONS.length) {
      list.innerHTML = `
        <div class="card empty-deck" style="width:100%">
          <div class="emoji">📭</div>
          <h2>Bạn chưa ứng tuyển việc nào</h2>
          <p>Vuốt lên ở trang chủ hoặc bấm "Ứng tuyển ngay" trong trang chi tiết để gửi hồ sơ.</p>
          <a class="btn btn-primary" href="/">Tìm việc phù hợp</a>
        </div>`;
    } else {
      list.innerHTML = APPLICATIONS.map((app, i) => {
        const job = layViecTheoId(app.jobId);
        if (!job) return "";
        const c = layCongTy(job.companyId);
        return `
        <article class="card card--hover app-card" style="animation:pageIn .4s ${i * 60}ms var(--ease) both">
          <div class="app-card-head">
            ${htmlLogo(c)}
            <div>
              <h2><a href="/jobs?id=${job.id}">${thoatHtml(job.title)}</a></h2>
              <small>${thoatHtml(job.company)} · ${thoatHtml(job.salary)} · Nộp ngày ${thoatHtml(ngayVn(app.appliedAt))}</small>
            </div>
            <span class="status-tag" data-status="${app.status}">${thoatHtml(app.statusLabel)}</span>
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
          <div class="app-card-actions">
            <a class="btn btn-soft btn-sm" href="/chat?c=${app.id}">${htmlIcon("message-circle")} Nhắn tin</a>
            ${app.canWithdraw ? `<button class="btn btn-ghost btn-sm" type="button" data-withdraw="${app.id}">${htmlIcon("undo-2")} Rút đơn</button>` : ""}
          </div>
        </article>`;
      }).join("");
    }
    doCotPhai(`${CotPhai.tomTatDon(APPLICATIONS)}${CotPhai.viecGoiY("Cơ hội tiếp theo")}${CotPhai.theSuNghiep()}`);
    veIcon();
  };

  list.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-withdraw]");
    if (!btn) return;
    const id = Number(btn.dataset.withdraw);
    const job = layViecTheoId(APPLICATIONS.find((a) => a.id === id)?.jobId);
    if (!window.confirm(`Rút đơn ứng tuyển${job ? ` "${job.title}"` : ""}? Tin nhắn với nhà tuyển dụng cũng sẽ bị xóa.`)) return;
    btn.disabled = true;
    try {
      const data = await Api.goi("DELETE", `/api/applications/${id}`);
      APPLICATIONS.splice(APPLICATIONS.findIndex((a) => a.id === id), 1);
      USER.stats.applied = APPLICATIONS.length;
      thongBao(data.message, "undo-2");
      veDanhSachDon();
    } catch (ex) {
      btn.disabled = false;
      thongBao(ex.message, "circle-alert");
    }
  });

  veDanhSachDon();
}

/** "2026-09-23" → "23/09/2026". */
function ngayVn(isoDate) {
  const [y, m, d] = String(isoDate || "").split("-");
  return d ? `${d}/${m}/${y}` : "";
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
          <img class="profile-avatar" src="${anhDaiDien(USER)}" alt="">
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
        <div><strong>${thoatHtml(USER.cv?.name || "Chưa có CV")}</strong><small>${thoatHtml(USER.cv?.updated || "")}</small></div>
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

  doCotPhai(`${CotPhai.diemHoSo()}${CotPhai.thongKeNhanh()}${CotPhai.viecGoiY("Việc phù hợp với bạn")}`);

  root.addEventListener("click", (e) => {
    const t = e.target.closest("[data-toast]");
    if (t) thongBao(t.dataset.toast, "info");
    if (e.target.closest("#edit-profile")) thongBao("Chế độ chỉnh sửa sẽ có trong bản kết nối API", "pencil");
    if (e.target.closest("#add-skill")) thongBao("Gợi ý: React, Motion Design", "sparkles");
  });
  veIcon();
}

/* ---------- COMPANY ----------
   /companies?id=<slug> — hero, tab Giới thiệu / Việc làm, theo dõi qua API.
   Nút nhắn tin chỉ hiện khi sinh viên đã có đơn ở công ty (hội thoại gắn với đơn).
   ---------- */
/** Khởi tạo trang công ty. */
function khoiTrangCongTy() {
  const root = chon("#company-root");
  if (!root) return;
  const company = layCongTy(thamSoUrl("id"));
  if (!company) {
    veKhongTimThay(root, "Không tìm thấy công ty", "Đường dẫn không đúng hoặc công ty đã ngừng hoạt động.");
    doCotPhai(CotPhai.viecGoiY("Việc đang tuyển"));
    return;
  }
  const jobs = viecCuaCongTy(company.id);
  const hoiThoai = CONVERSATIONS.find((c) => c.companyId === company.id);
  document.title = `${company.name} — Jobly`;

  const htmlNutTheoDoi = (on) => (on ? `${htmlIcon("check")} Đang theo dõi` : `${htmlIcon("plus")} Theo dõi`);
  const danhSach = jobs.length
    ? `<div class="job-list">${jobs.map(hangViec).join("")}</div>`
    : `<p class="muted">Công ty chưa có tin đang tuyển.</p>`;

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
          <button class="btn btn-primary follow-btn ${Kho.dangTheoDoi(company.id) ? "is-following" : ""}" id="follow-btn" type="button">
            ${htmlNutTheoDoi(Kho.dangTheoDoi(company.id))}
          </button>
          ${hoiThoai ? `<a class="icon-btn" href="/chat?c=${hoiThoai.id}" aria-label="Nhắn tin">${htmlIcon("message-circle")}</a>` : ""}
        </div>
      </div>
    </article>

    <div class="tabs" role="tablist">
      <button class="tab is-active" data-tab="about" type="button">Giới thiệu</button>
      <button class="tab" data-tab="jobs" type="button">Việc làm <span class="chip" style="padding:2px 8px;margin-left:4px">${jobs.length}</span></button>
    </div>

    <div class="tab-panel is-active" data-panel="about">
      <section class="card section"><h2>Về công ty</h2><p>${thoatHtml(company.about || "Công ty chưa cập nhật phần giới thiệu.")}</p></section>
      <section class="card section">
        <h2>Vị trí đang tuyển</h2>
        ${danhSach}
      </section>
    </div>
    <div class="tab-panel" data-panel="jobs">
      <div style="margin-top:16px">${danhSach}</div>
    </div>`;

  const veCot = () =>
    doCotPhai(`${CotPhai.thongKeCongTy(company)}${CotPhai.viecGoiY("Việc nổi bật", null, jobs.slice(0, 3))}${CotPhai.theSuNghiep()}`);
  veCot();

  const capNhatNut = () => {
    const btn = chon("#follow-btn");
    btn.classList.toggle("is-following", Kho.dangTheoDoi(company.id));
    btn.innerHTML = htmlNutTheoDoi(Kho.dangTheoDoi(company.id));
    veIcon();
  };
  document.addEventListener("jobly:follow-changed", capNhatNut);

  root.addEventListener("click", async (e) => {
    const tab = e.target.closest("[data-tab]");
    if (tab) {
      chonHet(".tab", root).forEach((t) => t.classList.toggle("is-active", t === tab));
      chonHet(".tab-panel", root).forEach((p) => p.classList.toggle("is-active", p.dataset.panel === tab.dataset.tab));
      veIcon();
    }
    const btn = e.target.closest("#follow-btn");
    if (btn && !btn.disabled) {
      if (!canDangNhap(`Đăng nhập để theo dõi ${company.name}.`, { type: "follow", slug: company.id })) return;
      btn.disabled = true;
      try {
        const data = await Kho.daoTheoDoi(company.id);
        company.followers = String(data.followers);
        capNhatNut();
        veCot();
        thongBao(data.message, data.following ? "bell-ring" : "bell-off");
      } catch (ex) {
        if (ex.status !== 401) thongBao(ex.message, "circle-alert");
      } finally {
        btn.disabled = false;
      }
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
  chayViecCho();
});
