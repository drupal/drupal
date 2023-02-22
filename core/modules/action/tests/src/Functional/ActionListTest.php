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
  protected static $modules = ['action', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the behavior when there are no actions to list in the admin page.
   */
  public function testEmptyActionList() {
    // Create a user with permission to view the actions administration pages.
    $this->drupalLogin($this->drupalCreateUser(['administer actions']));

    // Ensure the empty text appears on the action list page.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('action');
    $actions = $storage->loadMultiple();
    $storage->delete($actions);
    $this->drupalGet('/admin/config/system/actions');
    $this->assertSession()->pageTextContains('There are no actions yet.');
  }

  /**
   * Tests that non-configurable actions can be created by the UI.
   */
  public function testNonConfigurableActionsCanBeCreated() {
    $this->drupalLogin($this->drupalCreateUser(['administer actions']));
    $this->drupalGet('/admin/config/system/actions');
    $this->assertSession()->elementExists('css', 'select > option[value="user_block_user_action"]');
  }

}
