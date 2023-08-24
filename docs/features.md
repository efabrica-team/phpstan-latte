# Features
This document describes features which are available for phpstan latte, but they are not turn on by default. It may change in the future.

## transformDynamicFormControlNamesToString

- type: boolean
- default: false

This feature flag transforms dynamic names of form controls to string values.

For example if control name is `$item->name`, it is stored as string `"$item->name"`. The same dynamic name have to be used in latte template e.g. `{input $item->name}`

To turn this feature on, use:
```neon
parameters:
    latte:
        features:
            transformDynamicFormControlNamesToString: true    
```

## phpstanCommand

- type: nullable string
- default: null

Experimental feature which should speed up latte templates analysis. It executes defined command via shell exec and collect errors from this separated run.
As there is not possible to know the name of the directory where compiled templates are stored, you can use `{dir}` placeholder. We also add `--error-format json` to the end of command so you can't use your error formatter. But you can use any other phpstan command options like `configuration`, `level` etc.

Examples usage:
```neon
parameters:
    latte:
        features:
            phpstanCommand: "vendor/bin/phpstan analyse {dir}"
```

```neon
parameters:
    latte:
        features:
            phpstanCommand: "/some/cusetom/global/path/to/phpstan analyse {dir} --no-progress --configuration /some/cusetom/global/path/to/phpstan/config.max.neon"
```

## analyseLayoutFiles

- type: boolean
- default: false

This feature flag turns on testing layout files for each presenter's action

To turn this feature on, use:
```neon
parameters:
    latte:
        features:
            analyseLayoutFiles: true    
```
