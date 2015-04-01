<?php

/**
 * @file
 * Contains Drupal\link\Tests\LinkFieldUITest.
 */

namespace Drupal\link\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\link\LinkItemInterface;
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
  public static $modules = ['node', 'link', 'field_ui', 'block'];

  /**
   * A user that can edit content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer content types', 'administer node fields', 'administer node display']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the link field UI.
   */
  function testFieldUI() {
    // Add a content type.
    $type = $this->drupalCreateContentType();
    $type_path = 'admin/structure/types/manage/' . $type->id();
    $add_path = 'node/add/' . $type->id();

    // Add a link field to the newly-created type. It defaults to allowing both
    // internal and external links.
    $label = $this->randomMachineName();
    $field_name = Unicode::strtolower($label);
    $this->fieldUIAddNewField($type_path, $field_name, $label, 'link');

    // Load the formatter page to check that the settings summary does not
    // generate warnings.
    // @todo Mess with the formatter settings a bit here.
    $this->drupalGet("$type_path/display");
    $this->assertText(t('Link text trimmed to @limit characters', array('@limit' => 80)));

    // Test the help text displays when the link field allows both internal and
    // external links.
    $this->drupalLogin($this->drupalCreateUser(['create ' . $type->id() . ' content']));
    $this->drupalGet($add_path);
    $this->assertRaw('You can also enter an internal path such as <em class="placeholder">/node/add</em> or an external URL such as <em class="placeholder">http://example.com</em>.');

    // Log in an admin to set up the next content type.
    $this->drupalLogin($this->adminUser);

    // Add a different content type.
    $type = $this->drupalCreateContentType();
    $type_path = 'admin/structure/types/manage/' . $type->id();
    $add_path = 'node/add/' . $type->id();

    // Add a link field to the newly-created type. Specify it must allow
    // external only links.
    $label = $this->randomMachineName();
    $field_name = Unicode::strtolower($label);
    $field_edit = ['settings[link_type]' => LinkItemInterface::LINK_EXTERNAL];
    $this->fieldUIAddNewField($type_path, $field_name, $label, 'link', array(), $field_edit);

    // Test the help text displays when link allows only external links.
    $this->drupalLogin($this->drupalCreateUser(['create ' . $type->id() . ' content']));
    $this->drupalGet($add_path);
    $this->assertRaw('This must be an external URL such as <em class="placeholder">http://example.com</em>.');
  }

}
