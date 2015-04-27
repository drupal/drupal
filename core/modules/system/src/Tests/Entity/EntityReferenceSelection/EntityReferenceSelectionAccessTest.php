<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityReferenceSelection\EntityReferenceSelectionAccessTest.
 */

namespace Drupal\system\Tests\Entity\EntityReferenceSelection;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Tests for the base handlers provided by Entity Reference.
 *
 * @group entity_reference
 */
class EntityReferenceSelectionAccessTest extends WebTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment');

  protected function setUp() {
    parent::setUp();

    // Create an Article node type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Checks that a selection plugin returns the expected results.
   *
   * @param array $selection_options
   *   An array of options as required by entity reference selection plugins.
   * @param array $tests
   *   An array of tests to run.
   * @param string $handler_name
   *   The name of the entity type selection handler being tested.
   */
  protected function assertReferenceable(array $selection_options, $tests, $handler_name) {
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($selection_options);

    foreach ($tests as $test) {
      foreach ($test['arguments'] as $arguments) {
        $result = call_user_func_array(array($handler, 'getReferenceableEntities'), $arguments);
        $this->assertEqual($result, $test['result'], format_string('Valid result set returned by @handler.', array('@handler' => $handler_name)));

        $result = call_user_func_array(array($handler, 'countReferenceableEntities'), $arguments);
        if (!empty($test['result'])) {
          $bundle = key($test['result']);
          $count = count($test['result'][$bundle]);
        }
        else {
          $count = 0;
        }

        $this->assertEqual($result, $count, format_string('Valid count returned by @handler.', array('@handler' => $handler_name)));
      }
    }
  }

  /**
   * Test the node-specific overrides of the entity handler.
   */
  public function testNodeHandler() {
    $selection_options = array(
      'target_type' => 'node',
      'handler' => 'default',
      'handler_settings' => array(
        'target_bundles' => array(),
      ),
    );

    // Build a set of test data.
    // Titles contain HTML-special characters to test escaping.
    $node_values = array(
      'published1' => array(
        'type' => 'article',
        'status' => NODE_PUBLISHED,
        'title' => 'Node published1 (<&>)',
        'uid' => 1,
      ),
      'published2' => array(
        'type' => 'article',
        'status' => NODE_PUBLISHED,
        'title' => 'Node published2 (<&>)',
        'uid' => 1,
      ),
      'unpublished' => array(
        'type' => 'article',
        'status' => NODE_NOT_PUBLISHED,
        'title' => 'Node unpublished (<&>)',
        'uid' => 1,
      ),
    );

    $nodes = array();
    $node_labels = array();
    foreach ($node_values as $key => $values) {
      $node = entity_create('node', $values);
      $node->save();
      $nodes[$key] = $node;
      $node_labels[$key] = SafeMarkup::checkPlain($node->label());
    }

    // Test as a non-admin.
    $normal_user = $this->drupalCreateUser(array('access content'));
    \Drupal::currentUser()->setAccount($normal_user);
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array(NULL, 'CONTAINS'),
        ),
        'result' => array(
          'article' => array(
            $nodes['published1']->id() => $node_labels['published1'],
            $nodes['published2']->id() => $node_labels['published2'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('published1', 'CONTAINS'),
          array('Published1', 'CONTAINS'),
        ),
        'result' => array(
          'article' => array(
            $nodes['published1']->id() => $node_labels['published1'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('published2', 'CONTAINS'),
          array('Published2', 'CONTAINS'),
        ),
        'result' => array(
          'article' => array(
            $nodes['published2']->id() => $node_labels['published2'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('invalid node', 'CONTAINS'),
        ),
        'result' => array(),
      ),
      array(
        'arguments' => array(
          array('Node unpublished', 'CONTAINS'),
        ),
        'result' => array(),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Node handler');

    // Test as an admin.
    $admin_user = $this->drupalCreateUser(array('access content', 'bypass node access'));
    \Drupal::currentUser()->setAccount($admin_user);
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array(NULL, 'CONTAINS'),
        ),
        'result' => array(
          'article' => array(
            $nodes['published1']->id() => $node_labels['published1'],
            $nodes['published2']->id() => $node_labels['published2'],
            $nodes['unpublished']->id() => $node_labels['unpublished'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('Node unpublished', 'CONTAINS'),
        ),
        'result' => array(
          'article' => array(
            $nodes['unpublished']->id() => $node_labels['unpublished'],
          ),
        ),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Node handler (admin)');
  }

  /**
   * Test the user-specific overrides of the entity handler.
   */
  public function testUserHandler() {
    $selection_options = array(
      'target_type' => 'user',
      'handler' => 'default',
      'handler_settings' => array(
        'target_bundles' => array(),
        'include_anonymous' => TRUE,
      ),
    );

    // Build a set of test data.
    $user_values = array(
      'anonymous' => User::load(0),
      'admin' => User::load(1),
      'non_admin' => array(
        'name' => 'non_admin <&>',
        'mail' => 'non_admin@example.com',
        'roles' => array(),
        'pass' => user_password(),
        'status' => 1,
      ),
      'blocked' => array(
        'name' => 'blocked <&>',
        'mail' => 'blocked@example.com',
        'roles' => array(),
        'pass' => user_password(),
        'status' => 0,
      ),
    );

    $user_values['anonymous']->name = $this->config('user.settings')->get('anonymous');
    $users = array();

    $user_labels = array();
    foreach ($user_values as $key => $values) {
      if (is_array($values)) {
        $account = entity_create('user', $values);
        $account->save();
      }
      else {
        $account = $values;
      }
      $users[$key] = $account;
      $user_labels[$key] = SafeMarkup::checkPlain($account->getUsername());
    }

    // Test as a non-admin.
    \Drupal::currentUser()->setAccount($users['non_admin']);
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array(NULL, 'CONTAINS'),
        ),
        'result' => array(
          'user' => array(
            $users['admin']->id() => $user_labels['admin'],
            $users['non_admin']->id() => $user_labels['non_admin'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('non_admin', 'CONTAINS'),
          array('NON_ADMIN', 'CONTAINS'),
        ),
        'result' => array(
          'user' => array(
            $users['non_admin']->id() => $user_labels['non_admin'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('invalid user', 'CONTAINS'),
        ),
        'result' => array(),
      ),
      array(
        'arguments' => array(
          array('blocked', 'CONTAINS'),
        ),
        'result' => array(),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'User handler');

    \Drupal::currentUser()->setAccount($users['admin']);
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array(NULL, 'CONTAINS'),
        ),
        'result' => array(
          'user' => array(
            $users['anonymous']->id() => $user_labels['anonymous'],
            $users['admin']->id() => $user_labels['admin'],
            $users['non_admin']->id() => $user_labels['non_admin'],
            $users['blocked']->id() => $user_labels['blocked'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('blocked', 'CONTAINS'),
        ),
        'result' => array(
          'user' => array(
            $users['blocked']->id() => $user_labels['blocked'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('Anonymous', 'CONTAINS'),
          array('anonymous', 'CONTAINS'),
        ),
        'result' => array(
          'user' => array(
            $users['anonymous']->id() => $user_labels['anonymous'],
          ),
        ),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'User handler (admin)');

    // Test the 'include_anonymous' option.
    $selection_options['handler_settings']['include_anonymous'] = FALSE;
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array('Anonymous', 'CONTAINS'),
          array('anonymous', 'CONTAINS'),
        ),
        'result' => array(),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'User handler (does not include anonymous)');
  }

  /**
   * Test the comment-specific overrides of the entity handler.
   */
  public function testCommentHandler() {
    $selection_options = array(
      'target_type' => 'comment',
      'handler' => 'default',
      'handler_settings' => array(
        'target_bundles' => array(),
      ),
    );

    // Build a set of test data.
    $node_values = array(
      'published' => array(
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published',
        'uid' => 1,
      ),
      'unpublished' => array(
        'type' => 'article',
        'status' => 0,
        'title' => 'Node unpublished',
        'uid' => 1,
      ),
    );
    $nodes = array();
    foreach ($node_values as $key => $values) {
      $node = entity_create('node', $values);
      $node->save();
      $nodes[$key] = $node;
    }

    // Create comment field on article.
    $this->addDefaultCommentField('node', 'article');

    $comment_values = array(
      'published_published' => array(
        'entity_id' => $nodes['published']->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'uid' => 1,
        'cid' => NULL,
        'pid' => 0,
        'status' => CommentInterface::PUBLISHED,
        'subject' => 'Comment Published <&>',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ),
      'published_unpublished' => array(
        'entity_id' => $nodes['published']->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'uid' => 1,
        'cid' => NULL,
        'pid' => 0,
        'status' => CommentInterface::NOT_PUBLISHED,
        'subject' => 'Comment Unpublished <&>',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ),
      'unpublished_published' => array(
        'entity_id' => $nodes['unpublished']->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'uid' => 1,
        'cid' => NULL,
        'pid' => 0,
        'status' => CommentInterface::NOT_PUBLISHED,
        'subject' => 'Comment Published on Unpublished node <&>',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ),
    );

    $comments = array();
    $comment_labels = array();
    foreach ($comment_values as $key => $values) {
      $comment = entity_create('comment', $values);
      $comment->save();
      $comments[$key] = $comment;
      $comment_labels[$key] = SafeMarkup::checkPlain($comment->label());
    }

    // Test as a non-admin.
    $normal_user = $this->drupalCreateUser(array('access content', 'access comments'));
    \Drupal::currentUser()->setAccount($normal_user);
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array(NULL, 'CONTAINS'),
        ),
        'result' => array(
          'comment' => array(
            $comments['published_published']->cid->value => $comment_labels['published_published'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('Published', 'CONTAINS'),
        ),
        'result' => array(
          'comment' => array(
            $comments['published_published']->cid->value => $comment_labels['published_published'],
          ),
        ),
      ),
      array(
        'arguments' => array(
          array('invalid comment', 'CONTAINS'),
        ),
        'result' => array(),
      ),
      array(
        'arguments' => array(
          array('Comment Unpublished', 'CONTAINS'),
        ),
        'result' => array(),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Comment handler');

    // Test as a comment admin.
    $admin_user = $this->drupalCreateUser(array('access content', 'access comments', 'administer comments'));
    \Drupal::currentUser()->setAccount($admin_user);
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array(NULL, 'CONTAINS'),
        ),
        'result' => array(
          'comment' => array(
            $comments['published_published']->cid->value => $comment_labels['published_published'],
            $comments['published_unpublished']->cid->value => $comment_labels['published_unpublished'],
          ),
        ),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Comment handler (comment admin)');

    // Test as a node and comment admin.
    $admin_user = $this->drupalCreateUser(array('access content', 'access comments', 'administer comments', 'bypass node access'));
    \Drupal::currentUser()->setAccount($admin_user);
    $referenceable_tests = array(
      array(
        'arguments' => array(
          array(NULL, 'CONTAINS'),
        ),
        'result' => array(
          'comment' => array(
            $comments['published_published']->cid->value => $comment_labels['published_published'],
            $comments['published_unpublished']->cid->value => $comment_labels['published_unpublished'],
            $comments['unpublished_published']->cid->value => $comment_labels['unpublished_published'],
          ),
        ),
      ),
    );
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Comment handler (comment + node admin)');
  }

}
