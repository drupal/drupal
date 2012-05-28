<?php

/**
 * @file
 * Definition of Drupal\openid\Tests\OpenIDRegistrationTest.
 */

namespace Drupal\openid\Tests;

/**
 * Test account registration using Simple Registration and Attribute Exchange.
 */
class OpenIDRegistrationTest extends OpenIDTestBase {
  public static function getInfo() {
    return array(
      'name' => 'OpenID account registration',
      'description' => 'Creates a user account using auto-registration.',
      'group' => 'OpenID'
    );
  }

  function setUp() {
    // Add language module too to test with some non-built-in languages.
    parent::setUp('openid', 'openid_test', 'language');
    variable_set('user_register', USER_REGISTER_VISITORS);
  }

  /**
   * Test OpenID auto-registration with e-mail verification enabled.
   */
  function testRegisterUserWithEmailVerification() {
    variable_set('user_email_verification', TRUE);
    variable_get('configurable_timezones', 1);
    variable_set('date_default_timezone', 'Europe/Brussels');

    // Tell openid_test.module to respond with these SREG fields.
    variable_set('openid_test_response', array(
      'openid.sreg.nickname' => 'john',
      'openid.sreg.email' => 'john@example.com',
      'openid.sreg.language' => 'pt-BR',
      'openid.sreg.timezone' => 'Europe/London',
    ));

    // Save Portuguese and Portuguese, Portugal as optional languages. The
    // process should pick 'pt' based on the sreg.language being 'pt-BR'
    // (and falling back on least specific language given no pt-br available
    // locally).
    $language = (object) array(
      'langcode' => 'pt',
    );
    language_save($language);
    $language = (object) array(
      'langcode' => 'pt-pt',
    );
    language_save($language);

    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->submitLoginForm($identity);
    $this->assertRaw(t('Once you have verified your e-mail address, you may log in via OpenID.'), t('User was asked to verify e-mail address.'));
    $this->assertRaw(t('A welcome message with further instructions has been sent to your e-mail address.'), t('A welcome message was sent to the user.'));
    $reset_url = $this->getPasswordResetURLFromMail();

    $user = user_load_by_name('john');
    $this->assertTrue($user, t('User was registered with right username.'));
    $this->assertEqual($user->mail, 'john@example.com', t('User was registered with right email address.'));
    $this->assertEqual($user->timezone, 'Europe/London', t('User was registered with right timezone.'));
    $this->assertEqual($user->preferred_langcode, 'pt', t('User was registered with right language.'));
    $this->assertFalse($user->data, t('No additional user info was saved.'));

    $this->submitLoginForm($identity);
    $this->assertRaw(t('You must validate your email address for this account before logging in via OpenID.'));

    // Follow the one-time login that was sent in the welcome e-mail.
    $this->drupalGet($reset_url);
    $this->drupalPost(NULL, array(), t('Log in'));

    $this->drupalLogout();

    // Verify that the account was activated.
    $this->submitLoginForm($identity);
    $this->assertLink(t('Log out'), 0, t('User was logged in.'));
  }

  /**
   * Test OpenID auto-registration with e-mail verification disabled.
   */
  function testRegisterUserWithoutEmailVerification() {
    variable_set('user_email_verification', FALSE);
    variable_get('configurable_timezones', 1);
    variable_set('date_default_timezone', 'Europe/Brussels');

    // Tell openid_test.module to respond with these SREG fields.
    variable_set('openid_test_response', array(
      'openid.sreg.nickname' => 'john',
      'openid.sreg.email' => 'john@example.com',
      'openid.sreg.language' => 'pt-BR',
      'openid.sreg.timezone' => 'Europe/London',
    ));

    // Save Portuguese, Brazil as an optional language. The process should pick
    // 'pt-br' based on the sreg.language later.
    $language = (object) array(
      'langcode' => 'pt-br',
    );
    language_save($language);

    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->submitLoginForm($identity);
    $this->assertLink(t('Log out'), 0, t('User was logged in.'));

    $user = user_load_by_name('john');
    $this->assertTrue($user, t('User was registered with right username.'));
    $this->assertEqual($user->mail, 'john@example.com', t('User was registered with right email address.'));
    $this->assertEqual($user->timezone, 'Europe/London', t('User was registered with right timezone.'));
    $this->assertEqual($user->preferred_langcode, 'pt-br', t('User was registered with right language.'));
    $this->assertFalse($user->data, t('No additional user info was saved.'));

    $this->drupalLogout();

    $this->submitLoginForm($identity);
    $this->assertLink(t('Log out'), 0, t('User was logged in.'));
  }

