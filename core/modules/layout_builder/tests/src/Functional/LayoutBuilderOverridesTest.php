<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\node\Entity\Node;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 * @group #slow
 */
class LayoutBuilderOverridesTest extends LayoutBuilderTestBase {

  /**
   * Tests deleting a field in-use by an overridden layout.
   */
  public function testDeleteField() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node fields',
    ]));

    // Enable layout builder overrides.
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    // Ensure there is a layout override.
    $this->drupalGet('node/1/layout');
    $page->pressButton('Save layout');

    // Delete one of the fields in use.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/fields/node.bundle_with_section_field.body/delete');
    $page->pressButton('Delete');

    // The node should still be accessible.
    $this->drupalGet('node/1');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('node/1/layout');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests Layout Builder overrides without access to edit the default layout.
   */
  public function testOverridesWithoutDefaultsAccess() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser(['configure any layout']));

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalGet('node/1');
    $page->clickLink('Layout');
    $assert_session->elementTextContains('css', '.layout-builder__message.layout-builder__message--overrides', 'You are editing the layout for this Bundle with section field content item.');
    $assert_session->linkNotExists('Edit the template for all Bundle with section field content items instead.');
  }

  /**
   * Tests Layout Builder overrides without Field UI installed.
   */
  public function testOverridesWithoutFieldUi() {
    $this->container->get('module_installer')->uninstall(['field_ui']);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // @todo In https://www.drupal.org/node/540008 switch this to logging in as
    //   a user with the 'configure any layout' permission.
    $this->drupalLogin($this->rootUser);

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalGet('node/1');
    $page->clickLink('Layout');
    $assert_session->elementTextContains('css', '.layout-builder__message.layout-builder__message--overrides', 'You are editing the layout for this Bundle with section field content item.');
    $assert_session->linkNotExists('Edit the template for all Bundle with section field content items instead.');
  }

  /**
   * Tests functionality of Layout Builder for overrides.
   */
  public function testOverrides() {
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

    // Add a block with a custom label.
    $this->drupalGet('node/1');
    $page->clickLink('Layout');
    // The layout form should not contain fields for the title of the node by
    // default.
    $assert_session->fieldNotExists('title[0][value]');
    $assert_session->elementTextContains('css', '.layout-builder__message.layout-builder__message--overrides', 'You are editing the layout for this Bundle with section field content item. Edit the template for all Bundle with section field content items instead.');
    $assert_session->linkExists('Edit the template for all Bundle with section field content items instead.');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is an override');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');
    $assert_session->pageTextContains('This is an override');

    // Get the UUID of the component.
    $components = Node::load(1)->get('layout_builder__layout')->getSection(0)->getComponents();
    end($components);
    $uuid = key($components);

    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $page->uncheckField('settings[label_display]');
    $page->pressButton('Update');
    $assert_session->pageTextNotContains('This is an override');
    $page->pressButton('Save layout');
    $assert_session->pageTextNotContains('This is an override');
  }

  /**
   * Tests a custom alter of the overrides form.
   */
  public function testOverridesFormAlter() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer nodes',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Enable overrides.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1');

    // The status checkbox should be checked by default.
    $page->clickLink('Layout');
    $assert_session->checkboxChecked('status[value]');
    $page->pressButton('Save layout');
    $assert_session->pageTextContains('The layout override has been saved.');

    // Unchecking the status checkbox will unpublish the entity.
    $page->clickLink('Layout');
    $page->uncheckField('status[value]');
    $page->pressButton('Save layout');
    $assert_session->statusCodeEquals(403);
    $assert_session->pageTextContains('The layout override has been saved.');
  }

  /**
   * Tests removing all sections from overrides and defaults.
   */
  public function testRemovingAllSections() {
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

    // By default, there is one section.
    $this->drupalGet('node/1');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->pageTextContains('The first node body');

    $page->clickLink('Layout');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 2);

    // Remove the only section from the override.
    $page->clickLink('Remove Section 1');
    $page->pressButton('Remove');
    $assert_session->elementsCount('css', '.layout', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 1);

    // The override is still used instead of the default, despite being empty.
    $page->pressButton('Save layout');
    $assert_session->elementsCount('css', '.layout', 0);
    $assert_session->pageTextNotContains('The first node body');

    $page->clickLink('Layout');
    $assert_session->elementsCount('css', '.layout', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 1);

    // Add one section to the override.
    $page->clickLink('Add section');
    $page->clickLink('One column');
    $page->pressButton('Add section');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 2);

    $page->pressButton('Save layout');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->pageTextNotContains('The first node body');

    // By default, the default has one section.
    $this->drupalGet("$field_ui_prefix/display/default/layout");
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 2);

    // Remove the only section from the default.
    $page->clickLink('Remove Section 1');
    $page->pressButton('Remove');
    $assert_session->elementsCount('css', '.layout', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 1);

    $page->pressButton('Save layout');
    $page->clickLink('Manage layout');
    $assert_session->elementsCount('css', '.layout', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 0);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 1);

    // The override is still in use.
    $this->drupalGet('node/1');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->pageTextNotContains('The first node body');
    $page->clickLink('Layout');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-block', 1);
    $assert_session->elementsCount('css', '.layout-builder__add-section', 2);

    // Revert the override.
    $page->pressButton('Revert to defaults');
    $page->pressButton('Revert');
    $assert_session->elementsCount('css', '.layout', 0);
    $assert_session->pageTextNotContains('The first node body');
  }

}
