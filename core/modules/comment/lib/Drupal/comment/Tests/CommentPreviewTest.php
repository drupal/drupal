<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentPreviewTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Core\Datetime\DrupalDateTime;

use Drupal\Core\Language\Language;

/**
 * Tests previewing comments.
 */
class CommentPreviewTest extends CommentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image');

  public static function getInfo() {
    return array(
      'name' => 'Comment preview',
      'description' => 'Test comment preview.',
      'group' => 'Comment',
    );
  }

  function setUp() {
    parent::setUp();

    // Create user picture field.
    module_load_install('user');
    user_install_picture_field();

    // Add the basic_html filter format from the standard install profile.
    $filter_format_storage_controller = $this->container->get('plugin.manager.entity')->getStorageController('filter_format');
    $filter_format = $filter_format_storage_controller->create(array(
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'status' => '1',
      'roles' => array('authenticated'),
    ), 'filter_format');

    $filter_format->setFilterConfig('filter_html', array(
      'module' => 'filter',
      'status' => '1',
      'settings' => array(
        'allowed_html' => '<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <h4> <h5> <h6> <p> <span> <img>',
      ),
    ));
    $filter_format->save();
  }

  /**
   * Tests comment preview.
   */
  function testCommentPreview() {
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // As admin user, configure comment settings.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_OPTIONAL);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalLogout();

    // Login as web user and add a signature and a user picture.
    $this->drupalLogin($this->web_user);
    \Drupal::config('user.settings')->set('signatures', 1)->save();
    $test_signature = $this->randomName();
    $edit['signature[value]'] = '<a href="http://example.com/">' . $test_signature. '</a>';
    $image = current($this->drupalGetTestFiles('image'));
    $edit['files[user_picture_und_0]'] = drupal_realpath($image->uri);
    $this->drupalPost('user/' . $this->web_user->id() . '/edit', $edit, t('Save'));

    // As the web user, fill in the comment form and preview the comment.
    $edit = array();
    $edit['subject'] = $this->randomName(8);
    $edit['comment_body[' . $langcode . '][0][value]'] = $this->randomName(16);
    $this->drupalPost('node/' . $this->node->id(), $edit, t('Preview'));

    // Check that the preview is displaying the title and body.
    $this->assertTitle(t('Preview comment | Drupal'), 'Page title is "Preview comment".');
    $this->assertText($edit['subject'], 'Subject displayed.');
    $this->assertText($edit['comment_body[' . $langcode . '][0][value]'], 'Comment displayed.');

    // Check that the title and body fields are displayed with the correct values.
    $this->assertFieldByName('subject', $edit['subject'], 'Subject field displayed.');
    $this->assertFieldByName('comment_body[' . $langcode . '][0][value]', $edit['comment_body[' . $langcode . '][0][value]'], 'Comment field displayed.');

    // Check that the signature is displaying with the correct text format.
    $this->assertLink($test_signature);

    // Check that the user picture is displayed.
    $this->assertFieldByXPath("//article[contains(@class, 'preview')]//div[contains(@class, 'user-picture')]//img", NULL, 'User picture displayed.');
  }

  /**
   * Tests comment edit, preview, and save.
   */
  function testCommentEditPreviewSave() {
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $web_user = $this->drupalCreateUser(array('access comments', 'post comments', 'skip comment approval'));
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_OPTIONAL);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, 'Comment paging changed.');

    $edit = array();
    $date = new DrupalDateTime('2008-03-02 17:23');
    $edit['subject'] = $this->randomName(8);
    $edit['comment_body[' . $langcode . '][0][value]'] = $this->randomName(16);
    $edit['name'] = $web_user->getUsername();
    $edit['date[date]'] = $date->format('Y-m-d');
    $edit['date[time]'] = $date->format('H:i:s');
    $raw_date = $date->getTimestamp();
    $expected_text_date = format_date($raw_date);
    $expected_form_date = $date->format('Y-m-d');
    $expected_form_time = $date->format('H:i:s');
    $comment = $this->postComment($this->node, $edit['subject'], $edit['comment_body[' . $langcode . '][0][value]'], TRUE);
    $this->drupalPost('comment/' . $comment->id() . '/edit', $edit, t('Preview'));

    // Check that the preview is displaying the subject, comment, author and date correctly.
    $this->assertTitle(t('Preview comment | Drupal'), 'Page title is "Preview comment".');
    $this->assertText($edit['subject'], 'Subject displayed.');
    $this->assertText($edit['comment_body[' . $langcode . '][0][value]'], 'Comment displayed.');
    $this->assertText($edit['name'], 'Author displayed.');
    $this->assertText($expected_text_date, 'Date displayed.');

    // Check that the subject, comment, author and date fields are displayed with the correct values.
    $this->assertFieldByName('subject', $edit['subject'], 'Subject field displayed.');
    $this->assertFieldByName('comment_body[' . $langcode . '][0][value]', $edit['comment_body[' . $langcode . '][0][value]'], 'Comment field displayed.');
    $this->assertFieldByName('name', $edit['name'], 'Author field displayed.');
    $this->assertFieldByName('date[date]', $edit['date[date]'], 'Date field displayed.');
    $this->assertFieldByName('date[time]', $edit['date[time]'], 'Time field displayed.');

    // Check that saving a comment produces a success message.
    $this->drupalPost('comment/' . $comment->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('Your comment has been posted.'), 'Comment posted.');

    // Check that the comment fields are correct after loading the saved comment.
    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $this->assertFieldByName('subject', $edit['subject'], 'Subject field displayed.');
    $this->assertFieldByName('comment_body[' . $langcode . '][0][value]', $edit['comment_body[' . $langcode . '][0][value]'], 'Comment field displayed.');
    $this->assertFieldByName('name', $edit['name'], 'Author field displayed.');
    $this->assertFieldByName('date[date]', $expected_form_date, 'Date field displayed.');
    $this->assertFieldByName('date[time]', $expected_form_time, 'Time field displayed.');

    // Submit the form using the displayed values.
    $displayed = array();
    $displayed['subject'] = (string) current($this->xpath("//input[@id='edit-subject']/@value"));
    $displayed['comment_body[' . $langcode . '][0][value]'] = (string) current($this->xpath("//textarea[@id='edit-comment-body-" . $langcode . "-0-value']"));
    $displayed['name'] = (string) current($this->xpath("//input[@id='edit-name']/@value"));
    $displayed['date[date]'] = (string) current($this->xpath("//input[@id='edit-date-date']/@value"));
    $displayed['date[time]'] = (string) current($this->xpath("//input[@id='edit-date-time']/@value"));
    $this->drupalPost('comment/' . $comment->id() . '/edit', $displayed, t('Save'));

    // Check that the saved comment is still correct.
    $comment_loaded = comment_load($comment->id(), TRUE);
    $this->assertEqual($comment_loaded->subject->value, $edit['subject'], 'Subject loaded.');
    $this->assertEqual($comment_loaded->comment_body->value, $edit['comment_body[' . $langcode . '][0][value]'], 'Comment body loaded.');
    $this->assertEqual($comment_loaded->name->value, $edit['name'], 'Name loaded.');
    $this->assertEqual($comment_loaded->created->value, $raw_date, 'Date loaded.');

  }

}
