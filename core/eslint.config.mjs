// cspell:ignore UIDOM
import { defineConfig } from 'eslint/config';
import { fixupPluginRules } from '@eslint/compat';
import importPlugin from 'eslint-plugin-import';
import noJQuery from 'eslint-plugin-no-jquery';
import prettierConfig from 'eslint-plugin-prettier/recommended';
import ymlConfig from 'eslint-plugin-yml';
import jsdoc from 'eslint-plugin-jsdoc';
import js from '@eslint/js';
import globals from 'globals';

export default defineConfig(
  {
    ignores: [
      'assets/vendor/**/*',
      'node_modules/**/*',
      '**/js_test_files/**/*',
      '**/build/**/*',
      'modules/locale/tests/js/locale_test.js',
      'misc/jquery.form.js',

      // Ignore deliberately malformed YAML files.
      'modules/system/tests/fixtures/HtaccessTest/access_test.yml',
      'modules/system/tests/themes/test_theme_libraries_empty/test_theme_libraries_empty.info.yml',
      'tests/Drupal/Tests/Core/Asset/library_test_files/empty.libraries.yml',
      'tests/Drupal/Tests/Core/Asset/library_test_files/invalid_file.libraries.yml',
      'tests/Drupal/Tests/Composer/Plugin/Scaffold/fixtures/drupal-assets-fixture/assets/default.services.yml',
      'tests/Drupal/Tests/Composer/Plugin/Scaffold/fixtures/drupal-profile/assets/profile.default.services.yml',
      'modules/system/tests/themes/sdc_theme_test/components/bar/bar.component.yml',

      // Temporary until they are brought up to standards.
      'scripts/**/*',
      'modules/ckeditor5/webpack.config.js',
      'themes/default_admin/migration/js/*.js',
    ],
  },
  js.configs.recommended,
  importPlugin.flatConfigs.recommended,
  jsdoc.configs['flat/recommended'],
  prettierConfig,
  ymlConfig.configs['flat/recommended'],
  {
    plugins: {
      'no-jquery': fixupPluginRules(noJQuery),
    },
    languageOptions: {
      ecmaVersion: 2020,
      globals: {
        ...globals.browser,
        ...globals.node,

        Drupal: 'readonly',
        drupalSettings: 'readonly',
        drupalTranslations: 'readonly',
        jQuery: 'readonly',
        _: 'readonly',
        Cookies: 'readonly',
        Backbone: 'readonly',
        htmx: 'readonly',
        loadjs: 'readonly',
        Shepherd: 'readonly',
        Sortable: 'readonly',
        once: 'readonly',
        CKEditor5: 'readonly',
        CKEDITOR: 'readonly',
        tabbable: 'readonly',
        transliterate: 'readonly',
        bodyScrollLock: 'readonly',
        FloatingUIDOM: 'readonly',
      },
    },
    rules: {
      'prettier/prettier': 'error',
      'no-unexpected-multiline': ['off'],
      'consistent-return': ['off'],
      'no-underscore-dangle': ['off'],
      'max-nested-callbacks': ['warn', 3],
      'import/no-mutable-exports': ['warn'],
      'no-plusplus': [
        'warn',
        {
          allowForLoopAfterthoughts: true,
        },
      ],
      'no-param-reassign': ['off'],
      'no-prototype-builtins': ['off'],
      'no-unused-vars': ['warn'],
      'operator-linebreak': [
        'error',
        'after',
        {
          overrides: {
            '?': 'ignore',
            ':': 'ignore',
          },
        },
      ],
      'yml/indent': ['error', 2],
      ...noJQuery.configs.all.rules,
    },
    settings: {
      jsdoc: {
        tagNamePreference: {
          returns: 'return',
          property: 'prop',
        },
      },
    },
  },
);
