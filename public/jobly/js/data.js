/**
 * Jobly — du lieu mau (mock)
 *
 * Ham khong dau (layCongTy, layViecTheoId) = minh viet — xem comment tren ham.
 * JOBS / USER / companyId = du lieu, giu tieng Anh de sau nay khop API.
 * find, filter, Number, Object.keys = san cua JavaScript.
 */

/** Hồ sơ user đang đăng nhập — trang Home (“Chào Bảo”) và Profile đọc cái này */
const USER = {
  id: "u1",
  name: "Lê Bảo", // tên đầy đủ (sidebar, profile)
  firstName: "Bảo", // tên ngắn — tiêu đề Home
  year: "Sinh viên năm 2",
  school: "Đại học Công Nghệ",
  major: "Công nghệ thông tin",
  location: "TP. Hồ Chí Minh",
  email: "lebao@student.edu.vn",
  phone: "0901 234 567",
  bio: "Sinh viên năm 2, thích UI/UX và frontend. Muốn tìm việc part-time để học hỏi và tích lũy kinh nghiệm thực tế.",
  avatar:
    "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=400&q=80",
  skills: ["PHP", "JavaScript", "SQL", "HTML/CSS", "Figma"], // mảng string — trang Profile
  cvFile: "CV_Bao.pdf",
  cvUpdated: "Cập nhật 3 ngày trước",
  profileScore: 82, // % vòng tròn AI trên rail
  stats: {
    applied: 12,
    interviewed: 5,
    hired: 2,
    avgMatch: 92,
  },
};

/**
 * Danh sách công ty. Đây là object (không phải array):
 * key = id ("may-creative"), value = thông tin công ty.
 * So sánh PHP: $COMPANIES['may-creative']['name']
 */
const COMPANIES = {
  "may-creative": {
    id: "may-creative",
    name: "Mây Creative",
    verified: true,
    tagline: "Công ty sáng tạo nội dung & thiết kế",
    size: "1.000–5.000 nhân sự",
    location: "Quận 1, TP.HCM",
    color: "#7C5CFF",
    initial: "M",
    cover:
      "https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80",
    jobsCount: 24,
    followers: "2.4k",
    about:
      "Mây Creative thiết kế trải nghiệm số cho thương hiệu trẻ. Team nhỏ, nhịp làm việc linh hoạt — phù hợp sinh viên muốn học UI/UX thật.",
    rating: 4.8,
    reviews: 128,
  },
  techwind: {
    id: "techwind",
    name: "TechWind",
    verified: true,
    tagline: "Product studio",
    size: "50–200 nhân sự",
    location: "Quận 7, TP.HCM",
    color: "#2563EB",
    initial: "T",
    about:
      "TechWind xây sản phẩm web cho startup. Frontend team dùng React, thích người chịu học và code sạch.",
    rating: 4.6,
    reviews: 86,
  },
  storylab: {
    id: "storylab",
    name: "StoryLab",
    verified: true,
    tagline: "Content house",
    size: "20–50 nhân sự",
    location: "Bình Thạnh, TP.HCM",
    color: "#EC4899",
    initial: "S",
    about:
      "StoryLab làm nội dung cho brand Gen Z. Môi trường trẻ, deadline rõ, mentor kèm từng brief.",
    rating: 4.7,
    reviews: 54,
  },
  novastack: {
    id: "novastack",
    name: "NovaStack",
    verified: true,
    tagline: "Backend & cloud",
    size: "200–500 nhân sự",
    location: "Thủ Đức, TP.HCM",
    color: "#0EA5E9",
    initial: "N",
    about:
      "NovaStack chuyên API và hạ tầng. Intern/part-time được pair với senior, code review hàng tuần.",
    rating: 4.5,
    reviews: 91,
  },
  "bloom-agency": {
    id: "bloom-agency",
    name: "Bloom Agency",
    verified: false,
    tagline: "Performance marketing",
    size: "20–50 nhân sự",
    location: "Quận 3, TP.HCM",
    color: "#F59E0B",
    initial: "B",
    about:
      "Bloom chạy campaign cho F&B và edtech. Intern được cầm việc thật, không chỉ đi meeting.",
    rating: 4.4,
    reviews: 37,
  },
  datanest: {
    id: "datanest",
    name: "DataNest",
    verified: true,
    tagline: "Data for decisions",
    size: "50–200 nhân sự",
    location: "Quận 1, TP.HCM",
    color: "#10B981",
    initial: "D",
    about:
      "DataNest giúp SME đọc hiểu dữ liệu. Intern học SQL, dashboard và cách kể chuyện bằng số.",
    rating: 4.9,
    reviews: 62,
  },
  "pixel-co": {
    id: "pixel-co",
    name: "Pixel & Co",
    verified: true,
    tagline: "Visual studio",
    size: "10–50 nhân sự",
    location: "Phú Nhuận, TP.HCM",
    color: "#F43F5E",
    initial: "P",
    about:
      "Studio thiết kế nhận diện và social visual. Làm việc theo sprint ngắn, feedback nhanh.",
    rating: 4.6,
    reviews: 44,
  },
  "wave-social": {
    id: "wave-social",
    name: "Wave Social",
    verified: true,
    tagline: "Social-first agency",
    size: "20–50 nhân sự",
    location: "Quận 2, TP.HCM",
    color: "#8B5CF6",
    initial: "W",
    about:
      "Wave Social vận hành kênh TikTok/IG cho brand. Ca làm việc linh hoạt, phù hợp lịch học.",
    rating: 4.5,
    reviews: 71,
  },
};

