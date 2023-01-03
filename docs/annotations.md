# Annotations

There are cases that cannot be resolved automatically by static analysis. In these cases annotations could be used to guide resolvers to analyse latte templates correctly.

You can use these annotations:
* [`@phpstan-latte-ignore`](#phpstan-latte-ignore)
* [`@phpstan-latte-template`](#phpstan-latte-template)
* [`@phpstan-latte-var`](#phpstan-latte-var)
* [`@phpstan-latte-component`](#phpstan-latte-component)

Note: Annotations affects only class in which they are written. If child class overwrittes something by annotations it will not affect inherited methods because they are evaluated in context of parent class.

NOte: Annotations are evaluated only in allowed contexts. Annotation used in wrong context is silently ignored.
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
            $this->template->render(__DIR__ . '/MyControl.latte');
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
            $this->template->render(__DIR__ . $param); // <-- this will not be attepted to resolve (reported as expression that cannot be resolved)
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte');
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
            $this->template->render(__DIR__ . $param); // <-- this will not be resolved
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved
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
            $this->template->render(__DIR__ . $param); // <-- this will not be resolved when resolving render()
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved when resolving render()
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
            $this->template->render(__DIR__ . $param); // <-- this will not be resolved
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved
        }
    }

    public function renderAlternative(string $param): void {
        $this->template->something = $param; // <-- this variable will not be collected
        if ($param) {       
            $this->template->render(__DIR__ . $param); // <-- this will not be resolved
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- this will not be resolved
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
        $this->template->render($this->getTemplatePath($param)); // <-- will resolve all templates matching given pattern and will not report error of expression that cannot be evaluated
    }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-template {dir}/templates/{className}.*.latte */ // <-- will resolve all templates matching given pattern + anything resolved from method body
    public function render(string $param = null): void {
        if($param !== null) {     
            $this->template->render($this->getTemplatePath($param)); // <-- will be resolved normally (will report error of unresolved expression)
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- will be resolved normally
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
            $this->template->render($this->getTemplatePath($param)); // <-- will be ignored so no error now
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- will be resolved normally
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
            $this->template->render($this->getTemplatePath($param)); // <-- will be ignored so no error now
        } else {
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- will be resolved normally
        }
        // < -- all templates matching {dir}/templates/{className}.*.latte will be resolved from context of this method
    }
    public function renderAlternative(string $param = null): void {
        // < -- all templates matching {dir}/templates/{className}.*.latte will be resolved from context of this method too
    }
}
```

## `@phpstan-latte-var`
Allowed context: variable assign, render call, method, class

Value specifies variables and their types in given context.

Multiple annotations can be used on single element or at different levels to set multiple variables.

### Behaviour

When is used on template variable assign it will rewrite how this one assign statement is evaluated. 

When annotation is used on method it will add/change variables collected from assignments in that method and methods called from this method. This has higher priority than annotation over assignment itself.

When annotation is used on class it acts like it was added to all methods in class.

When this annotation is used on render call it will add/change variables available to template. Use on render call has highest priority.

When same variable name is defined in multiple context highest priority have annotation over render call, then method annotation, class annotation and annotations used on single assigment have lowest priority.

### Examples

```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-var string $myVar */
        $this->template->myVar = $param; // <-- myVar in template will have type string
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-var string */ // <-- variable name is optional when annotation is used on assignemnt
        $this->template->myVar = $param; // <-- myVar in template will have type string
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-var string $myVar */ // <-- you can explicitly set name of variable
        $this->template->{$param} = $param; // <-- myVar in template will have type string
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-var string $myVar */ // <-- myVar in template will have type string
    public function render(mixed $param): void {
        $this->template->myVar = $param; 
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    /** 
     * @phpstan-latte-var string $myVar // <-- myVar in template will have type string
     * @phpstan-latte-var string $secondVar // <-- secondVar in template will have type string
     */ 
    public function render(mixed $param): void {
        $this->template->myVar = $param; 
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-var string $myVar */ // <-- myVar in template will have type string (method annotation overrides annotations on assignments)
    public function render(mixed $param): void {
        /** @phpstan-latte-var int $myVar */ // <-- this is ignored
        $this->template->myVar = $param; 
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
/** @phpstan-latte-var string $myVar */ 
class MyControl extends Control
{
    public function render(mixed $param): void { // <-- myVar in template will have type string
        $this->template->render(__DIR__ . '/MyControl.latte');
    }

    public function renderAlternative(mixed $param): void { // <-- myVar in template will have type string
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
/** @phpstan-latte-var string $myVar */ 
class MyControl extends Control
{
    /** @phpstan-latte-var int $myVar */ // <-- this has higher priority that class annotation
    public function render(mixed $param): void { // <-- myVar in MyControl.latte template will have type int
        $this->template->render(__DIR__ . '/MyControl.latte');
    }

    public function renderAlternative(mixed $param): void { // <-- myVar in MyControl.alternative.latte template will have type string
        $this->template->render(__DIR__ . '/MyControl.alternative.latte');
    }
}
```

```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-var string $myVar */ // <-- myVar in template will have type string
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-var int $myVar */
    public function render(mixed $param): void {
        if ($param) {
            /** @phpstan-latte-var string $myVar */ // <-- this has higher priority than method annotation
            $this->template->render(__DIR__ . '/MyControl.latte'); // <-- myVar in template MyControl.latte will have type string
        } else {
            $this->template->render(__DIR__ . '/MyControl.alternative.latte'); // <-- myVar in template MyControl.alternative.latte will have type int
        }
    }
}
```

## `@phpstan-latte-component`
Allowed context: call to `addComponent`, component assign, render call, method, class

Value specifies components and their types in given context.

Multiple annotations can be used on single element or at different levels to set multiple components.

### Behaviour

Behaviour is identical to [`@phpstan-latte-var`](#phpstan-latte-var)

### Examples

```php
class MyControl extends Control
{
    public function render(): void {
        /** @phpstan-latte-component SomeControl $myComponent */
        $this['myComponent'] = $this->control; // <-- myComponent in template will have type SomeControl
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```


```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-component SomeControl */ // <-- component name is optional when annotation is used on assignemnt
        $this['myComponent'] = $this->control; // <-- myComponent in template will have type SomeControl
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-component SomeControl $myComponent */
        $this->addComponent($this->control, 'myComponent'); // <-- myComponent in template will have type SomeControl
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-component SomeControl */ // <-- component name is optional when annotation is used on addComponent
        $this->addComponent($this->control, 'myComponent'); // <-- myComponent in template will have type SomeControl
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    public function render(mixed $param): void {
        /** @phpstan-latte-var string $myVar */ // <-- you can explicitly set name of component
        $this[$param] = $this->control; // <-- myComponent in template will have type SomeControl
        $this->template->render(__DIR__ . '/MyControl.latte');
    }
}
```

```php
class MyControl extends Control
{
    /** @phpstan-latte-component SomeControl */ // <-- myComponent in template will have type SomeControl
    public function createComponentMyComponent() {
        // ...
    }
}
```

Usage on method or class is same as with [`@phpstan-latte-var`](#phpstan-latte-var)