  /**
   * Test OpenID auto-registration with a provider that supplies invalid SREG
   * information (a username that is already taken, and no e-mail address).
   */
  function testRegisterUserWithInvalidSreg() {
    variable_get('configurable_timezones', 1);
    variable_set('date_default_timezone', 'Europe/Brussels');

    // Tell openid_test.module to respond with these SREG fields.
    $web_user = $this->drupalCreateUser(array());
    variable_set('openid_test_response', array(
      'openid.sreg.nickname' => $web_user->name,
      'openid.sreg.email' => 'mail@invalid#',
      'openid.sreg.timezone' => 'Foo/Bar',
      'openid.sreg.language' => 'foobar',
    ));

    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->submitLoginForm($identity);

    $this->assertRaw(t('Account registration using the information provided by your OpenID provider failed due to the reasons listed below. Complete the registration by filling out the form below. If you already have an account, you can <a href="@login">log in</a> now and add your OpenID under "My account".', array('@login' => url('user/login'))), t('User was asked to complete the registration process manually.'));
    $this->assertRaw(t('The name %name is already taken.', array('%name' => $web_user->name)), t('Form validation error for username was displayed.'));
    $this->assertRaw(t('The e-mail address %mail is not valid.', array('%mail' => 'mail@invalid#')), t('Form validation error for e-mail address was displayed.'));
    $this->assertTrue(variable_get('openid_test_hook_openid_response_response'), t('hook_openid_response() was invoked.'));
    $this->assertFalse(variable_get('openid_test_hook_openid_response_account', TRUE), t('No user object passed to hook_openid_response().'));

    // Enter username and e-mail address manually.
    variable_del('openid_test_hook_openid_response_response');
    $edit = array('name' => 'john', 'mail' => 'john@example.com');
    $this->drupalPost(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('Once you have verified your e-mail address, you may log in via OpenID.'), t('User was asked to verify e-mail address.'));
    $reset_url = $this->getPasswordResetURLFromMail();

    $user = user_load_by_name('john');
    $this->assertTrue($user, t('User was registered with right username.'));
    $this->assertEqual($user->preferred_langcode, language_default()->langcode, t('User language is site default.'));
    $this->assertFalse($user->data, t('No additional user info was saved.'));

    // Follow the one-time login that was sent in the welcome e-mail.
    $this->drupalGet($reset_url);
    $this->drupalPost(NULL, array(), t('Log in'));
    $this->assertFalse(variable_get('openid_test_hook_openid_response_response'), t('hook_openid_response() was not invoked.'));

    // The user is taken to user/%uid/edit.
    $this->assertFieldByName('mail', 'john@example.com', t('User was registered with right e-mail address.'));

    $this->clickLink(t('OpenID identities'));
    $this->assertRaw($identity, t('OpenID identity was registered.'));
  }

  /**
   * Test OpenID auto-registration with a provider that does not supply SREG
   * information (i.e. no username or e-mail address).
   */
  function testRegisterUserWithoutSreg() {
    variable_get('configurable_timezones', 1);

    // Load the front page to get the user login block.
    $this->drupalGet('');

    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->submitLoginForm($identity);
    $this->assertRaw(t('Complete the registration by filling out the form below. If you already have an account, you can <a href="@login">log in</a> now and add your OpenID under "My account".', array('@login' => url('user/login'))), t('User was asked to complete the registration process manually.'));
    $this->assertNoRaw(t('You must enter a username.'), t('Form validation error for username was not displayed.'));
    $this->assertNoRaw(t('You must enter an e-mail address.'), t('Form validation error for e-mail address was not displayed.'));

    // Enter username and e-mail address manually.
    $edit = array('name' => 'john', 'mail' => 'john@example.com');
    $this->drupalPost(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('Once you have verified your e-mail address, you may log in via OpenID.'), t('User was asked to verify e-mail address.'));
    $reset_url = $this->getPasswordResetURLFromMail();

    $user = user_load_by_name('john');
    $this->assertTrue($user, t('User was registered with right username.'));
    $this->assertEqual($user->preferred_langcode, language_default()->langcode, t('User language is site default.'));
    $this->assertFalse($user->data, t('No additional user info was saved.'));

    // Follow the one-time login that was sent in the welcome e-mail.
    $this->drupalGet($reset_url);
    $this->drupalPost(NULL, array(), t('Log in'));

    // The user is taken to user/%uid/edit.
    $this->assertFieldByName('mail', 'john@example.com', t('User was registered with right e-mail address.'));

    $this->clickLink(t('OpenID identities'));
    $this->assertRaw($identity, t('OpenID identity was registered.'));
  }

  /**
   * Test OpenID auto-registration with a provider that supplies AX information,
   * but no SREG.
   */
  function testRegisterUserWithAXButNoSREG() {
    variable_set('user_email_verification', FALSE);
    variable_set('date_default_timezone', 'Europe/Brussels');

    // Tell openid_test.module to respond with these AX fields.
    variable_set('openid_test_response', array(
      'openid.ns.ext123' => 'http://openid.net/srv/ax/1.0',
      'openid.ext123.type.mail456' => 'http://axschema.org/contact/email',
      'openid.ext123.value.mail456' => 'john@example.com',
      'openid.ext123.type.name789' => 'http://schema.openid.net/namePerson/friendly',
      'openid.ext123.count.name789' => '1',
      'openid.ext123.value.name789.1' => 'john',
      'openid.ext123.type.timezone' => 'http://axschema.org/pref/timezone',
      'openid.ext123.value.timezone' => 'Europe/London',
      'openid.ext123.type.language' => 'http://axschema.org/pref/language',
      'openid.ext123.value.language' => 'pt-PT',
    ));

    // Save Portuguese and Portuguese, Portugal as optional languages. The
    // process should pick 'pt-pt' as the more specific language.
    $language = (object) array(
      'langcode' => 'pt',
    );
    language_save($language);
    $language = (object) array(
      'langcode' => 'pt-pt',
    );
    language_save($language);

    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->submitLoginForm($identity);
    $this->assertLink(t('Log out'), 0, t('User was logged in.'));

    $user = user_load_by_name('john');
    $this->assertTrue($user, t('User was registered with right username.'));
    $this->assertEqual($user->mail, 'john@example.com', t('User was registered with right email address.'));
    $this->assertEqual($user->timezone, 'Europe/London', t('User was registered with right timezone.'));
    $this->assertEqual($user->preferred_langcode, 'pt-pt', t('User was registered with right language.'));
  }
}
