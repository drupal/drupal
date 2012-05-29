<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserSignatureTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test case for user signatures.
 */
class UserSignatureTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User signatures',
      'description' => 'Test user signatures.',
      'group' => 'User',
    );
  }

  function setUp() {
    parent::setUp('comment');

    // Enable user signatures.
    variable_set('user_signatures', 1);

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Prefetch and create text formats.
    $this->plain_text_format = filter_format_load('plain_text');

    $filtered_html_format = array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    );
    $this->filtered_html_format = (object) $filtered_html_format;
    filter_format_save($this->filtered_html_format);

    $full_html_format = array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    );
    $this->full_html_format = (object) $full_html_format;
    filter_format_save($this->full_html_format);

    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array(filter_permission_name($this->filtered_html_format)));
    $this->checkPermissions(array(), TRUE);

    // Create regular and administrative users.
    $this->web_user = $this->drupalCreateUser(array('post comments'));

    $admin_permissions = array('administer comments');
    foreach (filter_formats() as $format) {
      if ($permission = filter_permission_name($format)) {
        $admin_permissions[] = $permission;
      }
    }
    $this->admin_user = $this->drupalCreateUser($admin_permissions);
  }

  /**
   * Test that a user can change their signature format and that it is respected
   * upon display.
   */
  function testUserSignature() {
    // Create a new node with comments on.
    $node = $this->drupalCreateNode(array('comment' => COMMENT_NODE_OPEN));

    // Verify that user signature field is not displayed on registration form.
    $this->drupalGet('user/register');
    $this->assertNoText(t('Signature'));

    // Log in as a regular user and create a signature.
    $this->drupalLogin($this->web_user);
    $signature_text = "<h1>" . $this->randomName() . "</h1>";
    $edit = array(
      'signature[value]' => $signature_text,
      'signature[format]' => $this->plain_text_format->format,
    );
    $this->drupalPost('user/' . $this->web_user->uid . '/edit', $edit, t('Save'));

    // Verify that values were stored.
    $this->assertFieldByName('signature[value]', $edit['signature[value]'], 'Submitted signature text found.');
    $this->assertFieldByName('signature[format]', $edit['signature[format]'], 'Submitted signature format found.');

    // Create a comment.
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit = array();
    $edit['subject'] = $this->randomName(8);
    $edit['comment_body[' . $langcode . '][0][value]'] = $this->randomName(16);
    $this->drupalPost('comment/reply/' . $node->nid, $edit, t('Preview'));
    $this->drupalPost(NULL, array(), t('Save'));

    // Get the comment ID. (This technique is the same one used in the Comment
    // module's CommentTestBase test case.)
    preg_match('/#comment-([0-9]+)/', $this->getURL(), $match);
    $comment_id = $match[1];

    // Log in as an administrator and edit the comment to use Full HTML, so
    // that the comment text itself is not filtered at all.
    $this->drupalLogin($this->admin_user);
    $edit['comment_body[' . $langcode . '][0][format]'] = $this->full_html_format->format;
    $this->drupalPost('comment/' . $comment_id . '/edit', $edit, t('Save'));

    // Assert that the signature did not make it through unfiltered.
    $this->drupalGet('node/' . $node->nid);
    $this->assertNoRaw($signature_text, 'Unfiltered signature text not found.');
    $this->assertRaw(check_markup($signature_text, $this->plain_text_format->format), 'Filtered signature text found.');
  }
}
