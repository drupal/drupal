<?php

namespace Drupal\path\Tests;

/**
 * Tests the Path Node form UI.
 *
 * @group path
 */
class PathNodeFormTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'path');

  protected function setUp() {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser(array('create page content', 'create url aliases'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the node form ui.
   */
  public function testNodeForm() {
    $this->drupalGet('node/add/page');

    // Make sure we have a Path fieldset and Path fields.
    $this->assertRaw(' id="edit-path-settings"', 'Path settings details exists');
    $this->assertFieldByName('path[0][alias]', NULL, 'Path alias field exists');

    // Disable the Path field for this content type.
    entity_get_form_display('node', 'page', 'default')
      ->removeComponent('path')
      ->save();

    $this->drupalGet('node/add/page');

    // See if the whole fieldset is gone now.
    $this->assertNoRaw(' id="edit-path-settings"', 'Path settings details does not exist');
    $this->assertNoFieldByName('path[0][alias]', NULL, 'Path alias field does not exist');
  }

}
