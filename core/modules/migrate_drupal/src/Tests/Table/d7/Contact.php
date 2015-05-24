<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Contact.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the contact table.
 */
class Contact extends DrupalDumpBase {

  public function load() {
    $this->createTable("contact", array(
      'primary key' => array(
        'cid',
      ),
      'fields' => array(
        'cid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'category' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'recipients' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'reply' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'selected' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("contact")->fields(array(
      'cid',
      'category',
      'recipients',
      'reply',
      'weight',
      'selected',
    ))
    ->values(array(
      'cid' => '1',
      'category' => 'Website testing',
      'recipients' => 'joseph@flattandsons.com',
      'reply' => '',
      'weight' => '0',
      'selected' => '1',
    ))->execute();
  }

}
#04f57d5c840ff51e75617ce50b184b81
