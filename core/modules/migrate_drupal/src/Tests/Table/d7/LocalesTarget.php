<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\LocalesTarget.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the locales_target table.
 */
class LocalesTarget extends DrupalDumpBase {

  public function load() {
    $this->createTable("locales_target", array(
      'primary key' => array(
        'lid',
        'language',
        'plural',
      ),
      'fields' => array(
        'lid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'translation' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'length' => 100,
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
          'default' => '',
        ),
        'plid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'plural' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("locales_target")->fields(array(
      'lid',
      'translation',
      'language',
      'plid',
      'plural',
    ))
    ->execute();
  }

}
#a8df04af5270adb1ba16a8a007d5fb01
