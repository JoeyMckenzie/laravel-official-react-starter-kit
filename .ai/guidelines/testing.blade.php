{{-- .ai/guidelines/testing.blade.php --}}

=== testing rules ===

## Test Structure
- Feature tests: Multiple component interactions
- Unit tests: Individual components

## Critical Requirements
- **All test classes MUST extend `Tests\TestCase`**
- **All code changes must be tested**
- Recycle related models when possible
- **All tests MUST use the `#[Test]` PHPUnit attribute rather than the `test` method prefix**
- **All tests MUST use the AAA (Arrange-Act-Assert) test pattern to clearly define blocks**

## Command Argument Order (CRITICAL)
**Laravel test command options MUST come FIRST before paths/filters:**
- ✅ `php artisan test --parallel --filter=TestName`
- ✅ `php artisan test --coverage tests/Feature/Api/`
- ✅ `php artisan test --coverage-html=coverage tests/Feature/Api/`
- ✅ `php artisan test --parallel --coverage-html=coverage tests/Feature/Api/`
- ❌ `php artisan test tests/Feature/Api/ --parallel` (WRONG - options must be first)
- ❌ `php artisan test --filter=TestName --parallel` (WRONG - parallel must come before filter)
- ❌ `php artisan test --parallel --coverage --coverage-html=coverage tests/` (WRONG - redundant coverage flags)

**Why:** Laravel parses arguments left-to-right and stops at first unknown option, passing remainder to PHPUnit. This is why `--filter` works despite not being in `--help`.

## Code Coverage
- Use `/coverage` directory for code coverage reports (already in .gitignore)
- **Coverage Flag Usage (CRITICAL):**
- Use `--coverage-html=directory` for HTML coverage reports (automatically enables coverage)
- Use `--coverage` for basic coverage output only
- **NEVER use both `--coverage` AND `--coverage-html` together** - causes "Too many arguments" error
- Generate HTML coverage with: `php artisan test --coverage-html=coverage`
- Generate basic coverage with: `php artisan test --coverage`

## Common Commands
- `php artisan test` or `php artisan test --parallel`
- `php artisan test --parallel tests/Feature/Api/DonationTest.php`
- `php artisan test --parallel --filter=TestName`
- `php artisan test --parallel --coverage tests/Feature/` (use --parallel for performance with coverage)
