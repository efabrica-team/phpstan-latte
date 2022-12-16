# Annotations

There are cases that cannot be resolved automatically by static analysis. In these cases annotations could be used to guide resolvers to analyse latte templates correctly.

You can use these annotations:
* [`@phpstan-latte-ignore`](#phpstan-latte-ignore)
* [`@phpstan-latte-template`](#phpstan-latte-ignore)

## `@phpstan-latte-ignore`
Allowed context: variable assign, method call, render call, method, class

Annotated part of code is not analysed. Analyser acts as if the annotated code (class/method/statement) does not exists at all.

### Examples
```php
class MyControl extends Control
{
    public function render(string $param): void {
        if ($param) {   
            /** @phpstan-latte-ignore */
            $this->template->something = $param; // <-- this variable will not be collected (by default all template variables assigned inside method are collected)
        } else {
            $this->render(__DIR__ . '/MyControl.latte');
        }
    }
}
```

```php
class MyControl extends Control
{
    public function render(string $param): void {
        if ($param) {       
            /** @phpstan-latte-ignore */   
            $this->render(__DIR__ . $param); // <-- this will not be attepted to resolve (reported as expression that cannot be resolved)
        } else {
            $this->render(__DIR__ . '/MyControl.latte');
        }
    }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-ignore */ // <-- whole method is ignored by resolvers and collectors
    public function render(string $param): void {
        $this->template->something = $param; // <-- this variable will not be collected
        if ($param) {       
            $this->render(__DIR__ . $param); // <-- this will not be resolved
        } else {
            $this->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved
        }
    }
}
```

```php
class MyControl extends Control
{
    public function render(string $param): void {
        /** @phpstan-latte-ignore */         
        $this->doRender(); // <-- this method call wil not be followed by resolvers (resolving renders or collecting variables inside called method)
    }

    private function doRender(): void {
        $this->template->something = $param; // <-- this variable will not be collected when resolving render()
        if ($param) {       
            $this->render(__DIR__ . $param); // <-- this will not be resolved when resolving render()
        } else {
            $this->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved when resolving render()
        }
    }
}
```

```php
/** @phpstan-latte-ignore */ // <-- whole class is ignored by resolvers and collectors
class MyControl extends Control
{
    public function render(string $param): void {
        $this->template->something = $param; // <-- this variable will not be collected
        if ($param) {       
            $this->render(__DIR__ . $param); // <-- this will not be resolved
        } else {
            $this->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved
        }
    }

    public function renderAlternative(string $param): void {
        $this->template->something = $param; // <-- this variable will not be collected
        if ($param) {       
            $this->render(__DIR__ . $param); // <-- this will not be resolved
        } else {
            $this->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved
        }
    }
}
```

## `@phpstan-latte-template`
Allowed context: render call, method, class

Value specifies what template or templates should be resolved and checked in given context.

Multiple annotations can be used on single element or at different levels to check multiple templates with same context.

### Behaviour

When this annotation is used on render call it will replace value passed as template to render call.

When annotation is used on method it will resolve given template in context of this method (alongside renders used in method body).

When annotation is used on class it acts like it was added to all methods in class.

### Annotation value

Wildcard `*` can be used to match multiple files.

Can use placeholders:
- `{dir}` = `__DIR__` (example `/app/dir/controls`)
- `{file}` = `__FILE__` (example `/app/dir/controls/MyControl.php`)
- `{baseName}` = `pathinfo(__FILE__, PATHINFO_BASENAME)` - file name including extension (example `MyControl.php`)
- `{fileName}` = `pathinfo(__FILE__, PATHINFO_FILENAME)` - file name without extension (example `MyControl`)
- `{className}` = class short name withnout namespace (example `MyControl`)
- (Do you need anything else? Create an issue)

### Examples

```php
class MyControl extends Control
{
    public function render(string $param): void {
        /** @phpstan-latte-template {dir}/templates/{className}.*.latte */
        $this->render($this->getTemplatePath($param)); // <-- will resolve all templates matching given pattern and will not report error of expression that cannot be evaluated
    }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-template {dir}/templates/{className}.*.latte */ // <-- will resolve all templates matching given pattern + anything resolved from method body
    public function render(string $param = null): void {
        if($param !== null) {     
            $this->render($this->getTemplatePath($param)); // <-- will be resolved normally (will report error of unresolved expression)
        } else {
            $this->render(__DIR__ . '/MyControl.latte'); // <-- will be resolved normally
        }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-template {dir}/templates/{className}.*.latte */ // <-- will resolve all templates matching given pattern + anything resolved from method body
    public function render(string $param = null): void {
        if($param !== null) {     
            /** @phpstan-latte-ignore */
            $this->render($this->getTemplatePath($param)); // <-- will be ignored so no error now
        } else {
            $this->render(__DIR__ . '/MyControl.latte'); // <-- will be resolved normally
        }
    }
}
```

```php
/** @phpstan-latte-template {dir}/templates/{className}.*.latte */ // <-- will resolve all templates matching given pattern in all class render methods + anything resolved from method bodies
class MyControl extends Control
{
    public function render(string $param = null): void {
        if($param !== null) {     
            /** @phpstan-latte-ignore */
            $this->render($this->getTemplatePath($param)); // <-- will be ignored so no error now
        } else {
            $this->render(__DIR__ . '/MyControl.latte'); // <-- will be resolved normally
        }
        // < -- all templates matching {dir}/templates/{className}.*.latte will be resolved from context of this method
    }
    public function renderAlternative(string $param = null): void {
        // < -- all templates matching {dir}/templates/{className}.*.latte will be resolved from context of this method too
    }
}
```