# PHPStan latte
PHPStan extension to check compiled latte templates in context of Presenter or Component etc.

## Installation
```shell
composer require efabrica/phpstan-latte --dev
```

## Setup
Add this to your phpstan.neon

```neon
includes:
    - vendor/efabrica/phpstan-latte/extension.neon
    - vendor/efabrica/phpstan-latte/rules.neon
```

You can also add some parameters for phpstan latte. All are under latte key:
- bool `strictMode` - defines if compiled template is declared as strict (`declare(strict_types=1);`), default `false`
- array `globalVariables` - list of variables and their types which are always defined in all your templates, default `[]`
- array `filters` - list of filters used in your apps. Name of filter is used as key, callback or function name is value. Default `[translate: [Nette\Localization\Translator, translate]]`

For example:
```neon
parameters:
    latte:
        strictMode: true
        globalVariables:
            myGlobalStringVariable: string
            myOtherGlobalVariable: My\Global\Type
        filters:
            myFilter: [My\Global\Type, doFoo]
```
