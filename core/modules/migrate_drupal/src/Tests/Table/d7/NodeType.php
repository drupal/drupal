<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\NodeType.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the node_type table.
 */
class NodeType extends DrupalDumpBase {

  public function load() {
    $this->createTable("node_type", array(
      'primary key' => array(
        'type',
      ),
      'fields' => array(
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'base' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'help' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'has_title' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'title_label' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'custom' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'modified' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'locked' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'disabled' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'orig_type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("node_type")->fields(array(
      'type',
      'name',
      'base',
      'module',
      'description',
      'help',
      'has_title',
      'title_label',
      'custom',
      'modified',
      'locked',
      'disabled',
      'orig_type',
    ))
    ->values(array(
      'type' => 'article',
      'name' => 'Article',
      'base' => 'node_content',
      'module' => 'node',
      'description' => 'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.',
      'help' => 'Help text for articles',
      'has_title' => '1',
      'title_label' => 'Title',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'disabled' => '0',
      'orig_type' => 'article',
    ))->values(array(
      'type' => 'blog',
      'name' => 'Blog entry',
      'base' => 'blog',
      'module' => 'blog',
      'description' => 'Use for multi-user blogs. Every user gets a personal blog.',
      'help' => 'Blog away, good sir!',
      'has_title' => '1',
      'title_label' => 'Title',
      'custom' => '0',
      'modified' => '1',
      'locked' => '1',
      'disabled' => '0',
      'orig_type' => 'blog',
    ))->values(array(
      'type' => 'book',
      'name' => 'Book page',
      'base' => 'node_content',
      'module' => 'node',
      'description' => '<em>Books</em> have a built-in hierarchical navigation. Use for handbooks or tutorials.',
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'disabled' => '0',
      'orig_type' => 'book',
    ))->values(array(
      'type' => 'forum',
      'name' => 'Forum topic',
      'base' => 'forum',
      'module' => 'forum',
      'description' => 'A <em>forum topic</em> starts a new discussion thread within a forum.',
      'help' => 'No name-calling, no flame wars. Be nice.',
      'has_title' => '1',
      'title_label' => 'Subject',
      'custom' => '0',
      'modified' => '1',
      'locked' => '1',
      'disabled' => '0',
      'orig_type' => 'forum',
    ))->values(array(
      'type' => 'page',
      'name' => 'Basic page',
      'base' => 'node_content',
      'module' => 'node',
      'description' => "Use <em>basic pages</em> for your static content, such as an 'About us' page.",
      'help' => 'Help text for basic pages',
      'has_title' => '1',
      'title_label' => 'Title',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'disabled' => '0',
      'orig_type' => 'page',
    ))->values(array(
      'type' => 'test_content_type',
      'name' => 'Test content type',
      'base' => 'node_content',
      'module' => 'node',
      'description' => 'This is the description of the test content type.',
      'help' => 'Help text for test content type',
      'has_title' => '1',
      'title_label' => 'Title',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'disabled' => '0',
      'orig_type' => 'test_content_type',
    ))->execute();
  }

}
#9bef45bc61d3712f730ca19ec8be6f21
