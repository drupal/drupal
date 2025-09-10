<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\layout_builder\Traits\EnableLayoutBuilderTrait;

/**
 * Test adding new fields to layout builder enabled bundles.
 *
 * @group layout_builder
 */
class LayoutBuilderNewFieldsTest extends LayoutBuilderTestBase {

  use EnableLayoutBuilderTrait;

  /**
   * Tests new fields are not added to the Layout Builder display.
   */
  public function testNewFieldsNotAddedToLayoutBuilder(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));
    $assert_session = $this->assertSession();
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $display = LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default');
    $this->enableLayoutBuilder($display);
    // Add a new field with default text.
    $field_edit = [
      'set_default_value' => '1',
      'default_value_input[field_should_not_exist][0][value]' => 'Not added by default',
    ];
    $this->fieldUIAddNewField($field_ui_prefix, 'should_not_exist', 'Not added by default', 'string', field_edit: $field_edit);
    $this->drupalGet("$field_ui_prefix/display/default/layout");
    // Assert the default text does not display.
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('Not added by default');
    $this->getSession()->getPage()->pressButton('Discard changes');
    $this->getSession()->getPage()->pressButton('Confirm');
    // Enable the module that adds new fields to the Layout Builder display.
    \Drupal::service('module_installer')->install(['layout_builder_add_new_fields_to_layout']);
    // Add a new field with default text.
    $field_edit = [
      'set_default_value' => '1',
      'default_value_input[field_should_exist][0][value]' => 'Added by default',
    ];
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->fieldUIAddNewField($field_ui_prefix, 'should_exist', 'Added by default', 'string', field_edit: $field_edit);
    $this->drupalGet("$field_ui_prefix/display/default/layout");
    // Assert the default text displays.
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('Not added by default');
    $assert_session->pageTextContains('Added by default');
  }

}
