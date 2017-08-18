<?php

namespace Drupal\tracker\Tests\Views;

@trigger_error(__NAMESPACE__ . '\TrackerTestBase is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\tracker\Functional\Views\TrackerTestBase', E_USER_DEPRECATED);

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\comment\Entity\Comment;

/**
 * Base class for all tracker tests.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0.
 *   Use \Drupal\Tests\tracker\Functional\Views\TrackerTestBase instead.
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

  protected function setUp() {
    parent::setUp();

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
