<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ContentFieldMultivalue.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the content_field_multivalue table.
 */
class ContentFieldMultivalue extends Drupal6DumpBase {

  public function load() {
    $this->createTable("content_field_multivalue", array(
      'primary key' => array(
        'vid',
        'delta',
      ),
      'fields' => array(
        'vid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'field_multivalue_value' => array(
          'type' => 'numeric',
          'not null' => FALSE,
          'precision' => '10',
          'scale' => '2',
        ),
        'delta' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("content_field_multivalue")->fields(array(
      'vid',
      'nid',
      'field_multivalue_value',
      'delta',
    ))
    ->values(array(
      'vid' => '4',
      'nid' => '3',
      'field_multivalue_value' => '33.00',
      'delta' => '0',
    ))->values(array(
      'vid' => '4',
      'nid' => '3',
      'field_multivalue_value' => '44.00',
      'delta' => '1',
    ))->execute();
  }

}
