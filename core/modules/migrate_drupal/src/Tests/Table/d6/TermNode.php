<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\TermNode.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the term_node table.
 */
class TermNode extends DrupalDumpBase {

  public function load() {
    $this->createTable("term_node", array(
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
      'primary key' => array(
        'vid',
        'tid',
      ),
    ));
    $this->database->insert("term_node")->fields(array(
      'nid',
      'vid',
      'tid',
    ))
    ->values(array(
      'nid' => '1',
      'vid' => '1',
      'tid' => '1',
    ))->values(array(
      'nid' => '1',
      'vid' => '2',
      'tid' => '4',
    ))->values(array(
      'nid' => '1',
      'vid' => '2',
      'tid' => '5',
    ))->values(array(
      'nid' => '2',
      'vid' => '3',
      'tid' => '2',
    ))->values(array(
      'nid' => '2',
      'vid' => '3',
      'tid' => '3',
    ))->execute();
  }

}
