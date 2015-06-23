<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\SearchTotal.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the search_total table.
 */
class SearchTotal extends DrupalDumpBase {

  public function load() {
    $this->createTable("search_total", array(
      'primary key' => array(
        'word',
      ),
      'fields' => array(
        'word' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '50',
          'default' => '',
        ),
        'count' => array(
          'type' => 'numeric',
          'not null' => FALSE,
          'precision' => '10',
          'scale' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("search_total")->fields(array(
      'word',
      'count',
    ))
    ->values(array(
      'word' => '1',
      'count' => '0',
    ))->values(array(
      'word' => '1192015',
      'count' => '0',
    ))->values(array(
      'word' => '19',
      'count' => '0',
    ))->values(array(
      'word' => '2015',
      'count' => '0',
    ))->values(array(
      'word' => '2215',
      'count' => '0',
    ))->values(array(
      'word' => '2218',
      'count' => '0',
    ))->values(array(
      'word' => '9',
      'count' => '0',
    ))->values(array(
      'word' => '99999999',
      'count' => '0',
    ))->values(array(
      'word' => 'abc5xyz',
      'count' => '0',
    ))->values(array(
      'word' => 'admin',
      'count' => '0',
    ))->values(array(
      'word' => 'another',
      'count' => '0',
    ))->values(array(
      'word' => 'click',
      'count' => '0',
    ))->values(array(
      'word' => 'comment',
      'count' => '0',
    ))->values(array(
      'word' => 'comments',
      'count' => '0',
    ))->values(array(
      'word' => 'default',
      'count' => '0',
    ))->values(array(
      'word' => 'examplecom',
      'count' => '0',
    ))->values(array(
      'word' => 'here',
      'count' => '0',
    ))->values(array(
      'word' => 'january',
      'count' => '0',
    ))->values(array(
      'word' => 'monday',
      'count' => '0',
    ))->values(array(
      'word' => 'more',
      'count' => '0',
    ))->values(array(
      'word' => 'node',
      'count' => '0',
    ))->values(array(
      'word' => 'permalink',
      'count' => '0',
    ))->values(array(
      'word' => 'post',
      'count' => '0',
    ))->values(array(
      'word' => 'prefix',
      'count' => '0',
    ))->values(array(
      'word' => 'register',
      'count' => '0',
    ))->values(array(
      'word' => 'some',
      'count' => '0',
    ))->values(array(
      'word' => 'submitted',
      'count' => '0',
    ))->values(array(
      'word' => 'text',
      'count' => '0',
    ))->values(array(
      'word' => 'this',
      'count' => '0',
    ))->values(array(
      'word' => 'value',
      'count' => '0',
    ))->values(array(
      'word' => 'value120suffix',
      'count' => '0',
    ))->execute();
  }

}
#7ef964179b2e8308418e09f4ef322854
