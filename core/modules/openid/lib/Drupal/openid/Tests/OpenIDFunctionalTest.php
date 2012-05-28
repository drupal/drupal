<?php

/**
 * @file
 * Definition of Drupal\openid\Tests\OpenIDFunctionalTest.
 */

namespace Drupal\openid\Tests;

use stdClass;

/**
 * Test discovery and login using OpenID
 */
class OpenIDFunctionalTest extends OpenIDTestBase {
  protected $web_user;

  public static function getInfo() {
    return array(
      'name' => 'OpenID discovery and login',
      'description' => "Adds an identity to a user's profile and uses it to log in.",
      'group' => 'OpenID'
    );
  }

  function setUp() {
    parent::setUp('openid', 'openid_test');

    // User doesn't need special permissions; only the ability to log in.
    $this->web_user = $this->drupalCreateUser(array());
  }

  /**
   * Test discovery of OpenID Provider Endpoint via Yadis and HTML.
   */
  function testDiscovery() {
    $this->drupalLogin($this->web_user);

    // The User-supplied Identifier entered by the user may indicate the URL of
    // the OpenID Provider Endpoint in various ways, as described in OpenID
    // Authentication 2.0 and Yadis Specification 1.0.
    // Note that all of the tested identifiers refer to the same endpoint, so
    // only the first will trigger an associate request in openid_association()
    // (association is only done the first time Drupal encounters a given
    // endpoint).


    // Yadis discovery (see Yadis Specification 1.0, section 6.2.5):
    // If the User-supplied Identifier is a URL, it may be a direct or indirect
    // reference to an XRDS document (a Yadis Resource Descriptor) that contains
    // the URL of the OpenID Provider Endpoint.

    // Identifier is the URL of an XRDS document.
    // On HTTP test environments, the URL scheme is stripped in order to test
    // that the supplied identifier is normalized in openid_begin().
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->addIdentity(preg_replace('@^http://@', '', $identity), 2, 'http://example.com/xrds', $identity);

    $identity = url('openid-test/yadis/xrds/delegate', array('absolute' => TRUE));
    $this->addIdentity(preg_replace('@^http://@', '', $identity), 2, 'http://example.com/xrds-delegate', $identity);

    // Identifier is the URL of an XRDS document containing an OP Identifier
    // Element. The Relying Party sends the special value
    // "http://specs.openid.net/auth/2.0/identifier_select" as Claimed
    // Identifier. The OpenID Provider responds with the actual identifier
    // including the fragment.
    $identity = url('openid-test/yadis/xrds/dummy-user', array('absolute' => TRUE, 'fragment' => $this->randomName()));
    // Tell openid_test.module to respond with this identifier. If the fragment
    // part is present in the identifier, it should be retained.
    variable_set('openid_test_response', array('openid.claimed_id' => $identity));
    $this->addIdentity(url('openid-test/yadis/xrds/server', array('absolute' => TRUE)), 2, 'http://specs.openid.net/auth/2.0/identifier_select', $identity);
    variable_set('openid_test_response', array());

    // Identifier is the URL of an HTML page that is sent with an HTTP header
    // that contains the URL of an XRDS document.
    $this->addIdentity(url('openid-test/yadis/x-xrds-location', array('absolute' => TRUE)), 2);

    // Identifier is the URL of an HTML page containing a <meta http-equiv=...>
    // element that contains the URL of an XRDS document.
    $this->addIdentity(url('openid-test/yadis/http-equiv', array('absolute' => TRUE)), 2);

    // Identifier is an XRI. Resolve using our own dummy proxy resolver.
    variable_set('xri_proxy_resolver', url('openid-test/yadis/xrds/xri', array('absolute' => TRUE)) . '/');
    $this->addIdentity('@example*résumé;%25', 2, 'http://example.com/xrds', 'http://example.com/user');

    // Make sure that unverified CanonicalID are not trusted.
    variable_set('openid_test_canonical_id_status', 'bad value');
    $this->addIdentity('@example*résumé;%25', 2, FALSE, FALSE);

    // HTML-based discovery:
    // If the User-supplied Identifier is a URL of an HTML page, the page may
    // contain a <link rel=...> element containing the URL of the OpenID
    // Provider Endpoint. OpenID 1 and 2 describe slightly different formats.

    // OpenID Authentication 1.1, section 3.1:
    $this->addIdentity(url('openid-test/html/openid1', array('absolute' => TRUE)), 1, 'http://example.com/html-openid1');

    // OpenID Authentication 2.0, section 7.3.3:
    $this->addIdentity(url('openid-test/html/openid2', array('absolute' => TRUE)), 2, 'http://example.com/html-openid2');

    // OpenID Authentication 2.0, section 7.2.4:
    // URL Identifiers MUST then be further normalized by both (1) following
    // redirects when retrieving their content and finally (2) applying the
    // rules in Section 6 of RFC3986 to the final destination URL. This final
    // URL MUST be noted by the Relying Party as the Claimed Identifier and be
    // used when requesting authentication.

    // Single redirect.
    $identity = $expected_claimed_id = url('openid-test/redirected/yadis/xrds/1', array('absolute' => TRUE));
    $this->addRedirectedIdentity($identity, 2, 'http://example.com/xrds', $expected_claimed_id, 0);

    // Exact 3 redirects (default value for the 'max_redirects' option in
    // drupal_http_request()).
    $identity = $expected_claimed_id = url('openid-test/redirected/yadis/xrds/2', array('absolute' => TRUE));
    $this->addRedirectedIdentity($identity, 2, 'http://example.com/xrds', $expected_claimed_id, 2);

    // Fails because there are more than 3 redirects (default value for the
    // 'max_redirects' option in drupal_http_request()).
    $identity = url('openid-test/redirected/yadis/xrds/3', array('absolute' => TRUE));
    $expected_claimed_id = FALSE;
    $this->addRedirectedIdentity($identity, 2, 'http://example.com/xrds', $expected_claimed_id, 3);
  }

