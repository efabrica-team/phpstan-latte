# Change Log

## [unreleased]
### Added
- Check if value outputted in template (or escaped for output) can be converted to string
- Support for PHP 8.4
- Caching of collected Latte context (variables, components, templates, ...) to improve performance
- Improved caching of compiled templates to improve performance
- Memoizing of repeatedly called methods to improve performance
### Fixed
- Fixed unwanted narrowing of template variable types

This version should significantly improve performace of repeated runs.

## [0.18] - 2025-07-22
### Updated
- Compatibility with PHPStan 2.x (**BC break**)
- Dropped support of PHPStan 1.x (**BC break**)

## [0.17.2] - 2025-07-21
### Fixed
- Maintenance release

## [0.17.1] - 2024-07-18
### Updated
- Coding standard
### Fixed
- RelatedFilesCollector collecting classes that were not present in vendor

## [0.17.0] - 2024-03-27
### Fixed
- Updated coding standard (Possible **BC break** - added `final` or `abstract` to (almost) all classes)
- Bleeding edge changes - updated typehints
- Fixed parameters parsing for multiline {define}
- Functions handling with FunctionExecutor in new Latte 
- Removed unformatPresenterClass of new PresenterFactory

## [0.16.3] - 2023-11-26
### Added
- Support for PHP 8.3

## [0.16.2] - 2023-11-22
### Fixed
- Fixed first class callable filters

## [0.16.1] - 2023-10-11
### Fixed
- Standalone presenter action template is not analysed if presenter dir and template dir are siblings
- Method call __toString() is considered template render call
- Compatibility with nette/application 3.1.14 (SnippetDriver renanamed to SnippetRuntime)

## [0.16.0] - 2023-08-24
### Changed
- All compiled templates from one run will be stored in one directory within tmpDir

### Added
- Feature: Separate phpstan command to analyse compiled templates (Turn this feature with parameter `latte.features.phpstanCommand: "vendor/bin/phpstan {dir}"`)
- Feature: Testing layout files for each presenter's action (Turn this feature with parameter `latte.features.analyseLayoutFiles: true`)

### Fixed
- Stubs for Latte\Essential\Filters
- Fixed include recursion failsafe that wrongly prevented expected analysis of templates in different contexts

## [0.15.0] - 2023-07-31
### Added
- Support for form groups
- Collecting options for checkbox list and radio list and report if some non-existing option is used
- Tip for error message "Latte template xxx.latte was not analysed"
- Tip for standalone templates
- Feature: Transform dynamic form controls to "dynamic" string (control with name $record->id will be transformed to "$record->id") (Turn this feature with parameter `latte.features.transformDynamicFormControlNamesToString: true`)
- Support for object shape variables
- latte extension to fileExtensions parameter to report unmatched errors also in latte (If it's causing any problems in your applications, please report issue and we will remove it)

### Fixed
- `If condition is always true` for CheckboxList::getLabelPart(), CheckboxList::getControlPart(), RadioList::getLabelPart() and RadioList::getControlPart() 

## [0.14.0] - 2023-07-26
### Changed
- Types handling - used smart extract feature from PHPStan (**Possible problems** please report any issue connected with variable types)
- Not defined variables are marked with error `Undefined variable ...` (**BC break** - if error was ignored, you need to change ignored error pattern)

### Added
- Type `int` as param for Runtime::item() method to support integer names of Form containers
- Transformer for ternary condition with is_object and dynamic form fields - it removes always true / always false condition errors
- Errors `Cannot call method endTag() on Nette\Utils\Html|string.` and `Cannot call method startTag() on Nette\Utils\Html|string.` added to ignore list until they are fixed in nette/forms
- Support for dynamic forms with known name
- Allowed `Stringable` as link destination parameter value if strict mode is not enabled

### Removed
- ignore-next-line for dynamic inputs - should be solved by removing ternary condition for dynamic inputs (If you have any issue with this, please report it)

### Fixed
- Collecting of conditionally defined (optioinal) variables from array
- "Cannot resolve latte template for action" when setView is used with bleeding edge
- Form classes can now have custom params in constructor

## [0.13.2] - 2023-07-10
### Fixed
- Avoid always terminating calls in links
- Transformed html attributes

## [0.13.1] - 2023-06-12
### Fixed
- Removed empty string as 1st argument from method calls getControlPart() and getLabelPart()
- Added missing stub for BaseControl
- Moved check for IntegerRange type to resolveStringsOrInts

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

[unreleased]: https://github.com/efabrica-team/phpstan-latte/compare/0.17.1...HEAD
[0.17.1]: https://github.com/efabrica-team/phpstan-latte/compare/0.17.0...0.17.1
[0.17.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.16.3...0.17.0
[0.16.3]: https://github.com/efabrica-team/phpstan-latte/compare/0.16.2...0.16.3
[0.16.2]: https://github.com/efabrica-team/phpstan-latte/compare/0.16.1...0.16.2
[0.16.1]: https://github.com/efabrica-team/phpstan-latte/compare/0.16.0...0.16.1
[0.16.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.15.0...0.16.0
[0.15.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.14.0...0.15.0
[0.14.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.13.2...0.14.0
[0.13.2]: https://github.com/efabrica-team/phpstan-latte/compare/0.13.1...0.13.2
[0.13.1]: https://github.com/efabrica-team/phpstan-latte/compare/0.13.0...0.13.1
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
