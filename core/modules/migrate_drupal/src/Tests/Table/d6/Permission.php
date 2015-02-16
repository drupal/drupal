<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Permission.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the permission table.
 */
class Permission extends DrupalDumpBase {

  public function load() {
    $this->createTable("permission", array(
      'primary key' => array(
        'pid',
      ),
      'fields' => array(
        'pid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'rid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'perm' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("permission")->fields(array(
      'pid',
      'rid',
      'perm',
      'tid',
    ))
    ->values(array(
      'pid' => '1',
      'rid' => '1',
      'perm' => 'migrate test anonymous permission',
      'tid' => '0',
    ))->values(array(
      'pid' => '2',
      'rid' => '2',
      'perm' => 'migrate test authenticated permission',
      'tid' => '0',
    ))->values(array(
      'pid' => '3',
      'rid' => '3',
      'perm' => 'migrate test role 1 test permission',
      'tid' => '0',
    ))->values(array(
      'pid' => '4',
      'rid' => '4',
      'perm' => 'migrate test role 2 test permission, use PHP for settings, administer contact forms, skip comment approval, edit own blog content, edit any blog content, delete own blog content, delete any blog content, create forum content, delete any forum content, delete own forum content, edit any forum content, edit own forum content, administer nodes',
      'tid' => '0',
    ))->values(array(
      'pid' => '5',
      'rid' => '1',
      'perm' => 'access content',
      'tid' => '0',
    ))->values(array(
      'pid' => '6',
      'rid' => '2',
      'perm' => 'access comments, access content, post comments, post comments without approval',
      'tid' => '0',
    ))->execute();
  }

}
