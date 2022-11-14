# PHPStan latte
PHPStan extension to check compiled latte templates in context of Presenter or Component etc.

## Installation
```shell
composer require efabrica/phpstan-latte --dev
```

Add these lines to your phpstan.neon:
```neon
includes:
    - vendor/efabrica/phpstan-latte/extension.neon
    - vendor/efabrica/phpstan-latte/rules.neon
```

## Setup
You can also add some parameters for phpstan latte extension. All of them are under latte key in parameters section:
- bool `strictMode` - defines if compiled template is declared as strict (`declare(strict_types=1);`), default: `false`
- array `macros` - list of methods to register macros in format `Class::method`. Default: `[Latte\Macros\CoreMacros::install, Latte\Macros\BlockMacros::install, Nette\Bridges\ApplicationLatte\UIMacros::install, Nette\Bridges\FormsLatte\FormMacros::install]`
- array `filters` - list of filters used in your apps. Name of filter is used as key, callback or function name is value. Default: `[translate: [Nette\Localization\Translator, translate]]`
- array `globalVariables` - list of variables and their types which are always defined in all your templates, default: `[]`
- array `errorPatternsToIgnore` - list of patterns which can be found in compiled latte specific error message. These errors are ignored, and they are not sent back to phpstan. Default: `[]`
- array `applicationMapping` - Should be the same as the mapping used in application. It is used for transforming links to correct method calls (`link SomePresenter:create` is transformed to `SomePresenter->actionCreate()` if mapping and method exists). Default: `[]`

For example:
```neon
parameters:
    latte:
        strictMode: true
        macros:
            - MyMacro::install
        filters:
            myFilter: [My\Global\Type, doFoo]
            functionFilter: strlen
        globalVariables:
            myGlobalStringVariable: string
            myOtherGlobalVariable: My\Global\Type
        errorPatternsToIgnore:
            - '/Unknown tag/'
        applicationMapping:
            *: App\*\Presenters\*Presenter
            Foo: Foo\Bar\Presenters\*Presenter
```
