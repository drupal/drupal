<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ContentFieldTestTwo.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the content_field_test_two table.
 */
class ContentFieldTestTwo extends Drupal6DumpBase {

  public function load() {
    $this->createTable("content_field_test_two", array(
      'primary key' => array(
        'vid',
        'delta',
      ),
      'fields' => array(
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'delta' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'field_test_two_value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
      ),
    ));
    $this->database->insert("content_field_test_two")->fields(array(
      'vid',
      'nid',
      'delta',
      'field_test_two_value',
    ))
    ->values(array(
      'vid' => '1',
      'nid' => '1',
      'delta' => '0',
      'field_test_two_value' => '10',
    ))->values(array(
      'vid' => '1',
      'nid' => '1',
      'delta' => '1',
      'field_test_two_value' => '20',
    ))->values(array(
      'vid' => '2',
      'nid' => '1',
      'delta' => '0',
      'field_test_two_value' => NULL,
    ))->values(array(
      'vid' => '3',
      'nid' => '2',
      'delta' => '0',
      'field_test_two_value' => NULL,
    ))->values(array(
      'vid' => '5',
      'nid' => '2',
      'delta' => '0',
      'field_test_two_value' => NULL,
    ))->execute();
  }

}
