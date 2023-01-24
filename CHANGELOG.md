# Change Log

## [Unreleased][unreleased]

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

[unreleased]: https://github.com/efabrica-team/phpstan-latte/compare/0.5.0...HEAD
[0.5.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/efabrica-team/phpstan-latte/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/efabrica-team/phpstan-latte/compare/0b29bd7924d89c16d68d804fecdf5427197f2497...0.1.0
