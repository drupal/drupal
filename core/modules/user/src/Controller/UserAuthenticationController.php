<?php

namespace Drupal\user\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\user\UserAuthenticationInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserFloodControlInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Provides controllers for login, login status and logout via HTTP requests.
 */
class UserAuthenticationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * String sent in responses, to describe the user as being logged in.
   *
   * @var string
   */
  const LOGGED_IN = '1';

  /**
   * String sent in responses, to describe the user as being logged out.
   *
   * @var string
   */
  const LOGGED_OUT = '0';

  /**
   * The user flood control service.
   *
   * @var \Drupal\user\UserFloodControl
   */
  protected $userFloodControl;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The user authentication.
   * @var \Drupal\user\UserAuthInterface|\Drupal\user\UserAuthenticationInterface
   */
  protected $userAuth;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new UserAuthenticationController object.
   *
   * @param \Drupal\user\UserFloodControlInterface $user_flood_control
   *   The user flood control service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   * @param \Drupal\user\UserAuthenticationInterface|\Drupal\user\UserAuthInterface $user_auth
   *   The user authentication.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(UserFloodControlInterface $user_flood_control, UserStorageInterface $user_storage, CsrfTokenGenerator $csrf_token, UserAuthenticationInterface|UserAuthInterface $user_auth, RouteProviderInterface $route_provider, Serializer $serializer, array $serializer_formats, LoggerInterface $logger) {
    $this->userFloodControl = $user_flood_control;
    $this->userStorage = $user_storage;
    $this->csrfToken = $csrf_token;
    if (!$user_auth instanceof UserAuthenticationInterface) {
      @trigger_error('The $user_auth parameter implementing UserAuthInterface is deprecated in drupal:10.3.0 and will be removed in drupal:12.0.0. Implement UserAuthenticationInterface instead. See https://www.drupal.org/node/3411040');
    }
    $this->userAuth = $user_auth;
    $this->serializer = $serializer;
    $this->serializerFormats = $serializer_formats;
    $this->routeProvider = $route_provider;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    if ($container->hasParameter('serializer.formats') && $container->has('serializer')) {
      $serializer = $container->get('serializer');
      $formats = $container->getParameter('serializer.formats');
    }
    else {
      $formats = ['json'];
      $encoders = [new JsonEncoder()];
      $serializer = new Serializer([], $encoders);
    }

    return new static(
      $container->get('user.flood_control'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('csrf_token'),
      $container->get('user.auth'),
      $container->get('router.route_provider'),
      $serializer,
      $formats,
      $container->get('logger.factory')->get('user')
    );
  }

  /**
   * Logs in a user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response which contains the ID and CSRF token.
   */
  public function login(Request $request) {
    $format = $this->getRequestFormat($request);

    $content = $request->getContent();
    $credentials = $this->serializer->decode($content, $format);
    if (!isset($credentials['name']) && !isset($credentials['pass'])) {
      throw new BadRequestHttpException('Missing credentials.');
    }

    if (!isset($credentials['name'])) {
      throw new BadRequestHttpException('Missing credentials.name.');
    }
    if (!isset($credentials['pass'])) {
      throw new BadRequestHttpException('Missing credentials.pass.');
    }

    $this->floodControl($request, $credentials['name']);

    $account = FALSE;

    if ($this->userAuth instanceof UserAuthenticationInterface) {
      $account = $this->userAuth->lookupAccount($credentials['name']);
    }
    else {
      $accounts = $this->userStorage->loadByProperties(['name' => $credentials['name']]);
      if ($accounts) {
        $account = reset($accounts);
      }
    }

    if ($account) {
      if ($account->isBlocked()) {
        throw new BadRequestHttpException('The user has not been activated or is blocked.');
      }
      if ($this->userAuth instanceof UserAuthenticationInterface) {
        $authenticated = $this->userAuth->authenticateAccount($account, $credentials['pass']) ? $account->id() : FALSE;
      }
      else {
        $authenticated = $this->userAuth->authenticateAccount($credentials['name'], $credentials['pass']);
      }
      if ($authenticated) {
        $this->userFloodControl->clear('user.http_login', $this->getLoginFloodIdentifier($request, $credentials['name']));
        $this->userLoginFinalize($account);

        // Send basic metadata about the logged in user.
        $response_data = [];
        if ($account->get('uid')->access('view', $account)) {
          $response_data['current_user']['uid'] = $account->id();
        }
        if ($account->get('roles')->access('view', $account)) {
          $response_data['current_user']['roles'] = $account->getRoles();
        }
        if ($account->get('name')->access('view', $account)) {
          $response_data['current_user']['name'] = $account->getAccountName();
        }
        $response_data['csrf_token'] = $this->csrfToken->get('rest');

        $logout_route = $this->routeProvider->getRouteByName('user.logout.http');
        // Trim '/' off path to match \Drupal\Core\Access\CsrfAccessCheck.
        $logout_path = ltrim($logout_route->getPath(), '/');
        $response_data['logout_token'] = $this->csrfToken->get($logout_path);

        $encoded_response_data = $this->serializer->encode($response_data, $format);
        return new Response($encoded_response_data);
      }
    }

    $flood_config = $this->config('user.flood');
    if ($identifier = $this->getLoginFloodIdentifier($request, $credentials['name'])) {
      $this->userFloodControl->register('user.http_login', $flood_config->get('user_window'), $identifier);
    }
    // Always register an IP-based failed login event.
    $this->userFloodControl->register('user.failed_login_ip', $flood_config->get('ip_window'));
    throw new BadRequestHttpException('Sorry, unrecognized username or password.');
  }

  /**
   * Resets a user password.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function resetPassword(Request $request) {
    $format = $this->getRequestFormat($request);

    $content = $request->getContent();
    $credentials = $this->serializer->decode($content, $format);

    // Check if a name or mail is provided.
    if (!isset($credentials['name']) && !isset($credentials['mail'])) {
      throw new BadRequestHttpException('Missing credentials.name or credentials.mail');
    }

    // Load by name if provided.
    $identifier = '';
    if (isset($credentials['name'])) {
      $identifier = $credentials['name'];
      $users = $this->userStorage->loadByProperties(['name' => trim($identifier)]);
    }
    elseif (isset($credentials['mail'])) {
      $identifier = $credentials['mail'];
      $users = $this->userStorage->loadByProperties(['mail' => trim($identifier)]);
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = reset($users);
    if ($account && $account->id()) {
      if ($account->isBlocked()) {
        $this->logger->error('Unable to send password reset email for blocked or not yet activated user %identifier.', [
          '%identifier' => $identifier,
        ]);
        return new Response();
      }

      // Send the password reset email.
      $mail = _user_mail_notify('password_reset', $account);
      if (empty($mail)) {
        throw new BadRequestHttpException('Unable to send email. Contact the site administrator if the problem persists.');
      }
      else {
        $this->logger->info('Password reset instructions mailed to %name at %email.', ['%name' => $account->getAccountName(), '%email' => $account->getEmail()]);
        return new Response();
      }
    }

    // Error if no users found with provided name or mail.
    $this->logger->error('Unable to send password reset email for unrecognized username or email address %identifier.', [
      '%identifier' => $identifier,
    ]);
    return new Response();
  }

  /**
   * Verifies if the user is blocked.
   *
   * @param string $name
   *   The username.
   *
   * @return bool
   *   TRUE if the user is blocked, otherwise FALSE.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. There
   * is no replacement.
   * @see https://www.drupal.org/node/3425340
   */
  protected function userIsBlocked($name) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3425340', E_USER_DEPRECATED);
    return user_is_blocked($name);
  }

  /**
   * Finalizes the user login.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   */
  protected function userLoginFinalize(UserInterface $user) {
    user_login_finalize($user);
  }

  /**
   * Logs out a user.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function logout() {
    $this->userLogout();
    return new Response(NULL, 204);
  }

  /**
   * Logs the user out.
   */
  protected function userLogout() {
    user_logout();
  }

  /**
   * Checks whether a user is logged in or not.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function loginStatus() {
    if ($this->currentUser()->isAuthenticated()) {
      $response = new Response(self::LOGGED_IN);
    }
    else {
      $response = new Response(self::LOGGED_OUT);
    }
    $response->headers->set('Content-Type', 'text/plain');
    return $response;
  }

  /**
   * Gets the format of the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   The format of the request.
   */
  protected function getRequestFormat(Request $request) {
    $format = $request->getRequestFormat();
    if (!in_array($format, $this->serializerFormats)) {
      throw new BadRequestHttpException("Unrecognized format: $format.");
    }
    return $format;
  }

  /**
   * Enforces flood control for the current login request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $username
   *   The user name sent for login credentials.
   */
  protected function floodControl(Request $request, $username) {
    $flood_config = $this->config('user.flood');
    if (!$this->userFloodControl->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      throw new AccessDeniedHttpException('Access is blocked because of IP based flood prevention.', NULL, Response::HTTP_TOO_MANY_REQUESTS);
    }

    if ($identifier = $this->getLoginFloodIdentifier($request, $username)) {
      // Don't allow login if the limit for this user has been reached.
      // Default is to allow 5 failed attempts every 6 hours.
      if (!$this->userFloodControl->isAllowed('user.http_login', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
        if ($flood_config->get('uid_only')) {
          $error_message = sprintf('There have been more than %s failed login attempts for this account. It is temporarily blocked. Try again later or request a new password.', $flood_config->get('user_limit'));
        }
        else {
          $error_message = 'Too many failed login attempts from your IP address. This IP address is temporarily blocked.';
        }
        throw new AccessDeniedHttpException($error_message, NULL, Response::HTTP_TOO_MANY_REQUESTS);
      }
    }
  }

  /**
   * Gets the login identifier for user login flood control.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $username
   *   The username supplied in login credentials.
   *
   * @return string
   *   The login identifier or if the user does not exist an empty string.
   */
  protected function getLoginFloodIdentifier(Request $request, $username) {
    $flood_config = $this->config('user.flood');
    $accounts = $this->userStorage->loadByProperties(['name' => $username, 'status' => 1]);
    if ($account = reset($accounts)) {
      if ($flood_config->get('uid_only')) {
        // Register flood events based on the uid only, so they apply for any
        // IP address. This is the most secure option.
        $identifier = $account->id();
      }
      else {
        // The default identifier is a combination of uid and IP address. This
        // is less secure but more resistant to denial-of-service attacks that
        // could lock out all users with public user names.
        $identifier = $account->id() . '-' . $request->getClientIp();
      }
      return $identifier;
    }
    return '';
  }

}
