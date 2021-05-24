<?php

namespace Drupal\FunctionalTests;

use Behat\Mink\Exception\ElementNotFoundException;
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
  protected static $modules = [
    'test_page_test',
    'form_test',
    'system_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
    $this->assertStringContainsString('Test page text.', $text);
    $this->assertStringNotContainsString('</html>', $text);

    // Response includes cache tags that we can assert.
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Tags');
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
    $this->assertSession()->responseHeaderExists('Test-Header');
    $this->assertSession()->responseHeaderEquals('Test-Header', 'header value');
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

    // Test submitForm().
    $this->drupalGet('form-test/object-builder');

    // Submit the form using the button label.
    $this->submitForm(['bananas' => 'red'], 'Save');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('red', $value);

    $this->submitForm([], 'Save');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('', $value);

    // Submit the form using the button id.
    $this->submitForm(['bananas' => 'blue'], 'edit-submit');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('blue', $value);

    // Submit the form using the button name.
    $this->submitForm(['bananas' => 'purple'], 'op');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertSame('purple', $value);

    // Test submitForm() with no-html response.
    $this->drupalGet('form_test/form-state-values-clean');
    $this->submitForm([], 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertSame(1000, $values['beer']);

    // Test submitForm() with form by HTML id.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalLogin($this->drupalCreateUser(['create page content']));
    $this->drupalGet('form-test/two-instances-of-same-form');
    $this->getSession()->getPage()->fillField('edit-title-0-value', 'form1');
    $this->getSession()->getPage()->fillField('edit-title-0-value--2', 'form2');
    $this->submitForm([], 'Save', 'node-page-form--2');
    $this->assertSession()->pageTextContains('Page form2 has been created.');
  }

  /**
   * Tests clickLink() functionality.
   */
  public function testClickLink() {
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links');
    $this->assertStringContainsString('user/login', $this->getSession()->getCurrentUrl());
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links', 0);
    $this->assertStringContainsString('user/login', $this->getSession()->getCurrentUrl());
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links', 1);
    $this->assertStringContainsString('user/register', $this->getSession()->getCurrentUrl());
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
   * Tests responseHeaderDoesNotExist() functionality.
   *
   * @see \Drupal\Tests\WebAssert::responseHeaderDoesNotExist()
   */
  public function testResponseHeaderDoesNotExist() {
    $this->drupalGet('test-pipe-char');
    $this->assertSession()->responseHeaderDoesNotExist('Foo-Bar');
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
   * Tests legacy assertResponse().
   *
   * @group legacy
   */
  public function testAssertResponse() {
    $this->expectDeprecation('AssertLegacyTrait::assertResponse() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->statusCodeEquals() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-encoded');
    $this->assertResponse(200);
  }

  /**
   * Tests legacy assertTitle().
   *
   * @group legacy
   */
  public function testAssertTitle() {
    $this->expectDeprecation('AssertLegacyTrait::assertTitle() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->titleEquals() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-encoded');
    $this->assertTitle("Page with encoded HTML | Drupal");
  }

  /**
   * Tests legacy assertHeader().
   *
   * @group legacy
   */
  public function testAssertHeader() {
    $this->expectDeprecation('AssertLegacyTrait::assertHeader() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderEquals() instead. See https://www.drupal.org/node/3129738');
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('test-page');
    $this->assertHeader('X-Drupal-Cache-Tags', 'http_response rendered');
  }

  /**
   * Tests legacy text asserts.
   */
  public function testTextAsserts() {
    $this->drupalGet('test-encoded');
    $dangerous = 'Bad html <script>alert(123);</script>';
    $sanitized = Html::escape($dangerous);
    $this->assertNoText($dangerous);
    $this->assertSession()->responseContains($sanitized);
  }

  /**
   * Tests legacy assertPattern().
   *
   * @group legacy
   */
  public function testAssertPattern() {
    $this->expectDeprecation('AssertLegacyTrait::assertPattern() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseMatches() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-escaped-characters');
    $this->assertPattern('/div class.*escaped/');
  }

  /**
   * Tests deprecated assertText.
   *
   * @group legacy
   */
  public function testAssertText() {
    $this->expectDeprecation('AssertLegacyTrait::assertText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseContains() or $this->assertSession()->pageTextContains() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('Calling AssertLegacyTrait::assertText() with more than one argument is deprecated in drupal:8.2.0 and the method is removed from drupal:10.0.0. Use $this->assertSession()->responseContains() or $this->assertSession()->pageTextContains() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-encoded');
    $dangerous = 'Bad html <script>alert(123);</script>';
    $this->assertText(Html::escape($dangerous), 'Sanitized text should be present.');
  }

  /**
   * Tests deprecated assertNoText.
   *
   * @group legacy
   */
  public function testAssertNoText() {
    $this->expectDeprecation('Calling AssertLegacyTrait::assertNoText() with more than one argument is deprecated in drupal:8.2.0 and the method is removed from drupal:10.0.0. Use $this->assertSession()->responseNotContains() or $this->assertSession()->pageTextNotContains() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-encoded');
    $dangerous = 'Bad html <script>alert(123);</script>';
    $this->assertNoText($dangerous, 'Dangerous text should not be present.');
  }

  /**
   * Tests legacy getRawContent().
   *
   * @group legacy
   */
  public function testGetRawContent() {
    $this->expectDeprecation('AssertLegacyTrait::getRawContent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->getPage()->getContent() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-encoded');
    $this->assertSame($this->getSession()->getPage()->getContent(), $this->getRawContent());
  }

  /**
   * Tests legacy buildXPathQuery().
   *
   * @group legacy
   */
  public function testBuildXPathQuery() {
    $this->expectDeprecation('AssertLegacyTrait::buildXPathQuery() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->buildXPathQuery() instead. See https://www.drupal.org/node/3129738');
    $this->buildXPathQuery('\\html');
  }

  /**
   * Tests legacy field asserts which use xpath directly.
   */
  public function testXpathAsserts() {
    $this->drupalGet('test-field-xpath');
    $this->assertFieldsByValue($this->xpath("//h1[@class = 'page-title']"), NULL);
    $this->assertFieldsByValue($this->xpath('//table/tbody/tr[2]/td[1]'), 'one');
    $this->assertSession()->elementTextContains('xpath', '//table/tbody/tr[2]/td[1]', 'one');

    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-name']"), 'Test name');
    $this->assertSession()->fieldValueEquals('edit-name', 'Test name');
    $this->assertFieldsByValue($this->xpath("//select[@id = 'edit-options']"), '2');
    $this->assertSession()->fieldValueEquals('edit-options', '2');

    $this->assertSession()->elementNotExists('xpath', '//notexisting');
    $this->assertSession()->fieldValueNotEquals('edit-name', 'wrong value');

    // Test that the assertion fails correctly.
    try {
      $this->assertSession()->fieldExists('notexisting');
      $this->fail('The "notexisting" field was found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    try {
      $this->assertSession()->fieldNotExists('edit-name');
      $this->fail('The "edit-name" field was not found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    try {
      $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-name']"), 'not the value');
      $this->fail('The "edit-name" field is found with the value "not the value".');
    }
    catch (ExpectationFailedException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests legacy field asserts using textfields.
   *
   * @group legacy
   */
  public function testAssertField() {
    $this->expectDeprecation('AssertLegacyTrait::assertField() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldExists() or $this->assertSession()->buttonExists() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertNoField() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldNotExists() or $this->assertSession()->buttonNotExists() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-field-xpath');
    $this->assertField('name');
    $this->assertNoField('invalid_name_and_id');
  }

  /**
   * Tests legacy field asserts by id and by Xpath.
   *
   * @group legacy
   */
  public function testAssertFieldById() {
    $this->expectDeprecation('AssertLegacyTrait::assertFieldById() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldExists() or $this->assertSession()->buttonExists() or $this->assertSession()->fieldValueEquals() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertNoFieldById() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldNotExists() or $this->assertSession()->buttonNotExists() or $this->assertSession()->fieldValueNotEquals() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertFieldByXPath() is deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use $this->xpath() instead and check the values directly in the test. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertNoFieldByXPath() is deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use $this->xpath() instead and assert that the result is empty. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-field-xpath');
    $this->assertFieldById('edit-save', NULL);
    $this->assertNoFieldById('invalid', NULL);
    $this->assertFieldByXPath("//input[@id = 'edit-name']", 'Test name');
    $this->assertNoFieldByXPath("//input[@id = 'edit-name']", 'wrong value');
  }

  /**
   * Tests field asserts using textfields.
   */
  public function testFieldAssertsForTextfields() {
    $this->drupalGet('test-field-xpath');

    // *** 1. fieldNotExists().
    $this->assertSession()->fieldNotExists('invalid_name_and_id');

    // Test that the assertion fails correctly when searching by name.
    try {
      $this->assertSession()->fieldNotExists('name');
      $this->fail('The "name" field was not found based on name.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // Test that the assertion fails correctly when searching by id.
    try {
      $this->assertSession()->fieldNotExists('edit-name');
      $this->fail('The "name" field was not found based on id.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // *** 2. fieldExists().
    $this->assertSession()->fieldExists('name');
    $this->assertSession()->fieldExists('edit-name');

    // Test that the assertion fails correctly if the field does not exist.
    try {
      $this->assertSession()->fieldExists('invalid_name_and_id');
      $this->fail('The "invalid_name_and_id" field was found.');
    }
    catch (ElementNotFoundException $e) {
      // Expected exception; just continue testing.
    }
    // *** 3. assertNoFieldById().
    $this->assertSession()->fieldValueNotEquals('name', 'not the value');
    $this->assertSession()->fieldNotExists('notexisting');
    // Test that the assertion fails correctly if no value is passed in.
    try {
      $this->assertSession()->fieldNotExists('edit-description');
      $this->fail('The "description" field, with no value was not found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // Test that the assertion fails correctly if a NULL value is passed in.
    try {
      $this->assertSession()->fieldNotExists('name', NULL);
      $this->fail('The "name" field was not found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // *** 4. assertFieldById().
    $this->assertSession()->fieldExists('edit-name');
    $this->assertSession()->fieldValueEquals('edit-name', 'Test name');
    $this->assertSession()->fieldExists('edit-description');
    $this->assertSession()->fieldValueEquals('edit-description', '');

    // Test that the assertion fails correctly if no value is passed in.
    try {
      $this->assertSession()->fieldValueNotEquals('edit-name', '');
    }
    catch (ExpectationFailedException $e) {
      // Expected exception; just continue testing.
    }

    // Test that the assertion fails correctly if the wrong value is passed in.
    try {
      $this->assertSession()->fieldValueNotEquals('edit-name', 'not the value');
    }
    catch (ExpectationFailedException $e) {
      // Expected exception; just continue testing.
    }

    // *** 5. fieldValueNotEquals().
    $this->assertSession()->fieldValueNotEquals('name', 'not the value');

    // Test that the assertion fails correctly if given the right value.
    try {
      $this->assertSession()->fieldValueNotEquals('name', 'Test name');
      $this->fail('fieldValueNotEquals failed to throw an exception.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // *** 6. fieldValueEquals().
    $this->assertSession()->fieldValueEquals('name', 'Test name');
    $this->assertSession()->fieldValueEquals('description', '');

    // Test that the assertion fails correctly if given the wrong value.
    try {
      $this->assertSession()->fieldValueEquals('name', 'not the value');
      $this->fail('fieldValueEquals failed to throw an exception.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // Test that text areas can contain new lines.
    $this->assertFieldsByValue($this->xpath("//textarea[@id = 'edit-test-textarea-with-newline']"), "Test text with\nnewline");
  }

  /**
   * Tests legacy field asserts for options field type.
   *
   * @group legacy
   */
  public function testFieldAssertsForOptions() {
    $this->expectDeprecation('AssertLegacyTrait::assertOptionByText() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertOption() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertNoOption() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionNotExists() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-field-xpath');

    // Option field type.
    $this->assertOptionByText('options', 'one');
    try {
      $this->assertOptionByText('options', 'four');
      $this->fail('The select option "four" was found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    $this->assertOption('options', 1);
    try {
      $this->assertOption('options', 4);
      $this->fail('The select option "4" was found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    $this->assertNoOption('options', 'non-existing');
    try {
      $this->assertNoOption('options', 'one');
      $this->fail('The select option "one" was not found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    $this->assertTrue($this->assertSession()->optionExists('options', 2)->isSelected());
    try {
      $this->assertTrue($this->assertSession()->optionExists('options', 4)->isSelected());
      $this->fail('The select option "4" was selected.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    try {
      $this->assertTrue($this->assertSession()->optionExists('options', 1)->isSelected());
      $this->fail('The select option "1" was selected.');
    }
    catch (ExpectationFailedException $e) {
      // Expected exception; just continue testing.
    }

  }

  /**
   * Tests legacy field asserts for button field type.
   */
  public function testFieldAssertsForButton() {
    $this->drupalGet('test-field-xpath');

    // Verify if the test passes with button ID.
    $this->assertSession()->buttonExists('edit-save');
    // Verify if the test passes with button Value.
    $this->assertSession()->buttonExists('Save');
    // Verify if the test passes with button Name.
    $this->assertSession()->buttonExists('op');

    // Verify if the test passes with button ID.
    $this->assertSession()->buttonNotExists('i-do-not-exist');
    // Verify if the test passes with button Value.
    $this->assertSession()->buttonNotExists('I do not exist');
    // Verify if the test passes with button Name.
    $this->assertSession()->buttonNotExists('no');

    // Test that multiple fields with the same name are validated correctly.
    $this->assertSession()->buttonExists('duplicate_button');
    $this->assertSession()->buttonExists('Duplicate button 1');
    $this->assertSession()->buttonExists('Duplicate button 2');
    $this->assertSession()->buttonNotExists('Rabbit');

    try {
      $this->assertSession()->buttonNotExists('Duplicate button 2');
      $this->fail('The "duplicate_button" field with the value Duplicate button 2 was not found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests legacy assertFieldChecked() and assertNoFieldChecked().
   *
   * @group legacy
   */
  public function testLegacyFieldAssertsForCheckbox() {
    $this->expectDeprecation('AssertLegacyTrait::assertFieldChecked() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->checkboxChecked() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertNoFieldChecked() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->checkboxNotChecked() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-field-xpath');
    $this->assertFieldChecked('edit-checkbox-enabled');
    $this->assertNoFieldChecked('edit-checkbox-disabled');
  }

  /**
   * Tests legacy field asserts for checkbox field type.
   */
  public function testFieldAssertsForCheckbox() {
    $this->drupalGet('test-field-xpath');

    // Part 1 - Test by name.
    // Test that checkboxes are found/not found correctly by name, when using
    // TRUE or FALSE to match their 'checked' state.
    $this->assertSession()->fieldExists('checkbox_enabled');
    $this->assertSession()->fieldExists('checkbox_disabled');
    $this->assertSession()->fieldValueEquals('checkbox_enabled', TRUE);
    $this->assertSession()->fieldValueEquals('checkbox_disabled', FALSE);
    $this->assertSession()->fieldValueNotEquals('checkbox_enabled', FALSE);
    $this->assertSession()->fieldValueNotEquals('checkbox_disabled', TRUE);

    // Test that we have legacy support.
    $this->assertSession()->fieldValueEquals('checkbox_enabled', '1');
    $this->assertSession()->fieldValueEquals('checkbox_disabled', '');

    // Test that the assertion fails correctly if given the right value.
    try {
      $this->assertSession()->fieldValueNotEquals('checkbox_enabled', TRUE);
      $this->fail('fieldValueNotEquals failed to throw an exception.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // Part 2 - Test by ID.
    // Test that checkboxes are found/not found correctly by ID, when using
    // TRUE or FALSE to match their 'checked' state.
    $this->assertSession()->fieldValueEquals('edit-checkbox-enabled', TRUE);
    $this->assertSession()->fieldValueEquals('edit-checkbox-disabled', FALSE);
    $this->assertSession()->fieldValueNotEquals('edit-checkbox-enabled', FALSE);
    $this->assertSession()->fieldValueNotEquals('edit-checkbox-disabled', TRUE);

    // Test that checkboxes are found by ID, when using NULL to ignore the
    // 'checked' state.
    $this->assertSession()->fieldExists('edit-checkbox-enabled');
    $this->assertSession()->fieldExists('edit-checkbox-disabled');

    // Test that checkboxes are found by ID when passing no second parameter.
    $this->assertSession()->fieldExists('edit-checkbox-enabled');
    $this->assertSession()->fieldExists('edit-checkbox-disabled');

    // Test that we have legacy support.
    $this->assertSession()->fieldValueEquals('edit-checkbox-enabled', '1');
    $this->assertSession()->fieldValueEquals('edit-checkbox-disabled', '');

    // Test that the assertion fails correctly when using NULL to ignore state.
    try {
      $this->assertSession()->fieldNotExists('edit-checkbox-disabled', NULL);
      $this->fail('The "edit-checkbox-disabled" field was not found by ID, using NULL value.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // Part 3 - Test the specific 'checked' assertions.
    $this->assertSession()->checkboxChecked('edit-checkbox-enabled');
    $this->assertSession()->checkboxNotChecked('edit-checkbox-disabled');

    // Test that the assertion fails correctly with non-existent field id.
    try {
      $this->assertSession()->checkboxNotChecked('incorrect_checkbox_id');
      $this->fail('The "incorrect_checkbox_id" field was found');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // Test that the assertion fails correctly for a checkbox that is checked.
    try {
      $this->assertSession()->checkboxNotChecked('edit-checkbox-enabled');
      $this->fail('The "edit-checkbox-enabled" field was not found in a checked state.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }

    // Test that the assertion fails correctly for a checkbox that is not
    // checked.
    try {
      $this->assertSession()->checkboxChecked('edit-checkbox-disabled');
      $this->fail('The "edit-checkbox-disabled" field was found and checked.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
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
    $this->assertFileExists($htaccess_filename);
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
   * Tests pageContainsNoDuplicateId() functionality.
   *
   * @see \Drupal\Tests\WebAssert::pageContainsNoDuplicateId()
   */
  public function testPageContainsNoDuplicateId() {
    $assert_session = $this->assertSession();
    $this->drupalGet(Url::fromRoute('test_page_test.page_without_duplicate_ids'));
    $assert_session->pageContainsNoDuplicateId();

    $this->drupalGet(Url::fromRoute('test_page_test.page_with_duplicate_ids'));
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('The page contains a duplicate HTML ID "page-element".');
    $assert_session->pageContainsNoDuplicateId();
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

  /**
   * Tests deprecation of legacy assertEscaped() and assertNoEscaped().
   *
   * @group legacy
   */
  public function testLegacyEscapingAssertions(): void {
    $this->expectDeprecation('AssertLegacyTrait::assertNoEscaped() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->assertNoEscaped() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertEscaped() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->assertEscaped() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-escaped-characters');
    $this->assertNoEscaped('<div class="escaped">');
    $this->assertEscaped('Escaped: <"\'&>');
  }

  /**
   * Tests deprecation of drupalPostForm().
   *
   * @group legacy
   */
  public function testLegacyDrupalPostForm(): void {
    $this->expectDeprecation('Calling Drupal\Tests\UiHelperTrait::drupalPostForm() with $edit set to NULL is deprecated in drupal:9.1.0 and the method is removed in drupal:10.0.0. Use $this->submitForm() instead. See https://www.drupal.org/node/3168858');
    $this->drupalPostForm('form-test/object-builder', NULL, t('Save'));
    $this->expectDeprecation('Calling Drupal\Tests\UiHelperTrait::drupalPostForm() with $path set to NULL is deprecated in drupal:9.2.0 and the method is removed in drupal:10.0.0. Use $this->submitForm() instead. See https://www.drupal.org/node/3168858');
    $this->drupalPostForm(NULL, [], 'Save');
  }

  /**
   * Tests that deprecation headers do not get duplicated.
   *
   * @group legacy
   *
   * @see \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware::__invoke()
   */
  public function testDeprecationHeaders() {
    $this->drupalGet('/test-deprecations');

    $deprecation_messages = [];
    foreach ($this->getSession()->getResponseHeaders() as $name => $values) {
      if (preg_match('/^X-Drupal-Assertion-[0-9]+$/', $name, $matches)) {
        foreach ($values as $value) {
          $parameters = unserialize(urldecode($value));
          if (count($parameters) === 3) {
            if ($parameters[1] === 'User deprecated function') {
              $deprecation_messages[] = (string) $parameters[0];
            }
          }
        }
      }
    }

    $this->assertContains('Test deprecation message', $deprecation_messages);
    $test_deprecation_messages = array_filter($deprecation_messages, function ($message) {
      return $message === 'Test deprecation message';
    });
    $this->assertCount(1, $test_deprecation_messages);
  }

  /**
   * Tests legacy assertFieldByName() and assertNoFieldByName().
   *
   * @group legacy
   */
  public function testLegacyFieldAssertsByName() {
    $this->expectDeprecation('AssertLegacyTrait::assertFieldByName() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldExists() or $this->assertSession()->buttonExists() or $this->assertSession()->fieldValueEquals() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('AssertLegacyTrait::assertNoFieldByName() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldNotExists() or $this->assertSession()->buttonNotExists() or $this->assertSession()->fieldValueNotEquals() instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('test-field-xpath');
    $this->assertFieldByName('checkbox_enabled', TRUE);
    $this->assertNoFieldByName('checkbox_enabled', FALSE);
  }

}
