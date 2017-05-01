<?php

namespace Drupal\FunctionalTests;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests BrowserTestBase functionality.
 *
 * @group browsertestbase
 */
class BrowserTestBaseTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['test_page_test', 'form_test', 'system_test'];

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

    // Check that returned plain text is correct.
    $text = $this->getTextContent();
    $this->assertContains('Test page text.', $text);
    $this->assertNotContains('</html>', $text);

    // Response includes cache tags that we can assert.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'http_response rendered');

    // Test that we can read the JS settings.
    $js_settings = $this->getDrupalSettings();
    $this->assertSame('azAZ09();.,\\\/-_{}', $js_settings['test-setting']);

    // Test drupalGet with a url object.
    $url = Url::fromRoute('test_page_test.render_title');
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test page contains some text.
    $this->assertSession()->pageTextContains('Hello Drupal');

    // Test that setting headers with drupalGet() works.
    $this->drupalGet('system-test/header', [], [
      'Test-Header' => 'header value',
    ]);
    $returned_header = $this->getSession()->getResponseHeader('Test-Header');
    $this->assertSame('header value', $returned_header);
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

    // Check that the hidden field exists and has a specific value.
    $this->assertSession()->hiddenFieldExists('strawberry');
    $this->assertSession()->hiddenFieldExists('red');
    $this->assertSession()->hiddenFieldExists('redstrawberryhiddenfield');
    $this->assertSession()->hiddenFieldValueNotEquals('strawberry', 'brown');
    $this->assertSession()->hiddenFieldValueEquals('strawberry', 'red');

    // Check that a hidden field does not exist.
    $this->assertSession()->hiddenFieldNotExists('bananas');
    $this->assertSession()->hiddenFieldNotExists('pineapple');

    $edit = ['bananas' => 'green'];
    $this->submitForm($edit, 'Save', 'form-test-form-test-object');

    $config_factory = $this->container->get('config.factory');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('green', $value);

    // Test drupalPostForm().
    $edit = ['bananas' => 'red'];
    $this->drupalPostForm('form-test/object-builder', $edit, 'Save');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('red', $value);

    $this->drupalPostForm('form-test/object-builder', NULL, 'Save');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('', $value);
  }

  /**
   * Tests clickLink() functionality.
   */
  public function testClickLink() {
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links');
    $this->assertContains('user/login', $this->getSession()->getCurrentUrl());
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links', 0);
    $this->assertContains('user/login', $this->getSession()->getCurrentUrl());
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links', 1);
    $this->assertContains('user/register', $this->getSession()->getCurrentUrl());
  }

  public function testError() {
    $this->setExpectedException('\Exception', 'User notice: foo');
    $this->drupalGet('test-error');
  }

  /**
   * Tests linkExists() with pipe character (|) in locator.
   *
   * @see \Drupal\Tests\WebAssert::linkExists()
   */
  public function testPipeCharInLocator() {
    $this->drupalGet('test-pipe-char');
    $this->assertSession()->linkExists('foo|bar|baz');
  }

  /**
   * Tests legacy text asserts.
   */
  public function testLegacyTextAsserts() {
    $this->drupalGet('test-encoded');
    $dangerous = 'Bad html <script>alert(123);</script>';
    $sanitized = Html::escape($dangerous);
    $this->assertNoText($dangerous);
    $this->assertText($sanitized);

    // Test getRawContent().
    $this->assertSame($this->getSession()->getPage()->getContent(), $this->getRawContent());
  }

  /**
   * Tests legacy field asserts.
   */
  public function testLegacyFieldAsserts() {
    $this->drupalGet('test-field-xpath');
    $this->assertFieldsByValue($this->xpath("//h1[@class = 'page-title']"), NULL);
    $this->assertFieldsByValue($this->xpath('//table/tbody/tr[2]/td[1]'), 'one');
    $this->assertFieldByXPath('//table/tbody/tr[2]/td[1]', 'one');

    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-name']"), 'Test name');
    $this->assertFieldByXPath("//input[@id = 'edit-name']", 'Test name');
    $this->assertFieldsByValue($this->xpath("//select[@id = 'edit-options']"), '2');
    $this->assertFieldByXPath("//select[@id = 'edit-options']", '2');

    $this->assertNoFieldByXPath('//notexisting');
    $this->assertNoFieldByXPath("//input[@id = 'edit-name']", 'wrong value');

    $this->assertNoFieldById('name');
    $this->assertNoFieldById('name', 'not the value');
    $this->assertNoFieldById('notexisting');
    $this->assertNoFieldById('notexisting', NULL);

    // Test that the assertion fails correctly if no value is passed in.
    try {
      $this->assertNoFieldById('edit-description');
      $this->fail('The "description" field, with no value was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass('The "description" field, with no value was found.');
    }

    // Test that the assertion fails correctly if a NULL value is passed in.
    try {
      $this->assertNoFieldById('edit-name', NULL);
      $this->fail('The "name" field was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass('The "name" field was found.');
    }

    $this->assertFieldById('edit-name', NULL);
    $this->assertFieldById('edit-name', 'Test name');
    $this->assertFieldById('edit-description', NULL);
    $this->assertFieldById('edit-description');

    // Test that the assertion fails correctly if no value is passed in.
    try {
      $this->assertFieldById('edit-name');
      $this->fail('The "edit-name" field with no value was found.');
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      $this->pass('The "edit-name" field with no value was not found.');
    }

    // Test that the assertion fails correctly if NULL is passed in.
    try {
      $this->assertFieldById('name', NULL);
      $this->fail('The "name" field was found.');
    }
    catch (ExpectationException $e) {
      $this->pass('The "name" field was not found.');
    }

    $this->assertNoFieldByName('name');
    $this->assertNoFieldByName('name', 'not the value');
    $this->assertNoFieldByName('notexisting');
    $this->assertNoFieldByName('notexisting', NULL);

    // Test that the assertion fails correctly if no value is passed in.
    try {
      $this->assertNoFieldByName('description');
      $this->fail('The "description" field, with no value was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass('The "description" field, with no value was found.');
    }

    // Test that the assertion fails correctly if a NULL value is passed in.
    try {
      $this->assertNoFieldByName('name', NULL);
      $this->fail('The "name" field was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass('The "name" field was found.');
    }

    $this->assertOptionByText('options', 'one');
    try {
      $this->assertOptionByText('options', 'four');
      $this->fail('The select option "four" was found.');
    }
    catch (ExpectationException $e) {
      $this->pass($e->getMessage());
    }

    $this->assertFieldById('edit-save', NULL);
    // Test that the assertion fails correctly if the field value is passed in
    // rather than the id.
    try {
      $this->assertFieldById('Save', NULL);
      $this->fail('The field with id of "Save" was found.');
    }
    catch (ExpectationException $e) {
      $this->pass($e->getMessage());
    }

    $this->assertNoFieldById('Save', NULL);
    // Test that the assertion fails correctly if the id of an actual field is
    // passed in.
    try {
      $this->assertNoFieldById('edit-save', NULL);
      $this->fail('The field with id of "edit-save" was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass($e->getMessage());
    }
  }

  /**
   * Tests the ::cronRun() method.
   */
  public function testCronRun() {
    $last_cron_time = \Drupal::state()->get('system.cron_last');
    $this->cronRun();
    $this->assertSession()->statusCodeEquals(204);
    $next_cron_time = \Drupal::state()->get('system.cron_last');

    $this->assertGreaterThan($last_cron_time, $next_cron_time);
  }

  /**
   * Tests the Drupal install done in \Drupal\Tests\BrowserTestBase::setUp().
   */
  public function testInstall() {
    $htaccess_filename = $this->tempFilesDirectory . '/.htaccess';
    $this->assertTrue(file_exists($htaccess_filename), "$htaccess_filename exists");
  }

}
