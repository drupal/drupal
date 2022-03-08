<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\user\RoleInterface;

/**
 * Tests making comment base fields' displays configurable.
 *
 * @group comment
 */
class CommentDisplayConfigurableTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  protected function setUp(): void {
    parent::setUp();

    // Allow anonymous users to see comments.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments',
      'access content',
    ]);
  }

  /**
   * Sets base fields to configurable display and check settings are respected.
   */
  public function testDisplayConfigurable() {
    // Add a comment.
    $nid = $this->node->id();
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = Comment::create([
      'entity_id' => $nid,
      'entity_type' => 'node',
      'field_name' => 'comment',
      'uid' => $this->webUser->id(),
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->randomMachineName(),
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'comment_body' => [LanguageInterface::LANGCODE_NOT_SPECIFIED => [$this->randomMachineName()]],
    ]);
    $comment->save();
    $assert = $this->assertSession();

    // Check the comment author with Drupal default non-configurable display.
    $this->drupalGet('node/' . $nid);
    $assert->elementExists('css', 'p.comment__author span');

    // Enable module to make base fields' displays configurable.
    \Drupal::service('module_installer')->install(['comment_display_configurable_test']);

    // Configure display.
    $display = EntityViewDisplay::load('comment.comment.default');
    $display->setComponent('uid',
      [
        'type' => 'entity_reference_label',
        'label' => 'above',
        'settings' => ['link' => FALSE],
      ])
      ->save();
    // Recheck the comment author with configurable display.
    $this->drupalGet('node/' . $nid);
    $assert->elementExists('css', '.field--name-uid .field__item');

    // Remove from display.
    $display->removeComponent('uid')
      ->removeComponent('created')
      ->save();

    $this->drupalGet('node/' . $this->node->id());
    $assert->elementNotExists('css', '.field--name-uid .field__item');
  }

}
