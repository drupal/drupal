<?php

/**
 * @file
 * Definition of Drupal\openid\Tests\OpenIDTestBase.
 */

namespace Drupal\openid\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for OpenID tests.
 */
abstract class OpenIDTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'openid', 'test_page_test');

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($this->admin_user);

    // Enable user login block.
    $edit = array(
      'machine_name' => 'user_login',
      'region' => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block/manage/user_login_block/stark', $edit, t('Save block'));

    $this->drupalLogout();

    // Use a different front page than login page for testing OpenID login from
    // the user login block.
    config('system.site')->set('page.front', 'test-page')->save();
  }

  /**
   * Initiates the login procedure using the specified User-supplied Identity.
   */
  function submitLoginForm($identity) {
    // Fill out and submit the login form.
    $edit = array('openid_identifier' => $identity);
    $this->drupalPost('', $edit, t('Log in'), array(), array(), 'openid-login-form');

    // Check we are on the OpenID redirect form.
    $this->assertTitle(t('OpenID redirect'), 'OpenID redirect page was displayed.');

    // Submit form to the OpenID Provider Endpoint.
    $this->drupalPost(NULL, array(), t('Send'));
  }

  /**
   * Parses the last sent e-mail and returns the one-time login link URL.
   */
  function getPasswordResetURLFromMail() {
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    preg_match('@.+user/reset/.+@', $mail['body'], $matches);
    return $matches[0];
  }
}
