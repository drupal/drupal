<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6MenuLink.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing the menu link migration.
 */
class Drupal6MenuLink extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('menu_links', array(
      'description' => 'Contains the individual links within a menu.',
      'fields' => array(
        'menu_name' => array(
          'description' => "The menu name. All links with the same menu name (such as 'navigation') are part of the same menu.",
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => ''),
        'mlid' => array(
          'description' => 'The menu link ID (mlid) is the integer primary key.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE),
        'plid' => array(
          'description' => 'The parent link ID (plid) is the mlid of the link above in the hierarchy, or zero if the link is at the top level in its menu.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'link_path' => array(
          'description' => 'The Drupal path or external path this link points to.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => ''),
        'router_path' => array(
          'description' => 'For links corresponding to a Drupal path (external = 0), this connects the link to a {menu_router}.path for joins.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => ''),
        'link_title' => array(
          'description' => 'The text displayed for the link, which may be modified by a title callback stored in {menu_router}.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => ''),
        'options' => array(
          'description' => 'A serialized array of options to be passed to the url() or l() function, such as a query string or HTML attributes.',
          'type' => 'text',
          'not null' => FALSE),
        'module' => array(
          'description' => 'The name of the module that generated this link.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'system'),
        'hidden' => array(
          'description' => 'A flag for whether the link should be rendered in menus. (1 = a disabled menu item that may be shown on admin screens, -1 = a menu callback, 0 = a normal, visible link)',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small'),
        'external' => array(
          'description' => 'A flag to indicate if the link points to a full URL starting with a protocol, like http:// (1 = external, 0 = internal).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small'),
        'has_children' => array(
          'description' => 'Flag indicating whether any links have this link as a parent (1 = children exist, 0 = no children).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small'),
        'expanded' => array(
          'description' => 'Flag for whether this link should be rendered as expanded in menus - expanded links always have their child links displayed, instead of only when the link is in the active trail (1 = expanded, 0 = not expanded)',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small'),
        'weight' => array(
          'description' => 'Link weight among links in the same menu at the same depth.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0),
        'depth' => array(
          'description' => 'The depth relative to the top level. A link with plid == 0 will have depth == 1.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small'),
        'customized' => array(
          'description' => 'A flag to indicate that the user has manually created or edited the link (1 = customized, 0 = not customized).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small'),
        'p1' => array(
          'description' => 'The first mlid in the materialized path. If N = depth, then pN must equal the mlid. If depth > 1 then p(N-1) must equal the plid. All pX where X > depth must equal zero. The columns p1 .. p9 are also called the parents.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p2' => array(
          'description' => 'The second mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p3' => array(
          'description' => 'The third mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p4' => array(
          'description' => 'The fourth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p5' => array(
          'description' => 'The fifth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p6' => array(
          'description' => 'The sixth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p7' => array(
          'description' => 'The seventh mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p8' => array(
          'description' => 'The eighth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'p9' => array(
          'description' => 'The ninth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'updated' => array(
          'description' => 'Flag that indicates that this link was generated during the update from Drupal 5.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small'),
      ),
      'indexes' => array(
        'path_menu' => array(array('link_path', 128), 'menu_name'),
        'menu_plid_expand_child' => array(
          'menu_name', 'plid', 'expanded', 'has_children'),
        'menu_parents' => array(
          'menu_name', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8', 'p9'),
        'router_path' => array(array('router_path', 128)),
      ),
      'primary key' => array('mlid'),
    ));
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
      'menu_name' => 'secondary-links',
      'mlid' => 138,
      'plid' => 0,
      'link_path' => 'user/login',
      'router_path' => 'user/login',
      'link_title' => 'Test 1',
      'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"title";s:16:"Test menu link 1";}}',
      'module' => 'menu',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 1,
      'expanded' => 0,
      'weight' => 15,
      'depth' => 1,
      'customized' => 1,
      'p1' => '138',
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
      'menu_name' => 'secondary-links',
      'mlid' => 139,
      'plid' => 138,
      'link_path' => 'admin',
      'router_path' => 'admin',
      'link_title' => 'Test 2',
      'options' => 'a:2:{s:5:"query";s:7:"foo=bar";s:10:"attributes";a:1:{s:5:"title";s:16:"Test menu link 2";}}',
      'module' => 'menu',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 0,
      'expanded' => 1,
      'weight' => 12,
      'depth' => 2,
      'customized' => 1,
      'p1' => '138',
      'p2' => '139',
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
      'menu_name' => 'secondary-links',
      'mlid' => 140,
      'plid' => 0,
      'link_path' => 'http://drupal.org',
      'router_path' => '',
      'link_title' => 'Drupal.org',
      'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"title";s:0:"";}}',
      'module' => 'menu',
      'hidden' => 0,
      'external' => 1,
      'has_children' => 0,
      'expanded' => 0,
      'weight' => 0,
      'depth' => 1,
      'customized' => 1,
      'p1' => '0',
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
