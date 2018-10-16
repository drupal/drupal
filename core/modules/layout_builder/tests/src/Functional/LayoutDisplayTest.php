<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of the entity view display with regard to Layout Builder.
 *
 * @group layout_builder
 */
class LayoutDisplayTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui', 'layout_builder', 'block', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    $this->createContentType([
      'type' => 'bundle_with_section_field',
    ]);
    $this->createNode(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer display modes',
    ], 'foobar'));
  }

  /**
   * Tests the interaction between multiple view modes.
   */
  public function testMultipleViewModes() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field/display';

    // Enable Layout Builder for the default view modes, and overrides.
    $this->drupalGet("$field_ui_prefix/default");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('Powered by Drupal');

    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->pressButton('Add Block');
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->pageTextContains('Powered by Drupal');

    // Add a new view mode.
    $this->drupalGet('admin/structure/display-modes/view/add/node');
    $page->fillField('label', 'New');
    $page->fillField('id', 'new');
    $page->pressButton('Save');

    // Enable the new view mode.
    $this->drupalGet("$field_ui_prefix/default");
    $page->checkField('display_modes_custom[new]');
    $page->pressButton('Save');

    // Enable and disable Layout Builder for the new view mode.
    $this->drupalGet("$field_ui_prefix/new");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->uncheckField('layout[enabled]');
    $page->pressButton('Save');
    $page->pressButton('Confirm');

    // The node using the default view mode still contains its overrides.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('Powered by Drupal');
  }

}
