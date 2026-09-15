/**
 * Jobly — bộ vuốt thẻ việc (trang Home)
 *
 * Tên hàm không dấu = mình viết. Xem comment ngay trên hàm để biết công dụng.
 * Tên tiếng Anh (addEventListener, querySelector, setTimeout) = sẵn của trình duyệt.
 *
 * Vuốt LÊN = ứng tuyển. Vuốt XUỐNG = bỏ qua.
 */
const BoVuot = (() => {
  /** Số pixel kéo dọc tối thiểu mới tính là vuốt (nhỏ hơn thì thả về chỗ). */
  const NGUONG = 110;
  /** Góc nghiêng tối đa khi kéo thẻ (độ). */
  const DO_NGHIENG_TOI_DA = 8;

  let chongThe = null;
  let khiUngTuyen = null;
  let khiBo = null;
  let khiBam = null;

  let startY = 0;
  let startX = 0;
  let currentY = 0;
  let dangKeo = false;
  let startTime = 0;
  let the = null;

  /** Gắn engine vào #card-stack; nhận callback khiUngTuyen / khiBo / khiBam. */
  function khoiTao(tuyChon) {
    chongThe = tuyChon.chongThe;
    khiUngTuyen = tuyChon.khiUngTuyen;
    khiBo = tuyChon.khiBo;
    khiBam = tuyChon.khiBam || null;
    chongThe.addEventListener("pointerdown", khiNhan);
  }

  /** Trả về thẻ đang ở mặt trước (class is-front). */
  function theMatTruoc() {
    return chongThe?.querySelector(".job-card.is-front");
  }

  /** Lấy 2 nhãn APPLY / SKIP bên trong một thẻ. */
  function nhanVuot(el) {
    return {
      apply: el.querySelector(".swipe-label--apply"),
      skip: el.querySelector(".swipe-label--skip"),
    };
  }

  /** Bắt đầu kéo: chỉ nút trái, bỏ qua nếu bấm vào button/link/ô "Vì sao phù hợp". */
  function khiNhan(e) {
    if (e.button !== undefined && e.button !== 0) return;
    the = e.target.closest(".job-card.is-front");
    if (!the || e.target.closest("button, a, [data-open-match]")) return;
    e.preventDefault();

    dangKeo = true;
    startY = e.clientY;
    startX = e.clientX;
    currentY = 0;
    startTime = Date.now();
    the.classList.add("is-dragging");
    the.setPointerCapture?.(e.pointerId);

    window.addEventListener("pointermove", khiKeo);
    window.addEventListener("pointerup", khiTha);
    window.addEventListener("pointercancel", khiTha);
  }

  /** Đang kéo: dịch thẻ theo ngón tay, nghiêng nhẹ, hiện nhãn APPLY (lên) hoặc SKIP (xuống). */
  function khiKeo(e) {
    if (!dangKeo || !the) return;
    currentY = e.clientY - startY;
    const dx = e.clientX - startX;
    const rotate = Math.max(-DO_NGHIENG_TOI_DA, Math.min(DO_NGHIENG_TOI_DA, dx * 0.04 + currentY * -0.02));
    the.style.transform = `translate(${dx * 0.15}px, ${currentY}px) rotate(${rotate}deg)`;

    const { apply, skip } = nhanVuot(the);
    const abs = Math.abs(currentY);
    const t = Math.min(1, abs / NGUONG);
    if (currentY < 0) {
      apply.style.opacity = String(t);
      apply.style.transform = `translateX(-50%) scale(${0.92 + t * 0.08}) rotate(-8deg)`;
      skip.style.opacity = "0";
    } else {
      skip.style.opacity = String(t);
      skip.style.transform = `translateX(-50%) scale(${0.92 + t * 0.08}) rotate(8deg)`;
      apply.style.opacity = "0";
    }
  }

  /** Thả tay: click nhẹ = xem chi tiết; vuốt đủ ngưỡng = phóng thẻ; kéo ngắn = trả về chỗ. */
  function khiTha(e) {
    window.removeEventListener("pointermove", khiKeo);
    window.removeEventListener("pointerup", khiTha);
    window.removeEventListener("pointercancel", khiTha);

    if (!the) return;
    const dy = currentY;
    const dist = Math.hypot(e.clientX - startX, e.clientY - startY);
    const elapsed = Date.now() - startTime;
    dangKeo = false;
    the.classList.remove("is-dragging");

    if (dist < 10 && elapsed < 400 && khiBam) {
      datLaiThe(the);
      khiBam(Number(the.dataset.jobId));
      return;
    }

    if (dy < -NGUONG) {
      phongThe(the, "up");
    } else if (dy > NGUONG) {
      phongThe(the, "down");
    } else {
      datLaiThe(the);
    }
  }

  /** Trả thẻ về vị trí ban đầu, ẩn nhãn APPLY/SKIP. */
  function datLaiThe(el) {
    el.style.transform = "";
    const { apply, skip } = nhanVuot(el);
    apply.style.opacity = "0";
    skip.style.opacity = "0";
  }

  /** Animation thẻ bay khỏi chồng rồi xóa khỏi DOM; xong thì đưa thẻ sau lên trước. */
  function phongThe(el, huong) {
    const jobId = Number(el.dataset.jobId);
    el.classList.add("is-leaving");
    const travel = el.offsetHeight * 1.35;
    el.style.transform =
      huong === "up"
        ? `translateY(-${travel}px) rotate(-6deg)`
        : `translateY(${travel}px) rotate(6deg)`;
    el.style.opacity = "0";

    window.setTimeout(() => {
      el.remove();
      duaTheSauLenTruoc();
      if (huong === "up") khiUngTuyen?.(jobId);
      else khiBo?.(jobId);
    }, 380);
  }

  /** Gán lại class chồng bài: thẻ[0]=front, [1]=back-1, [2]=back-2. */
  function duaTheSauLenTruoc() {
    if (!chongThe) return;
    const dsThe = [...chongThe.querySelectorAll(".job-card")];
    dsThe.forEach((c) => c.classList.remove("is-front", "is-back-1", "is-back-2"));
    if (dsThe[0]) dsThe[0].classList.add("is-front");
    if (dsThe[1]) dsThe[1].classList.add("is-back-1");
    if (dsThe[2]) dsThe[2].classList.add("is-back-2");
  }

  /** Nút Apply trên UI / phím mũi tên lên — bay thẻ hiện tại lên (ứng tuyển). */
  function ungTuyenTheHienTai() {
    const el = theMatTruoc();
    if (el) phongThe(el, "up");
  }

  /** Nút Skip trên UI / phím mũi tên xuống — bay thẻ hiện tại xuống (bỏ qua). */
  function boTheHienTai() {
    const el = theMatTruoc();
    if (el) phongThe(el, "down");
  }

  return {
    khoiTao,
    ungTuyenTheHienTai,
    boTheHienTai,
    duaTheSauLenTruoc,
    theMatTruoc,
  };
})();
