<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\CtoolsCssCache.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the ctools_css_cache table.
 */
class CtoolsCssCache extends DrupalDumpBase {

  public function load() {
    $this->createTable("ctools_css_cache", array(
      'primary key' => array(
        'cid',
      ),
      'fields' => array(
        'cid' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
        ),
        'filename' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'css' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'filter' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '4',
        ),
      ),
    ));
    $this->database->insert("ctools_css_cache")->fields(array(
      'cid',
      'filename',
      'css',
      'filter',
    ))
    ->execute();
  }

}
#3a1f1b6c213289483ef89e7562f0124a
