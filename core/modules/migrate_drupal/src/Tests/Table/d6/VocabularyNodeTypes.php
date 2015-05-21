<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\VocabularyNodeTypes.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the vocabulary_node_types table.
 */
class VocabularyNodeTypes extends DrupalDumpBase {

  public function load() {
    $this->createTable("vocabulary_node_types", array(
      'primary key' => array(
        'vid',
        'type',
      ),
      'fields' => array(
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("vocabulary_node_types")->fields(array(
      'vid',
      'type',
    ))
    ->values(array(
      'vid' => '4',
      'type' => 'article',
    ))->values(array(
      'vid' => '4',
      'type' => 'page',
    ))->values(array(
      'vid' => '1',
      'type' => 'story',
    ))->values(array(
      'vid' => '2',
      'type' => 'story',
    ))->values(array(
      'vid' => '3',
      'type' => 'story',
    ))->execute();
  }

}
#b427f4114483acbc05cf2e6dff8bfe0f
