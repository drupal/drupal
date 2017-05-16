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
   * Tests legacy field asserts which use xpath directly.
   */
  public function testLegacyXpathAsserts() {
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

    // Test that the assertion fails correctly.
    try {
      $this->assertFieldByXPath("//input[@id = 'notexisting']");
      $this->fail('The "notexisting" field was found.');
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      $this->pass('assertFieldByXPath correctly failed. The "notexisting" field was not found.');
    }

    try {
      $this->assertNoFieldByXPath("//input[@id = 'edit-name']");
      $this->fail('The "edit-name" field was not found.');
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      $this->pass('assertNoFieldByXPath correctly failed. The "edit-name" field was found.');
    }

    try {
      $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-name']"), 'not the value');
      $this->fail('The "edit-name" field is found with the value "not the value".');
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      $this->pass('The "edit-name" field is not found with the value "not the value".');
    }
  }

  /**
   * Tests legacy field asserts using textfields.
   */
  public function testLegacyFieldAssertsWithTextfields() {
    $this->drupalGet('test-field-xpath');

    // *** 1. assertNoField().
    $this->assertNoField('invalid_name_and_id');

    // Test that the assertion fails correctly when searching by name.
    try {
      $this->assertNoField('name');
      $this->fail('The "name" field was not found based on name.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoField correctly failed. The "name" field was found by name.');
    }

    // Test that the assertion fails correctly when searching by id.
    try {
      $this->assertNoField('edit-name');
      $this->fail('The "name" field was not found based on id.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoField correctly failed. The "name" field was found by id.');
    }

    // *** 2. assertField().
    $this->assertField('name');
    $this->assertField('edit-name');

    // Test that the assertion fails correctly if the field does not exist.
    try {
      $this->assertField('invalid_name_and_id');
      $this->fail('The "invalid_name_and_id" field was found.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertField correctly failed. The "invalid_name_and_id" field was not found.');
    }

    // *** 3. assertNoFieldById().
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

    // *** 4. assertFieldById().
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

    // Test that the assertion fails correctly if the wrong value is passed in.
    try {
      $this->assertFieldById('edit-name', 'not the value');
      $this->fail('The "name" field was found, using the wrong value.');
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      $this->pass('The "name" field was not found, using the wrong value.');
    }

    // *** 5. assertNoFieldByName().
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

    // *** 6. assertFieldByName().
    $this->assertFieldByName('name');
    $this->assertFieldByName('name', NULL);
    $this->assertFieldByName('name', 'Test name');
    $this->assertFieldByName('description');
    $this->assertFieldByName('description', '');
    $this->assertFieldByName('description', NULL);

    // Test that the assertion fails correctly if given the wrong name.
    try {
      $this->assertFieldByName('non-existing-name');
      $this->fail('The "non-existing-name" field was found.');
    }
    catch (ExpectationException $e) {
      $this->pass('The "non-existing-name" field was not found');
    }

    // Test that the assertion fails correctly if given the wrong value.
    try {
      $this->assertFieldByName('name', 'not the value');
      $this->fail('The "name" field with incorrect value was found.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertFieldByName correctly failed. The "name" field with incorrect value was not found.');
    }
  }

  /**
   * Tests legacy field asserts on other types of field.
   */
  public function testLegacyFieldAssertsWithNonTextfields() {
    $this->drupalGet('test-field-xpath');

    // Option field type.
    $this->assertOptionByText('options', 'one');
    try {
      $this->assertOptionByText('options', 'four');
      $this->fail('The select option "four" was found.');
    }
    catch (ExpectationException $e) {
      $this->pass($e->getMessage());
    }

    $this->assertOption('options', 1);
    try {
      $this->assertOption('options', 4);
      $this->fail('The select option "4" was found.');
    }
    catch (ExpectationException $e) {
      $this->pass($e->getMessage());
    }

    $this->assertNoOption('options', 'non-existing');
    try {
      $this->assertNoOption('options', 'one');
      $this->fail('The select option "one" was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass($e->getMessage());
    }

    $this->assertOptionSelected('options', 2);
    try {
      $this->assertOptionSelected('options', 4);
      $this->fail('The select option "4" was selected.');
    }
    catch (ExpectationException $e) {
      $this->pass($e->getMessage());
    }

    try {
      $this->assertOptionSelected('options', 1);
      $this->fail('The select option "1" was selected.');
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      $this->pass($e->getMessage());
    }

    // Button field type.
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

    // Checkbox field type.
    // Test that checkboxes are found/not found correctly by name, when using
    // TRUE or FALSE to match their 'checked' state.
    $this->assertFieldByName('checkbox_enabled', TRUE);
    $this->assertFieldByName('checkbox_disabled', FALSE);
    $this->assertNoFieldByName('checkbox_enabled', FALSE);
    $this->assertNoFieldByName('checkbox_disabled', TRUE);

    // Test that checkboxes are found by name when using NULL to ignore the
    // 'checked' state.
    $this->assertFieldByName('checkbox_enabled', NULL);
    $this->assertFieldByName('checkbox_disabled', NULL);

    // Test that checkboxes are found/not found correctly by ID, when using
    // TRUE or FALSE to match their 'checked' state.
    $this->assertFieldById('edit-checkbox-enabled', TRUE);
    $this->assertFieldById('edit-checkbox-disabled', FALSE);
    $this->assertNoFieldById('edit-checkbox-enabled', FALSE);
    $this->assertNoFieldById('edit-checkbox-disabled', TRUE);

    // Test that checkboxes are found by by ID, when using NULL to ignore the
    // 'checked' state.
    $this->assertFieldById('edit-checkbox-enabled', NULL);
    $this->assertFieldById('edit-checkbox-disabled', NULL);

    // Test that the assertion fails correctly when using NULL to ignore state.
    try {
      $this->assertNoFieldByName('checkbox_enabled', NULL);
      $this->fail('The "checkbox_enabled" field was not found by name, using NULL value.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldByName failed correctly. The "checkbox_enabled" field was found using NULL value.');
    }

    // Test that the assertion fails correctly when using NULL to ignore state.
    try {
      $this->assertNoFieldById('edit-checkbox-disabled', NULL);
      $this->fail('The "edit-checkbox-disabled" field was not found by ID, using NULL value.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldById failed correctly. The "edit-checkbox-disabled" field was found by ID using NULL value.');
    }

    // Test the specific 'checked' assertions.
    $this->assertFieldChecked('edit-checkbox-enabled');
    $this->assertNoFieldChecked('edit-checkbox-disabled');

    // Test that the assertion fails correctly with non-existant field id.
    try {
      $this->assertNoFieldChecked('incorrect_checkbox_id');
      $this->fail('The "incorrect_checkbox_id" field was found');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldChecked correctly failed. The "incorrect_checkbox_id" field was not found.');
    }

    // Test that the assertion fails correctly for a checkbox that is checked.
    try {
      $this->assertNoFieldChecked('edit-checkbox-enabled');
      $this->fail('The "edit-checkbox-enabled" field was not found in a checked state.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldChecked correctly failed. The "edit-checkbox-enabled" field was found in a checked state.');
    }

    // Test that the assertion fails correctly for a checkbox that is not
    // checked.
    try {
      $this->assertFieldChecked('edit-checkbox-disabled');
      $this->fail('The "edit-checkbox-disabled" field was found and checked.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertFieldChecked correctly failed. The "edit-checkbox-disabled" field was not found in a checked state.');
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
