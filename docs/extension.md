# How to extend this extension

This extension supports only common built-in methods for resolving paths to latte files, collecting variables, components, forms etc.
However, we know that in a wild there are applications which use their own methods for these purposes. That's why we create this extension extensible.

- [Template resolvers](#template-resolvers)
    - [Class method template resolvers](#class-method-template-resolvers)
    - [Class standalone template resolvers](#class-standalone-template-resolvers)
    - [Class template resolvers](#class-template-resolvers)
    - [Node template resolvers](#node-template-resolvers)
    - [Custom template resolvers](#custom-template-resolvers)
- [Latte context collectors](#latte-context-collectors)
    - [Variable collectors](#variable-collectors)
    - [Template path collectors](#template-path-collectors)
- [Node visitors](#node-visitors)
    - [Actual class](#actual-class)
    - [Forms](#forms)

## Template resolvers

Template resolvers are used to resolve what templates to analyse with what context (variables, components, ...). 

Built-in resolvers are:

- [`NetteApplicationUIControl`](../src/LatteTemplateResolver/Nette/NetteApplicationUIControl.php) - resolves templates in context of Control render* methods
- [`NetteApplicationUIPresenter`](../src/LatteTemplateResolver/Nette/NetteApplicationUIPresenter.php) - resolves templates in context of Presenter actions (based on action/render methods)
- [`NetteApplicationUIPresenterStandalone`](../src/LatteTemplateResolver/Nette/NetteApplicationUIPresenterStandalone.php) - resolves templates in context of Presenter standalone templates (without action/render methods)

You can create your own template resolver and register it in `phpstan.neon`:

```neon
services:
    - App\LatteTemplateResolver\MyTemplateResolver
```

### Class method template resolvers

Class method template resolvers are used to resolve templates in context of method calls like `render` methods.

You can create your own class method template resolver by extending [`AbstractClassMethodTemplateResolver`](../src/LatteTemplateResolver/AbstractClassMethodTemplateResolver.php).

```php
<?php

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteContext\Resolver\LatteContextResolverInterface;
use Efabrica\PHPStanLatte\LatteContext\Resolver\Nette\NetteApplicationUIControlLatteContextResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractClassMethodTemplateResolver;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Type\StringType;

final class MyTemplateResolver extends AbstractClassMethodTemplateResolver
{
    // resolver will be used only for this class and its children
    public function getSupportedClasses(): array
    {
        return ['App\MyControl']; 
    }

    // resolver will not be used for this class and its children
    public function getIgnoredClasses(): array
    {
        return ['App\AnotherControl']; 
    }

    // resolver will be used only for methods which name starts with "view"
    protected function getClassMethodPattern(): string
    {
        return '/^view.*/'; 
    }

    // you can use your own LatteContextResolver or use built-in one to resolve basic context
    protected function getClassContextResolver(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteContextResolverInterface
    {
        return new NetteApplicationUIControlLatteContextResolver($reflectionClass, $latteContext);            
    }

    // you can modify template context before it is used to resolve templates for example by adding context collected from methods
    protected function getClassGlobalTemplateContext(ReflectionClass $reflectionClass, LatteContext $latteContext): TemplateContext
    {
        parent::getClassGlobalTemplateContext($reflectionClass, $latteContext)
            ->union($latteContext->getMethodTemplateContext($reflectionClass->getName(), 'setTemplateData'));
    }

    // or you can modify only parts of template context like variables
    protected function getClassGlobalVariables(ReflectionClass $reflectionClass, LatteContext $latteContext): array
    {
        return ItemCombinator::merge(
            parent::getClassGlobalVariables($reflectionClass, $latteContext),
            [new Variable('myVar', new StringType())]
        );
    }
}
```

### Class standalone template resolvers

Class standalone template resolvers are used to resolve templates in context of class with no corresponding method.

You can create your own class standalone template resolver by extending [`AbstractClassStandaloneTemplateResolver`](../src/LatteTemplateResolver/AbstractClassStandaloneTemplateResolver.php).

```php
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteContext\Resolver\LatteContextResolverInterface;
use Efabrica\PHPStanLatte\LatteContext\Resolver\Nette\NetteApplicationUIControlLatteContextResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractClassMethodTemplateResolver;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Type\StringType;

final class MyTemplateResolver extends AbstractClassStandaloneTemplateResolver
{
    // resolver will be used only for this class and its children
    public function getSupportedClasses(): array
    {
        return ['App\MyControl']; 
    }

    // resolver will not be used for this class and its children
    public function getIgnoredClasses(): array
    {
        return ['App\AnotherControl']; 
    }

    // define regex patterns for standalone template paths
    protected function getTemplatePathPatterns(ReflectionClass $reflectionClass, string $dir) : array {
        $shortClassName = $reflectionClass->getShortName();
        return [
            $dir.'/templates/'.$shortClassName.'.latte',
            $dir.'/templates/'.$shortClassName.'.([a-zA-Z0-9_]?).latte',
        ];
    }

    // to prevent duplicit resolving of same template you can define logic to skip resolving of some templates as not-standalone
    protected function isStandaloneTemplate(ReflectionClass $reflectionClass, string $templateFile, array $matches) : bool {
        if(!is_string($matches[1])) {
            return count($this->getMethodsMatchingIncludingIgnored($reflectionClass, '/^render/')) === 0;
        }
        $action = $matches[1];
        return count($this->getMethodsMatchingIncludingIgnored($reflectionClass, '/^render'.preg_quote($action).'/')) === 0;
    }

    // tempalte context can be resolved and modified same was as in class method template resolver
}
```

### Class template resolvers

You can also create class template resolvers are used to resolve templates in context of class with custom logic different from method/standalone reolvers.

You can create your own class template resolver by extending [`AbstractClassTemplateResolver`](../src/LatteTemplateResolver/AbstractTemplateResolver.php).

```php
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Rules\RuleErrorBuilder;

abstract class MyTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassResult(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteTemplateResolverResult
    {
        // here write your own logic
    }
}
```

### Node template resolvers

If you need to resolve templates of different PHP parser node than class you can create your own node template resolver by implementing [`NodeLatteTemplateResolverInterface`](../src/LatteTemplateResolver/NodeLatteTemplateResolverInterface.php).

```php
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\LatteTemplateResolver\NodeLatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Application\UI\Control;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Type\StringType;

abstract class AbstractClassTemplateResolver implements NodeLatteTemplateResolverInterface
{
    private const PARAM_CLASS_NAME = 'className';

    // this method runs in initial phase of resolving templates for every parsed node and collects resolved nodes with some parameters
    public function collect(Node $node, Scope $scope): array
    {
        if(!$node instanceof Node\Stmt\ClassMethod) {
            return [];
        }
        return [
            new CollectedResolvedNode(
                static::class, // always pass current class here
                $scope->getFile(), // declare file where you want to run latte context collectors
                [self::PARAM_CLASS_NAME => $scope->getClassReflection()->getName()] // pass any primitive type parameters to resolve method
            )
        ];
    }

    // this method runs after all nodes and latte context colllectors finishes
    public function resolve(CollectedResolvedNode $resolvedNode, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        // add template with given context to set of analysed templates
        $result->addTemplate(new Template(
            'template/path.latte', // path to analysed template
            Control::class, // actual class passed to node visitors
            'resolved', // name of action (will show in error message)
            new TemplateContext(
                [new Variable('someVariable', new StringType())],
            ),
        ));
        return $result;
    }
}
```


### Custom template resolvers

If you need fully custom resolver you can implement [`CustomLatteTemplateResolverInterface`](../src/LatteTemplateResolver/CustomLatteTemplateResolverInterface.php).

This type of resolver can be used to resolve templates that have no corresponding code in app because they depends on code from some library and are sen only by configuration.

if you need list of all latte templates in analysed paths you can use service [`AnalysedTemplatesRegistry`](../src/Analyser/AnalysedTemplatesRegistry.php).

```php
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteTemplateResolver\CustomLatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Application\UI\Control;
use PHPStan\Type\StringType;

abstract class AbstractClassTemplateResolver implements CustomLatteTemplateResolverInterface
{
    // this method runs only once in initial phase of resolving templates and collects resolved nodes with some parameters
    public function collect(): array
    {
        return [
            new CollectedResolvedNode(
                static::class, // always pass current class here
                'path/to/file.php', // declare file where you want to run latte context collectors
                ['myParam' => 'value'] // pass any primitive type parameters to resolve method
            )
        ];
    }

    // this method runs after all nodes and latte context colllectors finishes
    public function resolve(CollectedResolvedNode $resolvedNode, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        // add template with given context to set of analysed templates
        $result->addTemplate(new Template(
            'template/path.latte', // path to analysed template
            Control::class, // actual class passed to node visitors
            'resolved', // name of action (will show in error message)
            new TemplateContext(
                [new Variable('someVariable', new StringType())],
            ),
        ));
        return $result;
    }
}
```

## Latte context collectors

Latte context collectors are used to collect information about context (variables, components, forms, ...) in which is then used by template resolvers to create context for analysed templates.

They also collects render calls and changes of rendered template path, which allows us to identify which templates are rendered where.

And they can collect other information which can be used by other collectors or template resolvers like method calls, etc.

### Variable collectors
VariableCollector is a service used to collect variables which can be used in compiled template.
It uses several sub collectors which basically finds:
- assigns to [$this->template->foo = 'bar';](../src/LatteContext/Collector/VariableCollector/AssignToTemplateVariableCollector.php)
- calls [$this->template->setParameters([...]);](../src/LatteContext/Collector/VariableCollector/SetParametersToTemplateVariableCollector.php)
- and more, see all [variable collectors](../src/LatteContext/Collector/VariableCollector)

You can implement your own variable collector implementing [`VariableCollectorInterface`](../src/LatteContext/Collector/VariableCollector/VariableCollectorInterface.php).
Then register it as a new service in config file.

### Template path collectors
TemplatePathCollector is a service which is used to find path to latte templates.
Now there is only one collector which finds `$template->setFile($path)`. So if you use some other way how to tell where the latte template is, feel free to implement [`TemplatePathCollectorInterface`](../src/LatteContext/Collector/TemplatePathCollector/TemplatePathCollectorInterface.php). Don't forget to register this new service in config file.

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

### Variables
With `VariablesNodeVisitorInterface` and `VariablesNodeVisitorBehavior` you will get all collected variables with global variables to your NodeVisitor.

```php
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;

final class MyNodeVisitor extends NodeVisitorAbstract implements VariablesNodeVisitorInterface
{
    use VariablesNodeVisitorBehavior;
    
    public function enterNode(Node $node)
    {
        foreach ($this->variables as $variable) {
            $this->doSomethingWithVariable($variable);
        }
    }
}
```

### Components
Collected components are available with `ComponentsNodeVisitorInterface` and with `ComponentsNodeVisitorBehavior` you will also can use method `findComponentByName` which returns you component matching name or null if component is not found.

```php
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ComponentsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ComponentsNodeVisitorInterface;

final class MyNodeVisitor extends NodeVisitorAbstract implements ComponentsNodeVisitorInterface
{
    use ComponentsNodeVisitorBehavior;
    
    public function enterNode(Node $node)
    {
        foreach ($this->components as $component) {
            $this->doSomethingWithComponent($component);
        }
        
        $this->findComponentByName('componentName');
        $this->findComponentByName('componentName-subcomponentName');
    }
}
```

### Filters
Global and collected filters are sent to NodeVisitor via `FiltersNodeVisitorInterface` and `FiltersNodeVisitorBehavior`.

```php
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorInterface;

final class MyNodeVisitor extends NodeVisitorAbstract implements FiltersNodeVisitorInterface
{
    use FiltersNodeVisitorBehavior;
    
    public function enterNode(Node $node)
    {
        foreach ($this->filters as $filter) {
            $this->doSomethingWithFilter($filter);
        }
    }
}
```

### Functions
Global and collected functions are sent to NodeVisitor via `FunctionsNodeVisitorInterface` and `FunctionsNodeVisitorBehavior`.

```php
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FunctionsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FunctionsNodeVisitorInterface;

final class MyNodeVisitor extends NodeVisitorAbstract implements FunctionsNodeVisitorInterface
{
    use FunctionsNodeVisitorBehavior;
    
    public function enterNode(Node $node)
    {
        foreach ($this->functions as $function) {
            $this->doSomethingWithFunction($function);
        }
    }
}
```

### Forms
To get list of forms available in template of actual Presenter or Control, you can use `FormsNodeVisitorInterface` with `FormsNodeVisitorBehavior`.

```php
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorInterface;

final class MyNodeVisitor extends NodeVisitorAbstract implements FormsNodeVisitorInterface
{
    use FormsNodeVisitorBehavior;
    
    public function enterNode(Node $node)
    {
        foreach ($this->forms as $form) {
            $this->doSomethingWithForm($form);
        }
    }    
}
```
