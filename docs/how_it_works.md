# How it works, when doesn't and troubleshooting

In this section you can read how phpstan latte works, how to solve several issues which are reported by this extension.

## Variables

Variables are collected from PHP classes (e.g. Presenters or Controls) and they have to be sent to template via one of these ways:

```php
1) $this->template->foo = 'bar';

2) $this->template->add('foo', 'bar');

3) $this->template->setParameters(['foo' => 'bar']);

4) [$this->template->foo, $this->template->bar] = ['bar', 'baz'];

5) list($this->template->foo, $this->template->bar) = ['bar', 'baz'];

6) $this->template->render('path_to_latte.latte', ['foo' => 'bar', 'bar' => 'baz']);

7) $this->template->render('path_to_latte.latte', new SomeControlTemplateType());
```
It has to be done in correspondent methods like `actionFoo`, `renderFoo` in Presenters or `render`, `renderSomething` in Controls. Assigning in called methods, parent methods and / or global methods like `__construct`, `startup`, `beforeRender` are also accepted:
```php
class SomeControlExtendsControl
{
    public function render(): void
    {
        $this->assignToTemplate();
    }
    
    private function assignToTemplate(): void
    {
        $this->template->foo = 'bar';   // $foo will be available in latte because this method is called from render
    }
    
    private function neverCalledMethod(): void
    {
        $this->template->baz = 'bar';   // $baz will not be available in latte because this method is never called
    }
}
```

If you use some non-standard way of assigning of variables to template, you have to create your own [VariableCollector](extension.md#variable-collectors).


### Common errors
#### Variable $baz might not be defined
This error can have several reasons. PHPStan latte checks latte templates with some context. One latte template can be used in several components or methods of Presenter.
It is important to check the context first (text after path of latte file - rendered from, included in etc.)

1) Missing assignment
    ```
    ------ ------------------------------------------------------------------------------------------ 
     Line   modules/Presenters/templates/Foo/bar.latte rendered from App\Presenters\FooPresenter::bar
    ------ ------------------------------------------------------------------------------------------
     5      Variable $baz might not be defined.                      
    ------ ------------------------------------------------------------------------------------------
    ```
    Here we have to check FooPresenter and its method(s) actionBar and/or renderBar. If there is no `$baz` sent to template, you have to add it and this error will disappear.  

2) Missing action/render, but variable is used in template
    ```
    ------ ------------------------------------------------------------------------------------- 
     Line   modules/Presenters/templates/Foo/bar.latte rendered from App\Presenters\FooPresenter
    ------ -------------------------------------------------------------------------------------
     5      Variable $baz might not be defined.                      
    ------ ------------------------------------------------------------------------------------- 
    ```
    Nette is sometimes tricky how it handles latte templates. All latte files in `templates` directory can be visited even without Presenter's action/render method.
    In the example above we can see there is no `::bar` action after FooPresenter so this is exactly the case when `bar.latte` exists but `actionBar` neither `renderBar` exists, so no variables are sent to this template in `bar` context.

#### Variable $baz in isset() always exists and is not nullable
1) Variable is conditionally assigned
    ```php
    public function actionBar(): void
    {
        if ($someCondition) {
            $this->template->baz = 'bar';
        }
    }
    ```
    Then latte looks like this:
    ```latte
    {ifset $baz}
        do something
    {/ifset}
    ```

    This extension is using phpdoc for type hinting variables in compiled templates. So even if you assign variable in some condition, variable is always assigned, because PHP has no type like `undefined`.
    Compiled template looks as follows:
    ```php
    public function main() : array
    {
        extract($this->params);
        /** @var 'bar' $baz */

        /* line 1 */
        if (isset($baz)) {
            echo 'do something';
        }
    }
    ```

    PHPStan will report error:
    ```
    ------ ------------------------------------------------------------------------------------------ 
     Line   modules/Presenters/templates/Foo/bar.latte rendered from App\Presenters\FooPresenter::bar
    ------ ------------------------------------------------------------------------------------------
     1      Variable $baz in isset() always exists and is not nullable.                     
    ------ ------------------------------------------------------------------------------------------
    ```

    The easiest way how to fix this error is to assign `$baz` in all branches of your code. Type of variable will be union.
    ```php
    public function actionBar(): void
    {
        $this->template->baz = null;
        if ($someCondition) {
            $this->template->baz = 'bar';
        }
    }
    ```

    Now the type of `$baz` will be `'bar'|null` and isset() in condition will be valid.

<!-- TODO
## Components
-->

<!-- TODO
## Forms
-->
