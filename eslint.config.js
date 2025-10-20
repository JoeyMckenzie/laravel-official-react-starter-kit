import js from '@eslint/js';
import prettier from 'eslint-config-prettier/flat';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import typescript from 'typescript-eslint';

/** @type {import('eslint').Linter.Config[]} */
export default [
    js.configs.recommended,
    ...typescript.configs.recommendedTypeChecked,
    ...typescript.configs.strictTypeChecked,
    ...typescript.configs.stylisticTypeChecked,
    {
        languageOptions: {
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },
    },
    {
        ...react.configs.flat.recommended,
        ...react.configs.flat['jsx-runtime'],
        languageOptions: {
            globals: {
                ...globals.browser,
            },
        },
        rules: {
            'react/react-in-jsx-scope': 'off',
            'react/no-unescaped-entities': 'warn',
            'react/jsx-no-leaked-render': 'error',
            'react/jsx-key': 'error',
            'react/jsx-no-useless-fragment': 'error',
            'react/self-closing-comp': 'error',
            'react/jsx-boolean-value': ['error', 'never'],
            'react/jsx-curly-brace-presence': [
                'error',
                { props: 'never', children: 'never' },
            ],
            'react/no-array-index-key': 'warn',
            'react/no-unused-state': 'error',
            'react/jsx-no-bind': ['warn', { allowArrowFunctions: true }],
            '@typescript-eslint/no-unused-vars': [
                'error',
                { argsIgnorePattern: '^_' },
            ],
            '@typescript-eslint/no-explicit-any': 'error',
            '@typescript-eslint/prefer-nullish-coalescing': 'error',
            '@typescript-eslint/prefer-optional-chain': 'error',
            '@typescript-eslint/no-non-null-assertion': 'error',
            '@typescript-eslint/no-floating-promises': 'error',
            '@typescript-eslint/await-thenable': 'error',
            '@typescript-eslint/prefer-as-const': 'error',
            '@typescript-eslint/no-unnecessary-type-assertion': 'error',
            '@typescript-eslint/consistent-type-imports': 'error',
            '@typescript-eslint/consistent-type-definitions': [
                'error',
                'interface',
            ],
            'no-console': 'warn',
            'no-debugger': 'error',
            'no-alert': 'error',
            'no-duplicate-imports': 'error',
            'no-unused-expressions': 'error',
            'prefer-const': 'error',
            'no-var': 'error',
            eqeqeq: 'error',
            'no-implicit-coercion': 'error',
            'no-return-assign': 'error',
            'no-sequences': 'error',
            radix: 'error',
            'no-throw-literal': 'error',
            'prefer-promise-reject-errors': 'error',
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
    {
        plugins: {
            'react-hooks': reactHooks,
        },
        rules: {
            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'error',
        },
    },
    {
        files: ['**/*.ts', '**/*.tsx'],
        rules: {
            'no-undef': 'off',
            'no-unused-vars': 'off',
        },
    },
    {
        ignores: [
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'tailwind.config.js',
            'dist',
            'build',
            'resources/js/components/ui',
            'resources/js/wayfinder',
            '*.config.js',
        ],
    },
    prettier,
];
