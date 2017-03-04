<?php

namespace Drupal\Tests\action\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test behaviors when visiting the action listing page.
 *
 * @group action
 */
class ActionListTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['action'];

  /**
   * Tests the behavior when there are no actions to list in the admin page.
   */
  public function testEmptyActionList() {
    // Create a user with permission to view the actions administration pages.
    $this->drupalLogin($this->drupalCreateUser(['administer actions']));

    // Ensure the empty text appears on the action list page.
    /** @var $storage \Drupal\Core\Entity\EntityStorageInterface */
    $storage = $this->container->get('entity.manager')->getStorage('action');
    $actions  = $storage->loadMultiple();
    $storage->delete($actions);
    $this->drupalGet('/admin/config/system/actions');
    $this->assertRaw('There is no Action yet.');
  }

}
