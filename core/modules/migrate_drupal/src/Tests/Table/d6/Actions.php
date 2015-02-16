<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Actions.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

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
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'description' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("actions")->fields(array(
      'aid',
      'type',
      'callback',
      'parameters',
      'description',
    ))
    ->values(array(
      'aid' => 'comment_publish_action',
      'type' => 'comment',
      'callback' => 'comment_publish_action',
      'parameters' => '',
      'description' => 'Publish comment',
    ))->values(array(
      'aid' => 'comment_unpublish_action',
      'type' => 'comment',
      'callback' => 'comment_unpublish_action',
      'parameters' => '',
      'description' => 'Unpublish comment',
    ))->values(array(
      'aid' => 'node_make_sticky_action',
      'type' => 'node',
      'callback' => 'node_make_sticky_action',
      'parameters' => '',
      'description' => 'Make post sticky',
    ))->values(array(
      'aid' => 'node_make_unsticky_action',
      'type' => 'node',
      'callback' => 'node_make_unsticky_action',
      'parameters' => '',
      'description' => 'Make post unsticky',
    ))->values(array(
      'aid' => 'node_promote_action',
      'type' => 'node',
      'callback' => 'node_promote_action',
      'parameters' => '',
      'description' => 'Promote post to front page',
    ))->values(array(
      'aid' => 'node_publish_action',
      'type' => 'node',
      'callback' => 'node_publish_action',
      'parameters' => '',
      'description' => 'Publish post',
    ))->values(array(
      'aid' => 'node_save_action',
      'type' => 'node',
      'callback' => 'node_save_action',
      'parameters' => '',
      'description' => 'Save post',
    ))->values(array(
      'aid' => 'node_unpromote_action',
      'type' => 'node',
      'callback' => 'node_unpromote_action',
      'parameters' => '',
      'description' => 'Remove post from front page',
    ))->values(array(
      'aid' => 'node_unpublish_action',
      'type' => 'node',
      'callback' => 'node_unpublish_action',
      'parameters' => '',
      'description' => 'Unpublish post',
    ))->values(array(
      'aid' => 'user_block_ip_action',
      'type' => 'user',
      'callback' => 'user_block_ip_action',
      'parameters' => '',
      'description' => 'Ban IP address of current user',
    ))->values(array(
      'aid' => 'user_block_user_action',
      'type' => 'user',
      'callback' => 'user_block_user_action',
      'parameters' => '',
      'description' => 'Block current user',
    ))->execute();
  }

}
