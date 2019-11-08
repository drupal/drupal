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
  public static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User account with all available permissions
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();
    // Create an administrator with all permissions.
    $this->adminUser = $this->drupalCreateUser(array_keys(\Drupal::service('user.permissions')
      ->getPermissions()));

    // Log in so there are more libraries to test with otherwise only html5shiv
    // is the only one in the source we can easily test for.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Default functionality without the param ajax_page_state[libraries].
   *
   * The libraries html5shiv and drupalSettings are loaded default from core
   * and available in code as scripts. Do this as the base test.
   */
  public function testLibrariesAvailable() {
    $this->drupalGet('node', []);
    $this->assertRaw(
      '/core/assets/vendor/html5shiv/html5shiv.min.js',
      'The html5shiv library from core should be loaded.'
    );
    $this->assertRaw(
      '/core/misc/drupalSettingsLoader.js',
      'The drupalSettings library from core should be loaded.'
    );
  }

  /**
   * Give ajax_page_state[libraries]=core/html5shiv to exclude the library.
   *
   * When called with ajax_page_state[libraries]=core/html5shiv the library
   * should be excluded as it is already loaded. This should not affect other
   * libraries so test if drupalSettings is still available.
   */
  public function testHtml5ShivIsNotLoaded() {
    $this->drupalGet('node',
      [
        "query" =>
          [
            'ajax_page_state' => [
              'libraries' => 'core/html5shiv',
            ],
          ],
      ]
    );
    $this->assertNoRaw(
      '/core/assets/vendor/html5shiv/html5shiv.min.js',
      'The html5shiv library from core should be excluded from loading'
    );

    $this->assertRaw(
      '/core/misc/drupalSettingsLoader.js',
      'The drupalSettings library from core should be loaded.'
    );
  }

  /**
   * Test if multiple libraries can be excluded.
   *
   * The ajax_page_state[libraries] should be able to support multiple libraries
   * comma separated.
   */
  public function testMultipleLibrariesAreNotLoaded() {
    $this->drupalGet('node',
      ['query' => ['ajax_page_state' => ['libraries' => 'core/html5shiv,core/drupalSettings']]]
    );
    $this->assertResponse(200);
    $this->assertNoRaw(
      '/core/assets/vendor/html5shiv/html5shiv.min.js',
      'The html5shiv library from core should be excluded from loading.'
    );

    $this->assertNoRaw(
      '/core/misc/drupalSettingsLoader.js',
      'The drupalSettings library from core should be excluded from loading.'
    );

    $this->drupalGet('node');
    $this->assertRaw(
      '/core/assets/vendor/html5shiv/html5shiv.min.js',
      'The html5shiv library from core should be included in loading.'
    );

    $this->assertRaw(
      '/core/misc/drupalSettingsLoader.js',
      'The drupalSettings library from core should be included in loading.'
    );
  }

}
