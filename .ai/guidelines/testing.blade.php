{{-- .ai/guidelines/testing.blade.php --}}

=== Testing rules ===

## Test Structure
- Feature tests: Multiple component interactions
- Unit tests: Individual components

## Critical Requirements
- **All test classes MUST extend `Tests\TestCase`**
- **All code changes must be tested**
- Recycle related models when possible
- **All tests MUST use the `#[Test]` PHPUnit attribute rather than the `test` method prefix**
- **All tests MUST use the AAA (Arrange-Act-Assert) test pattern to clearly define blocks**

## Common Commands
- `php artisan test` or `php artisan test --parallel`
- `php artisan test --parallel --filter=TestName`
- `php artisan test --parallel --coverage tests/Feature/` (use --parallel for performance with coverage)
