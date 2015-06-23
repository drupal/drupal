<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\ImageStyles.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the image_styles table.
 */
class ImageStyles extends DrupalDumpBase {

  public function load() {
    $this->createTable("image_styles", array(
      'primary key' => array(
        'isid',
      ),
      'fields' => array(
        'isid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'label' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("image_styles")->fields(array(
      'isid',
      'name',
      'label',
    ))
    ->execute();
  }

}
#64077dfb27e8f49e53f6d40a40b6d36d
