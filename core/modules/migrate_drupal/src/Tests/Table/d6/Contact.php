<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Contact.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

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
          'length' => '11',
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
      'category' => 'Website feedback',
      'recipients' => 'admin@example.com',
      'reply' => '',
      'weight' => '0',
      'selected' => '0',
    ))->values(array(
      'cid' => '2',
      'category' => 'Some other category',
      'recipients' => 'test@example.com',
      'reply' => 'Thanks for contacting us, we will reply ASAP!',
      'weight' => '1',
      'selected' => '1',
    ))->values(array(
      'cid' => '3',
      'category' => 'A category much longer than thirty two characters',
      'recipients' => 'fortyninechars@example.com',
      'reply' => '',
      'weight' => '2',
      'selected' => '0',
    ))->execute();
  }

}
#e530f86fd4d7ae2251ec4f485ae0e917
