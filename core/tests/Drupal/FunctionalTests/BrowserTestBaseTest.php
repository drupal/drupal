<?php

namespace Drupal\FunctionalTests;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use PHPUnit\Framework\ExpectationFailedException;

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
  public static $modules = ['test_page_test', 'form_test', 'system_test', 'node'];

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
   * Tests drupalGet().
   */
  public function testDrupalGet() {
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('test-page');
    $this->drupalGet('/test-page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('test-page');
    $this->drupalGet('/test-page/');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/test-page/');
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
    // Submit the form using the button label.
    $result = $this->drupalPostForm('form-test/object-builder', $edit, 'Save');
    $this->assertSame($this->getSession()->getPage()->getContent(), $result);
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('red', $value);

    $this->drupalPostForm('form-test/object-builder', NULL, 'Save');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('', $value);

    // Submit the form using the button id.
    $edit = ['bananas' => 'blue'];
    $result = $this->drupalPostForm('form-test/object-builder', $edit, 'edit-submit');
    $this->assertSame($this->getSession()->getPage()->getContent(), $result);
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('blue', $value);

    // Submit the form using the button name.
    $edit = ['bananas' => 'purple'];
    $result = $this->drupalPostForm('form-test/object-builder', $edit, 'op');
    $this->assertSame($this->getSession()->getPage()->getContent(), $result);
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('purple', $value);

    // Test drupalPostForm() with no-html response.
    $values = Json::decode($this->drupalPostForm('form_test/form-state-values-clean', [], t('Submit')));
    $this->assertTrue(1000, $values['beer']);

    // Test drupalPostForm() with form by HTML id.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalLogin($this->drupalCreateUser(['create page content']));
    $this->drupalGet('form-test/two-instances-of-same-form');
    $this->getSession()->getPage()->fillField('edit-title-0-value', 'form1');
    $this->getSession()->getPage()->fillField('edit-title-0-value--2', 'form2');
    $this->drupalPostForm(NULL, [], 'Save', [], 'node-page-form--2');
    $this->assertSession()->pageTextContains('Page form2 has been created.');
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
    $this->expectException('\Exception');
    $this->expectExceptionMessage('User notice: foo');
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
   * Tests linkExistsExact() functionality.
   *
   * @see \Drupal\Tests\WebAssert::linkExistsExact()
   */
  public function testLinkExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->assertSession()->linkExistsExact('foo|bar|baz');
  }

  /**
   * Tests linkExistsExact() functionality fail.
   *
   * @see \Drupal\Tests\WebAssert::linkExistsExact()
   */
  public function testInvalidLinkExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Link with label foo|bar found');
    $this->assertSession()->linkExistsExact('foo|bar');
  }

  /**
   * Tests linkNotExistsExact() functionality.
   *
   * @see \Drupal\Tests\WebAssert::linkNotExistsExact()
   */
  public function testLinkNotExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->assertSession()->linkNotExistsExact('foo|bar');
  }

  /**
   * Tests linkNotExistsExact() functionality fail.
   *
   * @see \Drupal\Tests\WebAssert::linkNotExistsExact()
   */
  public function testInvalidLinkNotExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Link with label foo|bar|baz not found');
    $this->assertSession()->linkNotExistsExact('foo|bar|baz');
  }

  /**
   * Tests legacy text asserts.
   */
  public function testTextAsserts() {
    $this->drupalGet('test-encoded');
    $dangerous = 'Bad html <script>alert(123);</script>';
    $sanitized = Html::escape($dangerous);
    $this->assertNoText($dangerous);
    $this->assertText($sanitized);

    // Test getRawContent().
    $this->assertSame($this->getSession()->getPage()->getContent(), $this->getSession()->getPage()->getContent());
  }

  /**
   * Tests legacy field asserts which use xpath directly.
   */
  public function testXpathAsserts() {
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
    catch (ExpectationFailedException $e) {
      $this->pass('assertFieldByXPath correctly failed. The "notexisting" field was not found.');
    }

    try {
      $this->assertNoFieldByXPath("//input[@id = 'edit-name']");
      $this->fail('The "edit-name" field was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldByXPath correctly failed. The "edit-name" field was found.');
    }

    try {
      $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-name']"), 'not the value');
      $this->fail('The "edit-name" field is found with the value "not the value".');
    }
    catch (ExpectationFailedException $e) {
      $this->pass('The "edit-name" field is not found with the value "not the value".');
    }
  }

  /**
   * Tests legacy field asserts using textfields.
   */
  public function testFieldAssertsForTextfields() {
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
    catch (ExpectationFailedException $e) {
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
    catch (ExpectationFailedException $e) {
      $this->pass('The "edit-name" field with no value was not found.');
    }

    // Test that the assertion fails correctly if the wrong value is passed in.
    try {
      $this->assertFieldById('edit-name', 'not the value');
      $this->fail('The "name" field was found, using the wrong value.');
    }
    catch (ExpectationFailedException $e) {
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
    catch (ExpectationFailedException $e) {
      $this->pass('The "non-existing-name" field was not found');
    }

    // Test that the assertion fails correctly if given the wrong value.
    try {
      $this->assertFieldByName('name', 'not the value');
      $this->fail('The "name" field with incorrect value was found.');
    }
    catch (ExpectationFailedException $e) {
      $this->pass('assertFieldByName correctly failed. The "name" field with incorrect value was not found.');
    }

    // Test that text areas can contain new lines.
    $this->assertFieldsByValue($this->xpath("//textarea[@id = 'edit-test-textarea-with-newline']"), "Test text with\nnewline");
  }

  /**
   * Tests legacy field asserts for options field type.
   */
  public function testFieldAssertsForOptions() {
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
    catch (ExpectationFailedException $e) {
      $this->pass($e->getMessage());
    }

  }

  /**
   * Tests legacy field asserts for button field type.
   */
  public function testFieldAssertsForButton() {
    $this->drupalGet('test-field-xpath');

    $this->assertFieldById('edit-save', NULL);
    // Test that the assertion fails correctly if the field value is passed in
    // rather than the id.
    try {
      $this->assertFieldById('Save', NULL);
      $this->fail('The field with id of "Save" was found.');
    }
    catch (ExpectationFailedException $e) {
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

    // Test that multiple fields with the same name are validated correctly.
    $this->assertFieldByName('duplicate_button', 'Duplicate button 1');
    $this->assertFieldByName('duplicate_button', 'Duplicate button 2');
    $this->assertNoFieldByName('duplicate_button', 'Rabbit');

    try {
      $this->assertNoFieldByName('duplicate_button', 'Duplicate button 2');
      $this->fail('The "duplicate_button" field with the value Duplicate button 2 was not found.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldByName correctly failed. The "duplicate_button" field with the value Duplicate button 2 was found.');
    }
  }

  /**
   * Tests legacy field asserts for checkbox field type.
   */
  public function testFieldAssertsForCheckbox() {
    $this->drupalGet('test-field-xpath');

    // Part 1 - Test by name.
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

    // Test that checkboxes are found by name when passing no second parameter.
    $this->assertFieldByName('checkbox_enabled');
    $this->assertFieldByName('checkbox_disabled');

    // Test that we have legacy support.
    $this->assertFieldByName('checkbox_enabled', '1');
    $this->assertFieldByName('checkbox_disabled', '');

    // Test that the assertion fails correctly when using NULL to ignore state.
    try {
      $this->assertNoFieldByName('checkbox_enabled', NULL);
      $this->fail('The "checkbox_enabled" field was not found by name, using NULL value.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldByName failed correctly. The "checkbox_enabled" field was found using NULL value.');
    }

    // Part 2 - Test by ID.
    // Test that checkboxes are found/not found correctly by ID, when using
    // TRUE or FALSE to match their 'checked' state.
    $this->assertFieldById('edit-checkbox-enabled', TRUE);
    $this->assertFieldById('edit-checkbox-disabled', FALSE);
    $this->assertNoFieldById('edit-checkbox-enabled', FALSE);
    $this->assertNoFieldById('edit-checkbox-disabled', TRUE);

    // Test that checkboxes are found by ID, when using NULL to ignore the
    // 'checked' state.
    $this->assertFieldById('edit-checkbox-enabled', NULL);
    $this->assertFieldById('edit-checkbox-disabled', NULL);

    // Test that checkboxes are found by ID when passing no second parameter.
    $this->assertFieldById('edit-checkbox-enabled');
    $this->assertFieldById('edit-checkbox-disabled');

    // Test that we have legacy support.
    $this->assertFieldById('edit-checkbox-enabled', '1');
    $this->assertFieldById('edit-checkbox-disabled', '');

    // Test that the assertion fails correctly when using NULL to ignore state.
    try {
      $this->assertNoFieldById('edit-checkbox-disabled', NULL);
      $this->fail('The "edit-checkbox-disabled" field was not found by ID, using NULL value.');
    }
    catch (ExpectationException $e) {
      $this->pass('assertNoFieldById failed correctly. The "edit-checkbox-disabled" field was found by ID using NULL value.');
    }

    // Part 3 - Test the specific 'checked' assertions.
    $this->assertFieldChecked('edit-checkbox-enabled');
    $this->assertNoFieldChecked('edit-checkbox-disabled');

    // Test that the assertion fails correctly with non-existent field id.
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

  /**
   * Tests the assumption that local time is in 'Australia/Sydney'.
   */
  public function testLocalTimeZone() {
    $expected = 'Australia/Sydney';
    // The 'Australia/Sydney' time zone is set in core/tests/bootstrap.php
    $this->assertEquals($expected, date_default_timezone_get());

    // The 'Australia/Sydney' time zone is also set in
    // FunctionalTestSetupTrait::initConfig().
    $config_factory = $this->container->get('config.factory');
    $value = $config_factory->get('system.date')->get('timezone.default');
    $this->assertEquals($expected, $value);

    // Test that users have the correct time zone set.
    $this->assertEquals($expected, $this->rootUser->getTimeZone());
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->assertEquals($expected, $admin_user->getTimeZone());
  }

  /**
   * Tests the ::checkForMetaRefresh() method.
   */
  public function testCheckForMetaRefresh() {
    // Disable following redirects in the client.
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
    // Set the maximumMetaRefreshCount to zero to make sure the redirect doesn't
    // happen when doing a drupalGet.
    $this->maximumMetaRefreshCount = 0;
    $this->drupalGet('test-meta-refresh');
    $this->assertNotEmpty($this->cssSelect('meta[http-equiv="refresh"]'));
    // Allow one redirect to happen.
    $this->maximumMetaRefreshCount = 1;
    $this->checkForMetaRefresh();
    // Check that we are now on the test page.
    $this->assertSession()->pageTextContains('Test page text.');
  }

  public function testGetDefaultDriveInstance() {
    putenv('MINK_DRIVER_ARGS=' . json_encode([NULL, ['key1' => ['key2' => ['key3' => 3, 'key3.1' => 3.1]]]]));
    $this->getDefaultDriverInstance();
    $this->assertEquals([NULL, ['key1' => ['key2' => ['key3' => 3, 'key3.1' => 3.1]]]], $this->minkDefaultDriverArgs);
  }

  /**
   * Ensures we can't access modules we shouldn't be able to after install.
   */
  public function testProfileModules() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The module demo_umami_content does not exist.');
    $this->assertFileExists('core/profiles/demo_umami/modules/demo_umami_content/demo_umami_content.info.yml');
    \Drupal::service('extension.list.module')->getPathname('demo_umami_content');
  }

  /**
   * Test the protections provided by .htkey.
   */
  public function testHtkey() {
    // Remove the Simpletest private key file so we can test the protection
    // against requests that forge a valid testing user agent to gain access
    // to the installer.
    // @see drupal_valid_test_ua()
    // Not using File API; a potential error must trigger a PHP warning.
    $install_url = Url::fromUri('base:core/install.php', ['external' => TRUE, 'absolute' => TRUE])->toString();
    $this->drupalGet($install_url);
    $this->assertSession()->statusCodeEquals(200);
    unlink($this->siteDirectory . '/.htkey');
    $this->drupalGet($install_url);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests assertEscaped() and assertUnescaped().
   *
   * @see \Drupal\Tests\WebAssert::assertNoEscaped()
   * @see \Drupal\Tests\WebAssert::assertEscaped()
   */
  public function testEscapingAssertions() {
    $assert = $this->assertSession();

    $this->drupalGet('test-escaped-characters');
    $assert->assertNoEscaped('<div class="escaped">');
    $assert->responseContains('<div class="escaped">');
    $assert->assertEscaped('Escaped: <"\'&>');

    $this->drupalGet('test-escaped-script');
    $assert->assertNoEscaped('<div class="escaped">');
    $assert->responseContains('<div class="escaped">');
    $assert->assertEscaped("<script>alert('XSS');alert(\"XSS\");</script>");

    $this->drupalGet('test-unescaped-script');
    $assert->assertNoEscaped('<div class="unescaped">');
    $assert->responseContains('<div class="unescaped">');
    $assert->responseContains("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
    $assert->assertNoEscaped("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
  }

}
