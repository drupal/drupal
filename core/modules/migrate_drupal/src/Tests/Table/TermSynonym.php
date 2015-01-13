<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\TermSynonym.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the term_synonym table.
 */
class TermSynonym extends Drupal6DumpBase {

  public function load() {
    $this->createTable("term_synonym", array(
      'primary key' => array(
        'tsid',
      ),
      'fields' => array(
        'tsid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("term_synonym")->fields(array(
      'tsid',
      'tid',
      'name',
    ))
    ->execute();
  }

}
