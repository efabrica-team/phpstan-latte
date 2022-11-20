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

Also add one of files `vendor/efabrica/phpstan-latte/latte2.neon` or `vendor/efabrica/phpstan-latte/latte3.neon` depending on which version of latte you use. 

## Setup
You can also add some parameters for phpstan-latte extension. All of them are under `latte` key in `parameters` section.

### strictMode
Type: `bool`

Defines if compiled template is declared as strict (`declare(strict_types=1);`).

Default:
```neon
parameters:
    latte:
        strictMode: false
```

Example:
```neon
parameters:
    latte:
        strictMode: true
```

### macros
Type: `array`

List of methods to register macros in format `Class::method`.

Default:
```neon
parameters:
    latte:
        macros:
            - Latte\Macros\CoreMacros::install
            - Latte\Macros\BlockMacros::install
            - Nette\Bridges\ApplicationLatte\UIMacros::install
            - Nette\Bridges\FormsLatte\FormMacros::install
```

Example:
```neon
parameters:
    latte:
        macros:
            - MyMacro::install
```

### filters
Type: `array`

List of filters used in your apps. Name of filter is used as key, callback or function name is value.

Default:
```neon
parameters:
    latte:
        filters:
            translate: [Nette\Localization\Translator, translate]
```

Example:
```neon
parameters:
    latte:
        filters:
            myFilter: [My\Global\Type, doFoo]
            functionFilter: strlen
```     

### globalVariables
Type: `array`

List of variables and their types which are always defined in all your templates.

Default:
```neon
parameters:
    latte:
        globalVariables: []
```

Example:
```neon
parameters:
    latte:
        globalVariables:
            myGlobalStringVariable: string
            myOtherGlobalVariable: My\Global\Type
```

### errorPatternsToIgnore
Type: `array`

List of patterns which can be found in compiled latte specific error message. These errors are ignored, and they are not sent back to phpstan.

Default:
```neon
parameters:
    latte:
        errorPatternsToIgnore: []
```

Example:
```neon
parameters:
    latte:
        errorPatternsToIgnore:
            - '/Unknown tag/'
```

### applicationMapping
Type: `array`

Application mapping should be the same as the mapping used in application. It is used for transforming links to correct method calls (`link SomePresenter:create` is transformed to `SomePresenter->actionCreate()` if mapping and method exists).

Default:
```neon
parameters:
    latte:
        applicationMapping: []
```

Example:
```neon
parameters:
    latte:
        applicationMapping:
            *: App\*\Presenters\*Presenter
            Foo: Foo\Bar\Presenters\*Presenter
```
