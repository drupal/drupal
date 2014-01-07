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
db_delete('block')
  ->condition('module', 'locale')
  ->execute();

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
  'status' => '1',
  'weight' => '0',
  'region' => 'sidebar_first',
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

// Prefill languages table from locale.install with some default languages
// for testing.
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
  'language' => 'hr',
  'name' => 'Croatian',
  'native' => 'Hrvatski',
  'direction' => '0',
  'enabled' => '1',
  'plurals' => '3',
  'formula' => '((($n%10)==1)&&(($n%100)!=11))?(0):((((($n%10)>=2)&&(($n%10)<=4))&&((($n%100)<10)||(($n%100)>=20)))?(1):2));',
  'domain' => '',
  'prefix' => '',
  'weight' => '0',
  'javascript' => '',
))
->execute();

// Fill locales_source table from locale.install schema with some sample data
// for testing.
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
->values(array(
  'lid' => '22',
  'location' => '',
  'textgroup' => 'default',
  'source' => '1 byte',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '23',
  'location' => '',
  'textgroup' => 'default',
  'source' => '@count bytes',
  'context' => '',
  'version' => 'none',
))
->values(array(
  'lid' => '24',
  'location' => '',
  'textgroup' => 'default',
  'source' => '@count[2] bytes',
  'context' => '',
  'version' => 'none',
))
->execute();

// Fill locales_target table from locale.install schema with some sample data
// for testing.
db_insert('locales_target')->fields(array(
  'lid',
  'translation',
  'language',
  'plid',
  'plural',
))
->values(array(
  'lid' => 22,
  'translation' => '1 byte',
  'language' => 'ca',
  'plid' => 0,
  'plural' => 0,
))
->values(array(
  'lid' => 23,
  'translation' => '@count bytes',
  'language' => 'ca',
  'plid' => 22,
  'plural' => 1,
))
->values(array(
  'lid' => 22,
  'translation' => '@count bajt',
  'language' => 'hr',
  'plid' => 0,
  'plural' => 0,
))
->values(array(
  'lid' => 23,
  'translation' => '@count bajta',
  'language' => 'hr',
  'plid' => 22,
  'plural' => 1,
))
->values(array(
  'lid' => 24,
  'translation' => '@count[2] bajtova',
  'language' => 'hr',
  'plid' => 23,
  'plural' => 2,
))
->execute();

// Set up variables needed for language support.
$deleted_variables = array(
  'javascript_parsed',
  'language_count',
  'language_default',
  'language_negotiation_language',
  'language_negotiation_language_content',
  'language_negotiation_language_url',
  'language_types',
  'locale_language_providers_weight_language',
  'language_content_type_article',
);
db_delete('variable')
  ->condition('name', $deleted_variables, 'IN')
  ->execute();

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
  'value' => 'O:8:"stdClass":7:{s:8:"language";s:2:"ca";s:4:"name";s:7:"Catalan";s:9:"direction";i:0;s:7:"enabled";b:1;s:6:"weight";i:0;s:7:"default";b:1;s:6:"is_new";b:1;}',
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
  'value' => 's:1:"2";',
))
->execute();

