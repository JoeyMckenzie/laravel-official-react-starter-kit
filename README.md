<div align="center" style="padding-top: 2rem;">
    <div style="display: inline-block;">
        <img src="https://img.shields.io/github/actions/workflow/status/joeymckenzie/laravel-official-react-starter-kit/run-ci.yml?branch=main&label=ci" alt="ci" />
        <img src="https://img.shields.io/github/actions/workflow/status/joeymckenzie/laravel-official-react-starter-kit/fix-code-styles.yml?branch=main&label=code%20style" alt="fix code styles" />
    </div>
</div>

# Laravel + React Starter Kit (Golden Path)

This is my own personal starter kit forked from the official starter kit. I've updated this kit to include some things
I'm quite fond of when working within a Laravel + React app:

- Max-level [PHPStan](https://phpstan.org/)
- [Pest](https://pestphp.com/) for test
- [Rector](https://github.com/rectorphp/rector) for refactoring
- [Biome](https://biomejs.dev/) for formatting and linting React/JS files
- Strict [Pint](https://github.com/nunomaduro/pint-strict-preset) formatting rules
- [Peck](https://github.com/peckphp/peck) for spellchecking
- [TS transformer](https://spatie.be/docs/typescript-transformer/v2/introduction) for types
- Local-first [CI](bin/ci)
