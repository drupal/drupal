<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\user\RoleInterface;
use Drupal\comment\Entity\Comment;

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
  protected $seen = [];

  /**
   * Use the main node listing to test rendering on teasers.
   *
   * @var array
   *
   * @todo Remove this dependency.
   */
  protected static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that comment links are output and can be hidden.
   */
  public function testCommentLinks() {
    // Bartik theme alters comment links, so use a different theme.
    \Drupal::service('theme_installer')->install(['stark']);
    $this->config('system.theme')
      ->set('default', 'stark')
      ->save();

    // Remove additional user permissions from $this->webUser added by setUp(),
    // since this test is limited to anonymous and authenticated roles only.
    $roles = $this->webUser->getRoles();
    \Drupal::entityTypeManager()->getStorage('user_role')->load(reset($roles))->delete();

    // Create a comment via CRUD API functionality, since
    // $this->postComment() relies on actual user permissions.
    $comment = Comment::create([
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
      'comment_body' => [['value' => $this->randomMachineName()]],
    ]);
    $comment->save();
    $this->comment = $comment;

    // Tests that reply link is not visible when threading is disabled.
    $this->drupalLogin($this->webUser);
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT, 'Comment paging changed.');
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->linkNotExists('Reply');
    // Tests that reply link is visible when threading is enabled.
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->linkExists('Reply');

    // Change comment settings.
    $this->setCommentSettings('form_location', CommentItemInterface::FORM_BELOW, 'Set comment form location');
    $this->setCommentAnonymous(TRUE);
    $this->node->comment = CommentItemInterface::OPEN;
    $this->node->save();

    // Change user permissions.
    $perms = [
      'access comments' => 1,
      'post comments' => 1,
      'skip comment approval' => 1,
      'edit own comments' => 1,
    ];
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, $perms);

    $nid = $this->node->id();

    // Assert basic link is output, actual functionality is unit-tested in
    // \Drupal\comment\Tests\CommentLinkBuilderTest.
    foreach (['node', "node/$nid"] as $path) {
      $this->drupalGet($path);

      // In teaser view, a link containing the comment count is always
      // expected.
      if ($path == 'node') {
        $this->assertSession()->linkExists('1 comment');
      }
      $this->assertSession()->linkExists('Add new comment');
    }

    $display_repository = $this->container->get('entity_display.repository');

    // Change weight to make links go before comment body.
    $display_repository->getViewDisplay('comment', 'comment')
      ->setComponent('links', ['weight' => -100])
      ->save();
    $this->drupalGet($this->node->toUrl());
    $element = $this->cssSelect('article.js-comment > div');
    // Get last child element.
    $element = end($element);
    $this->assertSame('div', $element->getTagName(), 'Last element is comment body.');

    // Change weight to make links go after comment body.
    $display_repository->getViewDisplay('comment', 'comment')
      ->setComponent('links', ['weight' => 100])
      ->save();
    $this->drupalGet($this->node->toUrl());
    $element = $this->cssSelect('article.js-comment > div');
    // Get last child element.
    $element = end($element);
    $this->assertNotEmpty($element->find('css', 'ul.links'), 'Last element is comment links.');

    // Make sure we can hide node links.
    $display_repository->getViewDisplay('node', $this->node->bundle())
      ->removeComponent('links')
      ->save();
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->linkNotExists('1 comment');
    $this->assertSession()->linkNotExists('Add new comment');

    // Visit the full node, make sure there are links for the comment.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->pageTextContains($comment->getSubject());
    $this->assertSession()->linkExists('Reply');

    // Make sure we can hide comment links.
    $display_repository->getViewDisplay('comment', 'comment')
      ->removeComponent('links')
      ->save();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->pageTextContains($comment->getSubject());
    $this->assertSession()->linkNotExists('Reply');
  }

}
