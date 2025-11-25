
### FOR YOU ###

```php
# Global Config
v_gl_config()
    ->configShort(15, false)
    ->configFull(15, false)
    ->useWebThemeDark()
    ->useCliThemeLight()
    ->withClassProperties(\App\Models\User::class, ['attributes'])
    ->addClassPropertiesFromPluginLaravel()
    // ->addClassPropertiesFromPlugin(
    //      new VariableDebugClassPropertyPluginAdapterLaravel
    //  );

# Usecase
$u = \App\Models\User::factory()->create();

v_dump($u, ['x' => ['tmp1' => 1, 'tmp2' => 2]])->includeProperties(['x.tmp1']);

v_dd($u, ['x' => 1, (object)['y' => 1]])
    ->showKeyOnly(true, ['connection', 'attributes.name'])
    ->includeProperties(['fillable', 'hidden', 'connection', 'attributes'])
    ->excludeProperties(['hidden'])
    ->addClassPropertiesFromPluginLaravel()
;
```

### FOR ME ####

## Cách 1: Git exclude local
• File .git/info/exclude giống như .gitignore nhưng chỉ tồn tại trên máy bạn
• Không được commit lên GitHub
• Chỉ bạn thấy, người khác clone về không có

## Cách 2: Git assume-unchanged
• Báo cho Git "làm như file này không thay đổi"
• Dù bạn sửa file, Git vẫn ignore
• Dùng khi muốn giữ file nhưng không track changes

./vendor/bin/pest this-library/tests
./vendor/bin/phpunit this-library/tests --testdox
./vendor/bin/pest this-library/tests --testdox