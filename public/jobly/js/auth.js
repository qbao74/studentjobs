/**
 * Jobly — gọi API + popup đăng nhập
 *
 * Api.goi(method, url, body) = fetch có sẵn CSRF, JSON, và xử lý lỗi chung:
 *   401 → mở popup đăng nhập, 419 → phiên hết hạn (tải lại), 422 → trả lỗi validate cho form.
 * Popup.moDangNhap() = popup đăng nhập / đăng ký ngay trên trang, không chuyển trang.
 * Sau khi đăng nhập, trang tải lại; hành động đang dở (VD: ứng tuyển) được lưu trong
 * sessionStorage và chạy tiếp sau khi tải lại.
 */

/** Lỗi từ server, giữ status + message + errors (422) để giao diện hiển thị. */
class LoiApi extends Error {
  constructor(status, message, errors = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

const Api = {
  /** CSRF token Laravel in trong <meta name="csrf-token">. */
  token() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
  },

  /**
   * Gửi request. body là object → JSON; là FormData → multipart (upload file).
   * Trả về JSON của server; lỗi thì ném LoiApi.
   */
  async goi(method, url, body = null) {
    const headers = { Accept: "application/json", "X-CSRF-TOKEN": this.token(), "X-Requested-With": "XMLHttpRequest" };
    const options = { method, headers, credentials: "same-origin" };

    if (body instanceof FormData) {
      options.body = body;
    } else if (body !== null) {
      headers["Content-Type"] = "application/json";
      options.body = JSON.stringify(body);
    }

    let res;
    try {
      res = await fetch(url, options);
    } catch {
      throw new LoiApi(0, "Không kết nối được máy chủ. Kiểm tra mạng rồi thử lại.");
    }

    const data = await res.json().catch(() => ({}));

    if (res.ok) return data;

    if (res.status === 401) {
      Popup.moDangNhap();
      throw new LoiApi(401, "Bạn cần đăng nhập để tiếp tục.");
    }
    if (res.status === 419) {
      throw new LoiApi(419, "Phiên làm việc đã hết hạn. Hãy tải lại trang.");
    }
    if (res.status === 429) {
      throw new LoiApi(429, "Bạn thao tác quá nhanh. Đợi một chút rồi thử lại.");
    }

    const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
    throw new LoiApi(res.status, firstError || data.message || "Có lỗi xảy ra, vui lòng thử lại.", data.errors || {});
  },
};

/** Hành động chờ sau khi đăng nhập (VD: { type: "apply", jobId: 3 }). */
const ViecCho = {
  key: "jobly_after_login",
  dat(action) {
    sessionStorage.setItem(this.key, JSON.stringify(action));
  },
  lay() {
    const raw = sessionStorage.getItem(this.key);
    sessionStorage.removeItem(this.key);
    try {
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  },
};

const Popup = {
  /** Mở popup. lyDo hiện ở đầu popup; viecCho chạy lại sau khi đăng nhập xong. */
  moDangNhap({ lyDo = "Đăng nhập để ứng tuyển và lưu hồ sơ.", viecCho = null, tab = "login" } = {}) {
    if (viecCho) ViecCho.dat(viecCho);
    this.dungKhung();
    document.querySelector("#auth-reason").textContent = lyDo;
    this.chonTab(tab);
    document.querySelector("#auth-overlay").classList.add("is-open");
    document.querySelector(`#auth-form-${tab} input`)?.focus();
  },

  dong() {
    document.querySelector("#auth-overlay")?.classList.remove("is-open");
  },

  chonTab(tab) {
    document.querySelectorAll("[data-auth-tab]").forEach((b) => b.classList.toggle("is-active", b.dataset.authTab === tab));
    document.querySelectorAll(".auth-pane").forEach((p) => (p.hidden = p.dataset.pane !== tab));
  },

  /** Tạo HTML popup một lần duy nhất. */
  dungKhung() {
    if (document.querySelector("#auth-overlay")) return;
    const el = document.createElement("div");
    el.className = "overlay";
    el.id = "auth-overlay";
    el.innerHTML = `
      <div class="modal auth-modal" role="dialog" aria-modal="true" aria-labelledby="auth-title">
        <button class="modal-close" type="button" data-auth-close aria-label="Đóng"><i data-lucide="x"></i></button>
        <h2 id="auth-title">Chào mừng đến Jobly</h2>
        <p class="muted" id="auth-reason"></p>
        <div class="auth-switch" role="tablist">
          <button type="button" data-auth-tab="login">Đăng nhập</button>
          <button type="button" data-auth-tab="register">Tạo tài khoản</button>
        </div>

        <form class="auth-pane" data-pane="login" id="auth-form-login" novalidate>
          <label>Email<input type="email" name="email" required autocomplete="email"></label>
          <label>Mật khẩu<input type="password" name="password" required autocomplete="current-password"></label>
          <label class="auth-check"><input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập</label>
          <p class="auth-err" role="alert"></p>
          <button class="btn btn-primary btn-lg" type="submit">Đăng nhập</button>
        </form>

        <form class="auth-pane" data-pane="register" id="auth-form-register" novalidate hidden>
          <label>Họ tên<input type="text" name="name" required maxlength="100" autocomplete="name"></label>
          <label>Email<input type="email" name="email" required autocomplete="email"></label>
          <label>Mật khẩu (ít nhất 8 ký tự, có chữ và số)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
          <label>Nhập lại mật khẩu<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
          <p class="auth-err" role="alert"></p>
          <button class="btn btn-primary btn-lg" type="submit">Tạo tài khoản sinh viên</button>
        </form>

        <p class="auth-foot">Bạn là nhà tuyển dụng? <a href="/register/employer">Đăng ký tại đây</a></p>
      </div>`;
    document.body.appendChild(el);
    window.lucide?.createIcons();

    el.addEventListener("click", (e) => {
      if (e.target === el || e.target.closest("[data-auth-close]")) this.dong();
      const tab = e.target.closest("[data-auth-tab]");
      if (tab) this.chonTab(tab.dataset.authTab);
    });
    document.addEventListener("keydown", (e) => e.key === "Escape" && this.dong());

    el.querySelector("#auth-form-login").addEventListener("submit", (e) => this.gui(e, "/login"));
    el.querySelector("#auth-form-register").addEventListener("submit", (e) => this.gui(e, "/register"));
  },

  /** Gửi form đăng nhập/đăng ký dạng JSON; thành công thì tải lại hoặc chuyển về khu của vai trò. */
  async gui(e, url) {
    e.preventDefault();
    const form = e.currentTarget;
    const btn = form.querySelector("button[type=submit]");
    const err = form.querySelector(".auth-err");
    const body = Object.fromEntries(new FormData(form));

    btn.disabled = true;
    btn.classList.add("is-loading");
    err.textContent = "";

    try {
      const data = await Api.goi("POST", url, body);
      if (data.role && data.role !== "student") {
        location.href = data.redirect;
        return;
      }
      location.reload();
    } catch (ex) {
      err.textContent = ex.message;
      btn.disabled = false;
      btn.classList.remove("is-loading");
    }
  },
};

/** Người đang xem có phải sinh viên đã đăng nhập không. */
function laSinhVien() {
  return window.JOBLY?.auth?.role === "student";
}

/**
 * Chặn hành động cần đăng nhập. Trả true nếu được làm tiếp.
 * Khách → mở popup (kèm việc chờ nếu có) và trả false.
 */
function canDangNhap(lyDo, viecCho = null) {
  if (laSinhVien()) return true;
  Popup.moDangNhap({ lyDo, viecCho });
  return false;
}
