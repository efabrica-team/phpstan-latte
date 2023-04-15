# How it works, when doesn't and troubleshooting

In this section you can read how to solve several issues which are reported by phpstan latte.

## Variables

Variables are collected from PHP classes (Presenters or Controls) and they have to be sent to template via one of these ways:

```php
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Resolve\SomeControlTemplateType;1) $this->template->foo = 'bar';

2) $this->template->add('foo', 'bar');

3) $this->template->setParameters(['foo' => 'bar']);

4) [$this->template->foo, $this->template->bar] = ['bar', 'baz'];

5) list($this->template->foo, $this->template->bar) = ['bar', 'baz'];

6) $this->template->render('path_to_latte.latte', ['foo' => 'bar', 'bar' => 'baz']);

7) $this->template->render('path_to_latte.latte', new SomeControlTemplateType());
```


### Variable $baz might not be defined

PHPStan latte checks latte template with some context. One latte template can be used in several components or methods of Presenter.
It is important to check the context first. 

```
 ------ ------------------------------------------------------------------------------------------ 
  Line   modules/Presenters/templates/Foo/bar.latte rendered from App\Presenters\FooPresenter::bar
 ------ ------------------------------------------------------------------------------------------
  5      Variable $baz might not be defined.                      
 ------ ------------------------------------------------------------------------------------------
```
Here we have to check FooPresenter and its method(s) actionBar and/or renderBar. If there is no `$baz` sent to template, you have to add it there.  


```
 ------ ------------------------------------------------------------------------------------- 
  Line   modules/Presenters/templates/Foo/bar.latte rendered from App\Presenters\FooPresenter
 ------ -------------------------------------------------------------------------------------
  5      Variable $baz might not be defined.                      
 ------ ------------------------------------------------------------------------------------- 
```
Nette is sometimes tricky how it handles latte templates. All latte files in `templates` directory can be visited even without Presenter's action/render method.
In the example above we can see there is no `::bar` action after FooPresenter so this is exactly the case when `bar.latte` exists but actionBar neither renderBar exists.
