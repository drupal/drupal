<?php

/**
 * @file
 * Database additions for language tests. Used in upgrade.language.test.
 *
 * This dump only contains data and schema components relevant for language
 * functionality. The drupal-7.filled.database.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

// Add blocks respective for language functionality.
db_insert('block')->fields(array(
  'module',
  'delta',
  'theme',
  'status',
  'weight',
  'region',
  'custom',
  'visibility',
  'pages',
  'title',
  'cache',
))
->values(array(
  'module' => 'locale',
  'delta' => 'language',
  'theme' => 'bartik',
  'status' => '0',
  'weight' => '0',
  'region' => '-1',
  'custom' => '0',
  'visibility' => '0',
  'pages' => '',
  'title' => '',
  'cache' => '-1',
))
->values(array(
  'module' => 'locale',
  'delta' => 'language',
  'theme' => 'seven',
  'status' => '0',
  'weight' => '0',
  'region' => '-1',
  'custom' => '0',
  'visibility' => '0',
  'pages' => '',
  'title' => '',
  'cache' => '-1',
))
->execute();

// Add language table from locale.install schema and prefill with some default
// languages for testing.
db_create_table('languages', array(
  'fields' => array(
    'language' => array(
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ),
    'name' => array(
      'type' => 'varchar',
      'length' => 64,
      'not null' => TRUE,
      'default' => '',
    ),
    'native' => array(
      'type' => 'varchar',
      'length' => 64,
      'not null' => TRUE,
      'default' => '',
    ),
    'direction' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
    'enabled' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
    'plurals' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
    'formula' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'domain' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'prefix' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
    'javascript' => array(
      'type' => 'varchar',
      'length' => 64,
      'not null' => TRUE,
      'default' => '',
    ),
  ),
  'primary key' => array(
    'language',
  ),
  'indexes' => array(
    'list' => array(
      'weight',
      'name',
    ),
  ),
  'module' => 'locale',
  'name' => 'languages',
));
db_insert('languages')->fields(array(
  'language',
  'name',
  'native',
  'direction',
  'enabled',
  'plurals',
  'formula',
  'domain',
  'prefix',
  'weight',
  'javascript',
))
->values(array(
  'language' => 'ca',
  'name' => 'Catalan',
  'native' => 'Català',
  'direction' => '0',
  'enabled' => '1',
  'plurals' => '2',
  'formula' => '($n>1)',
  'domain' => '',
  'prefix' => 'ca',
  'weight' => '0',
  'javascript' => '',
))
->values(array(
  'language' => 'cv',
  'name' => 'Chuvash',
  'native' => 'Chuvash',
  'direction' => '0',
  'enabled' => '1',
  'plurals' => '0',
  'formula' => '',
  'domain' => '',
  'prefix' => 'cv',
  'weight' => '0',
  'javascript' => '',
))
->values(array(
  'language' => 'en',
  'name' => 'English',
  'native' => 'English',
  'direction' => '0',
  'enabled' => '1',
  'plurals' => '0',
  'formula' => '',
  'domain' => '',
  'prefix' => '',
  'weight' => '0',
  'javascript' => '',
))
->execute();

// Add locales_source table from locale.install schema and fill with some
// sample data for testing.
db_create_table('locales_source', array(
  'fields' => array(
    'lid' => array(
      'type' => 'serial',
      'not null' => TRUE,
    ),
    'location' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ),
    'textgroup' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => 'default',
    ),
    'source' => array(
      'type' => 'text',
      'mysql_type' => 'blob',
      'not null' => TRUE,
    ),
    'context' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ),
    'version' => array(
      'type' => 'varchar',
      'length' => 20,
      'not null' => TRUE,
      'default' => 'none',
    ),
  ),
  'primary key' => array(
    'lid',
  ),
  'indexes' => array(
    'source_context' => array(
      array(
        'source',
        30,
      ),
      'context',
    ),
  ),
  'module' => 'locale',
  'name' => 'locales_source',
));
db_insert('locales_source')->fields(array(
  'lid',
  'location',
  'textgroup',
  'source',
  'context',
  'version',
))
->values(array(
  'lid' => '1',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'An AJAX HTTP error occurred.',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '2',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'HTTP Result Code: !status',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '3',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'An AJAX HTTP request terminated abnormally.',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '4',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'Debugging information follows.',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '5',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'Path: !uri',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '6',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'StatusText: !statusText',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '7',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'ResponseText: !responseText',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '8',
  'location' => 'misc/drupal.js',
  'textgroup' => 'default',
  'source' => 'ReadyState: !readyState',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '9',
  'location' => 'modules/overlay/overlay-parent.js',
  'textgroup' => 'default',
  'source' => '@title dialog',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '10',
  'location' => 'modules/contextual/contextual.js',
  'textgroup' => 'default',
  'source' => 'Configure',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '11',
  'location' => 'modules/toolbar/toolbar.js',
  'textgroup' => 'default',
  'source' => 'Show shortcuts',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '12',
  'location' => 'modules/toolbar/toolbar.js',
  'textgroup' => 'default',
  'source' => 'Hide shortcuts',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '13',
  'location' => 'modules/overlay/overlay-child.js',
  'textgroup' => 'default',
  'source' => 'Loading',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '14',
  'location' => 'modules/overlay/overlay-child.js',
  'textgroup' => 'default',
  'source' => '(active tab)',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '15',
  'location' => 'misc/tabledrag.js',
  'textgroup' => 'default',
  'source' => 'Re-order rows by numerical weight instead of dragging.',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '16',
  'location' => 'misc/tabledrag.js',
  'textgroup' => 'default',
  'source' => 'Show row weights',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '17',
  'location' => 'misc/tabledrag.js',
  'textgroup' => 'default',
  'source' => 'Hide row weights',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '18',
  'location' => 'misc/tabledrag.js',
  'textgroup' => 'default',
  'source' => 'Drag to re-order',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '19',
  'location' => 'misc/tabledrag.js',
  'textgroup' => 'default',
  'source' => 'Changes made in this table will not be saved until the form is submitted.',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '20',
  'location' => 'misc/collapse.js',
  'textgroup' => 'default',
  'source' => 'Hide',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '21',
  'location' => 'misc/collapse.js',
  'textgroup' => 'default',
  'source' => 'Show',
  'context' => '',
  'version' => 'none',
))
->execute();

// Add locales_target table from locale.install schema.
db_create_table('locales_target', array(
  'fields' => array(
    'lid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
    'translation' => array(
      'type' => 'text',
      'mysql_type' => 'blob',
      'not null' => TRUE,
    ),
    'language' => array(
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ),
    'plid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
    'plural' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
  ),
  'primary key' => array(
    'language',
    'lid',
    'plural',
  ),
  'foreign keys' => array(
    'locales_source' => array(
      'table' => 'locales_source',
      'columns' => array(
        'lid' => 'lid',
      ),
    ),
  ),
  'indexes' => array(
    'lid' => array(
      'lid',
    ),
    'plid' => array(
      'plid',
    ),
    'plural' => array(
      'plural',
    ),
  ),
  'module' => 'locale',
  'name' => 'locales_target',
));

// Set up variables needed for language support.
db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'javascript_parsed',
  'value' => 'a:16:{i:0;s:14:"misc/drupal.js";i:1;s:14:"misc/jquery.js";i:2;s:19:"misc/jquery.once.js";s:10:"refresh:ca";s:7:"waiting";i:3;s:29:"misc/ui/jquery.ui.core.min.js";i:4;s:21:"misc/jquery.ba-bbq.js";i:5;s:33:"modules/overlay/overlay-parent.js";i:6;s:32:"modules/contextual/contextual.js";i:7;s:21:"misc/jquery.cookie.js";i:8;s:26:"modules/toolbar/toolbar.js";i:9;s:32:"modules/overlay/overlay-child.js";i:10;s:19:"misc/tableheader.js";i:11;s:17:"misc/tabledrag.js";i:12;s:12:"misc/form.js";i:13;s:16:"misc/collapse.js";s:10:"refresh:cv";s:7:"waiting";}',
))
->values(array(
  'name' => 'language_count',
  'value' => 'i:3;',
))
->values(array(
  'name' => 'language_default',
  'value' => 'O:8:"stdClass":7:{s:8:"langcode";s:2:"ca";s:4:"name";s:7:"Catalan";s:9:"direction";i:0;s:7:"enabled";b:1;s:6:"weight";i:0;s:7:"default";b:1;s:6:"is_new";b:1;}',
))
->values(array(
  'name' => 'language_negotiation_language',
  'value' => 'a:5:{s:10:"locale-url";a:2:{s:9:"callbacks";a:3:{s:8:"language";s:24:"locale_language_from_url";s:8:"switcher";s:28:"locale_language_switcher_url";s:11:"url_rewrite";s:31:"locale_language_url_rewrite_url";}s:4:"file";s:19:"includes/locale.inc";}s:14:"locale-session";a:2:{s:9:"callbacks";a:3:{s:8:"language";s:28:"locale_language_from_session";s:8:"switcher";s:32:"locale_language_switcher_session";s:11:"url_rewrite";s:35:"locale_language_url_rewrite_session";}s:4:"file";s:19:"includes/locale.inc";}s:11:"locale-user";a:2:{s:9:"callbacks";a:1:{s:8:"language";s:25:"locale_language_from_user";}s:4:"file";s:19:"includes/locale.inc";}s:14:"locale-browser";a:3:{s:9:"callbacks";a:1:{s:8:"language";s:28:"locale_language_from_browser";}s:4:"file";s:19:"includes/locale.inc";s:5:"cache";i:0;}s:16:"language-default";a:1:{s:9:"callbacks";a:1:{s:8:"language";s:21:"language_from_default";}}}',
))
->values(array(
  'name' => 'language_negotiation_language_content',
  'value' => 'a:1:{s:16:"locale-interface";a:2:{s:9:"callbacks";a:1:{s:8:"language";s:30:"locale_language_from_interface";}s:4:"file";s:19:"includes/locale.inc";}}',
))
->values(array(
  'name' => 'language_negotiation_language_url',
  'value' => 'a:2:{s:10:"locale-url";a:2:{s:9:"callbacks";a:3:{s:8:"language";s:24:"locale_language_from_url";s:8:"switcher";s:28:"locale_language_switcher_url";s:11:"url_rewrite";s:31:"locale_language_url_rewrite_url";}s:4:"file";s:19:"includes/locale.inc";}s:19:"locale-url-fallback";a:2:{s:9:"callbacks";a:1:{s:8:"language";s:28:"locale_language_url_fallback";}s:4:"file";s:19:"includes/locale.inc";}}',
))
->values(array(
  'name' => 'language_types',
  'value' => 'a:3:{s:8:"language";b:1;s:16:"language_content";b:0;s:12:"language_url";b:0;}',
))
->values(array(
  'name' => 'locale_language_providers_weight_language',
  'value' => 'a:5:{s:10:"locale-url";s:2:"-8";s:14:"locale-session";s:2:"-6";s:11:"locale-user";s:2:"-4";s:14:"locale-browser";s:2:"-2";s:16:"language-default";s:2:"10";}',
))
->values(array(
  'name' => 'language_content_type_article',
  'value' => 's:1:"1";',
))
->execute();

// Enable the locale module.
db_update('system')->fields(array(
  'status' => 1,
  'schema_version' => '7001',
))
->condition('type', 'module')
->condition('name', 'locale')
->execute();
