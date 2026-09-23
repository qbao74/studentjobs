/**
 * Jobly — dữ liệu trang (đọc từ server)
 *
 * Trước đây file này chứa dữ liệu giả. Giờ server (JoblyPayload.php) in dữ liệu thật vào
 * window.JOBLY trong layout; file này chỉ đặt tên ngắn cho từng phần để app.js dùng.
 *   USER          hồ sơ sinh viên đang đăng nhập (null nếu là khách)
 *   COMPANIES     object: key = slug công ty
 *   JOBS          tin đang mở (+ tin đã đóng mà sinh viên từng nộp)
 *   APPLICATIONS  đơn của sinh viên, mỗi đơn có steps cho timeline
 *   CONVERSATIONS mỗi đơn là một hội thoại với nhà tuyển dụng
 * Sau khi API trả về state mới (sửa hồ sơ, tải CV...), gọi napLaiDuLieu(state).
 */

const JOBLY = window.JOBLY || {
  auth: { loggedIn: false, role: null, name: null },
  companies: {},
  jobs: [],
  user: null,
  applications: [],
  conversations: [],
  saved: [],
  following: [],
  unread: 0,
};

let USER = JOBLY.user;
let COMPANIES = JOBLY.companies;
let JOBS = JOBLY.jobs;
let APPLICATIONS = JOBLY.applications;
let CONVERSATIONS = JOBLY.conversations;

/** Thay toàn bộ dữ liệu bằng state mới server trả về. */
function napLaiDuLieu(state) {
  Object.assign(JOBLY, state);
  USER = JOBLY.user;
  COMPANIES = JOBLY.companies;
  JOBS = JOBLY.jobs;
  APPLICATIONS = JOBLY.applications;
  CONVERSATIONS = JOBLY.conversations;
}

/** Tìm 1 job theo id. Number(id) vì URL luôn là chuỗi ("1"), còn job.id là số */
function layViecTheoId(id) {
  return JOBS.find((job) => job.id === Number(id));
}

/** Lấy công ty theo slug. */
function layCongTy(companyId) {
  return COMPANIES[companyId];
}

/** Tất cả job đang mở thuộc 1 công ty — trang /companies */
function viecCuaCongTy(companyId) {
  return JOBS.filter((job) => job.companyId === companyId && !daDongTuyen(job));
}

/** Tin đã đóng: chỉ có trong JOBS vì sinh viên từng nộp, không hiện ở danh sách gợi ý. */
function daDongTuyen(job) {
  return job?.closed === true;
}

/** Đơn của sinh viên cho 1 job (hoặc undefined). */
function layDonTheoViec(jobId) {
  return APPLICATIONS.find((a) => a.jobId === Number(jobId));
}

/**
 * Việc gợi ý: sinh viên → điểm khớp cao nhất; khách → tin mới nhất.
 * Bỏ việc đã ứng tuyển và việc đang xem (excludeId).
 */
function viecGoiY(soLuong = 3, excludeId = null) {
  const applied = new Set(APPLICATIONS.map((a) => a.jobId));
  return [...JOBS]
    .filter((j) => j.id !== excludeId && !applied.has(j.id) && !daDongTuyen(j))
    .sort((a, b) => (b.match ?? -1) - (a.match ?? -1) || String(b.postedAt).localeCompare(String(a.postedAt)))
    .slice(0, soLuong);
}

/** Đổi % match thành high/mid/low — CSS dùng data-tone để tô màu. Ngưỡng khớp với config/matching.php */
function mucDoKhop(percent) {
  if (percent >= 80) return "high";
  if (percent >= 60) return "mid";
  return "low";
}

/** Ảnh đại diện: ảnh thật nếu có, không thì ô chữ cái đầu (SVG). */
function anhDaiDien(user) {
  if (user?.avatar) return user.avatar;
  const chu = (user?.firstName || user?.name || "?").trim().charAt(0).toUpperCase();
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="80" height="80" rx="40" fill="#6366f1"/><text x="50%" y="54%" font-family="Arial" font-size="34" fill="#fff" text-anchor="middle" dominant-baseline="middle">${chu}</text></svg>`;
  return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
}