  /**
   * Test login using OpenID.
   */
  function testLogin() {
    $this->drupalLogin($this->web_user);

    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->addIdentity($identity);
    $response = variable_get('openid_test_hook_openid_response_response');
    $account = variable_get('openid_test_hook_openid_response_account');
    $this->assertEqual($response['openid.claimed_id'], $identity, t('hook_openid_response() was invoked.'));
    $this->assertEqual($account->uid, $this->web_user->uid, t('Proper user object passed to hook_openid_response().'));

    $this->drupalLogout();

    // Test logging in via the login block on the front page.
    variable_del('openid_test_hook_openid_response_response');
    variable_del('openid_test_hook_openid_response_account');
    $this->submitLoginForm($identity);
    $this->assertLink(t('Log out'), 0, t('User was logged in.'));
    $response = variable_get('openid_test_hook_openid_response_response');
    $account = variable_get('openid_test_hook_openid_response_account');
    $this->assertEqual($response['openid.claimed_id'], $identity, t('hook_openid_response() was invoked.'));
    $this->assertEqual($account->uid, $this->web_user->uid, t('Proper user object passed to hook_openid_response().'));

    $this->drupalLogout();

    // Test logging in via the user/login page.
    $edit = array('openid_identifier' => $identity);
    $this->drupalPost('user/login', $edit, t('Log in'));

    // Check we are on the OpenID redirect form.
    $this->assertTitle(t('OpenID redirect'), t('OpenID redirect page was displayed.'));

    // Submit form to the OpenID Provider Endpoint.
    $this->drupalPost(NULL, array(), t('Send'));

    $this->assertLink(t('Log out'), 0, t('User was logged in.'));

    // Verify user was redirected away from user/login to an accessible page.
    $this->assertResponse(200);

    $this->drupalLogout();

    // Tell openid_test.module to alter the checkid_setup request.
    $new_identity = 'http://example.com/' . $this->randomName();
    variable_set('openid_test_identity', $new_identity);
    variable_set('openid_test_request_alter', array('checkid_setup' => array('openid.identity' => $new_identity)));
    $this->submitLoginForm($identity);
    $this->assertLink(t('Log out'), 0, t('User was logged in.'));
    $response = variable_get('openid_test_hook_openid_response_response');
    $this->assertEqual($response['openid.identity'], $new_identity, t('hook_openid_request_alter() were invoked.'));
  }

