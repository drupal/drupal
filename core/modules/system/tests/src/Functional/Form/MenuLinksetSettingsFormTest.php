<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the menu_linkset_settings form.
 *
 * @group Form
 */
class MenuLinksetSettingsFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * A user account to modify the menu linkset settings form.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminAccount;

  /**
   * Tests the menu_linkset_settings form.
   */
  public function testMenuLinksetSettingsForm(): void {
    // Users without the appropriate permissions should not be able to access.
    $this->drupalGet('admin/config/services/linkset');
    $this->assertSession()->pageTextContains('Access denied');

    // Users with permission should be able to access the form.
    $permissions = ['administer site configuration'];
    $this->adminAccount = $this->setUpCurrentUser([
      'name' => 'system_admin',
      'pass' => 'adminPass',
    ], $permissions);
    $this->drupalLogin($this->adminAccount);
    $this->drupalGet('admin/config/services/linkset');
    $this->assertSession()
      ->elementExists('css', '#edit-actions > input.button--primary');

    // Confirm endpoint can be enabled.
    $this->assertSession()->fieldExists('edit-enable-endpoint')->check();
    $this->submitForm([], 'Save configuration');
    $this->assertSession()
      ->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->fieldExists('edit-enable-endpoint')->isChecked();
    $is_endpoint_enabled = $this->config('system.feature_flags')->get('linkset_endpoint');
    $this->assertTrue($is_endpoint_enabled, 'Endpoint is enabled.');

    // Confirm endpoint can be disabled.
    $this->assertSession()->fieldExists('edit-enable-endpoint')->uncheck();
    $this->submitForm([], 'Save configuration');
    $this->assertSession()
      ->pageTextContains('The configuration options have been saved.');
    $is_endpoint_enabled = $this->config('system.feature_flags')->get('linkset_endpoint');
    $this->assertFalse($is_endpoint_enabled, 'Endpoint is disabled.');
  }

}
