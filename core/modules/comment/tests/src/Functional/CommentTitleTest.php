<?php

namespace Drupal\Tests\comment\Functional;

/**
 * Tests to ensure that appropriate and accessible markup is created for comment
 * titles.
 *
 * @group comment
 */
class CommentTitleTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests markup for comments with empty titles.
   */
  public function testCommentEmptyTitles() {
    // Create a node.
    $this->drupalLogin($this->webUser);
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()]);

    // Post comment #1 and verify that h3 is rendered.
    $subject_text = "Test subject";
    $comment_text = "Test comment";
    $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Tests that markup is generated for the comment title.
    $regex_h3 = '|<h3[^>]*>.*?</h3>|';
    $this->assertSession()->responseMatches($regex_h3);

    // Installs module that sets comment title to an empty string.
    \Drupal::service('module_installer')->install(['comment_empty_title_test']);

    // Set comments to have a subject with preview disabled.
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);

    // Create a new node.
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()]);

    // Post another comment and verify that h3 is not rendered.
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);

    // The entity fields for name and mail have no meaning if the user is not
    // Anonymous.
    $this->assertNull($comment->name->value);
    $this->assertNull($comment->mail->value);

    // Confirm that the comment was created.
    $regex = '/<article(.*?)id="comment-' . $comment->id() . '"(.*?)';
    $regex .= $comment->comment_body->value . '(.*?)';
    $regex .= '/s';
    // Verify that the comment is created successfully.
    $this->assertSession()->responseMatches($regex);
    // Tests that markup is not generated for the comment title.
    $this->assertSession()->responseNotMatches($regex_h3);
    $this->assertSession()->pageTextNotContains($subject_text);
  }

  /**
   * Tests markup for comments with populated titles.
   */
  public function testCommentPopulatedTitles() {
    // Set comments to have a subject with preview disabled.
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);

    // Create a node.
    $this->drupalLogin($this->webUser);
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()]);

    // Post comment #1 and verify that title is rendered in h3.
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();
    $comment1 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);

    // The entity fields for name and mail have no meaning if the user is not
    // Anonymous.
    $this->assertNull($comment1->name->value);
    $this->assertNull($comment1->mail->value);

    // Confirm that the comment was created.
    $this->assertTrue($this->commentExists($comment1), 'Comment #1. Comment found.');
    // Tests that markup is created for comment with heading.
    $this->assertSession()->responseMatches('|<h3[^>]*><a[^>]*>' . $subject_text . '</a></h3>|');
    // Tests that the comment's title link is the permalink of the comment.
    $comment_permalink = $this->cssSelect('.permalink');
    $comment_permalink = $comment_permalink[0]->getAttribute('href');
    // Tests that the comment's title link contains the url fragment.
    $this->assertStringContainsString('#comment-' . $comment1->id(), $comment_permalink, "The comment's title link contains the url fragment.");
    $this->assertEquals($comment1->permalink()->toString(), $comment_permalink, "The comment's title has the correct link.");
  }

}
