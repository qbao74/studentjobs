<?php

/*
| Luật chấm điểm khớp việc. Đổi trọng số ở đây, không cần sửa code.
| Tiêu chí không áp dụng được (tin không có dữ liệu) sẽ bị bỏ và chia lại tỷ trọng cho các tiêu chí còn lại.
*/

return [

    'weights' => [
        'skills' => 60,     // kỹ năng yêu cầu của tin
        'keywords' => 20,   // từ khóa mô tả/yêu cầu trùng với giới thiệu + CV
        'field' => 10,      // lĩnh vực ngành học
        'location' => 10,   // khu vực làm việc
    ],

    /* Kỹ năng bắt buộc nặng gấp đôi kỹ năng điểm cộng. */
    'skill_weight' => [
        'required' => 2,
        'optional' => 1,
    ],

    /* Chỉ cần trùng tỷ lệ này số từ khóa của tin là đạt tối đa tiêu chí từ khóa. */
    'keyword_full_coverage' => 0.35,

    'levels' => [
        80 => 'Rất phù hợp',
        60 => 'Khá phù hợp',
        40 => 'Phù hợp một phần',
        0 => 'Chưa phù hợp',
    ],

    /*
    | Lĩnh vực: dò trong tiêu đề/mô tả tin và trong ngành học của sinh viên (đã bỏ dấu, chữ thường).
    */
    'fields' => [
        'it' => [
            'label' => 'Công nghệ thông tin',
            'terms' => ['cong nghe thong tin', 'cntt', 'khoa hoc may tinh', 'ky thuat phan mem', 'he thong thong tin', 'lap trinh', 'developer', 'frontend', 'backend', 'software'],
        ],
        'design' => [
            'label' => 'Thiết kế',
            'terms' => ['thiet ke', 'designer', 'design', 'do hoa', 'my thuat', 'ui/ux'],
        ],
        'marketing' => [
            'label' => 'Marketing – Truyền thông',
            'terms' => ['marketing', 'truyen thong', 'content', 'social media', 'quan tri kinh doanh', 'thuong mai', 'bao chi'],
        ],
        'data' => [
            'label' => 'Dữ liệu – Phân tích',
            'terms' => ['data', 'du lieu', 'phan tich', 'analyst', 'thong ke', 'kinh te', 'toan ung dung'],
        ],
    ],

    /* Tên khác nhau của cùng một thành phố. */
    'cities' => [
        'hcm' => ['hcm', 'ho chi minh', 'sai gon', 'saigon', 'tp.hcm', 'thu duc'],
        'hn' => ['ha noi', 'hanoi'],
        'dn' => ['da nang', 'danang'],
        'ct' => ['can tho'],
    ],

];
