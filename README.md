# Quản lý công việc cá nhân

Ứng dụng web quản lý công việc cá nhân, xây dựng bằng **Laravel 12 (PHP 8.2) + MySQL**, giao diện **HTML/CSS/JS** thuần, tổ chức theo **Clean Architecture**.

## Chức năng

- **Tài khoản**: tự đăng ký, đăng nhập / đăng xuất, có sẵn tài khoản dùng thử. Mỗi tài khoản chỉ thấy dữ liệu của mình.
- **Công việc**: thêm, sửa, xóa, đánh dấu hoàn thành / chưa hoàn thành, xóa nhanh các việc đã xong
- **Danh mục** có icon (Công việc, Cá nhân, Học tập, Mua sắm, Sức khỏe, Khác) và **mức ưu tiên** (Cao / Trung bình / Thấp)
- **Hạn chót**: nút nhanh "Hôm nay", "Ngày mai"; đánh dấu việc đến hạn hôm nay và việc quá hạn
- **Hình ảnh**: đính kèm, đổi, gỡ ảnh cho từng việc; xem ảnh lớn. Ảnh riêng tư, chỉ chủ sở hữu xem được
- **Tìm kiếm** (gõ có dấu hoặc không dấu), **sắp xếp** (mới nhất, hạn chót, ưu tiên, tên), **lọc** theo trạng thái (Tất cả / Đang làm / Hôm nay / Quá hạn / Đã xong) và danh mục
- **Đầu trang**: lời chào theo giờ, tóm tắt việc cần làm, thanh tiến độ
- **Lịch sử hoạt động**: ghi lại thêm / sửa / hoàn thành / bỏ hoàn thành / đổi ảnh / xóa, nhóm theo ngày, biểu đồ hoàn thành 7 ngày
- **Hồ sơ**: ảnh đại diện, đổi tên, đổi mật khẩu, thống kê cá nhân
- Giao diện tối giản: nền trung tính, một màu nhấn xanh dương (đỏ chỉ dùng cho lỗi / quá hạn / xóa), icon SVG (`resources/views/partials/icons.blade.php`), hỗ trợ chế độ tối, responsive từ điện thoại 320px đến laptop
- Dữ liệu lưu trong MySQL nên **không bị mất khi tải lại trang**

### Tài khoản dùng thử

Tạo bởi `php artisan db:seed` (cấu hình trong `config/demo.php`), hiển thị sẵn trên trang đăng nhập:

| Email | Mật khẩu |
|---|---|
| demo@example.com | demo1234 (có sẵn 12 việc mẫu, ảnh minh họa và lịch sử 7 ngày) |
| khachhang@example.com | khach1234 |

Đặt `DEMO_ACCOUNTS_HINT=false` trong `.env` để ẩn gợi ý khi chạy thật.

## Kiến trúc

```
app/
├── Domain/                      # Tầng nghiệp vụ - PHP thuần, KHÔNG phụ thuộc Laravel
│   ├── Shared/                  # ImageUpload, ImageStorageInterface (dùng cho ảnh việc + ảnh đại diện)
│   ├── Task/
│   │   ├── Entities/            # Task (chủ sở hữu, ảnh, danh mục, ưu tiên, hạn chót), TaskActivity
│   │   ├── ValueObjects/        # TaskDetails, TaskCategory, TaskPriority, TaskAction
│   │   ├── Repositories/        # TaskRepositoryInterface, TaskActivityRepositoryInterface
│   │   ├── UseCases/            # GetTasks, GetTask, CreateTask, UpdateTask, SetTaskCompletion,
│   │   │                        # SetTaskImage, DeleteTask, ClearCompletedTasks,
│   │   │                        # GetTaskHistory, GetCompletionTrend
│   │   └── Exceptions/
│   └── User/
│       ├── Entities/User.php
│       ├── Repositories/UserRepositoryInterface.php
│       ├── Services/PasswordHasherInterface.php
│       └── UseCases/            # RegisterUser, GetUser, UpdateProfile, ChangePassword
├── Data/                        # Tầng dữ liệu - hiện thực các interface của Domain
│   ├── Shared/                  # LocalImageStorage (storage/app/private)
│   ├── Task/                    # TaskModel, TaskActivityModel, Eloquent*Repository
│   └── User/                    # UserModel (dùng cho đăng nhập), EloquentUserRepository, LaravelPasswordHasher
├── Presentation/                # Tầng giao diện - HTTP
│   └── Http/
│       ├── Controllers/         # Auth/Login, Auth/Register, TaskPage, Task (API), TaskImage,
│       │                        # History, Profile
│       ├── Requests/            # Validate dữ liệu đầu vào
│       ├── Resources/           # Chuyển Entity -> JSON
│       ├── ViewComposers/       # Thông tin người dùng hiện tại cho layout
│       └── TaskOptions.php      # Nhãn tiếng Việt + biểu tượng cho danh mục / ưu tiên
└── Providers/TaskServiceProvider.php  # Bind interface (Domain) -> hiện thực (Data)

resources/views/                       # layouts/, partials/, auth/, tasks/, history/, profile/
public/css/app.css, public/js/tasks.js # CSS + JS (fetch API, không cần build)
```

