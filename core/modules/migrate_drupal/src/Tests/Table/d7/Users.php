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
          'length' => '11',
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
      'mysql_character_set' => 'utf8',
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
    ->values(array(
      'uid' => '1',
      'name' => 'root',
      'pass' => '$S$D/HVkgCg1Hvi7DN5KVSgNl.2C5g8W6oe/OoIRMUlyjkmPugQRhoB',
      'mail' => '',
      'theme' => '',
      'signature' => '',
      'signature_format' => NULL,
      'created' => '0',
      'access' => '0',
      'login' => '0',
      'status' => '1',
      'timezone' => NULL,
      'language' => '',
      'picture' => '0',
      'init' => '',
      'data' => 'a:1:{s:7:"contact";i:1;}',
    ))->values(array(
      'uid' => '2',
      'name' => 'Odo',
      'pass' => '$S$DZ4P7zZOh92vgrgZDBbv8Pu6lQB337OJ1wsOy21602G4A5F7.M9K',
      'mail' => 'odo@local.host',
      'theme' => '',
      'signature' => '',
      'signature_format' => 'filtered_html',
      'created' => '1440532218',
      'access' => '0',
      'login' => '0',
      'status' => '1',
      'timezone' => 'America/Chicago',
      'language' => '',
      'picture' => '0',
      'init' => 'odo@local.host',
      'data' => 'a:1:{s:7:"contact";i:1;}',
    ))->execute();
  }

}
#b8a27fc07ad0e18e5fded43f9d54e4f2
