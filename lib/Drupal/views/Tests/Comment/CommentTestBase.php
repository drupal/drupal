<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Comment\CommentTestBase.
 */

namespace Drupal\views\Tests\Comment;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\View;

/**
 * Tests the argument_comment_user_uid handler.
 */
abstract class CommentTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment');

  function setUp() {
    parent::setUp();

    // Add two users, create a node with the user1 as author and another node
    // with user2 as author. For the second node add a comment from user1.
    $this->account = $this->drupalCreateUser();
    $this->account2 = $this->drupalCreateUser();
    $this->drupalLogin($this->account);

    $this->node_user_posted = $this->drupalCreateNode();
    $this->node_user_commented = $this->drupalCreateNode(array('uid' => $this->account2->uid));

    $comment = array(
      'uid' => $this->loggedInUser->uid,
      'nid' => $this->node_user_commented->nid,
      'cid' => '',
      'pid' => '',
    );
    entity_create('comment', $comment)->save();
  }

  function view_comment_user_uid() {
    $view = new View(array(), 'view');
    $view->name = 'test_comment_user_uid';
    $view->description = '';
    $view->tag = 'default';
    $view->base_table = 'node';
    $view->human_name = 'test_comment_user_uid';
    $view->core = 8;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['query']['options']['query_comment'] = FALSE;
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'node';
    /* Field: Content: nid */
    $handler->display->display_options['fields']['nid']['id'] = 'nid';
    $handler->display->display_options['fields']['nid']['table'] = 'node';
    $handler->display->display_options['fields']['nid']['field'] = 'nid';
    /* Contextual filter: Content: User posted or commented */
    $handler->display->display_options['arguments']['uid_touch']['id'] = 'uid_touch';
    $handler->display->display_options['arguments']['uid_touch']['table'] = 'node';
    $handler->display->display_options['arguments']['uid_touch']['field'] = 'uid_touch';
    $handler->display->display_options['arguments']['uid_touch']['default_argument_type'] = 'fixed';
    $handler->display->display_options['arguments']['uid_touch']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['uid_touch']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['uid_touch']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['uid_touch']['summary_options']['items_per_page'] = '25';

    return $view;
  }

}