**Quy tắc phụ thuộc:** `Presentation → Domain ← Data`. Domain chỉ biết interface; Data hiện thực interface đó; Laravel container nối hai bên trong `TaskServiceProvider`. Nhờ vậy có thể đổi MySQL sang nguồn dữ liệu khác mà không sửa Domain, và test Domain không cần database (xem `tests/Support/InMemoryTaskRepository.php`).

## API

Tất cả yêu cầu đăng nhập; chỉ thao tác được trên công việc của chính mình (của người khác trả 404).

| Method | URL                     | Body                                              | Mô tả                      |
|--------|-------------------------|---------------------------------------------------|----------------------------|
| GET    | `/api/tasks`            |                                                   | Danh sách (mới nhất trước) |
| POST   | `/api/tasks`            | multipart: `title`, `category`, `priority`, `due_date`, `image` (trừ title đều tùy chọn) | Thêm công việc |
| PUT    | `/api/tasks/{id}`       | `{"title", "category", "priority", "due_date"}`   | Sửa công việc              |
| PATCH  | `/api/tasks/{id}`       | `{"completed": true}`                             | Đổi trạng thái             |
| DELETE | `/api/tasks/{id}`       |                                                   | Xóa công việc              |
| DELETE | `/api/tasks/completed`  |                                                   | Xóa tất cả việc đã xong    |
| POST   | `/api/tasks/{id}/image` | multipart: `image`                                | Thêm / đổi ảnh             |
| DELETE | `/api/tasks/{id}/image` |                                                   | Gỡ ảnh                     |
| GET    | `/tasks/{id}/image`     |                                                   | Xem ảnh                    |

`category`: work, personal, study, shopping, health, other · `priority`: high, medium, low · `due_date`: `YYYY-MM-DD`

Trang: `/login`, `/register`, `/` (công việc), `/history` (lịch sử), `/profile` (hồ sơ, ảnh đại diện, đổi mật khẩu). Các route nằm trong nhóm `web` nên yêu cầu header `X-CSRF-TOKEN` (trang đã tự gửi); đăng nhập/đăng ký giới hạn 10 lần/phút.

## Cài đặt (XAMPP trên macOS)

Yêu cầu: XAMPP có PHP ≥ 8.2, [Composer](https://getcomposer.org).

```bash
export PATH=/Applications/XAMPP/xamppfiles/bin:$PATH   # dùng PHP/MySQL của XAMPP

git clone <repo-url> && cd Quan_ly_cong_viec_ca_nhan
composer install
cp .env.example .env
php artisan key:generate

# Bật Apache + MySQL trong XAMPP, rồi tạo database
mysql -uroot -e "CREATE DATABASE quan_ly_cong_viec CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed   # tạo bảng + tài khoản dùng thử
```

Cấu hình MySQL mặc định trong `.env` (theo XAMPP): `root`, mật khẩu rỗng, database `quan_ly_cong_viec`.

### Cách 1 - Chạy nhanh

```bash
php artisan serve
```

Mở http://127.0.0.1:8000

### Cách 2 - Chạy qua Apache (VirtualHost)

DocumentRoot của Laravel phải trỏ vào thư mục `public/` (không đặt cả project vào `htdocs`, vì sẽ lộ file `.env`).

1. Bật vhost trong `/Applications/XAMPP/xamppfiles/etc/httpd.conf` - bỏ dấu `#` ở dòng:
   ```apache
   Include etc/extra/httpd-vhosts.conf
   ```
2. Thêm vào `/Applications/XAMPP/xamppfiles/etc/extra/httpd-vhosts.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName todo.test
       DocumentRoot "/duong/dan/toi/Quan_ly_cong_viec_ca_nhan/public"
       <Directory "/duong/dan/toi/Quan_ly_cong_viec_ca_nhan/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Thêm `127.0.0.1 todo.test` vào `/etc/hosts`.
4. Apache của XAMPP chạy với user `daemon`, không đọc được thư mục trong `/Users/<bạn>`. Hoặc đặt project ở thư mục Apache đọc được (ví dụ `/Applications/XAMPP/xamppfiles/htdocs/`), hoặc sửa trong `httpd.conf`:
   ```apache
   User <ten-user-macos>
   Group staff
   ```
5. Restart Apache trong XAMPP, mở http://todo.test

## Kiểm thử

```bash
php artisan test
```

- `tests/Unit/ArchitectureTest.php` - giữ đúng quy tắc phụ thuộc giữa các tầng
- `tests/Unit/Domain` - test thực thể và use case với repository trong bộ nhớ (không cần DB)
- `tests/Feature` - test API, danh mục/ưu tiên/hạn chót, sửa, xóa hàng loạt, đăng nhập/đăng ký, hồ sơ, ảnh, lịch sử + biểu đồ, phân quyền giữa các tài khoản (SQLite in-memory)

Chạy trên MySQL thật: `DB_CONNECTION=mysql DB_DATABASE=quan_ly_cong_viec_test php vendor/bin/phpunit`

GitHub Actions (`.github/workflows/ci.yml`, job `build`) tự chạy test cho mọi Pull Request vào `main`. Quy tắc làm việc nhóm: xem [CONTRIBUTING.md](CONTRIBUTING.md).
