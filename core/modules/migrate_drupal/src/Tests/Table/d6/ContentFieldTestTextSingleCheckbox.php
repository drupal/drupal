<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ContentFieldTestTextSingleCheckbox.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_field_test_text_single_checkbox table.
 */
class ContentFieldTestTextSingleCheckbox extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_field_test_text_single_checkbox", array(
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
        'field_test_text_single_checkbox_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("content_field_test_text_single_checkbox")->fields(array(
      'vid',
      'nid',
      'field_test_text_single_checkbox_value',
    ))
    ->values(array(
      'vid' => '1',
      'nid' => '1',
      'field_test_text_single_checkbox_value' => '0',
    ))->values(array(
      'vid' => '2',
      'nid' => '1',
      'field_test_text_single_checkbox_value' => NULL,
    ))->values(array(
      'vid' => '3',
      'nid' => '2',
      'field_test_text_single_checkbox_value' => NULL,
    ))->values(array(
      'vid' => '5',
      'nid' => '2',
      'field_test_text_single_checkbox_value' => NULL,
    ))->execute();
  }

}
#ad2cfdaf51bfe9c961e00f14580d3102
