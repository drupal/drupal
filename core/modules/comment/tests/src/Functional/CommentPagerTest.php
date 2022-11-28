<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentManagerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\node\Entity\Node;

/**
 * Tests paging of comments and their settings.
 *
 * @group comment
 */
class CommentPagerTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Confirms comment paging works correctly with flat and threaded comments.
   */
  public function testCommentPaging() {
    $this->drupalLogin($this->adminUser);

    // Set comment variables.
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_DISABLED);

    // Create a node and three comments.
    $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $comments = [];
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT, 'Comment paging changed.');

    // Set comments to one per page so that we are able to test paging without
    // needing to insert large numbers of comments.
    $this->setCommentsPerPage(1);

    // Check the first page of the node, and confirm the correct comments are
    // shown.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('next');
    $this->assertTrue($this->commentExists($comments[0]), 'Comment 1 appears on page 1.');
    $this->assertFalse($this->commentExists($comments[1]), 'Comment 2 does not appear on page 1.');
    $this->assertFalse($this->commentExists($comments[2]), 'Comment 3 does not appear on page 1.');

    // Check the second page.
    $this->drupalGet('node/' . $node->id(), ['query' => ['page' => 1]]);
    $this->assertTrue($this->commentExists($comments[1]), 'Comment 2 appears on page 2.');
    $this->assertFalse($this->commentExists($comments[0]), 'Comment 1 does not appear on page 2.');
    $this->assertFalse($this->commentExists($comments[2]), 'Comment 3 does not appear on page 2.');

    // Check the third page.
    $this->drupalGet('node/' . $node->id(), ['query' => ['page' => 2]]);
    $this->assertTrue($this->commentExists($comments[2]), 'Comment 3 appears on page 3.');
    $this->assertFalse($this->commentExists($comments[0]), 'Comment 1 does not appear on page 3.');
    $this->assertFalse($this->commentExists($comments[1]), 'Comment 2 does not appear on page 3.');

    // Post a reply to the oldest comment and test again.
    $oldest_comment = reset($comments);
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $oldest_comment->id());
    $reply = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    $this->setCommentsPerPage(2);
    // We are still in flat view - the replies should not be on the first page,
    // even though they are replies to the oldest comment.
    $this->drupalGet('node/' . $node->id(), ['query' => ['page' => 0]]);
    $this->assertFalse($this->commentExists($reply, TRUE), 'In flat mode, reply does not appear on page 1.');

    // If we switch to threaded mode, the replies on the oldest comment
    // should be bumped to the first page and comment 6 should be bumped
    // to the second page.
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Switched to threaded mode.');
    $this->drupalGet('node/' . $node->id(), ['query' => ['page' => 0]]);
    $this->assertTrue($this->commentExists($reply, TRUE), 'In threaded mode, reply appears on page 1.');
    $this->assertFalse($this->commentExists($comments[1]), 'In threaded mode, comment 2 has been bumped off of page 1.');

    // If (# replies > # comments per page) in threaded expanded view,
    // the overage should be bumped.
    $reply2 = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $this->drupalGet('node/' . $node->id(), ['query' => ['page' => 0]]);
    $this->assertFalse($this->commentExists($reply2, TRUE), 'In threaded mode where # replies > # comments per page, the newest reply does not appear on page 1.');

    // Test that the page build process does not somehow generate errors when
    // # comments per page is set to 0.
    $this->setCommentsPerPage(0);
    $this->drupalGet('node/' . $node->id(), ['query' => ['page' => 0]]);
    $this->assertFalse($this->commentExists($reply2, TRUE), 'Threaded mode works correctly when comments per page is 0.');

    $this->drupalLogout();
  }

  /**
   * Confirms comment paging works correctly with flat and threaded comments.
   */
  public function testCommentPermalink() {
    $this->drupalLogin($this->adminUser);

    // Set comment variables.
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_DISABLED);

    // Create a node and three comments.
    $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $comments = [];
    $comments[] = $this->postComment($node, 'comment 1: ' . $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, 'comment 2: ' . $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, 'comment 3: ' . $this->randomMachineName(), $this->randomMachineName(), TRUE);

    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT, 'Comment paging changed.');

    // Set comments to one per page so that we are able to test paging without
    // needing to insert large numbers of comments.
    $this->setCommentsPerPage(1);

    // Navigate to each comment permalink as anonymous and assert it appears on
    // the page.
    foreach ($comments as $index => $comment) {
      $this->drupalGet($comment->toUrl());
      $this->assertTrue($this->commentExists($comment), sprintf('Comment %d appears on page %d.', $index + 1, $index + 1));
    }
  }

  /**
   * Tests comment ordering and threading.
   */
  public function testCommentOrderingThreading() {
    $this->drupalLogin($this->adminUser);

    // Set comment variables.
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_DISABLED);

    // Display all the comments on the same page.
    $this->setCommentsPerPage(1000);

    // Create a node and three comments.
    $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $comments = [];
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the second comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comments[1]->id());
    $comments[] = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the first comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comments[0]->id());
    $comments[] = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the last comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comments[2]->id());
    $comments[] = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the second comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comments[3]->id());
    $comments[] = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // At this point, the comment tree is:
    // - 0
    //   - 4
    // - 1
    //   - 3
    //     - 6
    // - 2
    //   - 5

    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT, 'Comment paging changed.');

    $expected_order = [
      0,
      1,
      2,
      3,
      4,
      5,
      6,
    ];
    $this->drupalGet('node/' . $node->id());
    $this->assertCommentOrder($comments, $expected_order);

    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Switched to threaded mode.');

    $expected_order = [
      0,
      4,
      1,
      3,
      6,
      2,
      5,
    ];
    $this->drupalGet('node/' . $node->id());
    $this->assertCommentOrder($comments, $expected_order);
  }

  /**
   * Asserts that the comments are displayed in the correct order.
   *
   * @param \Drupal\comment\CommentInterface[] $comments
   *   An array of comments, must be of the type CommentInterface.
   * @param array $expected_order
   *   An array of keys from $comments describing the expected order.
   *
   * @internal
   */
  public function assertCommentOrder(array $comments, array $expected_order): void {
    $expected_cids = [];

    // First, rekey the expected order by cid.
    foreach ($expected_order as $key) {
      $expected_cids[] = $comments[$key]->id();
    }

    $comment_anchors = $this->xpath('//article[starts-with(@id,"comment-")]');
    $result_order = [];
    foreach ($comment_anchors as $anchor) {
      $result_order[] = substr($anchor->getAttribute('id'), 8);
    }
    $this->assertEquals($expected_cids, $result_order, new FormattableMarkup('Comment order: expected @expected, returned @returned.', ['@expected' => implode(',', $expected_cids), '@returned' => implode(',', $result_order)]));
  }

  /**
   * Tests calculation of first page with new comment.
   */
  public function testCommentNewPageIndicator() {
    $this->drupalLogin($this->adminUser);

    // Set comment variables.
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_DISABLED);

    // Set comments to one per page so that we are able to test paging without
    // needing to insert large numbers of comments.
    $this->setCommentsPerPage(1);

    // Create a node and three comments.
    $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $comments = [];
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the second comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comments[1]->id());
    $comments[] = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the first comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comments[0]->id());
    $comments[] = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the last comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comments[2]->id());
    $comments[] = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // At this point, the comment tree is:
    // - 0
    //   - 4
    // - 1
    //   - 3
    // - 2
    //   - 5

    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT, 'Comment paging changed.');

    $expected_pages = [
      // Page of comment 5
      1 => 5,
      // Page of comment 4
      2 => 4,
      // Page of comment 3
      3 => 3,
      // Page of comment 2
      4 => 2,
      // Page of comment 1
      5 => 1,
      // Page of comment 0
      6 => 0,
    ];

    $node = Node::load($node->id());
    foreach ($expected_pages as $new_replies => $expected_page) {
      $returned_page = \Drupal::entityTypeManager()->getStorage('comment')
        ->getNewCommentPageNumber($node->get('comment')->comment_count, $new_replies, $node, 'comment');
      $this->assertSame($expected_page, $returned_page, new FormattableMarkup('Flat mode, @new replies: expected page @expected, returned page @returned.', ['@new' => $new_replies, '@expected' => $expected_page, '@returned' => $returned_page]));
    }

    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Switched to threaded mode.');

    $expected_pages = [
      // Page of comment 5
      1 => 5,
      // Page of comment 4
      2 => 1,
      // Page of comment 4
      3 => 1,
      // Page of comment 4
      4 => 1,
      // Page of comment 4
      5 => 1,
      // Page of comment 0
      6 => 0,
    ];

    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    $node = Node::load($node->id());
    foreach ($expected_pages as $new_replies => $expected_page) {
      $returned_page = \Drupal::entityTypeManager()->getStorage('comment')
        ->getNewCommentPageNumber($node->get('comment')->comment_count, $new_replies, $node, 'comment');
      $this->assertEquals($expected_page, $returned_page, new FormattableMarkup('Threaded mode, @new replies: expected page @expected, returned page @returned.', ['@new' => $new_replies, '@expected' => $expected_page, '@returned' => $returned_page]));
    }
  }

  /**
   * Confirms comment paging works correctly with two pagers.
   */
  public function testTwoPagers() {
    // Add another field to article content-type.
    $this->addDefaultCommentField('node', 'article', 'comment_2');
    // Set default to display comment list with unique pager id.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article')
      ->setComponent('comment_2', [
        'label' => 'hidden',
        'type' => 'comment_default',
        'weight' => 30,
        'settings' => [
          'pager_id' => 1,
          'view_mode' => 'default',
        ],
      ])
      ->save();

    // Make sure pager appears in formatter summary and settings form.
    $account = $this->drupalCreateUser(['administer node display']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/types/manage/article/display');
    // No summary for standard pager.
    $this->assertSession()->pageTextNotContains('Pager ID: 0');
    $this->assertSession()->pageTextContains('Pager ID: 1');
    $this->submitForm([], 'comment_settings_edit');
    // Change default pager to 2.
    $this->submitForm(['fields[comment][settings_edit_form][settings][pager_id]' => 2], 'Save');
    $this->assertSession()->pageTextContains('Pager ID: 2');
    // Revert the changes.
    $this->submitForm([], 'comment_settings_edit');
    $this->submitForm(['fields[comment][settings_edit_form][settings][pager_id]' => 0], 'Save');
    // No summary for standard pager.
    $this->assertSession()->pageTextNotContains('Pager ID: 0');

    $this->drupalLogin($this->adminUser);

    // Add a new node with both comment fields open.
    $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()]);
    // Set comment options.
    $comments = [];
    foreach (['comment', 'comment_2'] as $field_name) {
      $this->setCommentForm(TRUE, $field_name);
      $this->setCommentPreview(DRUPAL_OPTIONAL, $field_name);
      $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT, 'Comment paging changed.', $field_name);

      // Set comments to one per page so that we are able to test paging without
      // needing to insert large numbers of comments.
      $this->setCommentsPerPage(1, $field_name);
      for ($i = 1; $i <= 4; $i++) {
        $comment = "Comment $i on field $field_name";
        $comments[] = $this->postComment($node, $comment, $comment, TRUE, $field_name);
      }
    }

    // Check the first page of the node, and confirm the correct comments are
    // shown.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('next');
    $this->assertSession()->pageTextContains('Comment 1 on field comment');
    $this->assertSession()->pageTextContains('Comment 1 on field comment_2');
    // Navigate to next page of field 1.
    $this->clickLinkWithXPath('//h3/a[normalize-space(text())=:label]/ancestor::section[1]//a[@rel="next"]', [':label' => 'Comment 1 on field comment']);
    // Check only one pager updated.
    $this->assertSession()->pageTextContains('Comment 2 on field comment');
    $this->assertSession()->pageTextContains('Comment 1 on field comment_2');
    // Return to page 1.
    $this->drupalGet('node/' . $node->id());
    // Navigate to next page of field 2.
    $this->clickLinkWithXPath('//h3/a[normalize-space(text())=:label]/ancestor::section[1]//a[@rel="next"]', [':label' => 'Comment 1 on field comment_2']);
    // Check only one pager updated.
    $this->assertSession()->pageTextContains('Comment 1 on field comment');
    $this->assertSession()->pageTextContains('Comment 2 on field comment_2');
    // Navigate to next page of field 1.
    $this->clickLinkWithXPath('//h3/a[normalize-space(text())=:label]/ancestor::section[1]//a[@rel="next"]', [':label' => 'Comment 1 on field comment']);
    // Check only one pager updated.
    $this->assertSession()->pageTextContains('Comment 2 on field comment');
    $this->assertSession()->pageTextContains('Comment 2 on field comment_2');
  }

  /**
   * Follows a link found at a give xpath query.
   *
   * Will click the first link found with the given xpath query by default,
   * or a later one if an index is given.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string $xpath
   *   Xpath query that targets an anchor tag, or set of anchor tags.
   * @param array $arguments
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   * @param int $index
   *   Link position counting from zero.
   *
   * @return string|false
   *   Page contents on success, or FALSE on failure.
   *
   * @see \Drupal\Tests\UiHelperTrait::clickLink()
   */
  protected function clickLinkWithXPath($xpath, $arguments = [], $index = 0) {
    $url_before = $this->getUrl();
    $urls = $this->xpath($xpath, $arguments);
    if (isset($urls[$index])) {
      $url_target = $this->getAbsoluteUrl($urls[$index]->getAttribute('href'));
      return $this->drupalGet($url_target);
    }
    $this->fail(new FormattableMarkup('Link %label does not exist on @url_before', ['%label' => $xpath, '@url_before' => $url_before]));
    return FALSE;
  }

}
