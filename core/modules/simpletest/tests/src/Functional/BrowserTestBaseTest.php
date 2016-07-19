<?php

namespace Drupal\Tests\simpletest\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests BrowserTestBase functionality.
 *
 * @group simpletest
 */
class BrowserTestBaseTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('test_page_test', 'form_test');

  /**
   * Tests basic page test.
   */
  public function testGoTo() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Visit a Drupal page that requires login.
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    // Test page contains some text.
    $this->assertSession()->pageTextContains('Test page text.');

    // Response includes cache tags that we can assert.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'rendered');

    // Test that we can read the JS settings.
    $js_settings = $this->getDrupalSettings();
    $this->assertSame('azAZ09();.,\\\/-_{}', $js_settings['test-setting']);

    // Test drupalGet with a url object.
    $url = Url::fromRoute('test_page_test.render_title');
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test page contains some text.
    $this->assertSession()->pageTextContains('Hello Drupal');
  }

  /**
   * Tests basic form functionality.
   */
  public function testForm() {
    // Ensure the proper response code for a _form route.
    $this->drupalGet('form-test/object-builder');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the form and text field exist.
    $this->assertSession()->elementExists('css', 'form#form-test-form-test-object');
    $this->assertSession()->fieldExists('bananas');

    $edit = ['bananas' => 'green'];
    $this->submitForm($edit, 'Save', 'form-test-form-test-object');

    $config_factory = $this->container->get('config.factory');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('green', $value);
  }

  public function testError() {
    $this->setExpectedException('\Exception', 'User notice: foo');
    $this->drupalGet('test-error');
  }

}
