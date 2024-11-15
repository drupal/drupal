<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\FunctionalJavascript;

use Drupal\Tests\layout_builder\FunctionalJavascript\InlineBlockTestBase;
use Drupal\Tests\system\Traits\OffCanvasTestTrait;
use Drupal\Tests\workspaces\Functional\WorkspaceTestUtilities;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests for layout editing in workspaces.
 *
 * @group layout_builder
 * @group workspaces
 * @group #slow
 */
class WorkspacesLayoutBuilderIntegrationTest extends InlineBlockTestBase {

  use OffCanvasTestTrait;
  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'workspaces',
    'workspaces_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'create and edit custom blocks',
      'administer blocks',
      'administer content types',
      'administer workspaces',
      'view any workspace',
      'administer site configuration',
      'administer nodes',
      'bypass node access',
    ]));
    $this->setupWorkspaceSwitcherBlock();

    // Enable layout builder.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm([
      'layout[enabled]' => TRUE,
      'layout[allow_custom]' => TRUE,
    ], 'Save');
    $this->clickLink('Manage layout');
    $this->assertSession()->addressEquals(static::FIELD_UI_PREFIX . '/display/default/layout');
    // Add a basic block with the body field set.
    $this->addInlineBlockToLayout('Block title', 'The DEFAULT block body');
    $this->assertSaveLayout();
  }

  /**
   * Tests changing a layout/blocks inside a workspace.
   */
  public function testBlocksInWorkspaces(): void {
    $assert_session = $this->assertSession();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The DEFAULT block body');

    $stage = Workspace::load('stage');
    $this->switchToWorkspace($stage);

    // Confirm the block can be edited.
    $this->drupalGet('node/1/layout');
    $new_block_body = 'The NEW block body';
    $this->configureInlineBlock('The DEFAULT block body', $new_block_body);
    $this->assertSaveLayout();

    $this->drupalGet('node/1');
    $assert_session->pageTextContains($new_block_body);
    $assert_session->pageTextNotContains('The DEFAULT block body');
    $this->drupalGet('node/2');
    // Node 2 should use default layout.
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains($new_block_body);

    // Switch back to the live workspace and verify that the changes are not
    // visible there.
    $this->switchToLive();
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains($new_block_body);
    $assert_session->pageTextContains('The DEFAULT block body');

    $this->switchToWorkspace($stage);
    // Add a basic block with the body field set.
    $this->drupalGet('node/1/layout');
    $second_block_body = 'The 2nd block body';
    $this->addInlineBlockToLayout('2nd Block title', $second_block_body);
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains($second_block_body);
    $this->drupalGet('node/2');
    // Node 2 should use default layout.
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains($new_block_body);
    $assert_session->pageTextNotContains($second_block_body);

    // Switch back to the live workspace and verify that the new added block is
    // not visible there.
    $this->switchToLive();
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains($second_block_body);
    $assert_session->pageTextContains('The DEFAULT block body');

    // Check the concurrent editing protection on the Layout Builder form.
    $this->drupalGet('/node/1/layout');
    $assert_session->pageTextContains('The content is being edited in the Stage workspace. As a result, your changes cannot be saved.');

    $stage->publish();
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('The DEFAULT block body');
    $assert_session->pageTextContains($new_block_body);
    $assert_session->pageTextContains($second_block_body);
  }

  /**
   * Tests that blocks can be deleted inside workspaces.
   */
  public function testBlockDeletionInWorkspaces(): void {
    $assert_session = $this->assertSession();

    $stage = Workspace::load('stage');
    $this->switchToWorkspace($stage);

    $this->drupalGet('node/1/layout');
    $workspace_block_content = 'The WORKSPACE block body';
    $this->addInlineBlockToLayout('Workspace block title', $workspace_block_content);
    $this->assertSaveLayout();

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextContains($workspace_block_content);

    $this->switchToLive();
    $assert_session->pageTextNotContains($workspace_block_content);

    $this->switchToWorkspace($stage);
    $this->drupalGet('node/1/layout');
    $this->removeInlineBlockFromLayout(static::INLINE_BLOCK_LOCATOR . ' ~ ' . static::INLINE_BLOCK_LOCATOR);
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains($workspace_block_content);

    $this->drupalGet('node/1/layout');
    $this->removeInlineBlockFromLayout();
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('The DEFAULT block body');
    $assert_session->pageTextNotContains($workspace_block_content);

    $this->switchToLive();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');
    $stage->publish();
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('The DEFAULT block body');
    $assert_session->pageTextNotContains($workspace_block_content);
  }

}
