# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Ray.InputQuery integration for file upload handling
- Support for `AbstractFileUpload` return types in `InputFormParam` and `InputFormsParam`
- Ability to pass generated file upload objects directly to resource methods
- Integration tests for file upload functionality
- `InputParam` class for handling input query parameters
- Comprehensive file upload examples and documentation
- Comprehensive type definitions in `Types.php` for improved type safety

### Changed
- Updated `ray/input-query` dependency to version 0.1.0
- Updated `koriym/file-upload` dependency to version ^0.2.0
- Refactored file upload handling for improved consistency and readability
- Replaced `create` method with `newInstance` for inputQuery instantiation

### Fixed
- Parameter validation to prevent silent errors
- Code style issues in test files

### Dependencies
- `ray/input-query`: 0.1.0
- `koriym/file-upload`: ^0.2.0
- `ext-fileinfo`: * (required for Windows CI compatibility)

## [1.25.0] - 2024-12-22

### Added
- Request exception handler for JSON schema validation
- Arguments support for `JsonSchemaRequestExceptionHandlerInterface`

### Changed
- Enhanced JSON schema validation error handling

## [1.24.0] - 2024-11-30

### Added
- PHP 8.4 support in CI workflow
- PHPStan baseline configuration

### Changed
- Update `rize/uri-template` requirement from ^0.3 to ^0.4
- Update method parameters to allow nullable types
- Refactor codebase for improved readability and readonly properties
- Replace `assertSame` with `assertJsonStringEqualsJsonString` in tests
- Update copyright year to 2025

## [1.23.0] - 2024-10-01

### Changed
- Make `koriym/json-schema-faker` package optional

## Earlier versions

Please refer to the git history for changes in earlier versions.

[Unreleased]: https://github.com/bearsunday/BEAR.Resource/compare/1.25.0...HEAD
[1.25.0]: https://github.com/bearsunday/BEAR.Resource/compare/1.24.0...1.25.0
[1.24.0]: https://github.com/bearsunday/BEAR.Resource/compare/1.23.0...1.24.0
[1.23.0]: https://github.com/bearsunday/BEAR.Resource/compare/1.22.5...1.23.0
