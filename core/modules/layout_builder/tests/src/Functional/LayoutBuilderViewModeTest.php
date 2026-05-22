<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\layout_builder\Traits\EnableLayoutBuilderTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Layout Builder UI with view modes.
 */
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class LayoutBuilderViewModeTest extends LayoutBuilderTestBase {

  use EnableLayoutBuilderTrait;

  /**
   * Tests that a non-default view mode works as expected.
   */
  public function testNonDefaultViewMode(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Allow overrides for the layout.
    $display = LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default');
    $this->enableLayoutBuilder($display);

    $this->drupalGet("$field_ui_prefix/display/default");
    $this->clickLink('Manage layout');
    // Confirm the body field only is shown once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');

    $this->clickLink('Teaser');
    // Enabling Layout Builder for the default mode does not affect the teaser.
    $assert_session->addressEquals("$field_ui_prefix/display/teaser");
    $assert_session->elementNotExists('css', '#layout-builder__layout');
    $assert_session->checkboxNotChecked('layout[enabled]');
    $this->enableLayoutBuilderFromUi('bundle_with_section_field', 'teaser', FALSE);
    $assert_session->linkExists('Manage layout');
    $page->clickLink('Manage layout');
    // Confirm the body field only is shown once.
    $assert_session->elementsCount('css', '.field--name-body', 1);

    // Enable a disabled view mode.
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');
    $assert_session->addressEquals("$field_ui_prefix/display/teaser");
    $page->clickLink('Default');
    $assert_session->addressEquals("$field_ui_prefix/display/default");
    $page->pressButton('Save');

    $page->clickLink('Overview');
    $assert_session->addressEquals("$field_ui_prefix/display");
    // Verify that the "Full content" view mode is not enabled yet.
    $assert_session->elementNotExists('css', '#enabled-display-modes-wrapper #display-mode-node-bundle-with-section-field-full');

    // Enable the "Full content" view mode using the overview UI.
    $page = $this->getSession()->getPage();
    $enable_link = $page->find('xpath', "//tr[@id='display-mode-node-bundle-with-section-field-full']//a[contains(., 'Enable')]");
    $this->assertNotNull($enable_link, 'Enable link should exist for the full view mode.');
    $enable_link->click();
    // After enabling, the view mode should appear in the enabled table.
    $assert_session->elementExists('css', '#enabled-display-modes-wrapper #display-mode-node-bundle-with-section-field-full');

    // Enable Layout Builder for the full view mode using the UI.
    $display = LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.full');
    $this->enableLayoutBuilder($display);
    $this->drupalGet("$field_ui_prefix/display/full");
    $assert_session->linkExists('Manage layout');
    $page->clickLink('Manage layout');
    // The fields have all been hidden at this point.
    // Verify no Layout Builder blocks exist.
    $assert_session->addressEquals("$field_ui_prefix/display/full/layout");
    $assert_session->statusCodeEquals(200);
    $assert_session->elementsCount('css', '.layout-builder-block', 0);
  }

  /**
   * Tests the interaction between full and default view modes.
   *
   * @see \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage::getDefaultSectionStorage()
   */
  public function testLayoutBuilderUiFullViewMode(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // For the purposes of this test, turn the full view mode on and off on the
    // overview page to prevent copying from the customized default view mode.
    $this->drupalGet("{$field_ui_prefix}/display");
    $page = $this->getSession()->getPage();
    // Enable the "Full content" view mode if it is disabled.
    $enable_link = $page->find('xpath', "//tr[@id='display-mode-node-bundle-with-section-field-full']//a[contains(., 'Enable')]");
    $enable_link->click();
    $disable_link = $page->find('xpath', "//tr[@id='display-mode-node-bundle-with-section-field-full']//a[contains(., 'Disable')]");
    $disable_link->click();

    // Allow overrides for the layout.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    // Customize the default view mode.
    $this->drupalGet("$field_ui_prefix/display/default/layout");
    $this->clickLink('Add block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the default view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $assert_session->pageTextContains('This is the default view mode');
    $page->pressButton('Save layout');

    // The default view mode is used for both the node display and layout UI.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');

    // Enable the full view mode using the overview UI and customize it.
    $this->drupalGet("{$field_ui_prefix}/display");
    $page = $this->getSession()->getPage();
    $enable_link = $page->find('xpath', "//tr[@id='display-mode-node-bundle-with-section-field-full']//a[contains(., 'Enable')]");
    $this->assertNotNull($enable_link, 'Enable link should exist for the full view mode.');
    $enable_link->click();
    $this->drupalGet("{$field_ui_prefix}/display/full");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet("{$field_ui_prefix}/display/full");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet("$field_ui_prefix/display/full/layout");
    $this->clickLink('Add block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the full view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $assert_session->pageTextContains('This is the full view mode');
    $page->pressButton('Save layout');

    // The full view mode is now used for both the node display and layout UI.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');

    // Disable the full view mode using the overview UI, the default should be
    // used again.
    $this->drupalGet("{$field_ui_prefix}/display");
    $page = $this->getSession()->getPage();
    $disable_link = $page->find('xpath', "//tr[@id='display-mode-node-bundle-with-section-field-full']//a[contains(., 'Disable')]");
    $this->assertNotNull($disable_link, 'Disable link should exist for the full view mode.');
    $disable_link->click();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');

    // Re-enabling the full view mode using the overview UI restores the layout
    // changes.
    $this->drupalGet("{$field_ui_prefix}/display");
    $page = $this->getSession()->getPage();
    $enable_link = $page->find('xpath', "//tr[@id='display-mode-node-bundle-with-section-field-full']//a[contains(., 'Enable')]");
    $this->assertNotNull($enable_link, 'Enable link should exist for the full view mode.');
    $enable_link->click();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');

    // Create an override of the full view mode.
    $this->clickLink('Add block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is an override of the full view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $assert_session->pageTextContains('This is an override of the full view mode');
    $page->pressButton('Save layout');

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');

    // The override does not affect the full view mode.
    $this->drupalGet("$field_ui_prefix/display/full/layout");
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextNotContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');

    // Reverting the override restores back to the full view mode.
    $this->drupalGet('node/1/layout');
    $page->pressButton('Revert to default');
    $page->pressButton('Revert');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextNotContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextNotContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');

    // Recreate an override of the full view mode.
    $this->clickLink('Add block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is an override of the full view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $assert_session->pageTextContains('This is an override of the full view mode');
    $page->pressButton('Save layout');

    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');

    // Disable the full view mode using the overview UI.
    $this->drupalGet("{$field_ui_prefix}/display");
    $page = $this->getSession()->getPage();
    $disable_link = $page->find('xpath', "//tr[@id='display-mode-node-bundle-with-section-field-full']//a[contains(., 'Disable')]");
    $this->assertNotNull($disable_link, 'Disable link should exist for the full view mode.');
    $disable_link->click();

    // The override of the full view mode is still available.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the full view mode');
    $assert_session->pageTextContains('This is an override of the full view mode');
    $assert_session->pageTextNotContains('This is the default view mode');

    // Reverting the override restores back to the default view mode.
    $this->drupalGet('node/1/layout');
    $page->pressButton('Revert to default');
    $page->pressButton('Revert');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');
  }

  /**
   * Ensures that one bundle doesn't interfere with another bundle.
   */
  public function testFullViewModeMultipleBundles(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // Create one bundle with the full view mode enabled.
    $this->createContentType(['type' => 'full_bundle']);
    $this->drupalGet('admin/structure/types/manage/full_bundle/display');
    $page = $this->getSession()->getPage();
    $enable_link = $page->find('xpath', "//tr[@id='display-mode-node-full-bundle-full']//a[contains(., 'Enable')]");
    $this->assertNotNull($enable_link, 'Enable link should exist for the full view mode on full_bundle.');
    $enable_link->click();

    // Create another bundle without the full view mode enabled.
    $this->createContentType(['type' => 'default_bundle']);

    $this->drupalGet('admin/structure/types/manage/default_bundle/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet('admin/structure/types/manage/default_bundle/display/default');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('admin/structure/types/manage/default_bundle/display/default');
    $assert_session->checkboxChecked('layout[allow_custom]');
  }

}
