# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] - 2026-03-26

### Breaking Changes
- **PHP requirement raised** to `^8.2` (dropped support for PHP < 8.2).
- **Laravel requirement raised** to `^13.0` (dropped support for Laravel 4.x–9.x).
- `setAttribute()` now returns `$this` (fluent) instead of `void`. Code that assigned the result or checked for void is unaffected.
- `setJsonAttribute()` now returns `$this` (fluent) instead of `void`. Same impact as above.
- `mutateAttribute()` now returns explicit `null` for missing or JSON-operator keys instead of a bare `return;`. Any code asserting strict `void` returns must be updated.

### Added
- GitHub Actions CI matrix covering PHP `8.2`, `8.3`, and `8.4` against Laravel 13 (`--prefer-lowest` and `--prefer-stable` variants).
- `orchestra/testbench ^11.0` added as a dev dependency for package-level integration test support.
- `getJsonOperatorPattern()` private helper on the `Json` trait — centralises the regex used to detect PSQL JSON operators (`->`, `->>`, `#>`, `#>>`).
- `extractJsonAttributeKey()` private helper on the `Json` trait — extracts the terminal key from a JSON expression such as `column->>'key'`.
- Regression test suite (`tests/RegressionTest.php`) with 15 tests covering JSON attribute discovery, visibility, get/set, hinting, dirty tracking, operator handling, and error cases.

### Changed
- Replaced removed `array_get()` global helper with `data_get()` in `getDirty()` (`illuminate/support` ^9 removed the global helper).
- `getMutatedAttributes()` now deduplicates the merged attribute list — duplicate keys from parent mutators and JSON columns will no longer appear more than once.
- `hasGetMutator()` refactored to use centralised `getJsonOperatorPattern()`.
- `mutateAttribute()` key extraction refactored to use `extractJsonAttributeKey()`.
- `phpunit.xml.dist` migrated to the PHPUnit 11.5 schema.
- `tests/JsonDialectTest.php` migrated from `PHPUnit_Framework_TestCase` to `PHPUnit\Framework\TestCase`.
- All `@expectedException` annotations replaced with `$this->expectException(...)` method calls.
- `tests/src/MockJsonDialectModel.php` updated to use `Illuminate\Database\Eloquent\Model` directly (removed legacy boot-array hack incompatible with modern Eloquent).

### Removed
- Travis CI configuration (`.travis.yml` retained for historical reference but is no longer active — replaced by GitHub Actions).
- Dev dependency on `phpunit/phpunit: 4.*`.
- Support for the legacy `Eloquent` model alias — models must now extend `Illuminate\Database\Eloquent\Model` directly.

### Security
- No security advisories found in updated dependencies (`composer audit` clean).

---

## [1.x] - Legacy

Supported Laravel 4.x through 9.x and PHP >= 5.4.
See Git history for individual change details.

