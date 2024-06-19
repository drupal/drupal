<?php

declare(strict_types=1);

namespace Drupal\Tests\action\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that uninstalling Actions UI does not remove other modules' actions.
 *
 * @group action
 * @group legacy
 * @see \Drupal\views\Plugin\views\field\BulkForm
 * @see \Drupal\user\Plugin\Action\BlockUser
 */
class ActionUninstallTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['views', 'action'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Actions UI uninstall.
   */
  public function testActionUninstall(): void {
    \Drupal::service('module_installer')->uninstall(['action']);

    $storage = $this->container->get('entity_type.manager')->getStorage('action');
    $storage->resetCache(['user_block_user_action']);
    $this->assertNotEmpty($storage->load('user_block_user_action'), 'Configuration entity \'user_block_user_action\' still exists after uninstalling action module.');

    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/people');
    // Ensure we have the user_block_user_action listed.
    $this->assertSession()->responseContains('<option value="user_block_user_action">Block the selected user(s)</option>');

  }

}
