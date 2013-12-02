<?php

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing system.site.yml migration.
 */
class Drupal6SystemSite {

  /**
   * @param \Drupal\Core\Database\Connection $database
   */
  public static function load(Connection $database) {
    $database->schema()->createTable('variable', array(
      'fields' => array(
        'name' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ),
        'value' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
          'translatable' => TRUE,
        ),
      ),
      'primary key' => array(
        'name',
      ),
      'module' => 'system',
      'name' => 'variable',
    ));
    $database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'site_name',
      'value' => 's:6:"drupal";',
    ))
    ->values(array(
      'name' => 'site_mail',
      'value' => 's:17:"admin@example.com";',
    ))
    ->values(array(
      'name' => 'site_slogan',
      'value' => 's:13:"Migrate rocks";',
    ))
    ->values(array(
      'name' => 'site_frontpage',
      'value' => 's:12:"anonymous-hp";',
    ))
    ->values(array(
      'name' => 'site_403',
      'value' => 's:4:"user";',
    ))
    ->values(array(
      'name' => 'site_404',
      'value' => 's:14:"page-not-found";',
    ))
    ->values(array(
      'name' => 'drupal_weight_select_max',
      'value' => 'i:99;',
    ))
    ->values(array(
      'name' => 'admin_compact_mode',
      'value' => 'b:0;',
    ))
    ->execute();
  }
}
