<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ContentTypeTestPlanet.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_type_test_planet table.
 */
class ContentTypeTestPlanet extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_type_test_planet", array(
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'vid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
      ),
      'primary key' => array(
        'vid',
      ),
    ));
    $this->database->insert("content_type_test_planet")->fields(array(
      'nid',
      'vid',
    ))
    ->values(array(
      'nid' => '3',
      'vid' => '4',
    ))->values(array(
      'nid' => '4',
      'vid' => '6',
    ))->values(array(
      'nid' => '5',
      'vid' => '7',
    ))->values(array(
      'nid' => '6',
      'vid' => '8',
    ))->values(array(
      'nid' => '7',
      'vid' => '9',
    ))->values(array(
      'nid' => '8',
      'vid' => '10',
    ))->values(array(
      'nid' => '9',
      'vid' => '11',
    ))->execute();
  }

}
#2f3598809df1de9649ba0f556886687b
