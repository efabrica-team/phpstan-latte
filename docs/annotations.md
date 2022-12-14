# Annotations

There are cases that cannot be resolved automatically by static analysis. In these cases annotations could be used to guide resolvers to analyse latte templates correctly.

You can use these annotations:
* [`@phpstan-latte-ignore`](#phpstan-latte-ignore)

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
