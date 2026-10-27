<?php

/*
| Tài khoản dùng thử: được tạo bởi `php artisan db:seed` và gợi ý trên trang đăng nhập.
| Đặt DEMO_ACCOUNTS_HINT=false để ẩn gợi ý khi chạy thật.
|
| Mỗi công việc mẫu: due = số ngày so với hôm nay (âm = đã quá hạn), done = hoàn thành cách đây
| bao nhiêu ngày (null = chưa xong), image = ảnh minh họa được vẽ tự động khi seed.
*/

return [
    'show_on_login' => env('DEMO_ACCOUNTS_HINT', true),

    'accounts' => [
        [
            'name' => 'Tài khoản Demo',
            'email' => 'demo@example.com',
            'password' => 'demo1234',
            'tasks' => [
                ['title' => 'Nộp bài tập nhóm môn Kiến trúc phần mềm', 'category' => 'study', 'priority' => 'high', 'due' => -5, 'done' => 5],
                ['title' => 'Đọc xong cuốn "Atomic Habits"', 'category' => 'study', 'priority' => 'low', 'due' => null, 'done' => 3],
                ['title' => 'Chạy bộ 5km ở công viên', 'category' => 'health', 'priority' => 'low', 'due' => -2, 'done' => 2],
                ['title' => 'Tập gym 45 phút', 'category' => 'health', 'priority' => 'medium', 'due' => -1, 'done' => 1],
                ['title' => 'Thanh toán hóa đơn internet', 'category' => 'other', 'priority' => 'high', 'due' => -2, 'done' => null],
                ['title' => 'Hoàn thiện báo cáo quý III cho phòng kế toán', 'category' => 'work', 'priority' => 'high', 'due' => -1, 'done' => null],
                ['title' => 'Lên kế hoạch du lịch Đà Lạt cuối năm', 'category' => 'personal', 'priority' => 'low', 'due' => null, 'done' => null, 'image' => 'mountain'],
                ['title' => 'Chụp lại hóa đơn tiền điện tháng 10', 'category' => 'personal', 'priority' => 'medium', 'due' => 3, 'done' => null, 'image' => 'receipt'],
                ['title' => 'Mua rau, trứng và sữa cho cả tuần', 'category' => 'shopping', 'priority' => 'low', 'due' => 1, 'done' => null, 'image' => 'basket'],
                ['title' => 'Ôn tập chương 5 - Clean Architecture', 'category' => 'study', 'priority' => 'medium', 'due' => 2, 'done' => null, 'image' => 'books'],
                ['title' => 'Gửi email xác nhận lịch phỏng vấn', 'category' => 'work', 'priority' => 'high', 'due' => 0, 'done' => 0],
                ['title' => 'Họp nhóm dự án lúc 9h sáng', 'category' => 'work', 'priority' => 'medium', 'due' => 0, 'done' => null],
            ],
        ],
        [
            'name' => 'Khách hàng',
            'email' => 'khachhang@example.com',
            'password' => 'khach1234',
            'tasks' => [
                ['title' => 'Xem thử các chức năng của ứng dụng', 'category' => 'other', 'priority' => 'medium', 'due' => 0, 'done' => null],
                ['title' => 'Thêm một công việc kèm hình ảnh', 'category' => 'personal', 'priority' => 'low', 'due' => 1, 'done' => null],
            ],
        ],
    ],
];
