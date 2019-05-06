<?php

namespace Drupal\Tests\tracker\Functional\Views;

@trigger_error('The ' . __NAMESPACE__ . '\TrackerTestBase is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Instead, use Kernel tests to test tracker module views plugins integration. https://www.drupal.org/node/3046938', E_USER_DEPRECATED);

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\comment\Entity\Comment;

/**
 * Base class for all tracker tests.
 *
 * @deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Instead,
 *   use Kernel tests to test tracker module views plugins integration.
 *
 * @see https://www.drupal.org/node/3046938
 */
abstract class TrackerTestBase extends ViewTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['comment', 'tracker', 'tracker_test_views'];

  /**
   * The node used for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The comment used for testing.
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $comment;

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['tracker_test_views']);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    // Add a comment field.
    $this->addDefaultCommentField('node', 'page');

    $permissions = ['access comments', 'create page content', 'post comments', 'skip comment approval'];
    $account = $this->drupalCreateUser($permissions);

    $this->drupalLogin($account);

    $this->node = $this->drupalCreateNode([
      'title' => $this->randomMachineName(8),
      'uid' => $account->id(),
      'status' => 1,
    ]);

    $this->comment = Comment::create([
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => $this->randomMachineName(),
      'comment_body[' . LanguageInterface::LANGCODE_NOT_SPECIFIED . '][0][value]' => $this->randomMachineName(20),
    ]);

  }

}
