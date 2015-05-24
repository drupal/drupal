<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Users.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the users table.
 */
class Users extends DrupalDumpBase {

  public function load() {
    $this->createTable("users", array(
      'primary key' => array(
        'uid',
      ),
      'fields' => array(
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '60',
          'default' => '',
        ),
        'pass' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'mail' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '254',
          'default' => '',
        ),
        'theme' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'signature' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'signature_format' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'access' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'login' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'timezone' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '32',
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
          'default' => '',
        ),
        'picture' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'init' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '254',
          'default' => '',
        ),
        'data' => array(
          'type' => 'blob',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("users")->fields(array(
      'uid',
      'name',
      'pass',
      'mail',
      'theme',
      'signature',
      'signature_format',
      'created',
      'access',
      'login',
      'status',
      'timezone',
      'language',
      'picture',
      'init',
      'data',
    ))
    ->execute();
  }

}
#bb60d488e9420b75be9bb507fc35df8b
