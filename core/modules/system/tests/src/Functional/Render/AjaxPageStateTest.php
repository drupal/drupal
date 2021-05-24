<?php

namespace Drupal\Tests\system\Functional\Render;

use Drupal\Tests\BrowserTestBase;

/**
 * Performs tests for the effects of the ajax_page_state query parameter.
 *
 * @group Render
 */
class AjaxPageStateTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User account with all available permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  protected function setUp(): void {
    parent::setUp();
    // Create an administrator with all permissions.
    $this->adminUser = $this->drupalCreateUser(array_keys(\Drupal::service('user.permissions')
      ->getPermissions()));

    // Log in so there are more libraries to test for.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Default functionality without the param ajax_page_state[libraries].
   *
   * The libraries active-link and drupalSettings are loaded default from core
   * and available in code as scripts. Do this as the base test.
   */
  public function testLibrariesAvailable() {
    $this->drupalGet('node', []);
    // The active link library from core should be loaded.
    $this->assertSession()->responseContains('/core/misc/active-link.js');
    // The drupalSettings library from core should be loaded.
    $this->assertSession()->responseContains('/core/misc/drupalSettingsLoader.js');
  }

  /**
   * Give ajax_page_state[libraries]=core/drupalSettings to exclude the library.
   *
   * When called with ajax_page_state[libraries]=core/drupalSettings the library
   * should be excluded as it is already loaded. This should not affect other
   * libraries so test if active-link is still available.
   */
  public function testDrupalSettingsIsNotLoaded() {
    $this->drupalGet('node',
      [
        "query" =>
          [
            'ajax_page_state' => [
              'libraries' => 'core/drupalSettings',
            ],
          ],
      ]
    );
    // The drupalSettings library from core should be excluded from loading.
    $this->assertSession()->responseNotContains('/core/misc/drupalSettingsLoader.js');

    // The active-link library from core should be loaded.
    $this->assertSession()->responseContains('/core/misc/active-link.js');
  }

  /**
   * Tests if multiple libraries can be excluded.
   *
   * The ajax_page_state[libraries] should be able to support multiple libraries
   * comma separated.
   */
  public function testMultipleLibrariesAreNotLoaded() {
    $this->drupalGet('node',
      ['query' => ['ajax_page_state' => ['libraries' => 'core/drupal,core/drupalSettings']]]
    );
    $this->assertSession()->statusCodeEquals(200);
    // The drupal library from core should be excluded from loading.
    $this->assertSession()->responseNotContains('/core/misc/drupal.js');

    // The drupalSettings library from core should be excluded from loading.
    $this->assertSession()->responseNotContains('/core/misc/drupalSettingsLoader.js');

    $this->drupalGet('node');
    // The drupal library from core should be included in loading.
    $this->assertSession()->responseContains('/core/misc/drupal.js');

    // The drupalSettings library from core should be included in loading.
    $this->assertSession()->responseContains('/core/misc/drupalSettingsLoader.js');
  }

}
