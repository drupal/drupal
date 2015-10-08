<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigEntityStatusUITest.
 */

namespace Drupal\config\Tests;

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
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    $id = strtolower($this->randomMachineName());
    $edit = array(
      'id' => $id,
      'label' => $this->randomMachineName(),
    );
    $this->drupalPostForm('admin/structure/config_test/add', $edit, 'Save');

    $entity = entity_load('config_test', $id);

    // Disable an entity.
    $disable_url = $entity->urlInfo('disable');
    $this->assertLinkByHref($disable_url->toString());
    $this->drupalGet($disable_url);
    $this->assertResponse(200);
    $this->assertNoLinkByHref($disable_url->toString());

    // Enable an entity.
    $enable_url = $entity->urlInfo('enable');
    $this->assertLinkByHref($enable_url->toString());
    $this->drupalGet($enable_url);
    $this->assertResponse(200);
    $this->assertNoLinkByHref($enable_url->toString());
  }

}