/**
 * Danh sách việc làm — array (mảng). Mỗi phần tử {} là 1 job.
 * id phải UNIQUE. companyId phải khớp key trong COMPANIES.
 * match = % phù hợp (số). image = URL ảnh bìa thẻ.
 * Home / Explore / Chi tiết đều đọc mảng này.
 */
const JOBS = [
  {
    id: 1,
    title: "UI/UX Designer",
    companyId: "may-creative",
    company: "Mây Creative",
    salary: "10–13 triệu/tháng",
    location: "Quận 1, TP.HCM",
    type: "Part-time",
    hours: "3–5 giờ/ngày",
    skills: ["Figma", "Photoshop", "UI/UX", "Creative"],
    match: 92,
    image:
      "https://images.unsplash.com/photo-1561070791-2526d30994b5?auto=format&fit=crop&w=1200&q=80",
    description:
      "Thiết kế giao diện cho app và landing page của khách hàng trẻ. Bạn sẽ làm wireframe, UI kit và prototype trên Figma, làm việc trực tiếp với PM và developer.",
    requirements: [
      "Biết Figma (wireframe, auto-layout là lợi thế)",
      "Có cảm quan thị giác, portfolio dù nhỏ cũng được",
      "Sinh viên năm 2 trở lên, làm được 3–5 giờ/ngày",
      "Giao tiếp tiếng Việt rõ ràng, nhận feedback tốt",
    ],
    benefits: [
      "Mentor 1:1 với lead designer",
      "Lịch ca linh hoạt theo tuần học",
      "Được đứng tên trên case study (nếu release)",
      "Hỗ trợ ăn trưa những ngày onsite",
    ],
    whyMatch: {
      pros: [
        "Kỹ năng UI/UX và Figma phù hợp",
        "Thời gian 3–5 giờ/ngày phù hợp lịch học",
        "Địa điểm Quận 1 — gần nơi bạn sống",
        "Kinh nghiệm HTML/CSS giúp làm việc với dev",
      ],
      cons: ["Motion Design (có thể học thêm)"],
      comment:
        "Công việc này khá phù hợp với nền tảng hiện tại của bạn. Bạn đã có tư duy giao diện và HTML/CSS, chỉ cần bổ sung thêm Figma cơ bản.",
    },
  },
  {
    id: 2,
    title: "Frontend Developer",
    companyId: "techwind",
    company: "TechWind",
    salary: "18–22 triệu/tháng",
    location: "Quận 7, TP.HCM",
    type: "Part-time",
    hours: "3–5 giờ/ngày",
    skills: ["JavaScript", "HTML/CSS", "React", "Git"],
    match: 86,
    image:
      "https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1200&q=80",
    description:
      "Làm UI các module nhỏ trên dashboard sản phẩm. Code HTML/CSS/JS, dần làm quen React dưới sự hướng dẫn của senior.",
    requirements: [
      "Nắm JavaScript ES6 và HTML/CSS",
      "Biết Git cơ bản",
      "Ham học React (chưa biết vẫn nhận)",
      "Có thể onsite 2 buổi/tuần tại Quận 7",
    ],
    benefits: [
      "Pair programming với senior",
      "Review code mỗi tuần",
      "Có thể chuyển full-time sau 6 tháng",
      "Máy làm việc nếu onsite",
    ],
    whyMatch: {
      pros: [
        "JavaScript và HTML/CSS khớp CV",
        "Sinh viên IT — đúng JD",
        "Ca 3–5 giờ phù hợp",
        "Mức lương part-time tốt",
      ],
      cons: ["React chưa có trong CV", "Quận 7 hơi xa nếu ở trung tâm"],
      comment:
        "Nền tảng frontend của bạn đủ để vào team. React có thể học on the job — đây là cơ hội lên level khá rõ.",
    },
  },
  {
    id: 3,
    title: "Content Creator",
    companyId: "storylab",
    company: "StoryLab",
    salary: "8–12 triệu/tháng",
    location: "Bình Thạnh, TP.HCM",
    type: "Part-time",
    remote: true,
    hours: "4 giờ/ngày",
    skills: ["Copywriting", "TikTok", "Canva", "Storytelling"],
    match: 78,
    image:
      "https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=1200&q=80",
    description:
      "Viết script ngắn, caption và ý tưởng video cho brand Gen Z. Làm việc theo brief tuần, không cần xuất hiện trước camera.",
    requirements: [
      "Viết tiếng Việt tự nhiên, đúng chính tả",
      "Am hiểu TikTok/Reels",
      "Biết Canva là lợi thế",
      "Deadline rõ, chủ động hỏi khi kẹt brief",
    ],
    benefits: [
      "Được credit trên video nếu script được dùng",
      "Workshop kể chuyện mỗi tháng",
      "Hybrid: 1 ngày onsite / còn lại remote",
      "Thưởng theo video viral",
    ],
    whyMatch: {
      pros: [
        "Lịch part-time linh hoạt",
        "Môi trường trẻ, đúng độ tuổi sinh viên",
        "Có thể remote phần lớn thời gian",
      ],
      cons: ["Chưa thấy portfolio content trong CV", "Ít liên quan stack kỹ thuật"],
      comment:
        "Việc này hợp nếu bạn muốn thử creative. Match không cao vì CV đang nghiêng về kỹ thuật — vẫn làm được nếu thích viết.",
    },
  },
  {
    id: 4,
    title: "Backend Developer",
    companyId: "novastack",
    company: "NovaStack",
    salary: "16–20 triệu/tháng",
    location: "Thủ Đức, TP.HCM",
    type: "Part-time",
    hours: "4–6 giờ/ngày",
    skills: ["PHP", "SQL", "Laravel", "API"],
    match: 89,
    image:
      "https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=1200&q=80",
    description:
      "Tham gia viết API nhỏ, tối ưu query và sửa bug trên hệ thống Laravel. Được gán ticket vừa sức, có senior review.",
    requirements: [
      "Biết PHP và SQL",
      "Đã từng làm Laravel hoặc MVC tương tự",
      "Hiểu REST API cơ bản",
      "Sinh viên năm 2+, cam kết ít nhất 4 giờ/ngày",
    ],
    benefits: [
      "Mentor backend có kinh nghiệm product",
      "Được đụng code production (sau onboarding)",
      "Hỗ trợ gửi xe + trà/cà phê",
      "Lộ trình intern → junior rõ",
    ],
    whyMatch: {
      pros: [
        "Kỹ năng PHP phù hợp",
        "Biết SQL",
        "Là sinh viên — đúng JD",
        "Thời gian làm việc phù hợp",
        "Stack gần với hướng học hiện tại",
      ],
      cons: ["Laravel chưa ghi rõ trong CV"],
      comment:
        "Đây là match kỹ thuật khá tốt. PHP và SQL của bạn đủ để onboard; Laravel có thể bắt nhịp trong vài tuần.",
    },
  },
  {
    id: 5,
    title: "Marketing Intern",
    companyId: "bloom-agency",
    company: "Bloom Agency",
    salary: "5–8 triệu/tháng",
    location: "Quận 3, TP.HCM",
    type: "Thực tập",
    hours: "4 giờ/ngày",
    skills: ["Marketing", "Canva", "Excel", "Research"],
    match: 74,
    image:
      "https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80",
    description:
      "Hỗ trợ research thị trường, lên lịch bài post và theo dõi báo cáo campaign. Phù hợp bạn muốn hiểu marketing từ gốc.",
    requirements: [
      "Chăm chỉ, chủ động ghi chép",
      "Excel/Google Sheet cơ bản",
      "Ham học quảng cáo số",
      "Onsite 4 buổi/tuần",
    ],
    benefits: [
      "Được cầm 1 mini-campaign sau tháng đầu",
      "Mentor performance",
      "Certificate khi hoàn thành 3 tháng",
      "Xét lương khi lên part-time",
    ],
    whyMatch: {
      pros: ["Lịch thực tập vừa phải", "Quận 3 đi lại dễ", "Môi trường agency năng động"],
      cons: ["Ít liên quan kỹ năng PHP/JS", "Onsite khá nhiều so với lịch học"],
      comment:
        "Match trung bình vì CV của bạn nghiêng kỹ thuật. Chỉ nên apply nếu bạn muốn chuyển sang marketing.",
    },
  },
  {
    id: 6,
    title: "Data Analyst Intern",
    companyId: "datanest",
    company: "DataNest",
    salary: "7–10 triệu/tháng",
    location: "Quận 1, TP.HCM",
    type: "Thực tập",
    hours: "3–5 giờ/ngày",
    skills: ["SQL", "Excel", "Python", "Dashboard"],
    match: 84,
    image:
      "https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80",
    description:
      "Làm sạch data, viết query SQL và vẽ dashboard đơn giản cho khách SME. Học cách đặt câu hỏi từ số liệu.",
    requirements: [
      "SQL cơ bản (SELECT, JOIN)",
      "Excel thành thạo",
      "Python là lợi thế",
      "Cẩn thận, thích soi số",
    ],
    benefits: [
      "Dataset thật, không phải bài tập",
      "Kèm 1 analyst trong 4 tuần đầu",
      "Hybrid 2 ngày/tuần",
      "Cơ hội stay sau kỳ thực tập",
    ],
    whyMatch: {
      pros: [
        "SQL trong CV khớp JD",
        "Sinh viên IT học data khá tự nhiên",
        "Địa điểm trung tâm",
        "Giờ làm linh hoạt",
      ],
      cons: ["Python/dashboard chưa thấy trong CV"],
      comment:
        "SQL là điểm cộng lớn. Bạn có thể bắt đầu bằng query và Excel, rồi học dashboard dần — fit khá tốt.",
    },
  },
  {
    id: 7,
    title: "Graphic Designer",
    companyId: "pixel-co",
    company: "Pixel & Co",
    salary: "9–12 triệu/tháng",
    location: "Phú Nhuận, TP.HCM",
    type: "Part-time",
    hours: "3–4 giờ/ngày",
    skills: ["Photoshop", "Illustrator", "Branding", "Figma"],
    match: 79,
    image:
      "https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&w=1200&q=80",
    description:
      "Thiết kế key visual, banner social và asset campaign. Làm việc theo moodboard, deadline từng wave.",
    requirements: [
      "Photoshop hoặc Illustrator",
      "Có 3–5 sản phẩm để xem (béan cũng được)",
      "Hiểu hierarchy và màu",
      "Nhận feedback không tự ái",
    ],
    benefits: [
      "Portfolio review mỗi tháng",
      "Được in ấn một số ấn phẩm thật",
      "Ca ngắn, dễ xếp lịch học",
      "Môi trường studio yên, ít họp",
    ],
    whyMatch: {
      pros: [
        "Figma trong CV là điểm cộng",
        "Ca ngắn 3–4 giờ",
        "Phú Nhuận trung bình về khoảng cách",
      ],
      cons: ["Thiếu Illustrator/Photoshop chuyên sâu"],
      comment:
        "Bạn có nền tảng thị giác từ Figma. Studio này hợp để luyện tay nghề graphic, nhưng cần bổ sung Photoshop.",
    },
  },
  {
    id: 8,
    title: "Social Media Intern",
    companyId: "wave-social",
    company: "Wave Social",
    salary: "6–9 triệu/tháng",
    location: "Quận 2, TP.HCM",
    type: "Thực tập",
    remote: true,
    hours: "3–5 giờ/ngày",
    skills: ["TikTok", "Instagram", "Caption", "Canva"],
    match: 71,
    image:
      "https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=1200&q=80",
    description:
      "Lên lịch đăng, soạn caption và theo dõi insight kênh. Thỉnh thoảng hỗ trợ quay clip ngắn tại văn phòng.",
    requirements: [
      "Dùng TikTok/IG thành thạo",
      "Viết caption vui, không sến",
      "Canva cơ bản",
      "Có mặt 3 buổi/tuần",
    ],
    benefits: [
      "Được thử format content mới",
      "Lịch ca theo tuần",
      "Team Gen Z, ít formal",
      "Thưởng KPI theo tăng follow",
    ],
    whyMatch: {
      pros: ["Giờ làm phù hợp sinh viên", "Văn hóa trẻ", "Không yêu cầu stack nặng"],
      cons: ["Chưa có kinh nghiệm social trong CV", "Khác hướng kỹ thuật đang học"],
      comment:
        "Việc dễ bắt nhịp nếu bạn thích mạng xã hội. Match vừa phải vì chưa thấy dấu ấn content trong hồ sơ.",
    },
  },
];

