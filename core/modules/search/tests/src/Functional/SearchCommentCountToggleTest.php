<?php

namespace Drupal\Tests\search\Functional;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that comment count display toggles properly on comment status of node.
 *
 * Issue 537278
 *
 * - Nodes with comment status set to Open should always how comment counts
 * - Nodes with comment status set to Closed should show comment counts
 *     only when there are comments
 * - Nodes with comment status set to Hidden should never show comment counts
 *
 * @group search
 */
class SearchCommentCountToggleTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'comment', 'search', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to search and post comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchingUser;

  /**
   * Array of nodes available to search.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $searchableNodes;

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create searching user.
    $this->searchingUser = $this->drupalCreateUser([
      'search content',
      'access content',
      'access comments',
      'post comments',
      'skip comment approval',
    ]);

    // Log in with sufficient privileges.
    $this->drupalLogin($this->searchingUser);

    // Add a comment field.
    $this->addDefaultCommentField('node', 'article');
    // Create initial nodes.
    $node_params = ['type' => 'article', 'body' => [['value' => 'SearchCommentToggleTestCase']]];

    $this->searchableNodes['1 comment'] = $this->drupalCreateNode($node_params);
    $this->searchableNodes['0 comments'] = $this->drupalCreateNode($node_params);

    // Create a comment array
    $edit_comment = [];
    $edit_comment['subject[0][value]'] = $this->randomMachineName();
    $edit_comment['comment_body[0][value]'] = $this->randomMachineName();

    // Post comment to the test node with comment
    $this->drupalPostForm('comment/reply/node/' . $this->searchableNodes['1 comment']->id() . '/comment', $edit_comment, 'Save');

    // First update the index. This does the initial processing.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
  }

  /**
   * Verify that comment count display toggles properly on comment status of node.
   */
  public function testSearchCommentCountToggle() {
    // Search for the nodes by string in the node body.
    $edit = [
      'keys' => "'SearchCommentToggleTestCase'",
    ];
    $this->drupalGet('search/node');

    // Test comment count display for nodes with comment status set to Open
    $this->submitForm($edit, 'Search');
    $this->assertText('0 comments', 'Empty comment count displays for nodes with comment status set to Open');
    $this->assertText('1 comment', 'Non-empty comment count displays for nodes with comment status set to Open');

    // Test comment count display for nodes with comment status set to Closed
    $this->searchableNodes['0 comments']->set('comment', CommentItemInterface::CLOSED);
    $this->searchableNodes['0 comments']->save();
    $this->searchableNodes['1 comment']->set('comment', CommentItemInterface::CLOSED);
    $this->searchableNodes['1 comment']->save();

    $this->submitForm($edit, 'Search');
    $this->assertNoText('0 comments', 'Empty comment count does not display for nodes with comment status set to Closed');
    $this->assertText('1 comment', 'Non-empty comment count displays for nodes with comment status set to Closed');

    // Test comment count display for nodes with comment status set to Hidden
    $this->searchableNodes['0 comments']->set('comment', CommentItemInterface::HIDDEN);
    $this->searchableNodes['0 comments']->save();
    $this->searchableNodes['1 comment']->set('comment', CommentItemInterface::HIDDEN);
    $this->searchableNodes['1 comment']->save();

    $this->submitForm($edit, 'Search');
    $this->assertNoText('0 comments', 'Empty comment count does not display for nodes with comment status set to Hidden');
    $this->assertNoText('1 comment', 'Non-empty comment count does not display for nodes with comment status set to Hidden');
  }

}
