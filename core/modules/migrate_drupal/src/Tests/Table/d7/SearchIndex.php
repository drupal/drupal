<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\SearchIndex.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the search_index table.
 */
class SearchIndex extends DrupalDumpBase {

  public function load() {
    $this->createTable("search_index", array(
      'primary key' => array(
        'word',
        'sid',
        'type',
      ),
      'fields' => array(
        'word' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '50',
          'default' => '',
        ),
        'sid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '16',
        ),
        'score' => array(
          'type' => 'numeric',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("search_index")->fields(array(
      'word',
      'sid',
      'type',
      'score',
    ))
    ->values(array(
      'word' => '1',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => '1192015',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => '19',
      'sid' => '1',
      'type' => 'node',
      'score' => '2',
    ))->values(array(
      'word' => '2015',
      'sid' => '1',
      'type' => 'node',
      'score' => '2',
    ))->values(array(
      'word' => '2215',
      'sid' => '1',
      'type' => 'node',
      'score' => '2',
    ))->values(array(
      'word' => '2218',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => '9',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => '99999999',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'abc5xyz',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'admin',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'another',
      'sid' => '1',
      'type' => 'node',
      'score' => '11',
    ))->values(array(
      'word' => 'click',
      'sid' => '1',
      'type' => 'node',
      'score' => '11',
    ))->values(array(
      'word' => 'comment',
      'sid' => '1',
      'type' => 'node',
      'score' => '27',
    ))->values(array(
      'word' => 'comments',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'default',
      'sid' => '1',
      'type' => 'node',
      'score' => '11',
    ))->values(array(
      'word' => 'examplecom',
      'sid' => '1',
      'type' => 'node',
      'score' => '22',
    ))->values(array(
      'word' => 'here',
      'sid' => '1',
      'type' => 'node',
      'score' => '11',
    ))->values(array(
      'word' => 'january',
      'sid' => '1',
      'type' => 'node',
      'score' => '2',
    ))->values(array(
      'word' => 'monday',
      'sid' => '1',
      'type' => 'node',
      'score' => '2',
    ))->values(array(
      'word' => 'more',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'node',
      'sid' => '1',
      'type' => 'node',
      'score' => '26',
    ))->values(array(
      'word' => 'permalink',
      'sid' => '1',
      'type' => 'node',
      'score' => '11',
    ))->values(array(
      'word' => 'post',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'prefix',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'register',
      'sid' => '1',
      'type' => 'node',
      'score' => '2.2',
    ))->values(array(
      'word' => 'some',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'submitted',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'text',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'this',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'value',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->values(array(
      'word' => 'value120suffix',
      'sid' => '1',
      'type' => 'node',
      'score' => '1',
    ))->execute();
  }

}
#0e6123f2a2fffe419965e1e7bc51fb63
