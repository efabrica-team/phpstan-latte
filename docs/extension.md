# How to extend this extension

This extension supports only common built-in methods for resolving paths to latte files, collecting variables, components, forms etc.
However, we know that in a wild there are applications which use their own methods for these purposes. That's why we create this extension extensible.

## Variable collectors
VariableCollector is a service used to collect variables which can be used in compiled template.
It uses several sub collectors which basically finds:
- assigns to [$this->template->foo = 'bar';](../src/LatteContext/Collector/VariableCollector/AssignToTemplateVariableCollector.php)
- calls [$this->template->setParameters([...]);](../src/LatteContext/Collector/VariableCollector/SetParametersToTemplateVariableCollector.php)
- and more, see all [variable collectors](../src/LatteContext/Collector/VariableCollector)

You can implement your own variable collector implementing [`VariableCollectorInterface`](../src/LatteContext/Collector/VariableCollector/VariableCollectorInterface.php).
Then register it as a new service in config file.

## Template path collectors
TemplatePathCollector is a service which is used to find path to latte templates.
Now there is only one collector which finds `$template->setTemplate($path)`. So if you use some other way how to tell where the latte template is, feel free to implement [`TemplatePathCollectorInterface`](../src/LatteContext/Collector/TemplatePathCollector/TemplatePathCollectorInterface.php). Don't forget to register this new service in config file.

## Node visitors
Last but not least, we have to prepare code for PHPStan to analyse it. When Nette compiles the latte template to PHP, the final class is little messy. We use [PHP parser](https://github.com/nikic/PHP-Parser/) and its [NodeVisitor](https://github.com/nikic/PHP-Parser/blob/4.x/doc/component/Walking_the_AST.markdown#node-visitors) to clean it up.
All NodeVisitors are registered to service called `phpstanLatteNodeVisitorStorage` where they have their priority specified. The priority means when the NodeVisitor is executed (sorting in ascending order) and also which NodeVisitors are executed together (NodeVisitors with same priority are executed together). See more about this topic [here](https://github.com/nikic/PHP-Parser/blob/4.x/doc/component/Walking_the_AST.markdown#multiple-visitors).
Also see how this extension use NodeVisitors in [src/Compiler/NodeVisitor](../src/Compiler/NodeVisitor) and in [extension.neon](../extension.neon).
You can implement your own NodeVisitor and register it to `phpstanLatteNodeVisitorStorage`.
```neon
services:
    phpstanLatteNodeVisitorStorage:
        setup:
            - addNodeVisitor(123, MyNodeVisitor())
```

This extension also allows several behaviors which can be added to the NodeVisitor to enrich it with some additional data. These behaviors can be combined as desired.

### Actual class
If you need to use actual class name (e.g. actual Presenter or Control) in compiled template, you can use `ActualClassNodeVisitorInterface` in you NodeVisitor. We also prepared trait `ActualClassNodeVisitorBehavior` which can be used together with the interface.

```php
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;

final class MyNodeVisitor extends NodeVisitorAbstract implements ActualClassNodeVisitorInterface
{
    use ActualClassNodeVisitorBehavior;
    
    public function enterNode(Node $node)
    {
        // ...
        $this->doSomethingWithActualClass($this->actualClass);
        // ...
    }    
}
```

### Forms
To get list of forms registered to actual Presenter or Control, you can use `FormsNodeVisitorInterface` with `FormsNodeVisitorBehavior`.

```php
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorInterface;

final class MyNodeVisitor extends NodeVisitorAbstract implements FormsNodeVisitorInterface
{
    use FormsNodeVisitorBehavior;
    
    public function beforeTraverse(array $nodes)
    {
        $this->resetForms();    // Need to reset some properties from previous run
        return null;
    }
    
    public function enterNode(Node $node)
    {
        foreach ($this->forms as $form) {
            $this->doSomethingWithForm($form);
        }
    }    
}
```
