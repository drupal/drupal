<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigEntityTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration entities.
 */
class ConfigEntityTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration entities',
      'description' => 'Tests configuration entities.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests basic CRUD operations through the UI.
   */
  function testCRUD() {
    $id = strtolower($this->randomName());
    $label1 = $this->randomName();
    $label2 = $this->randomName();
    $label3 = $this->randomName();

    // Create a configuration entity.
    $edit = array(
      'id' => $id,
      'label' => $label1,
    );
    $this->drupalPost('admin/structure/config_test/add', $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText($label1);

    // Update the configuration entity.
    $this->assertLinkByHref('admin/structure/config_test/manage/' . $id);
    $edit = array(
      'label' => $label2,
    );
    $this->drupalPost('admin/structure/config_test/manage/' . $id, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertNoText($label1);
    $this->assertText($label2);

    // Delete the configuration entity.
    $this->assertLinkByHref('admin/structure/config_test/manage/' . $id . '/delete');
    $this->drupalPost('admin/structure/config_test/manage/' . $id . '/delete', array(), 'Delete');
    $this->assertResponse(200);
    $this->assertNoText($label1);
    $this->assertNoText($label2);

    // Re-create a configuration entity.
    $edit = array(
      'id' => $id,
      'label' => $label1,
    );
    $this->drupalPost('admin/structure/config_test/add', $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText($label1);

    // Rename the configuration entity's ID/machine name.
    $this->assertLinkByHref('admin/structure/config_test/manage/' . $id);
    $edit = array(
      'id' => strtolower($this->randomName()),
      'label' => $label3,
    );
    $this->drupalPost('admin/structure/config_test/manage/' . $id, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertNoText($label1);
    $this->assertText($label3);
  }

}
