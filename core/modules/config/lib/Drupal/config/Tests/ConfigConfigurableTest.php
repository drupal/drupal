<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigConfigurableTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests configurable entities.
 */
class ConfigConfigurableTest extends WebTestBase {

  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configurable entities',
      'description' => 'Tests configurable entities.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests basic CRUD operations through the UI.
   */
  function testCRUD() {
    // Create a configurable entity.
    $id = 'thingie';
    $edit = array(
      'id' => $id,
      'label' => 'Thingie',
    );
    $this->drupalPost('admin/structure/config_test/add', $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText('Thingie');

    // Update the configurable entity.
    $this->assertLinkByHref('admin/structure/config_test/manage/' . $id);
    $edit = array(
      'label' => 'Thongie',
    );
    $this->drupalPost('admin/structure/config_test/manage/' . $id, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertNoText('Thingie');
    $this->assertText('Thongie');

    // Delete the configurable entity.
    $this->assertLinkByHref('admin/structure/config_test/manage/' . $id . '/delete');
    $this->drupalPost('admin/structure/config_test/manage/' . $id . '/delete', array(), 'Delete');
    $this->assertResponse(200);
    $this->assertNoText('Thingie');
    $this->assertNoText('Thongie');

    // Re-create a configurable entity.
    $edit = array(
      'id' => $id,
      'label' => 'Thingie',
    );
    $this->drupalPost('admin/structure/config_test/add', $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText('Thingie');

    // Rename the configurable entity's ID/machine name.
    $this->assertLinkByHref('admin/structure/config_test/manage/' . $id);
    $new_id = 'zingie';
    $edit = array(
      'id' => $new_id,
      'label' => 'Zingie',
    );
    $this->drupalPost('admin/structure/config_test/manage/' . $id, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertNoText('Thingie');
    $this->assertText('Zingie');
  }
}
