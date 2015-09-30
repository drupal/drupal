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
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("image_effects")->fields(array(
      'ieid',
      'isid',
      'weight',
      'name',
      'data',
    ))
    ->values(array(
      'ieid' => '3',
      'isid' => '1',
      'weight' => '1',
      'name' => 'image_scale_and_crop',
      'data' => 'a:2:{s:5:"width";s:2:"55";s:6:"height";s:2:"55";}',
    ))->values(array(
      'ieid' => '4',
      'isid' => '1',
      'weight' => '2',
      'name' => 'image_desaturate',
      'data' => 'a:0:{}',
    ))->values(array(
      'ieid' => '5',
      'isid' => '2',
      'weight' => '1',
      'name' => 'image_resize',
      'data' => 'a:2:{s:5:"width";s:2:"55";s:6:"height";s:3:"100";}',
    ))->values(array(
      'ieid' => '6',
      'isid' => '2',
      'weight' => '2',
      'name' => 'image_rotate',
      'data' => 'a:3:{s:7:"degrees";s:2:"45";s:7:"bgcolor";s:7:"#FFFFFF";s:6:"random";i:0;}',
    ))->values(array(
      'ieid' => '7',
      'isid' => '3',
      'weight' => '1',
      'name' => 'image_scale',
      'data' => 'a:3:{s:5:"width";s:3:"150";s:6:"height";s:0:"";s:7:"upscale";i:0;}',
    ))->values(array(
      'ieid' => '8',
      'isid' => '3',
      'weight' => '2',
      'name' => 'image_crop',
      'data' => 'a:3:{s:5:"width";s:2:"50";s:6:"height";s:2:"50";s:6:"anchor";s:8:"left-top";}',
    ))->execute();
  }

}
#5e2aa799db43de19b84994816b4a426b
