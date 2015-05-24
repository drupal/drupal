<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Comment.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the comment table.
 */
class Comment extends DrupalDumpBase {

  public function load() {
    $this->createTable("comment", array(
      'primary key' => array(
        'cid',
      ),
      'fields' => array(
        'cid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'pid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'subject' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'hostname' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'changed' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
          'default' => '1',
          'unsigned' => TRUE,
        ),
        'thread' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '60',
        ),
        'mail' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '64',
        ),
        'homepage' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("comment")->fields(array(
      'cid',
      'pid',
      'nid',
      'uid',
      'subject',
      'hostname',
      'created',
      'changed',
      'status',
      'thread',
      'name',
      'mail',
      'homepage',
      'language',
    ))
    ->values(array(
      'cid' => '1',
      'pid' => '0',
      'nid' => '1',
      'uid' => '1',
      'subject' => 'A comment',
      'hostname' => '::1',
      'created' => '1421727536',
      'changed' => '1421727536',
      'status' => '1',
      'thread' => '01/',
      'name' => 'admin',
      'mail' => '',
      'homepage' => '',
      'language' => 'und',
    ))->execute();
  }

}
#a4c31706927b4da91d649feee8242c65
