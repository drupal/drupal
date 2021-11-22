<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 */
class LayoutBuilderUiTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * Path prefix for the field UI for the test bundle.
   *
   * @var string
   */
  const FIELD_UI_PREFIX = 'admin/structure/types/manage/bundle_with_section_field';

  protected static $modules = [
    'layout_builder',
    'block',
    'node',
    'block_content',
    'contextual',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'create and edit custom blocks',
      'administer node display',
      'administer node fields',
      'access contextual links',
    ]));

    // Enable layout builder.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
  }

  /**
   * Tests that after removing sections reloading the page does not re-add them.
   */
  public function testReloadWithNoSections() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Remove all of the sections from the page.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $page->clickLink('Remove Section 1');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();
    // Assert that there are no sections on the page.
    $assert_session->pageTextNotContains('Remove Section 1');
    $assert_session->pageTextNotContains('Add block');

    // Reload the page.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    // Assert that there are no sections on the page.
    $assert_session->pageTextNotContains('Remove Section 1');
    $assert_session->pageTextNotContains('Add block');
  }

  /**
   * Tests the message indicating unsaved changes.
   */
  public function testUnsavedChangesMessage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Make and then discard changes.
    $this->assertModifiedLayout(static::FIELD_UI_PREFIX . '/display/default/layout');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');
    $assert_session->pageTextNotContains('You have unsaved changes.');

    // Make and then save changes.
    $this->assertModifiedLayout(static::FIELD_UI_PREFIX . '/display/default/layout');
    $page->pressButton('Save layout');
    $assert_session->pageTextNotContains('You have unsaved changes.');
  }

  /**
   * Asserts that modifying a layout works as expected.
   *
   * @param string $path
   *   The path to a Layout Builder UI page.
   *
   * @internal
   */
  protected function assertModifiedLayout(string $path): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet($path);
    $page->clickLink('Add section');
    $assert_session->waitForElementVisible('named', ['link', 'One column']);
    $assert_session->pageTextNotContains('You have unsaved changes.');
    $page->clickLink('One column');
    $assert_session->waitForElementVisible('named', ['button', 'Add section']);
    $page->pressButton('Add section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContainsOnce('You have unsaved changes.');

    // Reload the page.
    $this->drupalGet($path);
    $assert_session->pageTextContainsOnce('You have unsaved changes.');
  }

  /**
   * Tests that elements that open the dialog are properly highlighted.
   */
  public function testAddHighlights() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic block',
      'revision' => 1,
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $assert_session->elementsCount('css', '.layout-builder__add-section', 2);
    $assert_session->elementNotExists('css', '.is-layout-builder-highlighted');
    $page->clickLink('Add section');
    $this->assertNotEmpty($assert_session->waitForElement('css', '#drupal-off-canvas .item-list'));
    $assert_session->assertWaitOnAjaxRequest();

    // Highlight is present with AddSectionController.
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="section-0"]');
    $page->clickLink('Two column');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas input[type="submit"][value="Add section"]'));
    $assert_session->assertWaitOnAjaxRequest();

    // The highlight is present with ConfigureSectionForm.
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="section-0"]');

    // Submit the form to add the section and then confirm that no element is
    // highlighted anymore.
    $page->pressButton("Add section");
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertHighlightNotExists();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[data-layout-delta="1"]'));
    $assert_session->elementsCount('css', '.layout-builder__add-block', 3);

    // Add a custom block.
    $page->clickLink('Add block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'a:contains("Create custom block")'));
    $assert_session->assertWaitOnAjaxRequest();

    // Highlight is present with ChooseBlockController::build().
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="block-0-first"]');
    $page->clickLink('Create custom block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas input[value="Add block"]'));
    $assert_session->assertWaitOnAjaxRequest();

    // Highlight is present with ChooseBlockController::inlineBlockList().
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="block-0-first"]');
    $page->pressButton('Close');
    $this->assertHighlightNotExists();

    // The highlight should persist with all block config dialogs.
    $page->clickLink('Add block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'a:contains("Recent content")'));
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="block-0-first"]');
    $page->clickLink('Recent content');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas input[value="Add block"]'));

    // The highlight is present with ConfigureBlockFormBase::doBuildForm().
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="block-0-first"]');
    $page->pressButton('Close');
    $this->assertHighlightNotExists();

    // The highlight is present when the "Configure section" dialog is open.
    $page->clickLink('Configure Section 1');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="section-update-0"]');
    $page->pressButton('Close');
    $this->assertHighlightNotExists();

    // The highlight is present when the "Remove Section" dialog is open.
    $page->clickLink('Remove Section 1');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertHighlightedElement('[data-layout-builder-highlight-id="section-update-0"]');
    $page->pressButton('Close');
    $this->assertHighlightNotExists();

    // A block is highlighted when its "Configure" contextual link is clicked.
    $this->clickContextualLink('.block-field-blocknodebundle-with-section-fieldbody', 'Configure');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertHighlightedElement('.block-field-blocknodebundle-with-section-fieldbody');

    // Make sure the highlight remains when contextual links are revealed with
    // the mouse.
    $this->toggleContextualTriggerVisibility('.block-field-blocknodebundle-with-section-fieldbody');
    $active_section = $page->find('css', '.block-field-blocknodebundle-with-section-fieldbody');
    $active_section->pressButton('Open configuration options');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.block-field-blocknodebundle-with-section-fieldbody .contextual.open'));

    $page->pressButton('Close');
    $this->assertHighlightNotExists();

    // @todo Remove the reload once https://www.drupal.org/node/2918718 is
    //   completed.
    $this->getSession()->reload();

    // Block is highlighted when its "Remove block" contextual link is clicked.
    $this->clickContextualLink('.block-field-blocknodebundle-with-section-fieldbody', 'Remove block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertHighlightedElement('.block-field-blocknodebundle-with-section-fieldbody');
    $page->pressButton('Close');
    $this->assertHighlightNotExists();
  }

  /**
   * Tests removing newly added extra field.
   */
  public function testNewExtraField() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // At this point layout builder has been enabled for the test content type.
    // Install a test module that creates a new extra field then clear cache.
    \Drupal::service('module_installer')->install(['layout_builder_extra_field_test']);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // View the layout and try to remove the new extra field.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $assert_session->pageTextContains('New Extra Field');
    $this->clickContextualLink('.block-extra-field-blocknodebundle-with-section-fieldlayout-builder-extra-field-test', 'Remove block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Are you sure you want to remove');
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('New Extra Field');
  }

  /**
   * Confirms the presence of the 'is-layout-builder-highlighted' class.
   *
   * @param string $selector
   *   The highlighted element must also match this selector.
   */
  private function assertHighlightedElement(string $selector): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // There is only one highlighted element.
    $assert_session->elementsCount('css', '.is-layout-builder-highlighted', 1);

    // The selector is also the highlighted element.
    $this->assertTrue($page->find('css', $selector)->hasClass('is-layout-builder-highlighted'));
  }

  /**
   * Waits for the dialog to close and confirms no highlights are present.
   */
  private function assertHighlightNotExists(): void {
    $assert_session = $this->assertSession();

    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->assertNoElementAfterWait('css', '.is-layout-builder-highlighted');
  }

}
