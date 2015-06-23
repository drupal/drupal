<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Actions.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the actions table.
 */
class Actions extends DrupalDumpBase {

  public function load() {
    $this->createTable("actions", array(
      'primary key' => array(
        'aid',
      ),
      'fields' => array(
        'aid' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '0',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'callback' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'parameters' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'length' => 100,
        ),
        'label' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("actions")->fields(array(
      'aid',
      'type',
      'callback',
      'parameters',
      'label',
    ))
    ->values(array(
      'aid' => 'comment_publish_action',
      'type' => 'comment',
      'callback' => 'comment_publish_action',
      'parameters' => '',
      'label' => 'Publish comment',
    ))->values(array(
      'aid' => 'comment_save_action',
      'type' => 'comment',
      'callback' => 'comment_save_action',
      'parameters' => '',
      'label' => 'Save comment',
    ))->values(array(
      'aid' => 'comment_unpublish_action',
      'type' => 'comment',
      'callback' => 'comment_unpublish_action',
      'parameters' => '',
      'label' => 'Unpublish comment',
    ))->values(array(
      'aid' => 'node_make_sticky_action',
      'type' => 'node',
      'callback' => 'node_make_sticky_action',
      'parameters' => '',
      'label' => 'Make content sticky',
    ))->values(array(
      'aid' => 'node_make_unsticky_action',
      'type' => 'node',
      'callback' => 'node_make_unsticky_action',
      'parameters' => '',
      'label' => 'Make content unsticky',
    ))->values(array(
      'aid' => 'node_promote_action',
      'type' => 'node',
      'callback' => 'node_promote_action',
      'parameters' => '',
      'label' => 'Promote content to front page',
    ))->values(array(
      'aid' => 'node_publish_action',
      'type' => 'node',
      'callback' => 'node_publish_action',
      'parameters' => '',
      'label' => 'Publish content',
    ))->values(array(
      'aid' => 'node_save_action',
      'type' => 'node',
      'callback' => 'node_save_action',
      'parameters' => '',
      'label' => 'Save content',
    ))->values(array(
      'aid' => 'node_unpromote_action',
      'type' => 'node',
      'callback' => 'node_unpromote_action',
      'parameters' => '',
      'label' => 'Remove content from front page',
    ))->values(array(
      'aid' => 'node_unpublish_action',
      'type' => 'node',
      'callback' => 'node_unpublish_action',
      'parameters' => '',
      'label' => 'Unpublish content',
    ))->values(array(
      'aid' => 'system_block_ip_action',
      'type' => 'user',
      'callback' => 'system_block_ip_action',
      'parameters' => '',
      'label' => 'Ban IP address of current user',
    ))->values(array(
      'aid' => 'user_block_user_action',
      'type' => 'user',
      'callback' => 'user_block_user_action',
      'parameters' => '',
      'label' => 'Block current user',
    ))->execute();
  }

}
#28b8f51b5608625c77b3d08229fb6a73
