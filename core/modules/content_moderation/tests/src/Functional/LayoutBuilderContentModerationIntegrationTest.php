<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests Content Moderation's integration with Layout Builder.
 *
 * @group content_moderation
 * @group layout_builder
 */
class LayoutBuilderContentModerationIntegrationTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'node',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    // Add a new bundle and add an editorial workflow.
    $this->createContentType(['type' => 'bundle_with_section_field']);
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'bundle_with_section_field');
    $workflow->save();

    // Enable layout overrides.
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'edit any bundle_with_section_field content',
      'view bundle_with_section_field revisions',
      'revert bundle_with_section_field revisions',
      'view own unpublished content',
      'view latest version',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
    ]));
  }

  /**
   * Tests that Layout changes are respected by Content Moderation.
   */
  public function testLayoutModeration() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Create an unpublished node. Revision count: 1.
    $node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);

    $this->drupalGet($node->toUrl());
    // Publish the node. Revision count: 2.
    $page->fillField('new_state', 'published');
    $page->pressButton('Apply');

    // Modify the layout.
    $page->clickLink('Layout');
    $assert_session->checkboxChecked('revision');
    $assert_session->fieldDisabled('revision');

    $page->clickLink('Add Block');
    $page->clickLink('Powered by Drupal');
    $page->pressButton('Add Block');
    // Save the node as a draft. Revision count: 3.
    $page->fillField('moderation_state[0][state]', 'draft');
    $page->pressButton('Save layout');

    // Block is visible on the revision page.
    $assert_session->addressEquals("node/{$node->id()}/latest");
    $assert_session->pageTextContains('Powered by Drupal');

    // Block is visible on the layout form.
    $page->clickLink('Layout');
    $assert_session->pageTextContains('Powered by Drupal');

    // Block is not visible on the live node page.
    $page->clickLink('View');
    $assert_session->pageTextNotContains('Powered by Drupal');

    // Publish the node. Revision count: 4.
    $page->clickLink('Latest version');
    $page->fillField('new_state', 'published');
    $page->pressButton('Apply');

    // Block is visible on the live node page.
    $assert_session->pageTextContains('Powered by Drupal');

    // Revert to the previous revision.
    $page->clickLink('Revisions');
    // Assert that there are 4 total revisions and 3 revert links.
    $assert_session->elementsCount('named', ['link', 'Revert'], 3);
    // Revert to the 2nd revision before modifying the layout.
    $this->clickLink('Revert', 1);
    $page->pressButton('Revert');

    $page->clickLink('View');
    $assert_session->pageTextNotContains('Powered by Drupal');
  }

}
