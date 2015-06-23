<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\UrlAlias.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

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
        'source' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'alias' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
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
      'source',
      'alias',
      'language',
    ))
    ->values(array(
      'pid' => '1',
      'source' => 'taxonomy/term/4',
      'alias' => 'term33',
      'language' => 'und',
    ))->execute();
  }

}
#13b4d67819660960304623cb5b30d2a5
