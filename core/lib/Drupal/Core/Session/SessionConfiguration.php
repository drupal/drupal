<?php

namespace Drupal\Core\Session;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the default session configuration generator.
 */
class SessionConfiguration implements SessionConfigurationInterface {

  /**
   * An associative array of session ini settings.
   */
  protected $options;

  /**
   * Constructs a new session configuration instance.
   *
   * @param array $options
   *   An associative array of session ini settings.
   *
   * @see \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage::__construct()
   * @see http://php.net/manual/session.configuration.php
   */
  public function __construct($options = []) {
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSession(Request $request) {
    return $request->cookies->has($this->getName($request));
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    $options = $this->options;

    // Generate / validate the cookie domain.
    $options['cookie_domain'] = $this->getCookieDomain($request) ?: '';

    // If the site is accessed via SSL, ensure that the session cookie is
    // issued with the secure flag.
    $options['cookie_secure'] = $request->isSecure();

    // Set the session cookie name.
    $options['name'] = $this->getName($request);

    return $options;
  }

  /**
   * Returns the session cookie name.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return string
   *   The name of the session cookie.
   */
  protected function getName(Request $request) {
    // To prevent session cookies from being hijacked, a user can configure the
    // SSL version of their website to only transfer session cookies via SSL by
    // using PHP's session.cookie_secure setting. The browser will then use two
    // separate session cookies for the HTTPS and HTTP versions of the site. So
    // we must use different session identifiers for HTTPS and HTTP to prevent a
    // cookie collision.
    $prefix = $request->isSecure() ? 'SSESS' : 'SESS';
    return $prefix . $this->getUnprefixedName($request);
  }

  /**
   * Returns the session cookie name without the secure/insecure prefix.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @returns string
   *   The session name without the prefix (SESS/SSESS).
   */
  protected function getUnprefixedName(Request $request) {
    if ($test_prefix = $this->drupalValidTestUa()) {
      $session_name = $test_prefix;
    }
    elseif (isset($this->options['cookie_domain'])) {
      // If the user specifies the cookie domain, also use it for session name.
      $session_name = $this->options['cookie_domain'];
    }
    else {
      // Otherwise use $base_url as session name, without the protocol
      // to use the same session identifiers across HTTP and HTTPS.
      $session_name = $request->getHost() . $request->getBasePath();
      // Replace "core" out of session_name so core scripts redirect properly,
      // specifically install.php.
      $session_name = preg_replace('#/core$#', '', $session_name);
    }

    return substr(hash('sha256', $session_name), 0, 32);
  }

  /**
   * Return the session cookie domain.
   *
   * The Set-Cookie response header and its domain attribute are defined in RFC
   * 2109, RFC 2965 and RFC 6265 each one superseeding the previous version.
   *
   * @see http://tools.ietf.org/html/rfc2109
   * @see http://tools.ietf.org/html/rfc2965
   * @see http://tools.ietf.org/html/rfc6265
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @returns string
   *   The session cookie domain.
   */
  protected function getCookieDomain(Request $request) {
    if (isset($this->options['cookie_domain'])) {
      $cookie_domain = $this->options['cookie_domain'];
    }
    else {
      $host = $request->getHost();
      // To maximize compatibility and normalize the behavior across user
      // agents, the cookie domain should start with a dot.
      $cookie_domain = '.' . $host;
    }

    // Cookies for domains without an embedded dot will be rejected by user
    // agents in order to defeat malicious websites attempting to set cookies
    // for top-level domains. Also IP addresses may not be used in the domain
    // attribute of a Set-Cookie header.
    if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      return $cookie_domain;
    }
  }

  /**
   * Wraps drupal_valid_test_ua().
   *
   * @return string|FALSE
   *   Either the simpletest prefix (the string "simpletest" followed by any
   *   number of digits) or FALSE if the user agent does not contain a valid
   *   HMAC and timestamp.
   */
  protected function drupalValidTestUa() {
    return drupal_valid_test_ua();
  }

}
