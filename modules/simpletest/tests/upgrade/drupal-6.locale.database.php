<?php

/**
 * Database additions for locale tests.
 */

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
      'length' => 32,
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
  'language' => 'en',
  'name' => 'English',
  'native' => 'English',
  'direction' => '0',
  'enabled' => '1',
  'plurals' => '0',
  'formula' => '',
  'domain' => 'http://en.example.com',
  'prefix' => 'en',
  'weight' => '0',
  'javascript' => '',
))
->values(array(
  'language' => 'fr',
  'name' => 'French',
  'native' => 'Français',
  'direction' => '0',
  'enabled' => '1',
  'plurals' => '2',
  'formula' => '($n>1)',
  'domain' => '',
  'prefix' => 'fr',
  'weight' => '-3',
  'javascript' => '51e92dcfe1491f4595b9df7f3b287753',
))
->execute();

db_create_table('locales_source', array(
  'fields' => array(
    'lid' => array(
      'type' => 'serial',
      'not null' => TRUE,
    ),
    'location' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
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
    'source' => array(
      array(
        'source',
        30,
      ),
    ),
  ),
  'module' => 'locale',
  'name' => 'locales_source',
));

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

// Enable the locale module.
db_update('system')->fields(array(
  'status' => 1,
  'schema_version' => '6006',
))
->condition('type', 'module')
->condition('name', 'locale')
->execute();

// Set the default language.
db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'language_default',
  'value' => 'O:8:"stdClass":11:{s:8:"language";s:2:"fr";s:4:"name";s:6:"French";s:6:"native";s:9:"Français";s:9:"direction";s:1:"0";s:7:"enabled";i:1;s:7:"plurals";s:1:"2";s:7:"formula";s:6:"($n>1)";s:6:"domain";s:0:"";s:6:"prefix";s:0:"";s:6:"weight";s:2:"-3";s:10:"javascript";s:32:"51e92dcfe1491f4595b9df7f3b287753";}',
))
->values(array(
  'name' => 'language_count',
  'value' => 'i:2;',
))
->values(array(
  'name' => 'language_negotiation',
  'value' => 'i:0;',
))
->execute();

// Add the language switcher block in the left region.
db_insert('blocks')->fields(array(
  'module',
  'delta',
  'theme',
  'status',
  'weight',
  'region',
  'custom',
  'throttle',
  'visibility',
  'pages',
  'title',
  'cache',
))
->values(array(
  'module' => 'locale',
  'delta' => '0',
  'theme' => 'garland',
  'status' => '1',
  'weight' => '0',
  'region' => 'left',
  'custom' => '0',
  'throttle' => '0',
  'visibility' => '0',
  'pages' => '',
  'title' => '',
  'cache' => '-1',
))
->execute();