  /**
   * Test login using OpenID during maintenance mode.
   */
  function testLoginMaintenanceMode() {
    $this->web_user = $this->drupalCreateUser(array('access site in maintenance mode'));
    $this->drupalLogin($this->web_user);

    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->addIdentity($identity);
    $this->drupalLogout();

    // Enable maintenance mode.
    variable_set('maintenance_mode', 1);

    // Test logging in via the user/login page while the site is offline.
    $edit = array('openid_identifier' => $identity);
    $this->drupalPost('user/login', $edit, t('Log in'));

    // Check we are on the OpenID redirect form.
    $this->assertTitle(t('OpenID redirect'), t('OpenID redirect page was displayed.'));

    // Submit form to the OpenID Provider Endpoint.
    $this->drupalPost(NULL, array(), t('Send'));

    $this->assertLink(t('Log out'), 0, t('User was logged in.'));

    // Verify user was redirected away from user/login to an accessible page.
    $this->assertText(t('Operating in maintenance mode.'));
    $this->assertResponse(200);
  }

  /**
   * Test deleting an OpenID identity from a user's profile.
   */
  function testDelete() {
    $this->drupalLogin($this->web_user);

    // Add identity to user's profile.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));
    $this->addIdentity($identity);
    $this->assertText($identity, t('Identity appears in list.'));

    // Delete the newly added identity.
    $this->clickLink(t('Delete'));
    $this->drupalPost(NULL, array(), t('Confirm'));

    $this->assertText(t('OpenID deleted.'), t('Identity deleted'));
    $this->assertNoText($identity, t('Identity no longer appears in list.'));
  }

