<?php

/**
 * @file
 * Contains \Drupal\basic_auth\Authentication\Provider\BasicAuth.
 */

namespace Drupal\basic_auth\Authentication\Provider;

use \Drupal\Component\Utility\String;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * HTTP Basic authentication provider.
 */
class BasicAuth implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $username = $request->headers->get('PHP_AUTH_USER');
    $password = $request->headers->get('PHP_AUTH_PW');
    return isset($username) && isset($password);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $username = $request->headers->get('PHP_AUTH_USER');
    $password = $request->headers->get('PHP_AUTH_PW');
    $uid = user_authenticate($username, $password);
    if ($uid) {
      return user_load($uid);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {}

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if (user_is_anonymous() && $exception instanceof AccessDeniedHttpException) {
      if (!$this->applies($event->getRequest())) {
        $site_name = $this->configFactory->get('system.site')->get('name');
        global $base_url;
        $challenge = String::format('Basic realm="@realm"', array(
          '@realm' => !empty($site_name) ? $site_name : $base_url,
        ));
        $event->setException(new UnauthorizedHttpException($challenge, 'No authentication credentials provided.', $exception));
      }
      return TRUE;
    }
    return FALSE;
  }
}
