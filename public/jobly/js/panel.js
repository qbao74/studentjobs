/**
 * Jobly — khu nhà tuyển dụng / admin (trang Blade render ở server)
 *
 * form[data-confirm] → hỏi lại trước khi gửi (xoá tin, đóng tin, khoá tài khoản...).
 * Nút submit bị khoá sau khi bấm để không gửi hai lần.
 */
document.addEventListener("submit", (e) => {
  const form = e.target;
  if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    e.preventDefault();
    return;
  }
  const btn = e.submitter || form.querySelector("button[type=submit]");
  if (btn && form.method.toLowerCase() === "post") {
    window.setTimeout(() => {
      btn.disabled = true;
      btn.classList.add("is-loading");
    });
  }
});

/** Ô chọn trạng thái có data-autosubmit → đổi là gửi luôn. */
document.addEventListener("change", (e) => {
  if (e.target.matches("[data-autosubmit]")) e.target.form.requestSubmit();
});

document.addEventListener("DOMContentLoaded", () => window.lucide?.createIcons());
