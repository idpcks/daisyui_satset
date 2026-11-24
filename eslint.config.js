import js from '@eslint/js';
import globals from 'globals';
import pluginVue from 'eslint-plugin-vue';
import tailwindcss from 'eslint-plugin-tailwindcss';

export default [
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/build/**',
            'storage/**',
            'bootstrap/cache/**',
            'coverage/**',
        ],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2024,
            },
        },
        plugins: {
            tailwindcss,
        },
        rules: {
            'tailwindcss/no-custom-classname': 'off',
            'vue/multi-word-component-names': 'off',
            'vue/html-indent': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/singleline-html-element-content-newline': 'off',
            'vue/html-self-closing': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/attributes-order': 'off',
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
            'no-empty': ['error', { allowEmptyCatch: true }],
        },
    },
    {
        files: ['tests/js/**/*.js'],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.vitest,
            },
        },
    },
];
