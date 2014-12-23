<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserSignatureTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests case for user signatures.
 *
 * @group user
 */
class UserSignatureTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment', 'field_ui');

  protected function setUp() {
    parent::setUp();

    // Enable user signatures.
    $this->config('user.settings')->set('signatures', 1)->save();

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    // Add a comment field with commenting enabled by default.
    $this->container->get('comment.manager')->addDefaultField('node', 'page');

    // Prefetch and create text formats.
    $this->filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html_format',
      'name' => 'Filtered HTML',
      'weight' => -1,
      'filters' => array(
        'filter_html' => array(
          'module' => 'filter',
          'status' => TRUE,
          'settings' => array(
            'allowed_html' => '<a> <em> <strong>',
          ),
        ),
      ),
    ));
    $this->filtered_html_format->save();

    $this->full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ));
    $this->full_html_format->save();

    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array($this->filtered_html_format->getPermissionName()));

    // Create regular and administrative users.
    $this->web_user = $this->drupalCreateUser(array('post comments'));

    $admin_permissions = array('post comments', 'administer comments', 'administer user form display', 'administer account settings');
    foreach (filter_formats() as $format) {
      if ($permission = $format->getPermissionName()) {
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
    $node = $this->drupalCreateNode(array(
      'body' => array(
        0 => array(
          'value' => $this->randomMachineName(32),
          'format' => 'full_html',
        ),
      ),
    ));

    // Verify that user signature field is not displayed on registration form.
    $this->drupalGet('user/register');
    $this->assertNoText(t('Signature'));

    // Log in as a regular user and create a signature.
    $this->drupalLogin($this->web_user);
    $signature_text = "<h1>" . $this->randomMachineName() . "</h1>";
    $edit = array(
      'signature[value]' => $signature_text,
    );
    $this->drupalPostForm('user/' . $this->web_user->id() . '/edit', $edit, t('Save'));

    // Verify that values were stored.
    $this->assertFieldByName('signature[value]', $edit['signature[value]'], 'Submitted signature text found.');

    // Verify that the user signature's text format's cache tag is absent.
    $this->drupalGet('node/' . $node->id());
    $this->assertTrue(!in_array('filter_format:filtered_html_format', explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'))));

    // Create a comment.
    $edit = array();
    $edit['subject[0][value]'] = $this->randomMachineName(8);
    $edit['comment_body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('comment/reply/node/' . $node->id() .'/comment', $edit, t('Preview'));
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Get the comment ID. (This technique is the same one used in the Comment
    // module's CommentTestBase test case.)
    preg_match('/#comment-([0-9]+)/', $this->getURL(), $match);
    $comment_id = $match[1];

    // Log in as an administrator and edit the comment to use Full HTML, so
    // that the comment text itself is not filtered at all.
    $this->drupalLogin($this->admin_user);
    $edit['comment_body[0][format]'] = $this->full_html_format->id();
    $this->drupalPostForm('comment/' . $comment_id . '/edit', $edit, t('Save'));

    // Assert that the signature did not make it through unfiltered.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw($signature_text, 'Unfiltered signature text not found.');
    $this->assertRaw(check_markup($signature_text, $this->filtered_html_format->id()), 'Filtered signature text found.');
    // Verify that the user signature's text format's cache tag is present.
    $this->drupalGet('node/' . $node->id());
    $this->assertTrue(in_array('filter_format:filtered_html_format', explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'))));

    // Verify the signature field is available on Manage form display page.
    $this->config('user.settings')->set('signatures', 0)->save();
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertNoText('Signature settings');
    $this->drupalPostForm('admin/config/people/accounts', array('user_signatures' => TRUE), t('Save configuration'));
    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertText('Signature settings');
  }
}
