<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing
 * the upgrade paths of the media library module form and view displays.
 *
 * @see https://www.drupal.org/project/drupal/issues/2988433
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'media_library')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'media_library',
    'value' => 'i:8000;',
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['media_library'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Add config.
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'core.entity_form_mode.media.media_library',
    'data' => 'a:9:{s:4:"uuid";s:36:"a95ff3d3-19ca-4a20-9ed5-63574ffaf4fa";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:13:"media_library";}}s:6:"module";a:1:{i:0;s:5:"media";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"pkq0uj-IoqEQRBOP_ddUDV0ZJ-dKQ_fLcppsEDF2UO8";}s:2:"id";s:19:"media.media_library";s:5:"label";s:13:"Media library";s:16:"targetEntityType";s:5:"media";s:5:"cache";b:1;}',
  ])
  ->values([
    'collection' => '',
    'name' => 'core.entity_view_mode.media.media_library',
    'data' => 'a:9:{s:4:"uuid";s:36:"aa86ec5c-3c36-44c5-b5b5-ade8bba3f549";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:13:"media_library";}}s:6:"module";a:1:{i:0;s:5:"media";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"pkq0uj-IoqEQRBOP_ddUDV0ZJ-dKQ_fLcppsEDF2UO8";}s:2:"id";s:19:"media.media_library";s:5:"label";s:13:"Media library";s:16:"targetEntityType";s:5:"media";s:5:"cache";b:1;}',
  ])
  ->execute();
