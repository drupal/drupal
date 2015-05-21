<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\TermRelation.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the term_relation table.
 */
class TermRelation extends DrupalDumpBase {

  public function load() {
    $this->createTable("term_relation", array(
      'primary key' => array(
        'trid',
      ),
      'fields' => array(
        'trid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'tid1' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'tid2' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("term_relation")->fields(array(
      'trid',
      'tid1',
      'tid2',
    ))
    ->execute();
  }

}
#008e1b937f330389e84d1b5604ed3b95
