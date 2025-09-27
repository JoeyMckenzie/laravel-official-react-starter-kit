{{-- .ai/guidelines/linting.blade.php --}}

=== Linting rules ===

## Backend Common Commands
- `composer lint` to run PHPStan for static code analysis
- `composer fmt` to run Pint (PHP-CS-Fixer) for formatting
- `composer fmt:test` to run Pint (PHP-CS-Fixer) to check for adherence to style rules
- `composer refactor` to run Rector for code refactoring
- `composer refactor:test` to run Rector to check for potential refactorings
- `composer ci` to run comprehensive code quality checks for both linting and formatting

## Frontend Common Commands
- `npm run lint` to run ESLint for static code analysis on JavaScript files
- `npm run lint:fix` to run ESLint for static code analysis on JavaScript files with fixes
- `npm run fmt` to run Prettier for formatting
- `npm run fmt:check` to run Prettier for formatting checks for adherence to style rules
- `npm run ci` to run comprehensive code quality checks for both linting and formatting
