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
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("search_total")->fields(array(
      'word',
      'count',
    ))
    ->values(array(
      'word' => '1',
      'count' => '0.30103',
    ))->values(array(
      'word' => '1192015',
      'count' => '0.30103',
    ))->values(array(
      'word' => '19',
      'count' => '0.176091',
    ))->values(array(
      'word' => '2015',
      'count' => '0.176091',
    ))->values(array(
      'word' => '2215',
      'count' => '0.176091',
    ))->values(array(
      'word' => '2218',
      'count' => '0.30103',
    ))->values(array(
      'word' => '9',
      'count' => '0.30103',
    ))->values(array(
      'word' => '99999999',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'abc5xyz',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'admin',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'another',
      'count' => '0.0377886',
    ))->values(array(
      'word' => 'click',
      'count' => '0.0377886',
    ))->values(array(
      'word' => 'comment',
      'count' => '0.0157943',
    ))->values(array(
      'word' => 'comments',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'default',
      'count' => '0.0377886',
    ))->values(array(
      'word' => 'examplecom',
      'count' => '0.0193052',
    ))->values(array(
      'word' => 'here',
      'count' => '0.0377886',
    ))->values(array(
      'word' => 'january',
      'count' => '0.176091',
    ))->values(array(
      'word' => 'monday',
      'count' => '0.176091',
    ))->values(array(
      'word' => 'more',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'node',
      'count' => '0.0163904',
    ))->values(array(
      'word' => 'permalink',
      'count' => '0.0377886',
    ))->values(array(
      'word' => 'post',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'prefix',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'register',
      'count' => '0.162727',
    ))->values(array(
      'word' => 'some',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'submitted',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'text',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'this',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'value',
      'count' => '0.30103',
    ))->values(array(
      'word' => 'value120suffix',
      'count' => '0.30103',
    ))->execute();
  }

}
#b0a1f9cd3e748132f5861416093877cf
