<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ContentGroup.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_group table.
 */
class ContentGroup extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_group", array(
      'fields' => array(
        'group_type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => 'standard',
        ),
        'type_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'group_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'label' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'settings' => array(
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
      ),
      'primary key' => array(
        'type_name',
        'group_name',
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("content_group")->fields(array(
      'group_type',
      'type_name',
      'group_name',
      'label',
      'settings',
      'weight',
    ))
    ->execute();
  }

}
#7e70933ab00570caa7341dab6424469a
