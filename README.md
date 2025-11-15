

> ## Cách 1: Git exclude local
• File .git/info/exclude giống như .gitignore nhưng chỉ tồn tại trên máy bạn
• Không được commit lên GitHub
• Chỉ bạn thấy, người khác clone về không có

## Cách 2: Git assume-unchanged
• Báo cho Git "làm như file này không thay đổi"
• Dù bạn sửa file, Git vẫn ignore
• Dùng khi muốn giữ file nhưng không track changes
