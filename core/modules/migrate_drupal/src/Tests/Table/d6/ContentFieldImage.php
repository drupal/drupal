<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ContentFieldImage.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_field_image table.
 */
class ContentFieldImage extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_field_image", array(
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
        'field_image_fid' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
        ),
        'field_image_list' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '4',
        ),
        'field_image_data' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("content_field_image")->fields(array(
      'vid',
      'nid',
      'field_image_fid',
      'field_image_list',
      'field_image_data',
    ))
    ->values(array(
      'vid' => '1',
      'nid' => '1',
      'field_image_fid' => '2',
      'field_image_list' => '1',
      'field_image_data' => 'a:2:{s:3:"alt";s:0:"";s:5:"title";s:0:"";}',
    ))->values(array(
      'vid' => '2',
      'nid' => '2',
      'field_image_fid' => NULL,
      'field_image_list' => NULL,
      'field_image_data' => NULL,
    ))->values(array(
      'vid' => '3',
      'nid' => '1',
      'field_image_fid' => '2',
      'field_image_list' => '1',
      'field_image_data' => 'a:2:{s:3:"alt";s:0:"";s:5:"title";s:0:"";}',
    ))->execute();
  }

}