/** Danh sách hội thoại (cột trái trang Chat) */
const CONVERSATIONS = [
  {
    id: "may-creative",
    companyId: "may-creative",
    jobId: 1,
    last: "Chúng mình muốn mời bạn tham gia một buổi phỏng vấn online.",
    time: "09:20",
    unread: 1,
    online: true,
  },
  {
    id: "techwind",
    companyId: "techwind",
    jobId: 2,
    last: "Cảm ơn bạn đã gửi bài test. Team sẽ phản hồi trong 2 ngày.",
    time: "Hôm qua",
    unread: 0,
    online: false,
  },
  {
    id: "datanest",
    companyId: "datanest",
    jobId: 6,
    last: "Bạn có thể gửi thêm portfolio dashboard không?",
    time: "Thứ 2",
    unread: 2,
    online: true,
  },
  {
    id: "novastack",
    companyId: "novastack",
    jobId: 4,
    last: "Hồ sơ của bạn đã được chuyển cho tech lead.",
    time: "Tuần trước",
    unread: 0,
    online: false,
  },
];

/** Demo chat với recruiter Mây Creative */
const CHAT_THREAD = {
  companyId: "may-creative",
  jobId: 1,
  messages: [
    {
      id: 1,
      from: "recruiter",
      text: "Chào Bảo! 👋\nCảm ơn bạn đã quan tâm đến vị trí UI/UX Designer.",
      time: "09:12",
    },
    {
      id: 2,
      from: "user",
      text: "Dạ vâng, em đã tìm hiểu thêm về Mây Creative và rất thích cách team làm sản phẩm.",
      time: "09:18",
    },
    {
      id: 3,
      from: "recruiter",
      text: "Chúng mình muốn mời bạn tham gia một buổi phỏng vấn online. Bạn chọn giúp một khung giờ nhé.",
      time: "09:20",
    },
  ],
  quickReplies: ["Thứ 2 - 10:00", "Thứ 2 - 14:00", "Thứ 3 - 09:00"],
};

