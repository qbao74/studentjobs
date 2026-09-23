<?php

/*
| Đọc CV và tin tuyển bằng mô hình ngôn ngữ.
| Không có AI_API_KEY thì project dùng bộ đọc theo luật hiện có.
*/

return [

    'key' => env('AI_API_KEY'),

    'url' => env('AI_API_URL', 'https://api.openai.com/v1/chat/completions'),

    'model' => env('AI_MODEL', 'gpt-4o-mini'),

    'timeout' => (int) env('AI_TIMEOUT', 25),

];
