<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the authorize.php script and related API.
 *
 * @group system
 */
class SystemAuthorizeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an administrator user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer software updates',
    ]));
  }

  /**
   * Helper function to initialize authorize.php and load it via drupalGet().
   *
   * Initializing authorize.php needs to happen in the child Drupal
   * installation, not the parent. So, we visit a menu callback provided by
   * system_test.module which calls system_authorized_init() to initialize the
   * user's session inside the test site, not the framework site. This callback
   * redirects to authorize.php when it's done initializing.
   *
   * @see system_authorized_init()
   */
  public function drupalGetAuthorizePHP($page_title = 'system-test-auth'): void {
    $this->drupalGet('system-test/authorize-init/' . $page_title);
  }

  /**
   * Tests the FileTransfer hooks.
   */
  public function testFileTransferHooks(): void {
    $page_title = $this->randomMachineName(16);
    $this->drupalGetAuthorizePHP($page_title);
    $this->assertSession()->titleEquals("$page_title | Drupal");
    $this->assertSession()->pageTextNotContains('It appears you have reached this page in error.');
    $this->assertSession()->pageTextContains('To continue, provide your server connection details');
    // Make sure we see the new connection method added by system_test.
    $this->assertSession()->pageTextContains('System Test FileTransfer');
    // Make sure the settings form callback works.
    $this->assertSession()->pageTextContains('System Test Username');
    // Test that \Drupal\Core\Render\BareHtmlPageRenderer adds assets as
    // expected to the first page of the authorize.php script.
    $this->assertSession()->responseContains('core/misc/states.js');
  }

  /**
   * Tests error handling in authorize.php.
   */
  public function testError(): void {
    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\ndefine('SIMPLETEST_COLLECT_ERRORS', FALSE);\n";
    $settings_php .= "\ntrigger_error('Test warning', E_USER_WARNING);\n";
    file_put_contents($settings_filename, $settings_php);

    $this->drupalGetAuthorizePHP();

    $this->assertSession()->pageTextContains('User warning: Test warning');
    $this->assertSession()->pageTextMatches('@line \d+ of sites/simpletest@');
  }

}