/**
 * Timeline mặc định cho trang tiến trình.
 * status: done | current | upcoming
 */
const APPLICATIONS = [
  {
    jobId: 1,
    appliedAt: "2026-09-04",
    steps: [
      { key: "applied", label: "Đã ứng tuyển", status: "done" },
      { key: "viewed", label: "Nhà tuyển dụng đã xem", status: "done" },
      { key: "shortlist", label: "Đã shortlist", status: "done" },
      { key: "interview", label: "Phỏng vấn", status: "current" },
      { key: "result", label: "Kết quả", status: "upcoming" },
    ],
  },
  {
    jobId: 2,
    appliedAt: "2026-09-07",
    steps: [
      { key: "applied", label: "Đã ứng tuyển", status: "done" },
      { key: "viewed", label: "Nhà tuyển dụng đã xem", status: "done" },
      { key: "shortlist", label: "Đã shortlist", status: "current" },
      { key: "interview", label: "Phỏng vấn", status: "upcoming" },
      { key: "result", label: "Kết quả", status: "upcoming" },
    ],
  },
  {
    jobId: 6,
    appliedAt: "2026-09-08",
    steps: [
      { key: "applied", label: "Đã ứng tuyển", status: "done" },
      { key: "viewed", label: "Nhà tuyển dụng đã xem", status: "current" },
      { key: "shortlist", label: "Đã shortlist", status: "upcoming" },
      { key: "interview", label: "Phỏng vấn", status: "upcoming" },
      { key: "result", label: "Kết quả", status: "upcoming" },
    ],
  },
];

