# Configuration
You can also add some parameters for phpstan-latte extension. All of them are under `latte` key in `parameters` section.

* [Latte engine configuration](#latte-engine-configuration)
* [Analyser configuration](#analyser-configuration)
* [Ignoring errors](#ignoring-errors)
* [Link checking](#link-checking)
* [Other configuration options](#other-configuration-options)

## Latte engine configuration


### engineBootstrap
Type: `string`

If provided this return value of this php file is used as Latte Engine.

If not provided default Latte Engine is used.

Example:
```neon
parameters:
    latte:
        engineBootstrap: latte.engine.php
```

Example `latte.engine.php`:

```php
<?php

$engine = new \Latte\Engine();
// ...
return $engine;
```

Example `latte.engine.php` for loading configured Engine from Nette application container:

```php
<?php

return App\Bootstrap::boot()->createContainer()->getService("latte.templateFactory")->createTemplate()->getLatte();
```

### macros (Latte 2 only)
Type: `array`

List of methods to register macros in format `Class::method`.

Macros known to Latte engine are added by default. If you use `engineBootstrap` you probably do not need to set this up manually. 

Example:
```neon
parameters:
    latte:
        macros:
            - MyMacro::install
```

### extensions (Latte 3 only)
Type: `array`

List of Latte extension classes.

Extensions known to Latte engine are added by default. If you use `engineBootstrap` you probably do not need to set this up manually. 

Example:
```neon
parameters:
    latte:
        extensions:
            - MyExtension()
```

### filters
Type: `array`

List of filters used in your apps. Name of filter is used as key, callback or function name is value.

Filters known to Latte engine are added by default. If you use `engineBootstrap` you probably do not need to set this up manually. 

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
            closureFilter: 'Closure(string, int): string'
            closureWithSlashFilter: '\Closure(string, int): string'
            callableFilter: 'callable(string, int): string'
```

## Analyser configuration

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

### resolveAllPossiblePaths
Type: `bool`

When expression containing variables is used as template path it is not resolved becase we do not know value of variable. 

With this option set to true we will search for all potentional templates that could match given expression. May lead to false positives.

Example:
```neon
parameters:
    latte:
        resolveAllPossiblePaths: true
```

### reportUnanalysedTemplates
Type: `bool`

When set to true all *.latte files in analysed paths that were not checked (because no render call of them was resolved) are reported as errors.

Example:
```neon
parameters:
    latte:
        reportUnanalysedTemplates: true
```

## Errors

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

### warningPatterns
Type: `array`

With our TableErrorFormatter, warnings are not count as errors, they are just printed to output. If you want to transform some errors to warnings, you can use this parameter. It is list of pattern strings.  

Default:
```neon
parameters:
    latte:
        warningPatterns: []
```

Example:
```neon
parameters:
    latte:
        warningPatterns:
            - '/Cannot automatically resolve latte template from expression\./'
```

## Link checking

### applicationMapping
Type: `array`

Application mapping should be the same as the mapping used in application. It is used for transforming links to correct method calls (`link SomePresenter:create` is transformed to `SomePresenter->actionCreate()` if mapping and method exists).

If not set link calls are not checked.

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

## Other configuration options

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