// Add sample nodes to test language assignment and translation functionality.
// The first node is also used for testing comment language functionality. This
// is a simple node with Language::LANGCODE_NOT_SPECIFIED as language code. The second
// node is a Catalan node (language code 'ca'). The third and fourth node are a
// translation set with an English source translation (language code 'en') and
// a Chuvash translation (language code 'cv').
db_insert('node')->fields(array(
  'nid',
  'vid',
  'type',
  'language',
  'title',
  'uid',
  'status',
  'created',
  'changed',
  'comment',
  'promote',
  'sticky',
  'tnid',
  'translate',
))
->values(array(
  'nid' => '50',
  'vid' => '70',
  'type' => 'article',
  'language' => 'und',
  'title' => 'Node title 50',
  'uid' => '6',
  'status' => '1',
  'created' => '1263769200',
  'changed' => '1314997642',
  'comment' => '2',
  'promote' => '0',
  'sticky' => '0',
  'tnid' => '0',
  'translate' => '0',
))
->values(array(
  'nid' => '51',
  'vid' => '75',
  'type' => 'article',
  'language' => 'ca',
  'title' => 'Node title 51',
  'uid' => '6',
  'status' => '1',
  'created' => '1263769300',
  'changed' => '1263769300',
  'comment' => '0',
  'promote' => '0',
  'sticky' => '0',
  'tnid' => '0',
  'translate' => '0',
))
->values(array(
  'nid' => '52',
  'vid' => '80',
  'type' => 'article',
  'language' => 'en',
  'title' => 'Node title 52',
  'uid' => '6',
  'status' => '1',
  'created' => '1263769534',
  'changed' => '1263769534',
  'comment' => '0',
  'promote' => '0',
  'sticky' => '0',
  'tnid' => '52',
  'translate' => '0',
))
->values(array(
  'nid' => '53',
  'vid' => '85',
  'type' => 'article',
  'language' => 'cv',
  'title' => 'Node title 53',
  'uid' => '6',
  'status' => '1',
  'created' => '1263770064',
  'changed' => '1263770064',
  'comment' => '0',
  'promote' => '0',
  'sticky' => '0',
  'tnid' => '52',
  'translate' => '0',
))
->execute();

// Add node comment statistics for the first node.
db_insert('node_comment_statistics')->fields(array(
  'nid',
  'cid',
  'last_comment_timestamp',
  'last_comment_name',
  'last_comment_uid',
  'comment_count',
))
->values(array(
  'nid' => '50',
  'cid' => '0',
  'last_comment_timestamp' => '1314997642',
  'last_comment_name' => NULL,
  'last_comment_uid' => '6',
  'comment_count' => '1',
))
->execute();

// Add node revision information.
db_insert('node_revision')->fields(array(
  'nid',
  'vid',
  'uid',
  'title',
  'log',
  'timestamp',
  'status',
  'comment',
  'promote',
  'sticky',
))
->values(array(
  'nid' => '50',
  'vid' => '70',
  'uid' => '6',
  'title' => 'Node title 50',
  'log' => 'Added a Language::LANGCODE_NOT_SPECIFIED node to comment on.',
  'timestamp' => '1314997642',
  'status' => '1',
  'comment' => '2',
  'promote' => '0',
  'sticky' => '0',
))
->values(array(
  'nid' => '51',
  'vid' => '75',
  'uid' => '6',
  'title' => 'Node title 51',
  'log' => 'Created a Catalan node.',
  'timestamp' => '1263769300',
  'status' => '1',
  'comment' => '0',
  'promote' => '0',
  'sticky' => '0',
))
->values(array(
  'nid' => '52',
  'vid' => '80',
  'uid' => '6',
  'title' => 'Node title 52',
  'log' => 'Created source translation in English.',
  'timestamp' => '1263769534',
  'status' => '1',
  'comment' => '0',
  'promote' => '0',
  'sticky' => '0',
))
->values(array(
  'nid' => '53',
  'vid' => '85',
  'uid' => '6',
  'title' => 'Node title 53',
  'log' => 'Created Chuvash translation.',
  'timestamp' => '1263770064',
  'status' => '1',
  'comment' => '0',
  'promote' => '0',
  'sticky' => '0',
))
->execute();

// Add the body field value.
db_insert('field_data_body')->fields(array(
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'body_value',
  'body_summary',
  'body_format',
))
->values(array(
  'entity_type' => 'node',
  'bundle' => 'article',
  'deleted' => '0',
  'entity_id' => '50',
  'revision_id' => '70',
  'language' => 'und',
  'delta' => '0',
  'body_value' => 'Node body',
  'body_summary' => 'Node body',
  'body_format' => 'filtered_html',
))
->execute();

