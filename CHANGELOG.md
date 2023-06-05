# Change Log

## [Unreleased][unreleased]

### Fixed
- Removed empty string as 1st argument from method calls getControlPart() and getLabelPart()

## [0.13.0] - 2023-06-05
### Changed
- Separated collection of Form Containers
- Renamed CollectedFormField to CollectedFormControl (**BC break**) 
- Renamed FormFieldCollector to FormControlCollector (**BC break**)
- Renamed FormFieldFinder to FormControlFinder (**BC break**)
- Error message `Form field with name "xxx" probably does not exist.` has been changed to `Form control with name "xxx" probably does not exist.` (**BC break**)

### Added
- Support for numeric form container names

### Fixed
- Subcomponents in multi registered components
- Stubs for Nette\Bridges\FormsLatte\Runtime::item
- FilterString type contains also null because Nette cast all inputs to string first and null is also available
- Ignored incorrect calls from latte Checkbox::getControlPart('') and Checkbox::getLabelPart('')

## [0.12.0] - 2023-05-25
### Added
- Class template resolvers allows matching classes by pattern
- Collect renders also from calls to Latte\Engine
- TemplateRender sub collectors
- IComponent::render resolved as output call

### Fixed
- Not analysed templates use realpath
- Error formatter

## [0.11.0] - 2023-05-02
### Added
- Line of compilation error (if available) instead of -1 
- Handling for parse errors

### Fixed
- Typehint for `$this->global->snippetDriver` in compiled template

## [0.10.0] - 2023-04-21
### Changed
- Improved processing of block's missing parameters - default value is used if it is available

### Added
- Support for any expression as default value of block parameters

### Removed
- Tip about type of variable comming from PHPDoc (all variable types in compiled templates are from PHPDoc, so this tip doesn't make sense)

### Fixed
- String default values for blocks
- Blocks called with no parameters are also transfered to method call
- Dynamic labels in form templates

## [0.9.0] - 2023-04-11
### Changed
- Params in block / define are analysed in the same way as they are defined by developer - they are no longer optional with default value `null` 
- Changed compiled code for n:tag-if conditions with latte 2 (`$ʟ_if[0]` changed to `$ʟ_if0`)

### Added
- Support for try / catch in foreach
- Support for n:ifcontent
- Date of generated compiled template

### Fixed
- Line numbers for CachingIterator above foreach
- Stubs for Latte\Runtime\Filters::escapeJs (accepts also array)

## [0.8.0] - 2023-03-27
### Added
- Support for nette/utils ^4.0
- Support for nette/forms ^3.0
- Support for `default` macro / tag

## [0.7.0] - 2023-03-13
### Added
- Support for 'class::method' syntax in filters
- Support for functions
- Support for multiplier
- NodeVisitors using Type from Scope

### Fixed
- Static method calls on variables are not analysed
- Fixed evaluation of encapsed strings

## [0.6.0] - 2023-02-03
### Added
- stubs for filters accepting any value which could be converted to string
- Resolving of generic template types in latte context
- PresenterFactory bootstrap

### Fixed
- exit() and die() evaluated as early terminating call
- Resolve only public methods with name render* or action*
- Prevent errors when dynamic components are used
- Support for any filter using FilterInfo as first parameter

## [0.5.0] - 2023-01-24
### Added
- Compiled template cache for better performance of repeated analysis
- Better error messages for render/include of non existing template files
- PHPStan extension installer support
- Parameter excludePaths can be used to exclude latte templates from analysis
- PHPDoc cache
- Collecting dynamic template variables `$this->template->{$name} = $value`
- Support for union types for components

## Fixed
- Performance issues
- Errors for skipped items in array deconstruct 
- Removed no needed if statemen in compiled template for $formField->getLabel() when $formField is CheckboxList or RadioList 
- Ignore all named extra parameters in links check (Nette appends them to query string)

## [0.4.0] - 2023-01-16
### Added
- Collect FormField name default value
- Ignore error BaseControl::getControlPart() invoked with 1 parameter, 0 required
- Collecting variables via Template::add()
- Support persistent params in links
- Support for switch
- Support for n:form

### Fixed
- Type of $presenter variable in Control templates
- Prevent multiple require of engin bootstrap
- export-ignore unneeded files
- Paths in outputs (relative paths used)
- Catching and transforming Invalid link error 

## [0.3.0] - 2023-01-11
### Added
- Annotation `@phpstan-latte-ignore` can be used to ignore render calls, variable assignments, component creation, whole methods or classes.
- Annotation `@phpstan-latte-template` can be used to specify what template is used to render.
- Annotation `@phpstan-latte-var` can be used to specify what variables are available in template.
- Annotation `@phpstan-latte-component` can be used to specify what components are available in template.
- Resolve calls to `setView` and `sendTemplate` in presenters
- Collecting form fields across method calls
- Collecting form fields added by `addComponent`
- Interface for Custom resolvers
- Sub collectors for variables and template paths

## [0.2.0] - 2022-12-06
### Changed
- Used collectors to find all variables, components, method calls and templates to analyse
- LatteTemplateRule changed to CollectedDataNode

### Added
- LatteCompileErrorsRule to cover more errors in generated template code
- Added support for latte 3.x
- Added checks of included templates in context of parent template
- Check all render* methods in components (`{control component:subrender}`)
- Check subcomponents (`{control component-subcomponent}`)
- Support for more template path definitions (simple string, concatenation, `__DIR__`, `__FILE__`, but also simple function calls like str_replace etc.)
- Error formater with reference to class and included template
- Report of unanalyzed templates
- Support for basic forms
- Support for closure filters
- Collect variables passed as #2 parameter in $template->render*() methods

### Fixed
- Link params processing
- Merged variable types if more variables with same name are assigned to template

## [0.1.0] - 2022-11-18
### Added
- Latte template rule which checks latte template in context of presenter / control
  - Latte compiler with post processors
    - Load variables for context of presenter / control (recursively)
    - Transform filters to explicit calls
    - Transform links to explicit calls
    - Transform components to explicit calls
- Error mapper for better DX

[unreleased]: https://github.com/efabrica-team/phpstan-latte/compare/0.13.0...HEAD
[0.13.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.12.0...0.13.0
[0.12.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.11.0...0.12.0
[0.11.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.10.0...0.11.0
[0.10.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.9.0...0.10.0
[0.9.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.8.0...0.9.0
[0.8.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.7.0...0.8.0
[0.7.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.6.0...0.7.0
[0.6.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/efabrica-team/phpstan-latte/compare/0b29bd7924d89c16d68d804fecdf5427197f2497...0.1.0
