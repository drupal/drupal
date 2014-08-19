<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentLinksTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;

/**
 * Basic comment links tests to ensure markup present.
 *
 * @group comment
 */
class CommentLinksTest extends CommentTestBase {

  /**
   * Comment being tested.
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $comment;

  /**
   * Seen comments, array of comment IDs.
   *
   * @var array
   */
  protected $seen = array();

  /**
   * Use the main node listing to test rendering on teasers.
   *
   * @var array
   *
   * @todo Remove this dependency.
   */
  public static $modules = array('views');

  /**
   * Tests comment links.
   *
   * The output of comment links depends on various environment conditions:
   * - Various Comment module configuration settings, user registration
   *   settings, and user access permissions.
   * - Whether the user is authenticated or not, and whether any comments exist.
   *
   * To account for all possible cases, this test creates permutations of all
   * possible conditions and tests the expected appearance of comment links in
   * each environment.
   */
  public function testCommentLinks() {
    // Bartik theme alters comment links, so use a different theme.
    \Drupal::service('theme_handler')->enable(array('stark'));
    \Drupal::config('system.theme')
      ->set('default', 'stark')
      ->save();

    // Remove additional user permissions from $this->web_user added by setUp(),
    // since this test is limited to anonymous and authenticated roles only.
    $roles = $this->web_user->getRoles();
    entity_delete_multiple('user_role', array(reset($roles)));

    // Create a comment via CRUD API functionality, since
    // $this->postComment() relies on actual user permissions.
    $comment = entity_create('comment', array(
      'cid' => NULL,
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => 0,
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->randomMachineName(),
      'hostname' => '127.0.0.1',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'comment_body' => array(LanguageInterface::LANGCODE_NOT_SPECIFIED => array($this->randomMachineName())),
    ));
    $comment->save();
    $this->comment = $comment;

    // Change comment settings.
    $this->setCommentSettings('form_location', CommentItemInterface::FORM_BELOW, 'Set comment form location');
    $this->setCommentAnonymous(TRUE);
    $this->node->comment = CommentItemInterface::OPEN;
    $this->node->save();

    // Change user permissions.
    $perms = array(
      'access comments' => 1,
      'post comments' => 1,
      'skip comment approval' => 1,
      'edit own comments' => 1,
    );
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, $perms);

    $nid = $this->node->id();

    // Assert basic link is output, actual functionality is unit-tested in
    // \Drupal\comment\Tests\CommentLinkBuilderTest.
    foreach (array('node', "node/$nid") as $path) {
      $this->drupalGet($path);

      // In teaser view, a link containing the comment count is always
      // expected.
      if ($path == 'node') {
        $this->assertLink(t('1 comment'));
      }
      $this->assertLink('Add new comment');
    }
  }

}
