
### FOR YOU ###

```php
// IF Laravel: add bootstrap/providers.php for Graceful Exit
VariableDebuggerLaravelServiceProvider::class

# Global Config
v_gl_config()
    ->presetCompact(15, false)
    ->presetDetailed(15, false)
    ->useWebThemeDark()
    ->useCliThemeLight()
//    ->withProperties() # warning (not use for global config)
    ->addClassProperties(\App\Models\User::class, ['attributes'])
    ->addClassPropertiesFromPluginLaravel()
    // ->addClassPropertiesFromPlugin(
    //      new VariableDebugClassPropertyPluginAdapterLaravel
    //  );

# Usecase
$u = \App\Models\User::factory()->create();

v_dump($u, ['x' => ['tmp1' => 1, 'tmp2' => 2]])->withProperties(['x.tmp1']);

v_dd($u, ['x' => 1, (object)['y' => 1]])
    ->showKeyOnly(['connection', 'attributes.name'], true)
    ->withProperties(['fillable', 'hidden', 'connection', 'attributes'])
    ->withoutProperties(['hidden'])
    ->addClassPropertiesFromPluginLaravel()
;
```

### FOR ME ####

```aiignore
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
```