// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
module.exports = {
    globals: {
        appVersion: true
    },
    parserOptions: {
        requireConfigFile: false
    },
    extends: [
        '@nextcloud'
    ],
    rules: {
        'indent': 'off',
        'import/extensions': 'off',
        'jsdoc/tag-lines': 'off',
        'jsdoc/require-jsdoc': 'off',
        'vue/first-attribute-linebreak': 'off',
        'vue/html-indent': 'off',
        'no-multi-spaces': 'off',
        'key-spacing': ["error", { mode: "minimum" }],
        "no-console": 'off',
    }
}
