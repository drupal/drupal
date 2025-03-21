<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\content_moderation\Functional\ModerationStateTestBase;

/**
 * Tests the top bar behavior along with content moderation.
 *
 * @group navigation
 */
class NavigationTopBarContentModerationTest extends ModerationStateTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'node',
    'navigation',
  ];

  /**
   * Node used to check top bar options.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser
      ->addRole($this->drupalCreateRole(['access navigation']))
      ->save();
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Moderated content', 'moderated_content', TRUE);
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'moderated_content');

    $this->node = $this->drupalCreateNode([
      'type' => 'moderated_content',
      'moderation_state' => 'published',
    ]);
  }

  /**
   * Tests the interaction of page actions and content moderation.
   */
  public function testContentModerationPageActions(): void {
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->elementNotContains('xpath', '//*[@id="top-bar-page-actions"]/ul', 'Latest version');

    // Publish a new draft.
    $this->node->setNewRevision(TRUE);
    $this->node->setTitle($this->node->getTitle() . ' - draft');
    $this->node->moderation_state->value = 'draft';
    $this->node->save();

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->elementContains('xpath', '//*[@id="top-bar-page-actions"]/ul', 'Latest version');
    $this->assertSession()->elementContains('css', '.toolbar-badge--success', 'Published (Draft available)');

    // Confirm that Edit option is featured  in Latest version page.
    $this->clickLink('Latest version');
    $this->assertSession()->elementNotContains('xpath', '//*[@id="top-bar-page-actions"]/ul', 'Edit');
    $this->assertSession()->elementContains('css', '.toolbar-badge--info', 'Draft');
    $this->assertSession()->elementTextEquals('xpath', "//div[contains(@class, 'top-bar__content')]/div[contains(@class, 'top-bar__actions')]/a[contains(@class, 'toolbar-button--icon--pencil')]", "Edit");
  }

}
