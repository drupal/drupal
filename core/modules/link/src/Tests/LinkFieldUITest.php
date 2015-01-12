<?php

/**
 * @file
 * Contains Drupal\link\Tests\LinkFieldUITest.
 */

namespace Drupal\link\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests link field UI functionality.
 *
 * @group link
 */
class LinkFieldUITest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'link', 'field_ui'];

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['administer content types', 'administer node fields', 'administer node display']));
  }

  /**
   * Tests that link field UI functionality does not generate warnings.
   */
  function testFieldUI() {
    // Add a content type.
    $type = $this->drupalCreateContentType();
    $type_path = 'admin/structure/types/manage/' . $type->id();

    // Add a link field to the newly-created type.
    $label = $this->randomMachineName();
    $field_name = Unicode::strtolower($label);
    $this->fieldUIAddNewField($type_path, $field_name, $label, 'link');

    // Load the formatter page to check that the settings summary does not
    // generate warnings.
    // @todo Mess with the formatter settings a bit here.
    $this->drupalGet("$type_path/display");
    $this->assertText(t('Link text trimmed to @limit characters', array('@limit' => 80)));
  }

}
