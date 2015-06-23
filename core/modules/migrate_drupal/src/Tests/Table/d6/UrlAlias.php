<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\UrlAlias.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the url_alias table.
 */
class UrlAlias extends DrupalDumpBase {

  public function load() {
    $this->createTable("url_alias", array(
      'primary key' => array(
        'pid',
      ),
      'fields' => array(
        'pid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'src' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'dst' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("url_alias")->fields(array(
      'pid',
      'src',
      'dst',
      'language',
    ))
    ->values(array(
      'pid' => '1',
      'src' => 'node/1',
      'dst' => 'alias-one',
      'language' => 'en',
    ))->values(array(
      'pid' => '2',
      'src' => 'node/2',
      'dst' => 'alias-two',
      'language' => 'en',
    ))->execute();
  }

}
#8aa75592c75220bfb2ad948f7528f943