const REVIEWS = [
  {
    name: "Minh Anh",
    role: "Intern UI",
    text: "Mentor dễ chịu, được làm việc trên file Figma thật chứ không phải task cho có.",
    stars: 5,
  },
  {
    name: "Khoa Trần",
    role: "Part-time Design",
    text: "Lịch linh hoạt mùa thi. Feedback nhanh, không bắt onsite mỗi ngày.",
    stars: 5,
  },
  {
    name: "Hà My",
    role: "Junior",
    text: "Team trẻ, họp ngắn. Đôi khi deadline gấp nhưng học được rất nhiều.",
    stars: 4,
  },
];

/** Tìm 1 job theo id. Number(id) vì URL luôn là chuỗi ("1"), còn job.id là số */
function layViecTheoId(id) {
  return JOBS.find((job) => job.id === Number(id));
}

/** Cover mặc định cho công ty chưa có ảnh riêng */
const ANH_BIA_MAC_DINH = [
  "https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=1600&q=80",
  "https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80",
  "https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1600&q=80",
];

/** Lấy công ty theo id. Nếu thiếu cover/jobsCount thì tự gán mặc định rồi trả về */
function layCongTy(companyId) {
  const c = COMPANIES[companyId];
  if (!c) return undefined;
  if (!c.cover) {
    const idx = Object.keys(COMPANIES).indexOf(companyId);
    c.cover = ANH_BIA_MAC_DINH[idx % ANH_BIA_MAC_DINH.length];
  }
  if (!c.jobsCount) c.jobsCount = 4 + (companyId.length % 9);
  if (!c.followers) c.followers = `${(companyId.length * 0.3).toFixed(1)}k`;
  return c;
}

/** Tất cả job thuộc 1 công ty — trang /companies */
function viecCuaCongTy(companyId) {
  return JOBS.filter((job) => job.companyId === companyId);
}

/** Đổi % match thành high/mid/low — CSS dùng data-tone để tô màu viên thuốc */
function mucDoKhop(percent) {
  if (percent >= 88) return "high";
  if (percent >= 75) return "mid";
  return "low";
}