  /**
   * Test that a blocked user cannot log in.
   */
  function testBlockedUserLogin() {
    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));

    // Log in and add an OpenID Identity to the account.
    $this->drupalLogin($this->web_user);
    $this->addIdentity($identity);
    $this->drupalLogout();

    // Log in as an admin user and block the account.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/people');
    $edit = array(
      'operation' => 'block',
      'accounts[' . $this->web_user->uid . ']' => TRUE,
    );
    $this->drupalPost('admin/people', $edit, t('Update'));
    $this->assertRaw('The update has been performed.', t('Account was blocked.'));
    $this->drupalLogout();

    $this->submitLoginForm($identity);
    $this->assertRaw(t('The username %name has not been activated or is blocked.', array('%name' => $this->web_user->name)), t('User login was blocked.'));
  }

  /**
   * Add OpenID identity to user's profile.
   *
   * @param $identity
   *   The User-supplied Identifier.
   * @param $version
   *   The protocol version used by the service.
   * @param $local_id
   *   The expected OP-Local Identifier found during discovery.
   * @param $claimed_id
   *   The expected Claimed Identifier returned by the OpenID Provider, or FALSE
   *   if the discovery is expected to fail.
   */
  function addIdentity($identity, $version = 2, $local_id = 'http://example.com/xrds', $claimed_id = NULL) {
    // Tell openid_test.module to only accept this OP-Local Identifier.
    variable_set('openid_test_identity', $local_id);

    $edit = array('openid_identifier' => $identity);
    $this->drupalPost('user/' . $this->web_user->uid . '/openid', $edit, t('Add an OpenID'));

    if ($claimed_id === FALSE) {
      $this->assertRaw(t('Sorry, that is not a valid OpenID. Ensure you have spelled your ID correctly.'), t('Invalid identity was rejected.'));
      return;
    }

    // OpenID 1 used a HTTP redirect, OpenID 2 uses a HTML form that is submitted automatically using JavaScript.
    if ($version == 2) {
      // Check we are on the OpenID redirect form.
      $this->assertTitle(t('OpenID redirect'), t('OpenID redirect page was displayed.'));

      // Submit form to the OpenID Provider Endpoint.
      $this->drupalPost(NULL, array(), t('Send'));
    }

    if (!isset($claimed_id)) {
      $claimed_id = $identity;
    }
    $this->assertRaw(t('Successfully added %identity', array('%identity' => $claimed_id)), t('Identity %identity was added.', array('%identity' => $identity)));
  }

  /**
   * Add OpenID identity, changed by the following redirects, to user's profile.
   *
   * According to OpenID Authentication 2.0, section 7.2.4, URL Identifiers MUST
   * be further normalized by following redirects when retrieving their content
   * and this final URL MUST be noted by the Relying Party as the Claimed
   * Identifier and be used when requesting authentication.
   *
   * @param $identity
   *   The User-supplied Identifier.
   * @param $version
   *   The protocol version used by the service.
   * @param $local_id
   *   The expected OP-Local Identifier found during discovery.
   * @param $claimed_id
   *   The expected Claimed Identifier returned by the OpenID Provider, or FALSE
   *   if the discovery is expected to fail.
   * @param $redirects
   *   The number of redirects.
   */
  function addRedirectedIdentity($identity, $version = 2, $local_id = 'http://example.com/xrds', $claimed_id = NULL, $redirects = 0) {
    // Set the final destination URL which is the same as the Claimed
    // Identifier, we insert the same identifier also to the provider response,
    // but provider could further change the Claimed ID actually (e.g. it could
    // add unique fragment).
    variable_set('openid_test_redirect_url', $identity);
    variable_set('openid_test_response', array('openid.claimed_id' => $identity));

    $this->addIdentity(url('openid-test/redirect/' . $redirects, array('absolute' => TRUE)), $version, $local_id, $claimed_id);

    // Clean up.
    variable_del('openid_test_redirect_url');
    variable_del('openid_test_response');
  }

  /**
   * Tests that openid.signed is verified.
   */
  function testSignatureValidation() {
    // Use a User-supplied Identity that is the URL of an XRDS document.
    $identity = url('openid-test/yadis/xrds', array('absolute' => TRUE));

    // Respond with an invalid signature.
    variable_set('openid_test_response', array('openid.sig' => 'this-is-an-invalid-signature'));
    $this->submitLoginForm($identity);
    $this->assertRaw('OpenID login failed.');

    // Do not sign the mandatory field openid.assoc_handle.
    variable_set('openid_test_response', array('openid.signed' => 'op_endpoint,claimed_id,identity,return_to,response_nonce'));
    $this->submitLoginForm($identity);
    $this->assertRaw('OpenID login failed.');

    // Sign all mandatory fields and a custom field.
    $keys_to_sign = array('op_endpoint', 'claimed_id', 'identity', 'return_to', 'response_nonce', 'assoc_handle', 'foo');
    $association = new stdClass();
    $association->mac_key = variable_get('mac_key');
    $response = array(
      'openid.op_endpoint' => url('openid-test/endpoint', array('absolute' => TRUE)),
      'openid.claimed_id' => $identity,
      'openid.identity' => $identity,
      'openid.return_to' => url('openid/authenticate', array('absolute' => TRUE)),
      'openid.response_nonce' => _openid_nonce(),
      'openid.assoc_handle' => 'openid-test',
      'openid.foo' => 123,
      'openid.signed' => implode(',', $keys_to_sign),
    );
    $response['openid.sig'] = _openid_signature($association, $response, $keys_to_sign);
    variable_set('openid_test_response', $response);
    $this->submitLoginForm($identity);
    $this->assertNoRaw('OpenID login failed.');
    $this->assertFieldByName('name', '', t('No username was supplied by provider.'));
    $this->assertFieldByName('mail', '', t('No e-mail address was supplied by provider.'));

    // Check that unsigned SREG fields are ignored.
    $response = array(
      'openid.signed' => 'op_endpoint,claimed_id,identity,return_to,response_nonce,assoc_handle,sreg.nickname',
      'openid.sreg.nickname' => 'john',
      'openid.sreg.email' => 'john@example.com',
    );
    variable_set('openid_test_response', $response);
    $this->submitLoginForm($identity);
    $this->assertNoRaw('OpenID login failed.');
    $this->assertFieldByName('name', 'john', t('Username was supplied by provider.'));
    $this->assertFieldByName('mail', '', t('E-mail address supplied by provider was ignored.'));
  }
}
