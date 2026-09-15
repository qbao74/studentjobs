/**
 * Jobly — bo vuot the viec (trang Home)
 *
 * Ten tieng Viet khong dau = ham minh viet. Xem comment ngay tren ham.
 * Ten tieng Anh (addEventListener, querySelector, setTimeout) = san cua trinh duyet.
 *
 * Vuot LEN = ung tuyen. Vuot XUONG = bo qua.
 */
const BoVuot = (() => {
  /** So pixel keo doc toi thieu moi tinh la vuot (nho hon thi tha ve cho). */
  const NGUONG = 110;
  /** Goc nghieng toi da khi keo the (do). */
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

  /** Gan engine vao #card-stack; nhan callback khiUngTuyen / khiBo / khiBam. */
  function khoiTao(tuyChon) {
    chongThe = tuyChon.chongThe;
    khiUngTuyen = tuyChon.khiUngTuyen;
    khiBo = tuyChon.khiBo;
    khiBam = tuyChon.khiBam || null;
    chongThe.addEventListener("pointerdown", khiNhan);
  }

  /** Tra ve the dang o mat truoc (class is-front). */
  function theMatTruoc() {
    return chongThe?.querySelector(".job-card.is-front");
  }

  /** Lay 2 nhan APPLY / SKIP ben trong mot the. */
  function nhanVuot(el) {
    return {
      apply: el.querySelector(".swipe-label--apply"),
      skip: el.querySelector(".swipe-label--skip"),
    };
  }

  /** Bat dau keo: chi nut trai, bo qua neu bam vao button/link/o "Vi sao phu hop". */
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

  /** Dang keo: dich the theo ngon tay, nghieng nhe, hien nhan APPLY (len) hoac SKIP (xuong). */
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

  /** Tha tay: click nhe = xem chi tiet; vuot du nguong = phong the; keo ngan = tra ve cho. */
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

  /** Tra the ve vi tri ban dau, an nhan APPLY/SKIP. */
  function datLaiThe(el) {
    el.style.transform = "";
    const { apply, skip } = nhanVuot(el);
    apply.style.opacity = "0";
    skip.style.opacity = "0";
  }

  /** Animation the bay khoi chong roi xoa khoi DOM; xong thi dua the sau len truoc. */
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

  /** Gan lai class chong bai: the[0]=front, [1]=back-1, [2]=back-2. */
  function duaTheSauLenTruoc() {
    if (!chongThe) return;
    const dsThe = [...chongThe.querySelectorAll(".job-card")];
    dsThe.forEach((c) => c.classList.remove("is-front", "is-back-1", "is-back-2"));
    if (dsThe[0]) dsThe[0].classList.add("is-front");
    if (dsThe[1]) dsThe[1].classList.add("is-back-1");
    if (dsThe[2]) dsThe[2].classList.add("is-back-2");
  }

  /** Nut Apply tren UI / phim mui ten len — bay the hien tai len (ung tuyen). */
  function ungTuyenTheHienTai() {
    const el = theMatTruoc();
    if (el) phongThe(el, "up");
  }

  /** Nut Skip tren UI / phim mui ten xuong — bay the hien tai xuong (bo qua). */
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
