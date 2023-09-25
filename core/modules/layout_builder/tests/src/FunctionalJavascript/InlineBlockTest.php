<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\node\Entity\Node;

/**
 * Tests that the inline block feature works correctly.
 *
 * @group layout_builder
 * @group #slow
 */
class InlineBlockTest extends InlineBlockTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
  ];

  /**
   * Tests adding and editing of inline blocks.
   */
  public function testInlineBlocks() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'create and edit custom blocks',
    ]));

    // Enable layout builder.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals(static::FIELD_UI_PREFIX . '/display/default/layout');
    // Add a basic block with the body field set.
    $this->addInlineBlockToLayout('Block title', 'The DEFAULT block body');
    $this->assertSaveLayout();

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The DEFAULT block body');

    // Enable overrides.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1/layout');

    // Confirm the block can be edited.
    $this->drupalGet('node/1/layout');
    $this->configureInlineBlock('The DEFAULT block body', 'The NEW block body!');
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The NEW block body');
    $assert_session->pageTextNotContains('The DEFAULT block body');
    $this->drupalGet('node/2');
    // Node 2 should use default layout.
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains('The NEW block body');

    // Add a basic block with the body field set.
    $this->drupalGet('node/1/layout');
    $this->addInlineBlockToLayout('2nd Block title', 'The 2nd block body');
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The NEW block body!');
    $assert_session->pageTextContains('The 2nd block body');
    $this->drupalGet('node/2');
    // Node 2 should use default layout.
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains('The NEW block body');
    $assert_session->pageTextNotContains('The 2nd block body');

    // Confirm the block can be edited.
    $this->drupalGet('node/1/layout');
    /** @var \Behat\Mink\Element\NodeElement $inline_block_2 */
    $inline_block_2 = $page->findAll('css', static::INLINE_BLOCK_LOCATOR)[1];
    $uuid = $inline_block_2->getAttribute('data-layout-block-uuid');
    $block_css_locator = static::INLINE_BLOCK_LOCATOR . "[data-layout-block-uuid=\"$uuid\"]";
    $this->configureInlineBlock('The 2nd block body', 'The 2nd NEW block body!', $block_css_locator);
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The NEW block body!');
    $assert_session->pageTextContains('The 2nd NEW block body!');
    $this->drupalGet('node/2');
    // Node 2 should use default layout.
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains('The NEW block body!');
    $assert_session->pageTextNotContains('The 2nd NEW block body!');

    // The default layout entity block should be changed.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $assert_session->pageTextContains('The DEFAULT block body');
    // Confirm default layout still only has 1 entity block.
    $assert_session->elementsCount('css', static::INLINE_BLOCK_LOCATOR, 1);
  }

  /**
   * Tests adding a new entity block and then not saving the layout.
   *
   * @dataProvider layoutNoSaveProvider
   */
  public function testNoLayoutSave($operation, $no_save_button_text, $confirm_button_text) {
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'create and edit custom blocks',
    ]));
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertEmpty($this->blockStorage->loadMultiple(), 'No entity blocks exist');
    // Enable layout builder and overrides.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm([
      'layout[enabled]' => TRUE,
      'layout[allow_custom]' => TRUE,
    ], 'Save');

    $this->drupalGet('node/1/layout');
    $this->addInlineBlockToLayout('Block title', 'The block body');
    $page->pressButton($no_save_button_text);
    if ($confirm_button_text) {
      $page->pressButton($confirm_button_text);
    }
    $this->drupalGet('node/1');
    $this->assertEmpty($this->blockStorage->loadMultiple(), 'No entity blocks were created when layout changes are discarded.');
    $assert_session->pageTextNotContains('The block body');

    $this->drupalGet('node/1/layout');

    $this->addInlineBlockToLayout('Block title', 'The block body');
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The block body');
    $blocks = $this->blockStorage->loadMultiple();
    $this->assertCount(1, $blocks);
    /** @var \Drupal\Core\Entity\ContentEntityBase $block */
    $block = array_pop($blocks);
    $revision_id = $block->getRevisionId();

    // Confirm the block can be edited.
    $this->drupalGet('node/1/layout');
    $this->configureInlineBlock('The block body', 'The block updated body');

    $page->pressButton($no_save_button_text);
    if ($confirm_button_text) {
      $page->pressButton($confirm_button_text);
    }
    $this->drupalGet('node/1');

    $blocks = $this->blockStorage->loadMultiple();
    // When reverting or discarding the update block should not be on the page.
    $assert_session->pageTextNotContains('The block updated body');
    if ($operation === 'discard_changes') {
      // When discarding the original block body should appear.
      $assert_session->pageTextContains('The block body');

      $this->assertCount(1, $blocks);
      $block = array_pop($blocks);
      $this->assertEquals($block->getRevisionId(), $revision_id);
      $this->assertEquals('The block body', $block->get('body')->getValue()[0]['value']);
    }
    else {
      // The block should not be visible.
      // Blocks are currently only deleted when the parent entity is deleted.
      $assert_session->pageTextNotContains('The block body');
    }
  }

  /**
   * Provides test data for ::testNoLayoutSave().
   */
  public function layoutNoSaveProvider() {
    return [
      'discard_changes' => [
        'discard_changes',
        'Discard changes',
        'Confirm',
      ],
      'revert' => [
        'revert',
        'Revert to defaults',
        'Revert',
      ],
    ];
  }

  /**
   * Tests entity blocks revisioning.
   */
  public function testInlineBlocksRevisioning() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'administer nodes',
      'bypass node access',
      'create and edit custom blocks',
    ]));
    // Enable layout builder and overrides.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE, 'layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1/layout');

    // Add an inline block.
    $this->addInlineBlockToLayout('Block title', 'The DEFAULT block body');
    $this->assertSaveLayout();
    $this->drupalGet('node/1');

    $assert_session->pageTextContains('The DEFAULT block body');

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $original_revision_id = $node_storage->getLatestRevisionId(1);

    // Create a new revision.
    $this->drupalGet('node/1/edit');
    $page->findField('title[0][value]')->setValue('Node updated');
    $page->pressButton('Save');

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');

    $assert_session->linkExists('Revisions');

    // Update the block.
    $this->drupalGet('node/1/layout');
    $this->configureInlineBlock('The DEFAULT block body', 'The NEW block body');
    $this->assertSaveLayout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The NEW block body');
    $assert_session->pageTextNotContains('The DEFAULT block body');

    $revision_url = "node/1/revisions/$original_revision_id";

    // Ensure viewing the previous revision shows the previous block revision.
    $this->drupalGet("$revision_url/view");
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains('The NEW block body');

    // Revert to first revision.
    $revision_url = "$revision_url/revert";
    $this->drupalGet($revision_url);
    $page->pressButton('Revert');

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');
    $assert_session->pageTextNotContains('The NEW block body');
  }

  /**
   * Tests entity blocks revisioning.
   */
  public function testInlineBlocksRevisioningIntegrity() {
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'view all revisions',
      'access content',
      'create and edit custom blocks',
    ]));
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE, 'layout[allow_custom]' => TRUE], 'Save');

    $block_1_locator = static::INLINE_BLOCK_LOCATOR;
    $block_2_locator = sprintf('%s + %s', static::INLINE_BLOCK_LOCATOR, static::INLINE_BLOCK_LOCATOR);

    // Add two blocks to the page and assert the content in each.
    $this->drupalGet('node/1/layout');
    $this->addInlineBlockToLayout('Block 1', 'Block 1 original');
    $this->addInlineBlockToLayout('Block 2', 'Block 2 original');
    $this->assertSaveLayout();
    $this->assertNodeRevisionContent(3, ['Block 1 original', 'Block 2 original']);
    $this->assertBlockRevisionCountByTitle('Block 1', 1);
    $this->assertBlockRevisionCountByTitle('Block 2', 1);

    // Update the contents of one of the blocks and assert the updated content
    // appears on the next revision.
    $this->drupalGet('node/1/layout');
    $this->configureInlineBlock('Block 2 original', 'Block 2 updated', $block_2_locator);
    $this->assertSaveLayout();
    $this->assertNodeRevisionContent(4, ['Block 1 original', 'Block 2 updated']);
    $this->assertBlockRevisionCountByTitle('Block 1', 1);
    $this->assertBlockRevisionCountByTitle('Block 2', 2);

    // Update block 1 without creating a new revision of the parent.
    $this->drupalGet('node/1/layout');
    $this->configureInlineBlock('Block 1 original', 'Block 1 updated', $block_1_locator);
    $this->getSession()->getPage()->uncheckField('revision');
    $this->getSession()->getPage()->pressButton('Save layout');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', '.messages--status'));
    $this->assertNodeRevisionContent(4, ['Block 1 updated', 'Block 2 updated']);
    $this->assertBlockRevisionCountByTitle('Block 1', 2);
    $this->assertBlockRevisionCountByTitle('Block 2', 2);

    // Reassert all of the parent revisions contain the correct block content
    // and the integrity of the revisions was preserved.
    $this->assertNodeRevisionContent(3, ['Block 1 original', 'Block 2 original']);
  }

  /**
   * Assert the contents of a node revision.
   *
   * @param int $revision_id
   *   The revision ID to assert.
   * @param array $content
   *   The content items to assert on the page.
   *
   * @internal
   */
  protected function assertNodeRevisionContent(int $revision_id, array $content): void {
    $this->drupalGet("node/1/revisions/$revision_id/view");
    foreach ($content as $content_item) {
      $this->assertSession()->pageTextContains($content_item);
    }
  }

  /**
   * Assert the number of block content revisions by the block title.
   *
   * @param string $block_title
   *   The block title.
   * @param int $expected_revision_count
   *   The revision count.
   *
   * @internal
   */
  protected function assertBlockRevisionCountByTitle(string $block_title, int $expected_revision_count): void {
    $actual_revision_count = $this->blockStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('info', $block_title)
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals($actual_revision_count, $expected_revision_count);
  }

  /**
   * Tests that entity blocks deleted correctly.
   */
  public function testDeletion() {
    /** @var \Drupal\Core\Cron $cron */
    $cron = \Drupal::service('cron');
    /** @var \Drupal\layout_builder\InlineBlockUsageInterface $usage */
    $usage = \Drupal::service('inline_block.usage');
    $this->drupalLogin($this->drupalCreateUser([
      'administer content types',
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'administer nodes',
      'bypass node access',
      'create and edit custom blocks',
    ]));
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Enable layout builder.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    // Add a block to default layout.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->addInlineBlockToLayout('Block title', 'The DEFAULT block body');
    $this->assertSaveLayout();

    $this->assertCount(1, $this->blockStorage->loadMultiple());
    $default_block_id = $this->getLatestBlockEntityId();

    // Ensure the block shows up on node pages.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The DEFAULT block body');
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The DEFAULT block body');

    // Enable overrides.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    // Ensure we have 2 copies of the block in node overrides.
    $this->drupalGet('node/1/layout');
    $this->assertSaveLayout();
    $node_1_block_id = $this->getLatestBlockEntityId();

    $this->drupalGet('node/2/layout');
    $this->assertSaveLayout();
    $node_2_block_id = $this->getLatestBlockEntityId();
    $this->assertCount(3, $this->blockStorage->loadMultiple());

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals(static::FIELD_UI_PREFIX . '/display/default/layout');

    $this->assertNotEmpty($this->blockStorage->load($default_block_id));
    $this->assertNotEmpty($usage->getUsage($default_block_id));
    // Remove block from default.
    $this->removeInlineBlockFromLayout();
    $this->assertSaveLayout();
    // Ensure the block in the default was deleted.
    $this->blockStorage->resetCache([$default_block_id]);
    $this->assertEmpty($this->blockStorage->load($default_block_id));
    // Ensure other blocks still exist.
    $this->assertCount(2, $this->blockStorage->loadMultiple());
    $this->assertEmpty($usage->getUsage($default_block_id));

    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('The DEFAULT block body');

    $this->removeInlineBlockFromLayout();
    $this->assertSaveLayout();
    $cron->run();
    // Ensure entity block is not deleted because it is needed in revision.
    $this->assertNotEmpty($this->blockStorage->load($node_1_block_id));
    $this->assertCount(2, $this->blockStorage->loadMultiple());

    $this->assertNotEmpty($usage->getUsage($node_1_block_id));
    // Ensure entity block is deleted when node is deleted.
    $this->drupalGet('node/1/delete');
    $page->pressButton('Delete');
    $this->assertEmpty(Node::load(1));
    $cron->run();
    $this->assertEmpty($this->blockStorage->load($node_1_block_id));
    $this->assertEmpty($usage->getUsage($node_1_block_id));
    $this->assertCount(1, $this->blockStorage->loadMultiple());

    // Add another block to the default.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->addInlineBlockToLayout('Title 2', 'Body 2');
    $this->assertSaveLayout();
    $cron->run();
    $default_block2_id = $this->getLatestBlockEntityId();
    $this->assertCount(2, $this->blockStorage->loadMultiple());

    // Delete the other node so bundle can be deleted.
    $this->assertNotEmpty($usage->getUsage($node_2_block_id));
    $this->drupalGet('node/2/delete');
    $page->pressButton('Delete');
    $this->assertEmpty(Node::load(2));
    $cron->run();
    // Ensure entity block was deleted.
    $this->assertEmpty($this->blockStorage->load($node_2_block_id));
    $this->assertEmpty($usage->getUsage($node_2_block_id));
    $this->assertCount(1, $this->blockStorage->loadMultiple());

    // Delete the bundle which has the default layout.
    $this->assertNotEmpty($usage->getUsage($default_block2_id));
    $this->drupalGet(static::FIELD_UI_PREFIX . '/delete');
    $page->pressButton('Delete');
    $cron->run();

    // Ensure the entity block in default is deleted when bundle is deleted.
    $this->assertEmpty($this->blockStorage->load($default_block2_id));
    $this->assertEmpty($usage->getUsage($default_block2_id));
    $this->assertCount(0, $this->blockStorage->loadMultiple());
  }

  /**
   * Tests access to the block edit form of inline blocks.
   *
   * This module does not provide links to these forms but in case the paths are
   * accessed directly they should accessible by users with the
   * 'configure any layout' permission.
   *
   * @see layout_builder_block_content_access()
   */
  public function testAccess() {
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'create and edit custom blocks',
    ]));
    $assert_session = $this->assertSession();

    // Enable layout builder and overrides.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE, 'layout[allow_custom]' => TRUE], 'Save');

    // Ensure we have 2 copies of the block in node overrides.
    $this->drupalGet('node/1/layout');
    $this->addInlineBlockToLayout('Block title', 'Block body');
    $this->assertSaveLayout();
    $node_1_block_id = $this->getLatestBlockEntityId();

    $this->drupalGet("block/$node_1_block_id");
    $assert_session->pageTextNotContains('You are not authorized to access this page');

    $this->drupalLogout();
    $this->drupalLogin($this->drupalCreateUser([
      'administer nodes',
    ]));

    $this->drupalGet("block/$node_1_block_id");
    $assert_session->pageTextContains('You are not authorized to access this page');

    $this->drupalLogin($this->drupalCreateUser([
      'create and edit custom blocks',
    ]));
    $this->drupalGet("block/$node_1_block_id");
    $assert_session->pageTextNotContains('You are not authorized to access this page');
  }

  /**
   * Tests the workflow for adding an inline block depending on number of types.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAddWorkFlow() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $type_storage = $this->container->get('entity_type.manager')->getStorage('block_content_type');
    foreach ($type_storage->loadByProperties() as $type) {
      $type->delete();
    }

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'create and edit custom blocks',
    ]));

    // Enable layout builder and overrides.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE, 'layout[allow_custom]' => TRUE], 'Save');

    $layout_default_path = 'admin/structure/types/manage/bundle_with_section_field/display/default/layout';
    $this->drupalGet($layout_default_path);
    // Add a basic block with the body field set.
    $page->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    // Confirm that with no block content types the link does not appear.
    $assert_session->linkNotExists('Create content block');

    $this->createBlockContentType('basic', 'Basic block');

    $this->drupalGet($layout_default_path);
    // Add a basic block with the body field set.
    $page->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    // Confirm with only 1 type the "Create content block" link goes directly t
    // block add form.
    $assert_session->linkNotExists('Basic block');
    $this->clickLink('Create content block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('Title');

    $this->createBlockContentType('advanced', 'Advanced block');

    $this->drupalGet($layout_default_path);
    // Add a basic block with the body field set.
    $page->clickLink('Add block');
    // Confirm that, when more than 1 type exists, "Create content block" shows a
    // list of block types.
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkNotExists('Basic block');
    $assert_session->linkNotExists('Advanced block');
    $this->clickLink('Create content block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('Title');
    $assert_session->linkExists('Basic block');
    $assert_session->linkExists('Advanced block');

    $this->clickLink('Advanced block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('Title');
  }

  /**
   * Tests the 'create and edit content blocks' permission to add a new block.
   */
  public function testAddInlineBlocksPermission() {
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $assert = function ($permissions, $expected) {
      $assert_session = $this->assertSession();
      $page = $this->getSession()->getPage();

      $this->drupalLogin($this->drupalCreateUser($permissions));
      $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
      $page->clickLink('Add block');
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas .block-categories'));
      if ($expected) {
        $assert_session->linkExists('Create content block');
      }
      else {
        $assert_session->linkNotExists('Create content block');
      }
    };

    $permissions = [
      'configure any layout',
      'administer node display',
    ];
    $assert($permissions, FALSE);
    $permissions[] = 'create and edit custom blocks';
    $assert($permissions, TRUE);
  }

  /**
   * Tests 'create and edit custom blocks' permission to edit an existing block.
   */
  public function testEditInlineBlocksPermission() {

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'create and edit custom blocks',
    ]));
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->addInlineBlockToLayout('The block label', 'The body value');

    $assert = function ($permissions, $expected) {
      $assert_session = $this->assertSession();

      $this->drupalLogin($this->drupalCreateUser($permissions));
      $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
      $this->clickContextualLink(static::INLINE_BLOCK_LOCATOR, 'Configure');
      $assert_session->assertWaitOnAjaxRequest();
      if ($expected) {
        $assert_session->fieldExists('settings[block_form][body][0][value]');
      }
      else {
        $assert_session->fieldNotExists('settings[block_form][body][0][value]');
      }
    };

    $permissions = [
      'access contextual links',
      'configure any layout',
      'administer node display',
    ];
    $assert($permissions, FALSE);
    $permissions[] = 'create and edit custom blocks';
    $assert($permissions, TRUE);
  }

  /**
   * Test editing inline blocks when the parent has been reverted.
   */
  public function testInlineBlockParentRevert() {
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'administer nodes',
      'bypass node access',
      'create and edit custom blocks',
    ]));
    $display = \Drupal::service('entity_display.repository')->getViewDisplay('node', 'bundle_with_section_field');
    $display->enableLayoutBuilder()->setOverridable()->save();
    $test_node = $this->createNode([
      'title' => 'test node',
      'type' => 'bundle_with_section_field',
    ]);

    $this->drupalGet("node/{$test_node->id()}/layout");
    $this->addInlineBlockToLayout('Example block', 'original content');
    $this->assertSaveLayout();
    $original_content_revision_id = Node::load($test_node->id())->getLoadedRevisionId();

    $this->drupalGet("node/{$test_node->id()}/layout");
    $this->configureInlineBlock('original content', 'updated content');
    $this->assertSaveLayout();

    $this->drupalGet("node/{$test_node->id()}/revisions/$original_content_revision_id/revert");
    $this->submitForm([], 'Revert');
    $this->drupalGet("node/{$test_node->id()}/layout");
    $this->configureInlineBlock('original content', 'second updated content');
    $this->assertSaveLayout();

    $this->drupalGet($test_node->toUrl());
    $this->assertSession()->pageTextContains('second updated content');
  }

}
