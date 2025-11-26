
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
    ->addClassPropertiesFromPluginPDO()
    ->addBuildLaterClassProperties(\App\Models\User::class, function (\App\Models\User $user) {
        return [
            'attributes' => $user->getAttributes(),
        ]       
    })
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

```text
----------------
# Test App (use for test library)
##  composer.json
"require": {
    "php": "^8.2",
    "lightla/variable-debugger": "*@dev" 
}
"repositories":[
    {
        "type": "path",
        "url": "/var/www/this-library" # Docker mount host
    }
],

## Docker mount host for test app
- ../../composer.json:/var/www/this-library/composer.json:ro
- ../../src:/var/www/this-library/src:ro
- ../../tests:/var/www/this-library/tests:ro

----------------
# GIT Knowledge
## Case 1: Git exclude local
• File .git/info/exclude like .gitignore but existing only on my local 
• No have any change or commit on GitHub

## Case 2: Git assume-unchanged
• Báo cho Git "làm như file này không thay đổi"
• Dù bạn sửa file, Git vẫn ignore
• Dùng khi muốn giữ file nhưng không track changes

## GIT SHOW REMOTE TAG
git ls-remote --tags origin

## GIT DELETE TAG VIA Pattern
git tag \
| grep "^v1\.0\." \
| grep -Ev "v1\.0\.16|v1\.0\.17" \
| xargs -r git tag -d

----------------
# Test
./vendor/bin/pest this-library/tests
./vendor/bin/phpunit this-library/tests --testdox
./vendor/bin/pest this-library/tests --testdox
```