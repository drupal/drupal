<?php

namespace Drupal\basic_auth\Authentication\Provider;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Authentication\AuthenticationProviderChallengeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Http\Exception\CacheableUnauthorizedHttpException;
use Drupal\user\UserAuthInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * HTTP Basic authentication provider.
 */
class BasicAuth implements AuthenticationProviderInterface, AuthenticationProviderChallengeInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserAuthInterface $user_auth, FloodInterface $flood, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->userAuth = $user_auth;
    $this->flood = $flood;
    $this->entityTypeManager = $entity_type_manager;
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
    $flood_config = $this->configFactory->get('user.flood');
    $username = $request->headers->get('PHP_AUTH_USER');
    $password = $request->headers->get('PHP_AUTH_PW');
    // Flood protection: this is very similar to the user login form code.
    // @see \Drupal\user\Form\UserLoginForm::validateAuthentication()
    // Do not allow any login from the current user's IP if the limit has been
    // reached. Default is 50 failed attempts allowed in one hour. This is
    // independent of the per-user limit to catch attempts from one IP to log
    // in to many different user accounts.  We have a reasonably high limit
    // since there may be only one apparent IP for all users at an institution.
    if ($this->flood->isAllowed('basic_auth.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username, 'status' => 1]);
      $account = reset($accounts);
      if ($account) {
        if ($flood_config->get('uid_only')) {
          // Register flood events based on the uid only, so they apply for any
          // IP address. This is the most secure option.
          $identifier = $account->id();
        }
        else {
          // The default identifier is a combination of uid and IP address. This
          // is less secure but more resistant to denial-of-service attacks that
          // could lock out all users with public user names.
          $identifier = $account->id() . '-' . $request->getClientIP();
        }
        // Don't allow login if the limit for this user has been reached.
        // Default is to allow 5 failed attempts every 6 hours.
        if ($this->flood->isAllowed('basic_auth.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          $uid = $this->userAuth->authenticate($username, $password);
          if ($uid) {
            $this->flood->clear('basic_auth.failed_login_user', $identifier);
            return $this->entityTypeManager->getStorage('user')->load($uid);
          }
          else {
            // Register a per-user failed login event.
            $this->flood->register('basic_auth.failed_login_user', $flood_config->get('user_window'), $identifier);
          }
        }
      }
    }
    // Always register an IP-based failed login event.
    $this->flood->register('basic_auth.failed_login_ip', $flood_config->get('ip_window'));
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function challengeException(Request $request, \Exception $previous) {
    $site_config = $this->configFactory->get('system.site');
    $site_name = $site_config->get('name');
    $challenge = new FormattableMarkup('Basic realm="@realm"', [
      '@realm' => !empty($site_name) ? $site_name : 'Access restricted',
    ]);

    // A 403 is converted to a 401 here, but it doesn't matter what the
    // cacheability was of the 403 exception: what matters here is that
    // authentication credentials are missing, i.e. that this request was made
    // as the anonymous user.
    // Therefore, all we must do, is make this response:
    // 1. vary by whether the current user has the 'anonymous' role or not. This
    //    works fine because:
    //    - Thanks to \Drupal\basic_auth\PageCache\DisallowBasicAuthRequests,
    //      Page Cache never caches a response whose request has Basic Auth
    //      credentials.
    //    - Dynamic Page Cache will cache a different result for when the
    //      request is unauthenticated (this 401) versus authenticated (some
    //      other response)
    // 2. have the 'config:user.role.anonymous' cache tag, because the only
    //    reason this 401 would no longer be a 401 is if permissions for the
    //    'anonymous' role change, causing that cache tag to be invalidated.
    // @see \Drupal\Core\EventSubscriber\AuthenticationSubscriber::onExceptionSendChallenge()
    // @see \Drupal\Core\EventSubscriber\ClientErrorResponseSubscriber()
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onAllResponds()
    $cacheability = CacheableMetadata::createFromObject($site_config)
      ->addCacheTags(['config:user.role.anonymous'])
      ->addCacheContexts(['user.roles:anonymous']);
    return $request->isMethodCacheable()
      ? new CacheableUnauthorizedHttpException($cacheability, (string) $challenge, 'No authentication credentials provided.', $previous)
      : new UnauthorizedHttpException((string) $challenge, 'No authentication credentials provided.', $previous);
  }

}
