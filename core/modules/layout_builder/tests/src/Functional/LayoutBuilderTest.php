<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Section;
use Drupal\node\Entity\Node;
use Drupal\Tests\layout_builder\Traits\EnableLayoutBuilderTrait;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 * @group #slow
 */
class LayoutBuilderTest extends LayoutBuilderTestBase {

  use EnableLayoutBuilderTrait;

  /**
   * Tests the Layout Builder UI for an entity type without a bundle.
   */
  public function testNonBundleEntityType(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Log in as a user that can edit layout templates.
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer user display',
    ]));

    $this->drupalGet('admin/config/people/accounts/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    $page->clickLink('Manage layout');
    $assert_session->pageTextContains('You are editing the layout template for all users.');

    $this->drupalGet('user');
    $page->clickLink('Layout');
    $assert_session->pageTextContains('You are editing the layout for this user. Edit the template for all users instead.');

    // Log in as a user that cannot edit layout templates.
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ]));
    $this->drupalGet('user');
    $page->clickLink('Layout');
    $assert_session->pageTextContains('You are editing the layout for this user.');
    $assert_session->pageTextNotContains('Edit the template for all users instead.');
  }

  /**
   * Tests that the Layout Builder preserves entity values.
   */
  public function testPreserverEntityValues(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // From the manage display page, go to manage the layout.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    // @todo This should not be necessary.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node body');

    // Create a layout override which will store the current node in the
    // tempstore.
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $page->pressButton('Add block');

    // Update the node to make a change that is not in the tempstore version.
    $node = Node::load(1);
    $node->set('body', 'updated body');
    $node->save();

    $page->clickLink('View');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextContains('updated body');

    $page->clickLink('Layout');
    $page->pressButton('Save layout');

    // Ensure that saving the layout does not revert other field values.
    $assert_session->addressEquals('node/1');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextContains('updated body');
  }

  /**
   * {@inheritdoc}
   */
  public function testLayoutBuilderUi(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $this->drupalGet('node/1');
    $assert_session->elementNotExists('css', '.layout-builder-block');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkNotExists('Layout');

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // From the manage display page, go to manage the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->linkNotExists('Manage layout');
    $assert_session->fieldDisabled('layout[allow_custom]');

    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals("$field_ui_prefix/display/default/layout");
    $assert_session->elementTextContains('css', '.layout-builder__message.layout-builder__message--defaults', 'You are editing the layout template for all Bundle with section field content items.');
    // The body field is only present once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
    // The extra field is only present once.
    $assert_session->pageTextContainsOnce('Placeholder for the "Extra label" field');
    // Blocks have layout builder specific block class.
    $assert_session->elementExists('css', '.layout-builder-block');
    // Save the defaults.
    $page->pressButton('Save layout');
    $assert_session->addressEquals("$field_ui_prefix/display/default");

    // Load the default layouts again after saving to confirm fields are only
    // added on new layouts.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals("$field_ui_prefix/display/default/layout");
    // The body field is only present once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
    // The extra field is only present once.
    $assert_session->pageTextContainsOnce('Placeholder for the "Extra label" field');

    // Add a new block.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the label');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the label');
    $assert_session->addressEquals("$field_ui_prefix/display/default/layout");

    // Save the defaults.
    $page->pressButton('Save layout');
    $assert_session->pageTextContains('The layout has been saved.');
    $assert_session->addressEquals("$field_ui_prefix/display/default");

    // The node uses the defaults, no overrides available.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');
    $assert_session->linkNotExists('Layout');
    $assert_session->pageTextContains(sprintf('Yes, I can access the %s', Node::load(1)->label()));

    // Enable overrides.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1');

    // Remove the section from the defaults.
    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $assert_session->pageTextContains('Placeholder for the "Extra label" field');
    $assert_session->linkExists('Remove Section 1');
    $this->clickLink('Remove Section 1');
    $page->pressButton('Remove');

    // Add a new section.
    $this->clickLink('Add section');
    $this->assertCorrectLayouts();
    $assert_session->linkExists('Two column');
    $this->clickLink('Two column');
    $assert_session->buttonExists('Add section');
    $page->pressButton('Add section');
    $page->pressButton('Save');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->pageTextNotContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');
    $assert_session->pageTextContains(sprintf('Yes, I can access the entity %s in two column', Node::load(1)->label()));

    // Assert that overrides cannot be turned off while overrides exist.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->checkboxChecked('layout[allow_custom]');
    $assert_session->fieldDisabled('layout[allow_custom]');

    // Alter the defaults.
    $this->drupalGet("$field_ui_prefix/display/default/layout");
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->linkExists('Title');
    $this->clickLink('Title');
    $page->pressButton('Add block');
    // The title field is present.
    $assert_session->elementExists('css', '.field--name-title');
    $page->pressButton('Save layout');

    // View the other node, which is still using the defaults.
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The second node title');
    $assert_session->pageTextContains('The second node body');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');
    $assert_session->pageTextContains(sprintf('Yes, I can access the %s', Node::load(2)->label()));

    // The overridden node does not pick up the changes to defaults.
    $this->drupalGet('node/1');
    $assert_session->elementNotExists('css', '.field--name-title');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->pageTextNotContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');
    $assert_session->linkExists('Layout');

    // Reverting the override returns it to the defaults.
    $this->clickLink('Layout');
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->linkExists('ID');
    $this->clickLink('ID');
    $page->pressButton('Add block');
    // The title field is present.
    $assert_session->elementExists('css', '.field--name-nid');
    $assert_session->pageTextContains('ID');
    $assert_session->pageTextContains('1');
    $page->pressButton('Revert to defaults');
    $page->pressButton('Revert');
    $assert_session->addressEquals('node/1');
    $assert_session->pageTextContains('The layout has been reverted back to defaults.');
    $assert_session->elementExists('css', '.field--name-title');
    $assert_session->elementNotExists('css', '.field--name-nid');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains(sprintf('Yes, I can access the entity %s in two column', Node::load(1)->label()));
    $assert_session->pageTextContains(sprintf('Yes, I can access the %s', Node::load(1)->label()));

    // Assert that overrides can be turned off now that all overrides are gone.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => FALSE], 'Save');
    $this->drupalGet('node/1');
    $assert_session->linkNotExists('Layout');

    // Add a new field.
    $this->fieldUIAddNewField($field_ui_prefix, 'my_text', 'My text field', 'string');
    $this->drupalGet("$field_ui_prefix/display/default/layout");
    $assert_session->pageTextContains('My text field');
    $assert_session->elementExists('css', '.field--name-field-my-text');

    // Delete the field.
    $this->drupalGet("{$field_ui_prefix}/fields/node.bundle_with_section_field.field_my_text/delete");
    $this->submitForm([], 'Delete');
    $this->drupalGet("$field_ui_prefix/display/default/layout");
    $assert_session->pageTextNotContains('My text field');
    $assert_session->elementNotExists('css', '.field--name-field-my-text');

    $this->clickLink('Add section');
    $this->clickLink('One column');
    $page->fillField('layout_settings[label]', 'My Cool Section');
    $page->pressButton('Add section');

    $expected_labels = [
      'My Cool Section',
      'Content region in My Cool Section',
      'Section 2',
      'Content region in Section 2',
    ];
    $labels = [];
    foreach ($page->findAll('css', '[role="group"]') as $element) {
      $labels[] = $element->getAttribute('aria-label');
    }
    $this->assertSame($expected_labels, $labels);
  }

  /**
   * Test decorating controller.entity_form while layout_builder is installed.
   */
  public function testHtmlEntityFormControllerDecoration(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // Install module that decorates controller.entity_form.
    \Drupal::service('module_installer')->install(['layout_builder_decoration_test']);
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $assert_session->pageTextContains('Manage Display');
  }

  /**
   * Tests that layout builder checks entity view access.
   */
  public function testAccess(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Allow overrides for the layout.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    $this->drupalLogin($this->drupalCreateUser(['configure any layout']));
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $node = Node::load(1);
    $node->setUnpublished();
    $node->save();
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextContains('Access denied');

    $this->drupalGet('node/1/layout');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextContains('Access denied');
  }

  /**
   * Tests that component's dependencies are respected during removal.
   */
  public function testPluginDependencies(): void {
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
    $page->fillField('id', 'my-menu');
    $page->pressButton('Save');
    $this->drupalGet('admin/structure/menu/add');
    $page->fillField('label', 'My Menu');
    $page->fillField('id', 'my-other-menu');
    $page->pressButton('Save');

    $page->clickLink('Add link');
    $page->fillField('title[0][value]', 'My link');
    $page->fillField('link[0][uri]', '/');
    $page->pressButton('Save');

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->linkExists('Add section');
    $this->clickLink('Add section');
    $assert_session->linkExists('Layout plugin (with dependencies)');
    $this->clickLink('Layout plugin (with dependencies)');
    $page->pressButton('Add section');
    $assert_session->elementExists('css', '.layout--layout-test-dependencies-plugin');
    $assert_session->elementExists('css', '.field--name-body');
    $page->pressButton('Save layout');
    $this->drupalGet('admin/structure/menu/manage/my-other-menu/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $assert_session->elementNotExists('css', '.layout--layout-test-dependencies-plugin');
    $assert_session->elementExists('css', '.field--name-body');

    // Add a menu block.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->linkExists('My Menu');
    $this->clickLink('My Menu');
    $page->pressButton('Add block');

    // Add another block alongside the menu.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->pressButton('Add block');

    // Assert that the blocks are visible, and save the layout.
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('My Menu');
    $assert_session->elementExists('css', '.block.menu--my-menu');
    $page->pressButton('Save layout');

    // Delete the menu.
    $this->drupalGet('admin/structure/menu/manage/my-menu/delete');
    $this->submitForm([], 'Delete');

    // Ensure that the menu block is gone, but that the other block remains.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextNotContains('My Menu');
    $assert_session->elementNotExists('css', '.block.menu--my-menu');
  }

  /**
   * Tests preview-aware templates.
   */
  public function testPreviewAwareTemplates(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $page->clickLink('Manage layout');
    $page->clickLink('Add section');
    $page->clickLink('1 column layout');
    $page->pressButton('Add section');
    $page->clickLink('Add block');
    $page->clickLink('Preview-aware block');
    $page->pressButton('Add block');

    $assert_session->pageTextContains('This is a preview, indeed');

    $page->pressButton('Save layout');
    $this->drupalGet('node/1');

    $assert_session->pageTextNotContains('This is a preview, indeed');
  }

  /**
   * Tests that extra fields work before and after enabling Layout Builder.
   */
  public function testExtraFields(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $this->drupalGet('node');
    $assert_session->linkExists('Read more');

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');

    // Extra fields display under "Content fields".
    $this->drupalGet("admin/structure/types/manage/bundle_with_section_field/display/default/layout");
    $this->clickLink('Add block');
    $assert_session->elementTextContains('xpath', '//details/summary[contains(text(),"Content fields")]/parent::details', 'Extra label');

    $this->drupalGet('node');
    $assert_session->linkExists('Read more');

    // Consider an extra field hidden by default. Make sure it's not displayed.
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('Extra Field 2 is hidden by default.');

    // View the layout and add the extra field that is not visible by default.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $assert_session->pageTextNotContains('Extra Field 2');
    $page = $this->getSession()->getPage();
    $page->clickLink('Add block');
    $page->clickLink('Extra Field 2');
    $page->pressButton('Add block');
    $assert_session->pageTextContains('Extra Field 2');
    $page->pressButton('Save layout');

    // Confirm that the newly added extra field is visible.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('Extra Field 2 is hidden by default.');
  }

  /**
   * Tests loading a pending revision in the Layout Builder UI.
   */
  public function testPendingRevision(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Enable overrides.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node = $storage->load(1);
    // Create a pending revision.
    $pending_revision = $storage->createRevision($node, FALSE);
    $pending_revision->set('title', 'The pending title of the first node');
    $pending_revision->save();

    // The original node title is available when viewing the node, but the
    // pending title is visible within the Layout Builder UI.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node title');
    $page->clickLink('Layout');
    $assert_session->pageTextNotContains('The first node title');
    $assert_session->pageTextContains('The pending title of the first node');
  }

  /**
   * Tests that hook_form_alter() has access to the Layout Builder info.
   */
  public function testFormAlter(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $display = LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default');
    $this->enableLayoutBuilder($display);
    $this->drupalGet("$field_ui_prefix/display/default");

    $page->clickLink('Manage layout');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $assert_session->pageTextContains('Layout Builder Storage: node.bundle_with_section_field.default');
    $assert_session->pageTextContains('Layout Builder Section: layout_onecol');
    $assert_session->pageTextContains('Layout Builder Component: system_powered_by_block');

    $this->drupalGet("$field_ui_prefix/display/default");
    $page->clickLink('Manage layout');
    $page->clickLink('Add section');
    $page->clickLink('One column');
    $assert_session->pageTextContains('Layout Builder Storage: node.bundle_with_section_field.default');
    $assert_session->pageTextContains('Layout Builder Section: layout_onecol');
    $assert_session->pageTextContains('Layout Builder Layout: layout_onecol');
  }

  /**
   * Tests the functionality of custom section labels.
   */
  public function testSectionLabels(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $display = LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default');
    $this->enableLayoutBuilder($display);

    $this->drupalGet('node/1/layout');
    $page->clickLink('Add section');
    $page->clickLink('One column');
    $page->fillField('layout_settings[label]', 'My Cool Section');
    $page->pressButton('Add section');
    $assert_session->pageTextContains('My Cool Section');
    $page->pressButton('Save layout');
    $assert_session->pageTextNotContains('My Cool Section');
  }

  /**
   * Tests that layouts can be context-aware.
   */
  public function testContextAwareLayouts(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $page->clickLink('Manage layout');
    $page->clickLink('Add section');
    $page->clickLink('Layout Builder Test: Context Aware');
    $page->pressButton('Add section');
    // See \Drupal\layout_builder_test\Plugin\Layout\TestContextAwareLayout::build().
    $assert_session->elementExists('css', '.user--' . $account->getAccountName());
    $page->clickLink('Configure Section 1');
    $page->fillField('layout_settings[label]', 'My section');
    $page->pressButton('Update');
    $assert_session->linkExists('Configure My section');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');
    $this->drupalGet('node/1');
    // See \Drupal\layout_builder_test\Plugin\Layout\TestContextAwareLayout::build().
    $assert_session->elementExists('css', '.user--' . $account->getAccountName());
  }

  /**
   * Tests that sections can provide custom attributes.
   */
  public function testCustomSectionAttributes(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $page->clickLink('Manage layout');
    $page->clickLink('Add section');
    $page->clickLink('Layout Builder Test Plugin');
    $page->pressButton('Add section');
    // See \Drupal\layout_builder_test\Plugin\Layout\LayoutBuilderTestPlugin::build().
    $assert_session->elementExists('css', '.go-birds');
  }

  /**
   * Tests the expected breadcrumbs of the Layout Builder UI.
   */
  public function testBreadcrumb(): void {
    $page = $this->getSession()->getPage();

    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer content types',
      'access administration pages',
    ]));

    // From the manage display page, go to manage the layout.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $page->clickLink('Manage layout');

    $breadcrumb_titles = [];
    foreach ($page->findAll('css', '.breadcrumb a') as $link) {
      $breadcrumb_titles[$link->getText()] = $link->getAttribute('href');
    }
    $base_path = base_path();
    $expected = [
      'Home' => $base_path,
      'Administration' => $base_path . 'admin',
      'Structure' => $base_path . 'admin/structure',
      'Content types' => $base_path . 'admin/structure/types',
      'Bundle with section field' => $base_path . 'admin/structure/types/manage/bundle_with_section_field',
      'Manage display' => $base_path . 'admin/structure/types/manage/bundle_with_section_field/display/default',
      'External link' => 'http://www.example.com',
    ];
    $this->assertSame($expected, $breadcrumb_titles);
  }

  /**
   * Tests a config-based implementation of Layout Builder.
   *
   * @see \Drupal\layout_builder_test\Plugin\SectionStorage\SimpleConfigSectionStorage
   */
  public function testSimpleConfigBasedLayout(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->createUser(['configure any layout']));

    // Prepare an object with a pre-existing section.
    $this->container->get('config.factory')->getEditable('layout_builder_test.test_simple_config.existing')
      ->set('sections', [(new Section('layout_twocol'))->toArray()])
      // `layout_builder_test.test_simple_config.existing.sections.0.layout_settings.label`
      // contains a translatable label, so a `langcode` is required.
      // @see \Drupal\Core\Config\Plugin\Validation\Constraint\LangcodeRequiredIfTranslatableValuesConstraint
      ->set('langcode', 'en')
      ->save();

    // The pre-existing section is found.
    $this->drupalGet('layout-builder-test-simple-config/existing');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->elementsCount('css', '.layout--twocol', 1);

    // No layout is selected for a new object.
    $this->drupalGet('layout-builder-test-simple-config/new');
    $assert_session->elementNotExists('css', '.layout');
  }

  /**
   * Tests removing section without layout label configuration.
   */
  public function testRemovingSectionWithoutLayoutLabel(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // Enable overrides.
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("$field_ui_prefix/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    $this->drupalGet("$field_ui_prefix/display/default/layout");
    $page->clickLink('Add section');

    $assert_session->linkExists('Layout Without Label');
    $page->clickLink('Layout Without Label');
    $page->pressButton('Add section');
    $assert_session->elementsCount('css', '.layout', 2);

    $assert_session->linkExists('Remove Section 1');
    $this->clickLink('Remove Section 1');
    $page->pressButton('Remove');

    $assert_session->statusCodeEquals(200);
    $assert_session->elementsCount('css', '.layout', 1);
  }

  /**
   * Asserts that the correct layouts are available.
   *
   * @internal
   */
  protected function assertCorrectLayouts(): void {
    $assert_session = $this->assertSession();
    // Ensure the layouts provided by layout_builder are available.
    $expected_layouts_hrefs = [
      'layout_builder/configure/section/overrides/node.1/0/layout_onecol',
      'layout_builder/configure/section/overrides/node.1/0/layout_twocol_section',
      'layout_builder/configure/section/overrides/node.1/0/layout_threecol_section',
      'layout_builder/configure/section/overrides/node.1/0/layout_fourcol_section',
    ];
    foreach ($expected_layouts_hrefs as $expected_layouts_href) {
      $assert_session->linkByHrefExists($expected_layouts_href);
    }
    // Ensure the layout_discovery module's layouts were removed.
    $unexpected_layouts = [
      'twocol',
      'twocol_bricks',
      'threecol_25_50_25',
      'threecol_33_34_33',
    ];
    foreach ($unexpected_layouts as $unexpected_layout) {
      $assert_session->linkByHrefNotExists("layout_builder/add/section/overrides/node.1/0/$unexpected_layout");
      $assert_session->linkByHrefNotExists("layout_builder/configure/section/overrides/node.1/0/$unexpected_layout");
    }
  }

  /**
   * Tests the Layout Builder UI with a context defined at runtime.
   */
  public function testLayoutBuilderContexts(): void {
    $node_url = 'node/1';

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("$field_ui_prefix/display/default");
    $this->submitForm([
      'layout[enabled]' => TRUE,
    ], 'Save');

    $this->drupalGet("$field_ui_prefix/display/default");
    $this->submitForm([
      'layout[allow_custom]' => TRUE,
    ], 'Save');

    $this->drupalGet($node_url);
    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $assert_session->linkExists('Add section');

    // Add the testing block.
    $page->clickLink('Add block');
    $this->clickLink('Can I have runtime contexts');
    $page->pressButton('Add block');

    // Ensure the runtime context value is rendered before saving.
    $assert_session->pageTextContains('for sure you can');

    // Save the layout, and test that the value is rendered after save.
    $page->pressButton('Save layout');
    $assert_session->addressEquals($node_url);
    $assert_session->pageTextContains('for sure you can');
    $assert_session->elementExists('css', '.layout');
  }

}
