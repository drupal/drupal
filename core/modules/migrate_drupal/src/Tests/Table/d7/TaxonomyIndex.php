<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\TaxonomyIndex.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the taxonomy_index table.
 */
class TaxonomyIndex extends DrupalDumpBase {

  public function load() {
    $this->createTable("taxonomy_index", array(
      'fields' => array(
        'nid' => array(
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
        'sticky' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '4',
          'default' => '0',
        ),
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("taxonomy_index")->fields(array(
      'nid',
      'tid',
      'sticky',
      'created',
    ))
    ->values(array(
      'nid' => '1',
      'tid' => '4',
      'sticky' => '0',
      'created' => '1421727515',
    ))->execute();
  }

}
#b4d7ca5b3adaf8e6e3938a7a6f43bd33
