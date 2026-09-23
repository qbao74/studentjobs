<?php

return [
    'accepted' => ':Attribute phải được chấp nhận.',
    'array' => ':Attribute phải là danh sách.',
    'boolean' => ':Attribute chỉ nhận đúng hoặc sai.',
    'confirmed' => 'Xác nhận :attribute không khớp.',
    'email' => ':Attribute không đúng định dạng email.',
    'enum' => ':Attribute không hợp lệ.',
    'exists' => ':Attribute không tồn tại.',
    'file' => ':Attribute phải là một tệp.',
    'image' => ':Attribute phải là hình ảnh.',
    'in' => ':Attribute không hợp lệ.',
    'integer' => ':Attribute phải là số nguyên.',
    'max' => [
        'array' => ':Attribute không được quá :max mục.',
        'file' => ':Attribute không được lớn hơn :max KB.',
        'numeric' => ':Attribute không được lớn hơn :max.',
        'string' => ':Attribute không được dài quá :max ký tự.',
    ],
    'mimes' => ':Attribute phải là tệp dạng: :values.',
    'mimetypes' => ':Attribute phải là tệp dạng: :values.',
    'min' => [
        'array' => ':Attribute phải có ít nhất :min mục.',
        'file' => ':Attribute phải lớn hơn :min KB.',
        'numeric' => ':Attribute phải lớn hơn hoặc bằng :min.',
        'string' => ':Attribute phải có ít nhất :min ký tự.',
    ],
    'numeric' => ':Attribute phải là số.',
    'password' => [
        'letters' => ':Attribute phải có ít nhất một chữ cái.',
        'mixed' => ':Attribute phải có cả chữ hoa và chữ thường.',
        'numbers' => ':Attribute phải có ít nhất một chữ số.',
        'symbols' => ':Attribute phải có ít nhất một ký tự đặc biệt.',
        'uncompromised' => ':Attribute này đã từng bị lộ, vui lòng chọn mật khẩu khác.',
    ],
    'required' => 'Vui lòng nhập :attribute.',
    'string' => ':Attribute phải là chuỗi ký tự.',
    'unique' => ':Attribute đã được sử dụng.',
    'uploaded' => 'Tải :attribute lên thất bại.',
    'url' => ':Attribute không đúng định dạng đường dẫn.',

    'attributes' => [
        'name' => 'họ tên',
        'email' => 'email',
        'password' => 'mật khẩu',
    ],
];
