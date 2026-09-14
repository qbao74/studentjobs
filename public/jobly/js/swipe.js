/**
 * Jobly — Swipe engine
 *
 * Engine vuốt card việc làm trên trang Home.
 * - Vuốt LÊN  = Apply (ứng tuyển)
 * - Vuốt XUỐNG = Skip (bỏ qua)
 *
 * Dùng Pointer Events nên chạy được cả chuột (desktop) và ngón tay (mobile).
 * IIFE trả về object công khai: init / applyCurrent / skipCurrent / promoteStack / frontCard.
 * app.js gọi các hàm này; engine không biết JOBS hay Store.
 */
const SwipeEngine = (() => {
  // Số pixel vuốt dọc tối thiểu mới tính là swipe (nhỏ hơn thì snap về hoặc coi là click)
  const THRESHOLD = 110;
  // Góc nghiêng tối đa khi kéo card (độ)
  const MAX_ROTATE = 8;

  // DOM của chồng card (#card-stack) — gắn listener một lần ở init()
  let stackEl = null;
  // Callback từ app.js: onApply(jobId), onSkip(jobId), onClick(jobId)
  let onApply = null;
  let onSkip = null;
  let onClick = null;

  // Trạng thái một lần kéo: tọa độ bắt đầu, độ lệch Y hiện tại, card đang cầm
  let startY = 0;
  let startX = 0;
  let currentY = 0;
  let dragging = false;
  let startTime = 0;
  let card = null;

  /**
   * Khởi tạo engine trên một stack DOM.
   * options.stack   — phần tử chứa các .job-card
   * options.onApply — gọi khi card bay lên (apply)
   * options.onSkip  — gọi khi card bay xuống (skip)
   * options.onClick — gọi khi tap nhẹ (không đủ là swipe) → mở chi tiết
   */
  function init(options) {
    stackEl = options.stack;
    onApply = options.onApply;
    onSkip = options.onSkip;
    onClick = options.onClick || null;
    // Ủy quyền sự kiện trên stack — tránh gắn trùng listener mỗi lần đổi card
    stackEl.addEventListener("pointerdown", onDown);
  }

  /** Card đang ở mặt trước (class is-front) — card người dùng tương tác */
  function frontCard() {
    return stackEl?.querySelector(".job-card.is-front");
  }

  /** Hai nhãn APPLY / SKIP nằm trên card; opacity tăng dần khi kéo đúng hướng */
  function labels(el) {
    return {
      apply: el.querySelector(".swipe-label--apply"),
      skip: el.querySelector(".swipe-label--skip"),
    };
  }

  /** Bắt đầu kéo: chỉ nhận nút trái, bỏ qua nếu bấm vào button/link/ô "Vì sao phù hợp" */
  function onDown(e) {
    if (e.button !== undefined && e.button !== 0) return;
    card = e.target.closest(".job-card.is-front");
    // Không bắt đầu kéo khi bấm vào nút/link/ô "Vì sao phù hợp" bên trong card
    if (!card || e.target.closest("button, a, [data-open-match]")) return;
    e.preventDefault();

    dragging = true;
    startY = e.clientY;
    startX = e.clientX;
    currentY = 0;
    startTime = Date.now();
    card.classList.add("is-dragging");
    // Giữ pointer trên card dù ngón tay/chuột ra ngoài vùng card
    card.setPointerCapture?.(e.pointerId);

    window.addEventListener("pointermove", onMove);
    window.addEventListener("pointerup", onUp);
    window.addEventListener("pointercancel", onUp);
  }

  /**
   * Khi đang kéo: dịch card theo ngón tay, nghiêng nhẹ, hiện nhãn APPLY (lên) hoặc SKIP (xuống).
   * dx chỉ dùng để nghiêng / trượt ngang rất nhẹ — quyết định apply/skip dựa vào currentY.
   */
  function onMove(e) {
    if (!dragging || !card) return;
    currentY = e.clientY - startY;
    const dx = e.clientX - startX;
    const rotate = Math.max(-MAX_ROTATE, Math.min(MAX_ROTATE, dx * 0.04 + currentY * -0.02));
    card.style.transform = `translate(${dx * 0.15}px, ${currentY}px) rotate(${rotate}deg)`;

    const { apply, skip } = labels(card);
    const abs = Math.abs(currentY);
    // t = 0..1: càng gần ngưỡng thì nhãn càng đậm / to
    const t = Math.min(1, abs / THRESHOLD);
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

  /**
   * Thả tay: phân biệt click / swipe-up / swipe-down / kéo chưa đủ thì trả card về chỗ.
   * dist + elapsed: tap ngắn → xem chi tiết, không tính swipe.
   */
  function onUp(e) {
    window.removeEventListener("pointermove", onMove);
    window.removeEventListener("pointerup", onUp);
    window.removeEventListener("pointercancel", onUp);

    if (!card) return;
    const dy = currentY;
    const dist = Math.hypot(e.clientX - startX, e.clientY - startY);
    const elapsed = Date.now() - startTime;
    dragging = false;
    card.classList.remove("is-dragging");

    // Click nhẹ trên card → xem chi tiết (không tính là swipe)
    if (dist < 10 && elapsed < 400 && onClick) {
      resetCard(card);
      onClick(Number(card.dataset.jobId));
      return;
    }

    if (dy < -THRESHOLD) {
      fly(card, "up");
    } else if (dy > THRESHOLD) {
      fly(card, "down");
    } else {
      resetCard(card);
    }
  }

  /** Trả card về vị trí ban đầu, ẩn nhãn APPLY/SKIP */
  function resetCard(el) {
    el.style.transform = "";
    const { apply, skip } = labels(el);
    apply.style.opacity = "0";
    skip.style.opacity = "0";
  }

  /**
   * Animation card bay khỏi stack rồi xóa khỏi DOM.
   * Sau 380ms: promote card phía sau lên front, gọi onApply hoặc onSkip.
   */
  function fly(el, dir) {
    const jobId = Number(el.dataset.jobId);
    el.classList.add("is-leaving");
    const travel = el.offsetHeight * 1.35;
    el.style.transform =
      dir === "up"
        ? `translateY(-${travel}px) rotate(-6deg)`
        : `translateY(${travel}px) rotate(6deg)`;
    el.style.opacity = "0";

    window.setTimeout(() => {
      el.remove();
      promoteStack();
      if (dir === "up") onApply?.(jobId);
      else onSkip?.(jobId);
    }, 380);
  }

  /**
   * Gán lại class chồng bài: card[0]=front, [1]=back-1, [2]=back-2.
   * CSS dùng các class này để scale/dịch card phía sau, tạo hiệu ứng deck.
   */
  function promoteStack() {
    if (!stackEl) return;
    const cards = [...stackEl.querySelectorAll(".job-card")];
    cards.forEach((c) => c.classList.remove("is-front", "is-back-1", "is-back-2"));
    if (cards[0]) cards[0].classList.add("is-front");
    if (cards[1]) cards[1].classList.add("is-back-1");
    if (cards[2]) cards[2].classList.add("is-back-2");
  }

  /** Nút Apply trên UI / phím ↑ — animate card hiện tại bay lên */
  function applyCurrent() {
    const el = frontCard();
    if (el) fly(el, "up");
  }

  /** Nút Skip trên UI / phím ↓ — animate card hiện tại bay xuống */
  function skipCurrent() {
    const el = frontCard();
    if (el) fly(el, "down");
  }

  return { init, applyCurrent, skipCurrent, promoteStack, frontCard };
})();
