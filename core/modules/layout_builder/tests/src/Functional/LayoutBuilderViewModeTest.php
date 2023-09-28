<?php

namespace Drupal\Tests\layout_builder\Functional;

/**
 * Tests the Layout Builder UI with view modes.
 *
 * @group layout_builder
 * @group #slow
 */
class LayoutBuilderViewModeTest extends LayoutBuilderTestBase {

  /**
   * Tests that a non-default view mode works as expected.
   */
  public function testNonDefaultViewMode() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Allow overrides for the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

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
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $assert_session->linkExists('Manage layout');
    $page->clickLink('Manage layout');
    // Confirm the body field only is shown once.
    $assert_session->elementsCount('css', '.field--name-body', 1);

    // Enable a disabled view mode.
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');
    $assert_session->addressEquals("$field_ui_prefix/display/teaser");
    $page->clickLink('Default');
    $assert_session->addressEquals("$field_ui_prefix/display");
    $assert_session->linkNotExists('Full content');
    $page->checkField('display_modes_custom[full]');
    $page->pressButton('Save');

    $assert_session->linkExists('Full content');
    $page->clickLink('Full content');
    $assert_session->addressEquals("$field_ui_prefix/display/full");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $assert_session->linkExists('Manage layout');
    $page->clickLink('Manage layout');
    // Confirm the body field only is shown once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
  }

  /**
   * Tests the interaction between full and default view modes.
   *
   * @see \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage::getDefaultSectionStorage()
   */
  public function testLayoutBuilderUiFullViewMode() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // For the purposes of this test, turn the full view mode on and off to
    // prevent copying from the customized default view mode.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['display_modes_custom[full]' => TRUE], 'Save');
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['display_modes_custom[full]' => FALSE], 'Save');

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

    // Enable the full view mode and customize it.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['display_modes_custom[full]' => TRUE], 'Save');
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

    // Disable the full view mode, the default should be used again.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['display_modes_custom[full]' => FALSE], 'Save');
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $assert_session->pageTextNotContains('This is the full view mode');
    $page->pressButton('Discard changes');
    $page->pressButton('Confirm');

    // Re-enabling the full view mode restores the layout changes.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['display_modes_custom[full]' => TRUE], 'Save');
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

    // Disable the full view mode.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['display_modes_custom[full]' => FALSE], 'Save');

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
  public function testFullViewModeMultipleBundles() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // Create one bundle with the full view mode enabled.
    $this->createContentType(['type' => 'full_bundle']);
    $this->drupalGet('admin/structure/types/manage/full_bundle/display/default');
    $page->checkField('display_modes_custom[full]');
    $page->pressButton('Save');

    // Create another bundle without the full view mode enabled.
    $this->createContentType(['type' => 'default_bundle']);
    $this->drupalGet('admin/structure/types/manage/default_bundle/display/default');

    // Enable Layout Builder for defaults and overrides.
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');
    $assert_session->checkboxChecked('layout[allow_custom]');
  }

}
