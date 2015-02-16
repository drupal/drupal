<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ContentTypeTestPage.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_type_test_page table.
 */
class ContentTypeTestPage extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_type_test_page", array(
      'primary key' => array(
        'vid',
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
        'field_test_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_format' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("content_type_test_page")->fields(array(
      'vid',
      'nid',
      'field_test_value',
      'field_test_format',
    ))
    ->execute();
  }

}
