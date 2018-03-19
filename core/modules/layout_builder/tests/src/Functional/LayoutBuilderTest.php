<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 */
class LayoutBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_builder',
    'layout_test',
    'block',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    // Create two nodes.
    $this->createContentType(['type' => 'bundle_with_section_field']);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The second node title',
      'body' => [
        [
          'value' => 'The second node body',
        ],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testLayoutBuilderUi() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkNotExists('Layout');

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // From the manage display page, go to manage the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals("$field_ui_prefix/display-layout/default");
    // The body field is present.
    $assert_session->elementExists('css', '.field--name-body');

    // Add a new block.
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the label');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add Block');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the label');
    $assert_session->addressEquals("$field_ui_prefix/display-layout/default");

    // Save the defaults.
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->addressEquals("$field_ui_prefix/display/default");

    // The node uses the defaults, no overrides available.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->linkNotExists('Layout');

    // Enable overrides.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1');

    // Remove the section from the defaults.
    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $assert_session->linkExists('Remove section');
    $this->clickLink('Remove section');
    $page->pressButton('Remove');

    // Add a new section.
    $this->clickLink('Add Section');
    $assert_session->linkExists('Two column');
    $this->clickLink('Two column');
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');

    // Assert that overrides cannot be turned off while overrides exist.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->fieldDisabled('layout[allow_custom]');

    // Alter the defaults.
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('Title');
    $this->clickLink('Title');
    $page->pressButton('Add Block');
    // The title field is present.
    $assert_session->elementExists('css', '.field--name-title');
    $this->clickLink('Save Layout');

    // View the other node, which is still using the defaults.
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The second node title');
    $assert_session->pageTextContains('The second node body');
    $assert_session->pageTextContains('Powered by Drupal');

    // The overridden node does not pick up the changes to defaults.
    $this->drupalGet('node/1');
    $assert_session->elementNotExists('css', '.field--name-title');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkExists('Layout');

    // Reverting the override returns it to the defaults.
    $this->clickLink('Layout');
    $assert_session->linkExists('Revert to defaults');
    $this->clickLink('Revert to defaults');
    $page->pressButton('Revert');
    $assert_session->pageTextContains('The layout has been reverted back to defaults.');
    $assert_session->elementExists('css', '.field--name-title');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextContains('Powered by Drupal');

    // Assert that overrides can be turned off now that all overrides are gone.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => FALSE], 'Save');
    $this->drupalGet('node/1');
    $assert_session->linkNotExists('Layout');

    // Add a new field.
    $edit = [
      'new_storage_type' => 'string',
      'label' => 'My text field',
      'field_name' => 'my_text',
    ];
    $this->drupalPostForm("$field_ui_prefix/fields/add-field", $edit, 'Save and continue');
    $page->pressButton('Save field settings');
    $page->pressButton('Save settings');
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $assert_session->pageTextContains('My text field');
    $assert_session->elementExists('css', '.field--name-field-my-text');

    // Delete the field.
    $this->drupalPostForm("$field_ui_prefix/fields/node.bundle_with_section_field.field_my_text/delete", [], 'Delete');
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $assert_session->pageTextNotContains('My text field');
    $assert_session->elementNotExists('css', '.field--name-field-my-text');
  }

  /**
   * Tests that component's dependencies are respected during removal.
   */
  public function testPluginDependencies() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->container->get('module_installer')->install(['menu_ui']);
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer menu',
    ]));

    // Create a new menu.
    $this->drupalGet('admin/structure/menu/add');
    $page->fillField('label', 'My Menu');
    $page->fillField('id', 'mymenu');
    $page->pressButton('Save');
    $this->drupalGet('admin/structure/menu/add');
    $page->fillField('label', 'My Menu');
    $page->fillField('id', 'myothermenu');
    $page->pressButton('Save');

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display-layout/default');
    $assert_session->linkExists('Add Section');
    $this->clickLink('Add Section');
    $assert_session->linkExists('Layout plugin (with dependencies)');
    $this->clickLink('Layout plugin (with dependencies)');
    $assert_session->elementExists('css', '.layout--layout-test-dependencies-plugin');
    $assert_session->elementExists('css', '.field--name-body');
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $this->drupalPostForm('admin/structure/menu/manage/myothermenu/delete', [], 'Delete');
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display-layout/default');
    $assert_session->elementNotExists('css', '.layout--layout-test-dependencies-plugin');
    $assert_session->elementExists('css', '.field--name-body');

    // Add a menu block.
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('My Menu');
    $this->clickLink('My Menu');
    $page->pressButton('Add Block');

    // Add another block alongside the menu.
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->pressButton('Add Block');

    // Assert that the blocks are visible, and save the layout.
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('My Menu');
    $assert_session->elementExists('css', '.block.menu--mymenu');
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');

    // Delete the menu.
    $this->drupalPostForm('admin/structure/menu/manage/mymenu/delete', [], 'Delete');

    // Ensure that the menu block is gone, but that the other block remains.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display-layout/default');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextNotContains('My Menu');
    $assert_session->elementNotExists('css', '.block.menu--mymenu');
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
    // Allow overrides for the layout.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => TRUE], 'Save');

    // Customize the default view mode.
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $this->clickLink('Add Block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the default view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add Block');
    $assert_session->pageTextContains('This is the default view mode');
    $this->clickLink('Save Layout');

    // The default view mode is used for both the node display and layout UI.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $this->clickLink('Cancel Layout');

    // Enable the full view mode and customize it.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['display_modes_custom[full]' => TRUE], 'Save');
    $this->drupalGet("$field_ui_prefix/display-layout/full");
    $this->clickLink('Add Block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the full view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add Block');
    $assert_session->pageTextContains('This is the full view mode');
    $this->clickLink('Save Layout');

    // The full view mode is now used for both the node display and layout UI.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the full view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the full view mode');
    $this->clickLink('Cancel Layout');

    // Disable the full view mode, the default should be used again.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['display_modes_custom[full]' => FALSE], 'Save');
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $this->clickLink('Cancel Layout');
  }

}
