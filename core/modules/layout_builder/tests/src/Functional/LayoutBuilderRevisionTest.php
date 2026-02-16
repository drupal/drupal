<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\node\Entity\Node;
use Drupal\Tests\block_content\Traits\BlockContentCreationTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that entity revisions work with Layout Builder.
 */
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class LayoutBuilderRevisionTest extends BrowserTestBase {

  use BlockContentCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'node',
    'field_ui',
    'block',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create a node.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
      'new_revision' => TRUE,
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
    ]);
    $this->createBlockContentType([
      'id' => 'basic',
      'label' => 'Basic block',
      'revision' => 1,
    ], TRUE);

  }

  /**
   * Tests revisions are created with log message, and layout can be reverted.
   */
  public function testBlockRevisions(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer nodes',
      'view all revisions',
      'revert all revisions',
    ]));

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalGet('node/1');
    $page->clickLink('Layout');

    // Add a custom block, and add a revision message, save.
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is an override');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $page->fillField('revision_log[0][value]', 'Adding the first block to the page.');
    $page->pressButton('Save layout');
    $assert_session->pageTextContains('This is an override');

    // Ensure that the revisions tab is showing the message.
    $page->clickLink('Revisions');
    $assert_session->pageTextContains('Adding the first block to the page.');

    // Make a change to custom block text, add revision message, save.
    $page->clickLink('Layout');
    $components = Node::load(1)->get(OverridesSectionStorage::FIELD_NAME)->getSection(0)->getComponents();
    end($components);
    $uuid = key($components);
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $page->fillField('settings[label]', 'Making another change to this!');
    $page->pressButton('Update');
    $page->fillField('revision_log[0][value]', 'Changing the block label.');
    $page->pressButton('Save layout');
    $this->drupalGet('node/1');

    // Ensure that revisions tab is showing second message.
    $page->clickLink('Revisions');
    $assert_session->pageTextContains('Changing the block label.');

    // Revert back to first version, assert old text exists.
    $this->drupalGet('node/1/revisions/2/revert');
    $page->pressButton('Revert');
    $page->clickLink('View');
    $assert_session->pageTextContains('This is an override');
  }

  /**
   * Tests entity blocks revisioning.
   */
  public function testInlineBlocksRevisioning(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'administer nodes',
      'bypass node access',
      'create and edit custom blocks',
    ]));

    $this->drupalGet('node/1/layout');
    $this->clickLink('Add block');
    $this->clickLink('Create content block');
    $this->submitForm([
      'settings[label]' => 'Block title',
      'settings[block_form][body][0][value]' => 'The DEFAULT block body',
    ], 'Add block');
    $this->submitForm([], 'Save layout');

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
    $components = Node::load(1)->get(OverridesSectionStorage::FIELD_NAME)->getSection(0)->getComponents();
    end($components);
    $uuid = key($components);
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $this->submitForm([
      'settings[label]' => 'Block title',
      'settings[block_form][body][0][value]' => 'The NEW block body',
    ], 'Update');
    $this->drupalGet('node/1/layout');
    $this->submitForm([], 'Save layout');
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

}