// Add revision information for the body field value.
db_insert('field_revision_body')->fields(array(
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'body_value',
  'body_summary',
  'body_format',
))
->values(array(
  'entity_type' => 'node',
  'bundle' => 'article',
  'deleted' => '0',
  'entity_id' => '50',
  'revision_id' => '70',
  'language' => 'und',
  'delta' => '0',
  'body_value' => 'Node body',
  'body_summary' => 'Node body',
  'body_format' => 'filtered_html',
))
->execute();

// Add two comments to the first node in a thread.
db_insert('comment')->fields(array(
  'cid',
  'pid',
  'nid',
  'uid',
  'subject',
  'hostname',
  'created',
  'changed',
  'status',
  'thread',
  'name',
  'mail',
  'homepage',
  'language',
))
->values(array(
  'cid' => '1',
  'pid' => '0',
  'nid' => '50',
  'uid' => '6',
  'subject' => 'First test comment',
  'hostname' => '127.0.0.1',
  'created' => '1314997642',
  'changed' => '1314997642',
  'status' => '1',
  'thread' => '01/',
  'name' => 'test user 4',
  'mail' => '',
  'homepage' => '',
  'language' => 'und',
))
->values(array(
  'cid' => '2',
  'pid' => '0',
  'nid' => '50',
  'uid' => '6',
  'subject' => 'Reply to first test comment',
  'hostname' => '127.0.0.1',
  'created' => '1314997642',
  'changed' => '1314997642',
  'status' => '1',
  'thread' => '01.00/',
  'name' => 'test user 4',
  'mail' => '',
  'homepage' => '',
  'language' => 'und',
))
->execute();

// Add both comment bodies.
db_insert('field_data_comment_body')->fields(array(
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'comment_body_value',
  'comment_body_format',
))
->values(array(
  'entity_type' => 'comment',
  'bundle' => 'comment_node_article',
  'deleted' => '0',
  'entity_id' => '1',
  'revision_id' => '1',
  'language' => 'und',
  'delta' => '0',
  'comment_body_value' => 'Comment body',
  'comment_body_format' => 'filtered_html',
))
->values(array(
  'entity_type' => 'comment',
  'bundle' => 'comment_node_article',
  'deleted' => '0',
  'entity_id' => '2',
  'revision_id' => '2',
  'language' => 'und',
  'delta' => '0',
  'comment_body_value' => 'Second comment body',
  'comment_body_format' => 'filtered_html',
))
->execute();

// Add revisions for comment bodies.
db_insert('field_revision_comment_body')->fields(array(
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'comment_body_value',
  'comment_body_format',
))
->values(array(
  'entity_type' => 'comment',
  'bundle' => 'comment_node_article',
  'deleted' => '0',
  'entity_id' => '1',
  'revision_id' => '1',
  'language' => 'und',
  'delta' => '0',
  'comment_body_value' => 'Comment body',
  'comment_body_format' => 'filtered_html',
))
->values(array(
  'entity_type' => 'comment',
  'bundle' => 'comment_node_article',
  'deleted' => '0',
  'entity_id' => '2',
  'revision_id' => '2',
  'language' => 'und',
  'delta' => '0',
  'comment_body_value' => 'Second comment body',
  'comment_body_format' => 'filtered_html',
))
->execute();

// Add a managed file.
db_insert('file_managed')->fields(array(
  'fid',
  'uid',
  'filename',
  'uri',
  'filemime',
  'filesize',
  'status',
  'created',
  'changed',
))
->values(array(
  'fid' => '1',
  'uid' => '1',
  'filename' => 'foo.txt',
  'uri' => 'public://foo.txt',
  'filemime' => 'text/plain',
  'filesize' => 0,
  'status' => 1,
  'created' => '1314997642',
  'changed' => '1314997642',
))
->execute();
