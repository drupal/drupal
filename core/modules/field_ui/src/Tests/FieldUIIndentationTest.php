<?php

/**
 * @file
 * Contains \Drupal\field_ui\Tests\FieldUIIndentationTest.
 */

namespace Drupal\field_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests indentation on Field UI.
 *
 * @group field_ui
 */
class FieldUIIndentationTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'field_ui_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer node display'));
    $this->drupalLogin($admin_user);

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

  }

  function testIndentation() {
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertRaw('js-indentation indentation');
  }
}
