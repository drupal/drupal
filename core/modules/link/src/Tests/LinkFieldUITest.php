<?php

/**
 * @file
 * Contains Drupal\link\Tests\LinkFieldUITest.
 */

namespace Drupal\link\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Tests link field UI functionality.
 *
 * @group link
 */
class LinkFieldUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'link', 'field_ui');

  protected function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('administer content types', 'administer node fields', 'administer node display'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests that link field UI functionality does not generate warnings.
   */
  function testFieldUI() {
    // Add a content type.
    $type = $this->drupalCreateContentType();
    $type_path = 'admin/structure/types/manage/' . $type->type;

    // Add a link field to the newly-created type.
    $label = $this->randomMachineName();
    $field_name = Unicode::strtolower($label);
    $edit = array(
      'fields[_add_new_field][label]' => $label,
      'fields[_add_new_field][field_name]' => $field_name,
      'fields[_add_new_field][type]' => 'link',
    );
    $this->drupalPostForm("$type_path/fields", $edit, t('Save'));
    // Proceed to the Edit (field settings) page.
    $this->drupalPostForm(NULL, array(), t('Save field settings'));
    // Proceed to the Manage fields overview page.
    $this->drupalPostForm(NULL, array(), t('Save settings'));

    // Load the formatter page to check that the settings summary does not
    // generate warnings.
    // @todo Mess with the formatter settings a bit here.
    $this->drupalGet("$type_path/display");
    $this->assertText(t('Link text trimmed to @limit characters', array('@limit' => 80)));
  }

}
