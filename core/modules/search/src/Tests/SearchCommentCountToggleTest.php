<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchCommentCountToggleTest.
 */

namespace Drupal\search\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;

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
class SearchCommentCountToggleTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment');

  protected $searching_user;
  protected $searchable_nodes;

  protected function setUp() {
    parent::setUp();

    // Create searching user.
    $this->searching_user = $this->drupalCreateUser(array('search content', 'access content', 'access comments', 'post comments', 'skip comment approval'));

    // Login with sufficient privileges.
    $this->drupalLogin($this->searching_user);

    // Add a comment field.
    $this->container->get('comment.manager')->addDefaultField('node', 'article');
    // Create initial nodes.
    $node_params = array('type' => 'article', 'body' => array(array('value' => 'SearchCommentToggleTestCase')));

    $this->searchable_nodes['1 comment'] = $this->drupalCreateNode($node_params);
    $this->searchable_nodes['0 comments'] = $this->drupalCreateNode($node_params);

    // Create a comment array
    $edit_comment = array();
    $edit_comment['subject[0][value]'] = $this->randomMachineName();
    $edit_comment['comment_body[0][value]'] = $this->randomMachineName();

    // Post comment to the test node with comment
    $this->drupalPostForm('comment/reply/node/' . $this->searchable_nodes['1 comment']->id() . '/comment', $edit_comment, t('Save'));

    // First update the index. This does the initial processing.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Then, run the shutdown function. Testing is a unique case where indexing
    // and searching has to happen in the same request, so running the shutdown
    // function manually is needed to finish the indexing process.
    search_update_totals();
  }

  /**
   * Verify that comment count display toggles properly on comment status of node
   */
  function testSearchCommentCountToggle() {
    // Search for the nodes by string in the node body.
    $edit = array(
      'keys' => "'SearchCommentToggleTestCase'",
    );
    $this->drupalGet('search/node');

    // Test comment count display for nodes with comment status set to Open
    $this->drupalPostForm(NULL, $edit, t('Search'));
    $this->assertText(t('0 comments'), 'Empty comment count displays for nodes with comment status set to Open');
    $this->assertText(t('1 comment'), 'Non-empty comment count displays for nodes with comment status set to Open');

    // Test comment count display for nodes with comment status set to Closed
    $this->searchable_nodes['0 comments']->set('comment', CommentItemInterface::CLOSED);
    $this->searchable_nodes['0 comments']->save();
    $this->searchable_nodes['1 comment']->set('comment', CommentItemInterface::CLOSED);
    $this->searchable_nodes['1 comment']->save();

    $this->drupalPostForm(NULL, $edit, t('Search'));
    $this->assertNoText(t('0 comments'), 'Empty comment count does not display for nodes with comment status set to Closed');
    $this->assertText(t('1 comment'), 'Non-empty comment count displays for nodes with comment status set to Closed');

    // Test comment count display for nodes with comment status set to Hidden
    $this->searchable_nodes['0 comments']->set('comment', CommentItemInterface::HIDDEN);
    $this->searchable_nodes['0 comments']->save();
    $this->searchable_nodes['1 comment']->set('comment', CommentItemInterface::HIDDEN);
    $this->searchable_nodes['1 comment']->save();

    $this->drupalPostForm(NULL, $edit, t('Search'));
    $this->assertNoText(t('0 comments'), 'Empty comment count does not display for nodes with comment status set to Hidden');
    $this->assertNoText(t('1 comment'), 'Non-empty comment count does not display for nodes with comment status set to Hidden');
  }
}
