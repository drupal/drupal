<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Book.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

class Drupal6Book extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('book', array(
      'fields' => array(
        'mlid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'nid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'bid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array(
        'mlid',
      ),
      'unique keys' => array(
        'nid' => array(
          'nid',
        ),
      ),
      'indexes' => array(
        'bid' => array(
          'bid',
        ),
      ),
      'module' => 'book',
      'name' => 'book',
    ));
    $this->createTable('menu_links', array(
      'fields' => array(
        'menu_name' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'mlid' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'plid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'link_path' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'router_path' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'link_title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'options' => array(
          'type' => 'text',
          'not null' => FALSE,
        ),
        'module' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'system',
        ),
        'hidden' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'external' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'has_children' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'expanded' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'depth' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'customized' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'p1' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p2' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p3' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p4' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p5' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p6' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p7' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p8' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p9' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'updated' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
      ),
      'indexes' => array(
        'path_menu' => array(
          array(
            'link_path',
            128,
          ),
          'menu_name',
        ),
        'menu_plid_expand_child' => array(
          'menu_name',
          'plid',
          'expanded',
          'has_children',
        ),
        'menu_parents' => array(
          'menu_name',
          'p1',
          'p2',
          'p3',
          'p4',
          'p5',
          'p6',
          'p7',
          'p8',
          'p9',
        ),
        'router_path' => array(
          array(
            'router_path',
            128,
          ),
        ),
      ),
      'primary key' => array(
        'mlid',
      ),
      'module' => 'system',
      'name' => 'menu_links',
    ));
    $this->database->insert('book')->fields(array(
      'mlid',
      'nid',
      'bid',
    ))
    ->values(array(
      'mlid' => '1',
      'nid' => '4',
      'bid' => '4',
    ))
    ->values(array(
      'mlid' => '2',
      'nid' => '5',
      'bid' => '4',
    ))
    ->values(array(
      'mlid' => '3',
      'nid' => '6',
      'bid' => '4',
    ))
    ->values(array(
      'mlid' => '4',
      'nid' => '7',
      'bid' => '4',
    ))
    ->values(array(
      'mlid' => '5',
      'nid' => '8',
      'bid' => '8',
    ))
    ->execute();
    $this->database->insert('menu_links')->fields(array(
      'menu_name',
      'mlid',
      'plid',
      'link_path',
      'router_path',
      'link_title',
      'options',
      'module',
      'hidden',
      'external',
      'has_children',
      'expanded',
      'weight',
      'depth',
      'customized',
      'p1',
      'p2',
      'p3',
      'p4',
      'p5',
      'p6',
      'p7',
      'p8',
      'p9',
      'updated',
    ))
    ->values(array(
      'menu_name' => 'book-toc-1',
      'mlid' => '1',
      'plid' => '0',
      'link_path' => 'node/4',
      'router_path' => 'node/%',
      'link_title' => 'Test top book title',
      'options' => 'a:0:{}',
      'module' => 'book',
      'hidden' => '0',
      'external' => '0',
      'has_children' => '1',
      'expanded' => '0',
      'weight' => '-10',
      'depth' => '1',
      'customized' => '0',
      'p1' => '1',
      'p2' => '0',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
    ))
    ->values(array(
      'menu_name' => 'book-toc-1',
      'mlid' => '2',
      'plid' => '1',
      'link_path' => 'node/5',
      'router_path' => 'node/%',
      'link_title' => 'Test book title child 1',
      'options' => 'a:0:{}',
      'module' => 'book',
      'hidden' => '0',
      'external' => '0',
      'has_children' => '1',
      'expanded' => '0',
      'weight' => '0',
      'depth' => '2',
      'customized' => '0',
      'p1' => '1',
      'p2' => '2',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
    ))
    ->values(array(
      'menu_name' => 'book-toc-1',
      'mlid' => '3',
      'plid' => '2',
      'link_path' => 'node/6',
      'router_path' => 'node/%',
      'link_title' => 'Test book title child 1.1',
      'options' => 'a:0:{}',
      'module' => 'book',
      'hidden' => '0',
      'external' => '0',
      'has_children' => '0',
      'expanded' => '0',
      'weight' => '0',
      'depth' => '3',
      'customized' => '0',
      'p1' => '1',
      'p2' => '2',
      'p3' => '3',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
    ))
    ->values(array(
      'menu_name' => 'book-toc-1',
      'mlid' => '4',
      'plid' => '2',
      'link_path' => 'node/7',
      'router_path' => 'node/%',
      'link_title' => 'Test book title child 1.2',
      'options' => 'a:0:{}',
      'module' => 'book',
      'hidden' => '0',
      'external' => '0',
      'has_children' => '0',
      'expanded' => '0',
      'weight' => '0',
      'depth' => '3',
      'customized' => '0',
      'p1' => '1',
      'p2' => '2',
      'p3' => '4',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
    ))
    ->values(array(
      'menu_name' => 'book-toc-2',
      'mlid' => '5',
      'plid' => '0',
      'link_path' => 'node/8',
      'router_path' => 'node/%',
      'link_title' => 'Test top book 2 title',
      'options' => 'a:0:{}',
      'module' => 'book',
      'hidden' => '0',
      'external' => '0',
      'has_children' => '1',
      'expanded' => '0',
      'weight' => '-10',
      'depth' => '1',
      'customized' => '0',
      'p1' => '5',
      'p2' => '0',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
    ))
    ->execute();
  }
}
