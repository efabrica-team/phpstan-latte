# Change Log

## [Unreleased][unreleased]

## [0.1.0] - 2022-11-18
### Added
- Latte template rule which checks latte template in context of presenter / control
  - Latte compiler with post processors
    - Load variables for context of presenter / control (recursively)
    - Transform filters to explicit calls
    - Transform links to explicit calls
    - Transform components to explicit calls
- Error mapper for better DX

[unreleased]: https://github.com/efabrica-team/phpstan-latte/compare/0.1.0...HEAD
[0.1.0]: https://github.com/efabrica-team/phpstan-latte/compare/0b29bd7924d89c16d68d804fecdf5427197f2497...0.1.0
