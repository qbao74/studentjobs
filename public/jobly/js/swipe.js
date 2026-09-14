/**
 * Jobly — Swipe engine
 * Vuốt LÊN = Apply, vuốt XUỐNG = Skip.
 * Dùng Pointer Events nên chạy được cả chuột (desktop) và ngón tay (mobile).
 */
const SwipeEngine = (() => {
  const THRESHOLD = 110;
  const MAX_ROTATE = 8;

  let stackEl = null;
  let onApply = null;
  let onSkip = null;
  let onClick = null;

  let startY = 0;
  let startX = 0;
  let currentY = 0;
  let dragging = false;
  let startTime = 0;
  let card = null;

  function init(options) {
    stackEl = options.stack;
    onApply = options.onApply;
    onSkip = options.onSkip;
    onClick = options.onClick || null;
    // Ủy quyền sự kiện trên stack — tránh gắn trùng listener mỗi lần đổi card
    stackEl.addEventListener("pointerdown", onDown);
  }

  function frontCard() {
    return stackEl?.querySelector(".job-card.is-front");
  }

  function labels(el) {
    return {
      apply: el.querySelector(".swipe-label--apply"),
      skip: el.querySelector(".swipe-label--skip"),
    };
  }

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
    card.setPointerCapture?.(e.pointerId);

    window.addEventListener("pointermove", onMove);
    window.addEventListener("pointerup", onUp);
    window.addEventListener("pointercancel", onUp);
  }

  function onMove(e) {
    if (!dragging || !card) return;
    currentY = e.clientY - startY;
    const dx = e.clientX - startX;
    const rotate = Math.max(-MAX_ROTATE, Math.min(MAX_ROTATE, dx * 0.04 + currentY * -0.02));
    card.style.transform = `translate(${dx * 0.15}px, ${currentY}px) rotate(${rotate}deg)`;

    const { apply, skip } = labels(card);
    const abs = Math.abs(currentY);
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

  function resetCard(el) {
    el.style.transform = "";
    const { apply, skip } = labels(el);
    apply.style.opacity = "0";
    skip.style.opacity = "0";
  }

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

  function promoteStack() {
    if (!stackEl) return;
    const cards = [...stackEl.querySelectorAll(".job-card")];
    cards.forEach((c) => c.classList.remove("is-front", "is-back-1", "is-back-2"));
    if (cards[0]) cards[0].classList.add("is-front");
    if (cards[1]) cards[1].classList.add("is-back-1");
    if (cards[2]) cards[2].classList.add("is-back-2");
  }

  function applyCurrent() {
    const el = frontCard();
    if (el) fly(el, "up");
  }

  function skipCurrent() {
    const el = frontCard();
    if (el) fly(el, "down");
  }

  return { init, applyCurrent, skipCurrent, promoteStack, frontCard };
})();
