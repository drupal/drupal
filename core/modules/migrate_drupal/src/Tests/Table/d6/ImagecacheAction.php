<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ImagecacheAction.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the imagecache_action table.
 */
class ImagecacheAction extends DrupalDumpBase {

  public function load() {
    $this->createTable("imagecache_action", array(
      'primary key' => array(
        'actionid',
      ),
      'fields' => array(
        'actionid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'presetid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'action' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'data' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("imagecache_action")->fields(array(
      'actionid',
      'presetid',
      'weight',
      'module',
      'action',
      'data',
    ))
    ->values(array(
      'actionid' => '3',
      'presetid' => '1',
      'weight' => '0',
      'module' => 'imagecache',
      'action' => 'imagecache_scale_and_crop',
      'data' => 'a:2:{s:5:"width";s:4:"100%";s:6:"height";s:4:"100%";}',
    ))->values(array(
      'actionid' => '4',
      'presetid' => '2',
      'weight' => '0',
      'module' => 'imagecache',
      'action' => 'imagecache_crop',
      'data' => 'a:4:{s:5:"width";s:3:"555";s:6:"height";s:4:"5555";s:7:"xoffset";s:6:"center";s:7:"yoffset";s:6:"center";}',
    ))->values(array(
      'actionid' => '5',
      'presetid' => '2',
      'weight' => '0',
      'module' => 'imagecache',
      'action' => 'imagecache_resize',
      'data' => 'a:2:{s:5:"width";s:3:"55%";s:6:"height";s:3:"55%";}',
    ))->values(array(
      'actionid' => '6',
      'presetid' => '2',
      'weight' => '0',
      'module' => 'imagecache',
      'action' => 'imagecache_rotate',
      'data' => 'a:3:{s:7:"degrees";s:2:"55";s:6:"random";i:0;s:7:"bgcolor";s:0:"";}',
    ))->execute();
  }

}
#90267fd3a92bd722208f016a23ab5f97
