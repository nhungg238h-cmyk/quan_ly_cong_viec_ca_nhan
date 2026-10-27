# Quy tắc làm việc nhóm

Nhóm làm việc theo **GitHub Flow**: chỉ có nhánh `main` và các nhánh ngắn hạn cho từng việc.
Nhánh `main` luôn ở trạng thái chạy được.

## Thành viên

| Thành viên | Vai trò |
|---|---|
| @nhungg238h-cmyk | Trưởng nhóm (Admin) |
| @nguyenminhkhue12102005-collab | Thành viên (Write) |

## Quy trình cho mỗi thay đổi

1. Tạo **issue** mô tả việc cần làm.
2. Lấy code mới nhất và tạo nhánh từ `main`:
   ```bash
   git switch main && git pull
   git switch -c feature/12-dang-nhap
   ```
3. Commit nhỏ, thường xuyên; đẩy nhánh lên: `git push -u origin <ten-nhanh>`.
4. Mở **Pull Request** vào `main`, điền theo mẫu, ghi `Closes #<so-issue>`.
5. Chờ **CI xanh** và **ít nhất 1 thành viên khác duyệt** (tác giả không tự duyệt được).
6. Người review góp ý trong tab *Files changed*; tác giả sửa và push tiếp vào cùng nhánh,
   bấm *Resolve conversation* sau khi sửa xong từng góp ý.
7. Merge bằng **Squash and merge**. Nhánh được xóa tự động sau khi merge.
8. Trên máy: `git switch main && git pull`.

## Đặt tên nhánh

Dạng `loai/so-issue-mo-ta`, chữ thường, không dấu, nối bằng `-`.

| Tiền tố | Dùng khi | Ví dụ |
|---|---|---|
| `feature/` | Chức năng mới | `feature/12-dang-nhap` |
| `bugfix/` | Sửa lỗi | `bugfix/34-loi-tinh-diem` |
| `hotfix/` | Sửa gấp bản đang chạy | `hotfix/41-loi-ket-noi` |
| `docs/` | Chỉ sửa tài liệu | `docs/cap-nhat-readme` |
| `refactor/` | Cấu trúc lại code | `refactor/tach-service` |
| `chore/` | Cấu hình, thư viện | `chore/1-cau-hinh-repo` |

## Commit message

Theo [Conventional Commits](https://www.conventionalcommits.org/): `<type>(<scope>): <mô tả>`

- `feat`: chức năng mới · `fix`: sửa lỗi · `docs`: tài liệu · `style`: định dạng code
- `refactor`: cấu trúc lại code · `test`: kiểm thử · `chore`/`ci`: cấu hình, thư viện, CI

Mỗi commit làm một việc, mô tả ngắn dạng mệnh lệnh. Tiêu đề PR cũng viết theo quy ước này,
vì khi Squash and merge, tiêu đề PR trở thành commit trên `main`.

**Không bao giờ commit** `.env`, mật khẩu, khóa bí mật, thư mục `vendor/`.

## Pull Request tốt

- Nhỏ (dưới khoảng 400 dòng), chỉ một mục đích.
- Mô tả rõ cách kiểm thử.
- CI xanh trước khi nhờ review.

## Luật bảo vệ `main` (Ruleset `bao-ve-main`)

- Không push thẳng, không force push, không xóa nhánh `main`.
- Bắt buộc qua Pull Request với ít nhất 1 lượt duyệt; có commit mới thì phải duyệt lại.
- Phải giải quyết hết góp ý trước khi merge.
- CI (`build`) phải chạy thành công.
- Không ai nằm trong danh sách được bỏ qua luật, kể cả trưởng nhóm.

## Xử lý xung đột

Khi PR báo *This branch has conflicts*:

```bash
git switch <nhanh-cua-minh>
git fetch origin
git rebase origin/main
# sửa file, xóa các dấu <<<<<<< ======= >>>>>>>
git add <file-da-sua>
git rebase --continue
git push --force-with-lease
```

Chỉ force push trên nhánh của chính mình. Người mới có thể dùng `git merge origin/main`
(không cần force push) hoặc nút *Resolve conflicts* trên trang PR với xung đột nhỏ.
