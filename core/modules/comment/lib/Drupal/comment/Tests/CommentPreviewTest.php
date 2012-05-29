<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentPreviewTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests previewing comments.
 */
class CommentPreviewTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment preview',
      'description' => 'Test comment preview.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests comment preview.
   */
  function testCommentPreview() {
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // As admin user, configure comment settings.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_OPTIONAL);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, t('Comment paging changed.'));
    $this->drupalLogout();

    // Login as web user and add a signature and a user picture.
    $this->drupalLogin($this->web_user);
    variable_set('user_signatures', 1);
    variable_set('user_pictures', 1);
    $test_signature = $this->randomName();
    $edit['signature[value]'] = '<a href="http://example.com/">' . $test_signature. '</a>';
    $edit['signature[format]'] = 'filtered_html';
    $image = current($this->drupalGetTestFiles('image'));
    $edit['files[picture_upload]'] = drupal_realpath($image->uri);
    $this->drupalPost('user/' . $this->web_user->uid . '/edit', $edit, t('Save'));

    // As the web user, fill in the comment form and preview the comment.
    $edit = array();
    $edit['subject'] = $this->randomName(8);
    $edit['comment_body[' . $langcode . '][0][value]'] = $this->randomName(16);
    $this->drupalPost('node/' . $this->node->nid, $edit, t('Preview'));

    // Check that the preview is displaying the title and body.
    $this->assertTitle(t('Preview comment | Drupal'), t('Page title is "Preview comment".'));
    $this->assertText($edit['subject'], t('Subject displayed.'));
    $this->assertText($edit['comment_body[' . $langcode . '][0][value]'], t('Comment displayed.'));

    // Check that the title and body fields are displayed with the correct values.
    $this->assertFieldByName('subject', $edit['subject'], t('Subject field displayed.'));
    $this->assertFieldByName('comment_body[' . $langcode . '][0][value]', $edit['comment_body[' . $langcode . '][0][value]'], t('Comment field displayed.'));

    // Check that the signature is displaying with the correct text format.
    $this->assertLink($test_signature);

    // Check that the user picture is displayed.
    $this->assertFieldByXPath("//div[contains(@class, 'preview')]//div[contains(@class, 'user-picture')]//img", NULL, 'User picture displayed.');
  }

  /**
   * Tests comment edit, preview, and save.
   */
  function testCommentEditPreviewSave() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $web_user = $this->drupalCreateUser(array('access comments', 'post comments', 'skip comment approval'));
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_OPTIONAL);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, t('Comment paging changed.'));

    $edit = array();
    $edit['subject'] = $this->randomName(8);
    $edit['comment_body[' . $langcode . '][0][value]'] = $this->randomName(16);
    $edit['name'] = $web_user->name;
    $edit['date'] = '2008-03-02 17:23 +0300';
    $raw_date = strtotime($edit['date']);
    $expected_text_date = format_date($raw_date);
    $expected_form_date = format_date($raw_date, 'custom', 'Y-m-d H:i O');
    $comment = $this->postComment($this->node, $edit['subject'], $edit['comment_body[' . $langcode . '][0][value]'], TRUE);
    $this->drupalPost('comment/' . $comment->id . '/edit', $edit, t('Preview'));

    // Check that the preview is displaying the subject, comment, author and date correctly.
    $this->assertTitle(t('Preview comment | Drupal'), t('Page title is "Preview comment".'));
    $this->assertText($edit['subject'], t('Subject displayed.'));
    $this->assertText($edit['comment_body[' . $langcode . '][0][value]'], t('Comment displayed.'));
    $this->assertText($edit['name'], t('Author displayed.'));
    $this->assertText($expected_text_date, t('Date displayed.'));

    // Check that the subject, comment, author and date fields are displayed with the correct values.
    $this->assertFieldByName('subject', $edit['subject'], t('Subject field displayed.'));
    $this->assertFieldByName('comment_body[' . $langcode . '][0][value]', $edit['comment_body[' . $langcode . '][0][value]'], t('Comment field displayed.'));
    $this->assertFieldByName('name', $edit['name'], t('Author field displayed.'));
    $this->assertFieldByName('date', $edit['date'], t('Date field displayed.'));

    // Check that saving a comment produces a success message.
    $this->drupalPost('comment/' . $comment->id . '/edit', $edit, t('Save'));
    $this->assertText(t('Your comment has been posted.'), t('Comment posted.'));

    // Check that the comment fields are correct after loading the saved comment.
    $this->drupalGet('comment/' . $comment->id . '/edit');
    $this->assertFieldByName('subject', $edit['subject'], t('Subject field displayed.'));
    $this->assertFieldByName('comment_body[' . $langcode . '][0][value]', $edit['comment_body[' . $langcode . '][0][value]'], t('Comment field displayed.'));
    $this->assertFieldByName('name', $edit['name'], t('Author field displayed.'));
    $this->assertFieldByName('date', $expected_form_date, t('Date field displayed.'));

    // Submit the form using the displayed values.
    $displayed = array();
    $displayed['subject'] = (string) current($this->xpath("//input[@id='edit-subject']/@value"));
    $displayed['comment_body[' . $langcode . '][0][value]'] = (string) current($this->xpath("//textarea[@id='edit-comment-body-" . $langcode . "-0-value']"));
    $displayed['name'] = (string) current($this->xpath("//input[@id='edit-name']/@value"));
    $displayed['date'] = (string) current($this->xpath("//input[@id='edit-date']/@value"));
    $this->drupalPost('comment/' . $comment->id . '/edit', $displayed, t('Save'));

    // Check that the saved comment is still correct.
    $comment_loaded = comment_load($comment->id);
    $this->assertEqual($comment_loaded->subject, $edit['subject'], t('Subject loaded.'));
    $this->assertEqual($comment_loaded->comment_body[$langcode][0]['value'], $edit['comment_body[' . $langcode . '][0][value]'], t('Comment body loaded.'));
    $this->assertEqual($comment_loaded->name, $edit['name'], t('Name loaded.'));
    $this->assertEqual($comment_loaded->created, $raw_date, t('Date loaded.'));

  }

}
