<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentLinksTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\entity\Entity\EntityViewDisplay;

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
   * Tests that comment links are output and can be hidden.
   */
  public function testCommentLinks() {
    // Bartik theme alters comment links, so use a different theme.
    \Drupal::service('theme_handler')->install(array('stark'));
    \Drupal::config('system.theme')
      ->set('default', 'stark')
      ->save();

    // Remove additional user permissions from $this->webUser added by setUp(),
    // since this test is limited to anonymous and authenticated roles only.
    $roles = $this->webUser->getRoles();
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

    // Make sure we can hide node links.
    entity_get_display('node', $this->node->bundle(), 'default')
      ->removeComponent('links')
      ->save();
    $this->drupalGet($this->node->url());
    $this->assertNoLink('1 comment');
    $this->assertNoLink('Add new comment');

    // Visit the full node, make sure there are links for the comment.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($comment->getSubject());
    $this->assertLink('Reply');

    // Make sure we can hide comment links.
    entity_get_display('comment', 'comment', 'default')
      ->removeComponent('links')
      ->save();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($comment->getSubject());
    $this->assertNoLink('Reply');
  }

}
