<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Languages.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the languages table.
 */
class Languages extends DrupalDumpBase {

  public function load() {
    $this->createTable("languages", array(
      'primary key' => array(
        'language',
      ),
      'fields' => array(
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
          'default' => '',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'native' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'direction' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'enabled' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'plurals' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'formula' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'domain' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'prefix' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'javascript' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("languages")->fields(array(
      'language',
      'name',
      'native',
      'direction',
      'enabled',
      'plurals',
      'formula',
      'domain',
      'prefix',
      'weight',
      'javascript',
    ))
    ->values(array(
      'language' => 'en',
      'name' => 'English',
      'native' => 'English',
      'direction' => '0',
      'enabled' => '1',
      'plurals' => '0',
      'formula' => '',
      'domain' => '',
      'prefix' => '',
      'weight' => '0',
      'javascript' => '',
    ))->execute();
  }

}
#458e20ce6a713188fcd8d1efb9e82c3d
