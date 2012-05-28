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
  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'block';
    $modules[] = 'openid';
    parent::setUp($modules);

    // Enable user login block.
    db_merge('block')
      ->key(array(
        'module' => 'user',
        'delta' => 'login',
        'theme' => variable_get('theme_default', 'stark'),
      ))
      ->fields(array(
        'status' => 1,
        'weight' => 0,
        'region' => 'sidebar_first',
        'pages' => '',
        'cache' => -1,
      ))
      ->execute();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }

  /**
   * Initiates the login procedure using the specified User-supplied Identity.
   */
  function submitLoginForm($identity) {
    // Fill out and submit the login form.
    $edit = array('openid_identifier' => $identity);
    $this->drupalPost('', $edit, t('Log in'));

    // Check we are on the OpenID redirect form.
    $this->assertTitle(t('OpenID redirect'), t('OpenID redirect page was displayed.'));

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
