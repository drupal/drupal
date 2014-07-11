<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Block.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

class Drupal6Block extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('blocks', array(
      'fields' => array(
        'bid' => array(
          'type' => 'serial',
          'not null' => TRUE,
        ),
        'module' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
        ),
        'delta' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '0',
        ),
        'theme' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'region' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
        ),
        'custom' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'throttle' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'visibility' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'pages' => array(
          'type' => 'text',
          'not null' => TRUE,
        ),
        'title' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
        ),
        'cache' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 1,
          'size' => 'tiny',
        ),
      ),
      'primary key' => array(
        'bid',
      ),
      'unique keys' => array(
        'tmd' => array(
          'theme',
          'module',
          'delta',
        ),
      ),
      'indexes' => array(
        'list' => array(
          'theme',
          'status',
          'region',
          'weight',
          'module',
        ),
      ),
    ));
    $this->createTable('blocks_roles', array(
      'fields' => array(
        'module' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
        ),
        'delta' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
        ),
        'rid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
      ),
      'primary key' => array(
        'module',
        'delta',
        'rid',
      ),
      'indexes' => array(
        'rid' => array(
          'rid',
        ),
      ),
      'module' => 'block',
      'name' => 'blocks_roles',
    ));
    $this->database->insert('blocks')->fields(array(
      'bid',
      'module',
      'delta',
      'theme',
      'status',
      'weight',
      'region',
      'custom',
      'throttle',
      'visibility',
      'pages',
      'title',
      'cache',
    ))
    ->values(array(
      'bid' => '1',
      'module' => 'user',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '0',
      'region' => 'left',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '2',
      'module' => 'user',
      'delta' => '1',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '0',
      'region' => 'left',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '3',
      'module' => 'system',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '-5',
      'region' => 'footer',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '4',
      'module' => 'comment',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-6',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1'
    ))
    ->values(array(
      'bid' => '5',
      'module' => 'menu',
      'delta' => 'primary-links',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '-5',
      'region' => 'header',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '6',
      'module' => 'menu',
      'delta' => 'secondary-links',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-5',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '7',
      'module' => 'node',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-4',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '8',
      'module' => 'user',
      'delta' => '2',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-3',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1'
    ))
    ->values(array(
      'bid' => '9',
      'module' => 'user',
      'delta' => '3',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-1',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '10',
      'module' => 'block',
      'delta' => '1',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '0',
      'region' => 'content',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '1',
      'pages' => '<front>',
      'title' => 'Static Block',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '11',
      'module' => 'block',
      'delta' => '2',
      'theme' => 'bluemarine',
      'status' => '1',
      'weight' => '-4',
      'region' => 'right',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '1',
      'pages' => 'node',
      'title' => 'Another Static Block',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '12',
      'module' => 'block',
      'delta' => '1',
      'theme' => 'test_theme',
      'status' => '1',
      'weight' => '-7',
      'region' => 'right',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->values(array(
      'bid' => '13',
      'module' => 'block',
      'delta' => '2',
      'theme' => 'test_theme',
      'status' => '1',
      'weight' => '-2',
      'region' => 'left',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1'
    ))
    ->execute();
  }
}
