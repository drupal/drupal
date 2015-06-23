<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ContentFieldTestTwo.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_field_test_two table.
 */
class ContentFieldTestTwo extends DrupalDumpBase {

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
      'mysql_character_set' => 'utf8',
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
    ))->values(array(
      'vid' => '1',
      'nid' => '1',
      'delta' => '1',
      'field_test_two_value' => '20',
    ))->execute();
  }

}
#c4cffd2dbffd6ffdc97ef5b1cb4e0e3a
