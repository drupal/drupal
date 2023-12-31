<?php

namespace Drupal\FunctionalTests;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\StreamCapturer;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Tests BrowserTestBase functionality.
 *
 * @group browsertestbase
 * @group #slow
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
  protected $defaultTheme = 'stark';

  /**
   * Tests that JavaScript Drupal settings can be read.
   */
  public function testDrupalSettings() {
    // Trigger a 403 because those pages have very little else going on.
    $this->drupalGet('admin');
    $this->assertSame([], $this->getDrupalSettings());

    // Now try the same 403 as an authenticated user and verify that Drupal
    // settings do show up.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('admin');
    $this->assertNotSame([], $this->getDrupalSettings());
  }

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
    // Ensure Drupal Javascript settings are not part of the page text.
    $this->assertArrayHasKey('currentPathIsAdmin', $this->getDrupalSettings()['path']);
    $this->assertStringNotContainsString('currentPathIsAdmin', $text);

    // Response includes cache tags that we can assert.
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Tags');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'http_response rendered');

    // Test that we can read the JS settings.
    $js_settings = $this->getDrupalSettings();
    $this->assertSame('azAZ09();.,\\\/-_{}', $js_settings['test-setting']);

    // Test drupalGet with a URL object.
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

    // Ensure that \Drupal\Tests\UiHelperTrait::isTestUsingGuzzleClient() works
    // as expected.
    $this->assertTrue($this->isTestUsingGuzzleClient());
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
    $this->assertSession()->hiddenFieldExists('red-strawberry-hidden-field');
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
   * Tests legacy field asserts which use xpath directly.
   */
  public function testXpathAsserts() {
    $this->drupalGet('test-field-xpath');
    $this->assertSession()->elementTextContains('xpath', '//table/tbody/tr[2]/td[1]', 'one');

    $this->assertSession()->fieldValueEquals('edit-name', 'Test name');
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
    $this->assertSession()->fieldValueEquals('edit-test-textarea-with-newline', "Test text with\nnewline");
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

    // Ensure the update module is not installed.
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('update'), 'The Update module is not installed.');
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
   * Tests the protections provided by .htkey.
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
   * Tests the dump() function provided by the var-dumper Symfony component.
   */
  public function testVarDump() {
    // Append the stream capturer to the STDOUT stream, so that we can test the
    // dump() output and also prevent it from actually outputting in this
    // particular test.
    stream_filter_register("capture", StreamCapturer::class);
    stream_filter_append(STDOUT, "capture");

    // Dump some variables to check that dump() in test code produces output
    // on the command line that is running the test.
    $role = Role::load('authenticated');
    dump($role);
    dump($role->id());

    $this->assertStringContainsString('Drupal\user\Entity\Role', StreamCapturer::$cache);
    $this->assertStringContainsString('authenticated', StreamCapturer::$cache);

    // Visit a Drupal page with call to the dump() function to check that dump()
    // in site code produces output in the requested web page's HTML.
    $body = $this->drupalGet('test-page-var-dump');
    $this->assertSession()->statusCodeEquals(200);

    // It is too strict to assert all properties of the Role and it is easy to
    // break if one of these properties gets removed or gets a new default
    // value. It should be sufficient to test just a couple of properties.
    $this->assertStringContainsString('<span class=sf-dump-note>', $body);
    $this->assertStringContainsString('  #<span class=sf-dump-protected title="Protected property">id</span>: "<span class=sf-dump-str title="9 characters">test_role</span>"', $body);
    $this->assertStringContainsString('  #<span class=sf-dump-protected title="Protected property">label</span>: "<span class=sf-dump-str title="9 characters">Test role</span>"', $body);
    $this->assertStringContainsString('  #<span class=sf-dump-protected title="Protected property">permissions</span>: []', $body);
    $this->assertStringContainsString('  #<span class=sf-dump-protected title="Protected property">uuid</span>: "', $body);
    $this->assertStringContainsString('</samp>}', $body);
  }

  /**
   * Test if setting an invalid scheme in SIMPLETEST_BASE_URL throws an exception.
   */
  public function testSimpleTestBaseUrlValidation() {
    putenv('SIMPLETEST_BASE_URL=mysql://user:pass@localhost/database');
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('You must provide valid scheme for the SIMPLETEST_BASE_URL environment variable. Valid schema are: http, https.');
    $this->setupBaseUrl();
  }

}
