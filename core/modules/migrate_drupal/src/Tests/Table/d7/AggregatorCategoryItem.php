<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\AggregatorCategoryItem.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the aggregator_category_item table.
 */
class AggregatorCategoryItem extends DrupalDumpBase {

  public function load() {
    $this->createTable("aggregator_category_item", array(
      'primary key' => array(
        'iid',
        'cid',
      ),
      'fields' => array(
        'iid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'cid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("aggregator_category_item")->fields(array(
      'iid',
      'cid',
    ))
    ->execute();
  }

}
#18a56c59bc4bbcf06db0b68ba24b1c49
