<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ContentTypeStory.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_type_story table.
 */
class ContentTypeStory extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_type_story", array(
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
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'field_test_three_value' => array(
          'type' => 'numeric',
          'not null' => FALSE,
          'precision' => '10',
          'scale' => '2',
        ),
        'field_test_identical1_value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'field_test_identical2_value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'field_test_link_url' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '2048',
        ),
        'field_test_link_title' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'field_test_link_attributes' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_date_value' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '20',
        ),
        'field_test_datestamp_value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
        'field_test_datetime_value' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '100',
        ),
        'field_test_email_email' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'field_test_filefield_fid' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
        'field_test_filefield_list' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '4',
        ),
        'field_test_filefield_data' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_four_value' => array(
          'type' => 'numeric',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_text_single_checkbox_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_integer_selectlist_value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
        'field_test_float_single_checkbox_value' => array(
          'type' => 'numeric',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_decimal_radio_buttons_value' => array(
          'type' => 'numeric',
          'not null' => FALSE,
          'precision' => '10',
          'scale' => '2',
        ),
        'field_test_phone_value' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'field_test_exclude_unset_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_exclude_unset_format' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'field_test_imagefield_fid' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
        'field_test_imagefield_list' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '4',
        ),
        'field_test_imagefield_data' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'primary key' => array(
        'vid',
      ),
    ));
    $this->database->insert("content_type_story")->fields(array(
      'nid',
      'vid',
      'uid',
      'field_test_three_value',
      'field_test_identical1_value',
      'field_test_identical2_value',
      'field_test_link_url',
      'field_test_link_title',
      'field_test_link_attributes',
      'field_test_date_value',
      'field_test_datestamp_value',
      'field_test_datetime_value',
      'field_test_email_email',
      'field_test_filefield_fid',
      'field_test_filefield_list',
      'field_test_filefield_data',
      'field_test_four_value',
      'field_test_text_single_checkbox_value',
      'field_test_integer_selectlist_value',
      'field_test_float_single_checkbox_value',
      'field_test_decimal_radio_buttons_value',
      'field_test_phone_value',
      'field_test_exclude_unset_value',
      'field_test_exclude_unset_format',
      'field_test_imagefield_fid',
      'field_test_imagefield_list',
      'field_test_imagefield_data',
    ))
    ->values(array(
      'nid' => '1',
      'vid' => '1',
      'uid' => '1',
      'field_test_three_value' => '42.42',
      'field_test_identical1_value' => '1',
      'field_test_identical2_value' => '1',
      'field_test_link_url' => 'https://www.drupal.org/project/drupal',
      'field_test_link_title' => 'Drupal project page',
      'field_test_link_attributes' => 's:32:"a:1:{s:6:"target";s:6:"_blank";}";',
      'field_test_date_value' => NULL,
      'field_test_datestamp_value' => NULL,
      'field_test_datetime_value' => NULL,
      'field_test_email_email' => NULL,
      'field_test_filefield_fid' => '5',
      'field_test_filefield_list' => '1',
      'field_test_filefield_data' => 'a:1:{s:11:"description";s:4:"desc";}',
      'field_test_four_value' => NULL,
      'field_test_text_single_checkbox_value' => '0',
      'field_test_integer_selectlist_value' => '3412',
      'field_test_float_single_checkbox_value' => '3.142',
      'field_test_decimal_radio_buttons_value' => NULL,
      'field_test_phone_value' => NULL,
      'field_test_exclude_unset_value' => 'This is a field with exclude unset.',
      'field_test_exclude_unset_format' => '1',
      'field_test_imagefield_fid' => NULL,
      'field_test_imagefield_list' => NULL,
      'field_test_imagefield_data' => NULL,
    ))->values(array(
      'nid' => '1',
      'vid' => '2',
      'uid' => '1',
      'field_test_three_value' => '42.42',
      'field_test_identical1_value' => '1',
      'field_test_identical2_value' => '1',
      'field_test_link_url' => 'https://www.drupal.org/project/drupal',
      'field_test_link_title' => 'Drupal project page',
      'field_test_link_attributes' => 's:32:"a:1:{s:6:"target";s:6:"_blank";}";',
      'field_test_date_value' => NULL,
      'field_test_datestamp_value' => NULL,
      'field_test_datetime_value' => NULL,
      'field_test_email_email' => NULL,
      'field_test_filefield_fid' => NULL,
      'field_test_filefield_list' => NULL,
      'field_test_filefield_data' => NULL,
      'field_test_four_value' => NULL,
      'field_test_text_single_checkbox_value' => NULL,
      'field_test_integer_selectlist_value' => NULL,
      'field_test_float_single_checkbox_value' => NULL,
      'field_test_decimal_radio_buttons_value' => NULL,
      'field_test_phone_value' => NULL,
      'field_test_exclude_unset_value' => NULL,
      'field_test_exclude_unset_format' => NULL,
      'field_test_imagefield_fid' => NULL,
      'field_test_imagefield_list' => NULL,
      'field_test_imagefield_data' => NULL,
    ))->values(array(
      'nid' => '2',
      'vid' => '3',
      'uid' => '1',
      'field_test_three_value' => '23.20',
      'field_test_identical1_value' => '1',
      'field_test_identical2_value' => '1',
      'field_test_link_url' => 'http://groups.drupal.org/',
      'field_test_link_title' => 'Drupal Groups',
      'field_test_link_attributes' => 's:6:"a:0:{}";',
      'field_test_date_value' => NULL,
      'field_test_datestamp_value' => NULL,
      'field_test_datetime_value' => NULL,
      'field_test_email_email' => NULL,
      'field_test_filefield_fid' => NULL,
      'field_test_filefield_list' => NULL,
      'field_test_filefield_data' => NULL,
      'field_test_four_value' => NULL,
      'field_test_text_single_checkbox_value' => NULL,
      'field_test_integer_selectlist_value' => NULL,
      'field_test_float_single_checkbox_value' => NULL,
      'field_test_decimal_radio_buttons_value' => NULL,
      'field_test_phone_value' => NULL,
      'field_test_exclude_unset_value' => NULL,
      'field_test_exclude_unset_format' => NULL,
      'field_test_imagefield_fid' => NULL,
      'field_test_imagefield_list' => NULL,
      'field_test_imagefield_data' => NULL,
    ))->values(array(
      'nid' => '2',
      'vid' => '5',
      'uid' => '1',
      'field_test_three_value' => '23.20',
      'field_test_identical1_value' => '1',
      'field_test_identical2_value' => '1',
      'field_test_link_url' => 'http://groups.drupal.org/',
      'field_test_link_title' => 'Drupal Groups',
      'field_test_link_attributes' => 's:6:"a:0:{}";',
      'field_test_date_value' => NULL,
      'field_test_datestamp_value' => NULL,
      'field_test_datetime_value' => NULL,
      'field_test_email_email' => NULL,
      'field_test_filefield_fid' => NULL,
      'field_test_filefield_list' => NULL,
      'field_test_filefield_data' => NULL,
      'field_test_four_value' => NULL,
      'field_test_text_single_checkbox_value' => NULL,
      'field_test_integer_selectlist_value' => NULL,
      'field_test_float_single_checkbox_value' => NULL,
      'field_test_decimal_radio_buttons_value' => NULL,
      'field_test_phone_value' => NULL,
      'field_test_exclude_unset_value' => NULL,
      'field_test_exclude_unset_format' => NULL,
      'field_test_imagefield_fid' => NULL,
      'field_test_imagefield_list' => NULL,
      'field_test_imagefield_data' => NULL,
    ))->execute();
  }

}
