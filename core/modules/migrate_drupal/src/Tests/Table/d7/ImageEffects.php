<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\ImageEffects.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the image_effects table.
 */
class ImageEffects extends DrupalDumpBase {

  public function load() {
    $this->createTable("image_effects", array(
      'primary key' => array(
        'ieid',
      ),
      'fields' => array(
        'ieid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'isid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'data' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("image_effects")->fields(array(
      'ieid',
      'isid',
      'weight',
      'name',
      'data',
    ))
    ->execute();
  }

}
#a9ad7344cf818347e8074c68c88f882b
