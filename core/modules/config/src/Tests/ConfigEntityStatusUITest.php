<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigEntityStatusUITest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration entity status UI functionality.
 *
 * @group config
 */
class ConfigEntityStatusUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  /**
   * Tests status operations.
   */
  function testCRUD() {
    $id = strtolower($this->randomMachineName());
    $edit = array(
      'id' => $id,
      'label' => $this->randomMachineName(),
    );
    $this->drupalPostForm('admin/structure/config_test/add', $edit, 'Save');

    $entity = entity_load('config_test', $id);

    // Disable an entity.
    $disable_path = $entity->getSystemPath('disable');
    $this->assertLinkByHref($disable_path);
    $this->drupalGet($disable_path);
    $this->assertResponse(200);
    $this->assertNoLinkByHref($disable_path);

    // Enable an entity.
    $enable_path = $entity->getSystemPath('enable');
    $this->assertLinkByHref($enable_path);
    $this->drupalGet($enable_path);
    $this->assertResponse(200);
    $this->assertNoLinkByHref($enable_path);
  }

}
