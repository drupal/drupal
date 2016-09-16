<?php

namespace Drupal\simpletest\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Simpletest UI internal browser.
 *
 * @group simpletest
 */
class SimpleTestBrowserTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest', 'test_page_test');

  protected function setUp() {
    parent::setUp();
    // Create and log in an admin user.
    $this->drupalLogin($this->drupalCreateUser(array('administer unit tests')));
  }

  /**
   * Test the internal browsers functionality.
   */
  public function testInternalBrowser() {
    // Retrieve the test page and check its title and headers.
    $this->drupalGet('test-page');
    $this->assertTrue($this->drupalGetHeader('Date'), 'An HTTP header was received.');
    $this->assertTitle(t('Test page | @site-name', array(
      '@site-name' => $this->config('system.site')->get('name'),
    )));
    $this->assertNoTitle('Foo');

    $old_user_id = $this->container->get('current_user')->id();
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    // Check that current user service updated.
    $this->assertNotEqual($old_user_id, $this->container->get('current_user')->id(), 'Current user service updated.');
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual(count($headers), 2, 'There was one intermediate request.');
    $this->assertTrue(strpos($headers[0][':status'], '303') !== FALSE, 'Intermediate response code was 303.');
    $this->assertFalse(empty($headers[0]['location']), 'Intermediate request contained a Location header.');
    $this->assertEqual($this->getUrl(), $headers[0]['location'], 'HTTP redirect was followed');
    $this->assertFalse($this->drupalGetHeader('Location'), 'Headers from intermediate request were reset.');
    $this->assertResponse(200, 'Response code from intermediate request was reset.');

    $this->drupalLogout();
    // Check that current user service updated to anonymous user.
    $this->assertEqual(0, $this->container->get('current_user')->id(), 'Current user service updated.');

    // Test the maximum redirection option.
    $this->maximumRedirects = 1;
    $edit = array(
      'name' => $user->getUsername(),
      'pass' => $user->pass_raw
    );
    $this->drupalPostForm('user/login', $edit, t('Log in'), array(
      'query' => array('destination' => 'user/logout'),
    ));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual(count($headers), 2, 'Simpletest stopped following redirects after the first one.');

    // Remove the Simpletest private key file so we can test the protection
    // against requests that forge a valid testing user agent to gain access
    // to the installer.
    // @see drupal_valid_test_ua()
    // Not using File API; a potential error must trigger a PHP warning.
    unlink($this->siteDirectory . '/.htkey');
    $this->drupalGet(Url::fromUri('base:core/install.php', array('external' => TRUE, 'absolute' => TRUE))->toString());
    $this->assertResponse(403, 'Cannot access install.php.');
  }

  /**
   * Test validation of the User-Agent header we use to perform test requests.
   */
  public function testUserAgentValidation() {
    global $base_url;

    // Logout the user which was logged in during test-setup.
    $this->drupalLogout();

    $system_path = $base_url . '/' . drupal_get_path('module', 'system');
    $HTTP_path = $system_path . '/tests/http.php/user/login';
    $https_path = $system_path . '/tests/https.php/user/login';
    // Generate a valid simpletest User-Agent to pass validation.
    $this->assertTrue(preg_match('/test\d+/', $this->databasePrefix, $matches), 'Database prefix contains test prefix.');
    $test_ua = drupal_generate_test_ua($matches[0]);
    $this->additionalCurlOptions = array(CURLOPT_USERAGENT => $test_ua);

    // Test pages only available for testing.
    $this->drupalGet($HTTP_path);
    $this->assertResponse(200, 'Requesting http.php with a legitimate simpletest User-Agent returns OK.');
    $this->drupalGet($https_path);
    $this->assertResponse(200, 'Requesting https.php with a legitimate simpletest User-Agent returns OK.');

    // Now slightly modify the HMAC on the header, which should not validate.
    $this->additionalCurlOptions = array(CURLOPT_USERAGENT => $test_ua . 'X');
    $this->drupalGet($HTTP_path);
    $this->assertResponse(403, 'Requesting http.php with a bad simpletest User-Agent fails.');
    $this->drupalGet($https_path);
    $this->assertResponse(403, 'Requesting https.php with a bad simpletest User-Agent fails.');

    // Use a real User-Agent and verify that the special files http.php and
    // https.php can't be accessed.
    $this->additionalCurlOptions = array(CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12');
    $this->drupalGet($HTTP_path);
    $this->assertResponse(403, 'Requesting http.php with a normal User-Agent fails.');
    $this->drupalGet($https_path);
    $this->assertResponse(403, 'Requesting https.php with a normal User-Agent fails.');
  }

  /**
   * Tests that PHPUnit and KernelTestBase tests work through the UI.
   */
  public function testTestingThroughUI() {
    $this->drupalGet('admin/config/development/testing');
    $this->assertTrue(strpos($this->drupalSettings['simpleTest']['images'][0], 'core/misc/menu-collapsed.png') > 0, 'drupalSettings contains a link to core/misc/menu-collapsed.png.');
    // We can not test WebTestBase tests here since they require a valid .htkey
    // to be created. However this scenario is covered by the testception of
    // \Drupal\simpletest\Tests\SimpleTestTest.

    $tests = array(
      // A KernelTestBase test.
      'Drupal\KernelTests\KernelTestBaseTest',
      // A PHPUnit unit test.
      'Drupal\Tests\action\Unit\Menu\ActionLocalTasksTest',
      // A PHPUnit functional test.
      'Drupal\FunctionalTests\BrowserTestBaseTest',
    );

    foreach ($tests as $test) {
      $this->drupalGet('admin/config/development/testing');
      $edit = array(
        "tests[$test]" => TRUE,
      );
      $this->drupalPostForm(NULL, $edit, t('Run tests'));
      $this->assertText('0 fails, 0 exceptions');
    }
  }

}
