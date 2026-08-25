<?php

declare(strict_types=1);

namespace Drupal\system_test\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Routing\Attribute\Route;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for system_test routes.
 */
class SystemTestController extends ControllerBase implements TrustedCallbackInterface {

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The persistent lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $persistentLock;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the SystemTestController.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\Lock\LockBackendInterface $persistent_lock
   *   The persistent lock service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch|null $killSwitch
   *   The page cache kill switch. This is here to test nullable types with
   *   \Drupal\Core\DependencyInjection\AutowireTrait::create().
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch|null $killSwitch2
   *   The page cache kill switch. This is here to test nullable types with
   *   \Drupal\Core\DependencyInjection\AutowireTrait::create().
   */
  public function __construct(
    #[Autowire(service: 'lock')]
    LockBackendInterface $lock,
    #[Autowire(service: 'lock.persistent')]
    LockBackendInterface $persistent_lock,
    AccountInterface $current_user,
    RendererInterface $renderer,
    MessengerInterface $messenger,
    public ?KillSwitch $killSwitch = NULL,
    public KillSwitch|null $killSwitch2 = NULL,
  ) {
    $this->lock = $lock;
    $this->persistentLock = $persistent_lock;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
  }

  /**
   * Tests main content fallback.
   *
   * @return string
   *   The text to display.
   */
  #[Route(
    path: '/system-test/main-content-handling',
    name: 'system_test.main_content_handling',
    title: new TranslatableMarkup('Test main content handling'),
    requirements: ['_access' => 'TRUE'],
  )]
  #[Route(
    path: '/system-test/main-content-fallback',
    name: 'system_test.main_content_fallback',
    title: new TranslatableMarkup('Test main content fallback'),
    requirements: ['_access' => 'TRUE'],
  )]
  #[Route(
    path: '/system-test/permission-dependent-route-access',
    name: 'system_test.permission_dependent_route_access',
    requirements: ['_permission' => 'pet llamas'],
  )]
  #[Route(
    path: '/system-test/custom-4xx',
    name: 'system_test.custom_4xx_with_limited_access',
    title: new TranslatableMarkup('Admin-only 4xx response'),
    requirements: ['_role' => 'administrator'],
  )]
  public function mainContentFallback() {
    return ['#markup' => $this->t('Content to test main content fallback')];
  }

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return string
   *   Empty string, we just test the setting of messages.
   */
  #[Route(
    path: '/system-test/messenger-service',
    name: 'system_test.messenger_service',
    title: new TranslatableMarkup('Set messages with Messenger service'),
    requirements: ['_access' => 'TRUE'],
  )]
  public function messengerServiceTest() {
    // Set two messages.
    $this->messenger->addStatus('First message (removed).');
    $this->messenger->addStatus($this->t('Second message with <em>markup!</em> (not removed).'));
    $messages = $this->messenger->deleteByType('status');
    // Remove the first.
    unset($messages[0]);

    foreach ($messages as $message) {
      $this->messenger->addStatus($message);
    }

    // Duplicate message check.
    $this->messenger->addStatus('Non Duplicated message');
    $this->messenger->addStatus('Non Duplicated message');

    $this->messenger->addStatus('Duplicated message', TRUE);
    $this->messenger->addStatus('Duplicated message', TRUE);

    // Add a Markup message.
    $this->messenger->addStatus(Markup::create('Markup with <em>markup!</em>'));
    // Test duplicate Markup messages.
    $this->messenger->addStatus(Markup::create('Markup with <em>markup!</em>'));
    // Ensure that multiple Markup messages work.
    $this->messenger->addStatus(Markup::create('Markup2 with <em>markup!</em>'));

    // Test mixing of types.
    $this->messenger->addStatus(Markup::create('Non duplicate Markup / string.'));
    $this->messenger->addStatus('Non duplicate Markup / string.');
    $this->messenger->addStatus(Markup::create('Duplicate Markup / string.'), TRUE);
    $this->messenger->addStatus('Duplicate Markup / string.', TRUE);

    // Test auto-escape of non safe strings.
    $this->messenger->addStatus('<em>This<span>markup will be</span> escaped</em>.');

    return [];
  }

  /**
   * Sets messages for testing the WebAssert methods related to messages.
   *
   * @return array
   *   Empty array, we just need the messages.
   */
  #[Route(
    path: '/system-test/status-messages-for-assertions',
    name: 'system_test.status_messages_for_assertions',
    title: new TranslatableMarkup('Set various message to test status message assertion methods'),
    requirements: ['_access' => 'TRUE'],
  )]
  public function statusMessagesForAssertions(): array {
    // Add a simple message of each type.
    $this->messenger->addMessage('My Status Message', 'status');
    $this->messenger->addMessage('My Error Message', 'error');
    $this->messenger->addMessage('My Warning Message', 'warning');

    // Add messages with special characters and/or markup.
    $this->messenger->addStatus('This has " in the middle');
    $this->messenger->addStatus('This has \' in the middle');
    $this->messenger->addStatus('<em>This<span>markup will be</span> escaped</em>.');
    $this->messenger->addStatus('Peaches & cream');

    return [];
  }

  /**
   * Controller to return the HTTP method for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  #[Route(
    path: '/system-test/method',
    name: 'system_test.method',
    requirements: ['_access' => 'TRUE'],
  )]
  public function getMethod(Request $request): Response {
    return new Response($request->getMethod());
  }

  /**
   * Controller to return $_GET['destination'] for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  #[Route(
    path: '/system-test/get-destination',
    name: 'system_test.get_destination',
    requirements: ['_access' => 'TRUE'],
  )]
  public function getDestination(Request $request) {
    $response = new Response($request->query->get('destination'));
    return $response;
  }

  /**
   * Controller to return $_REQUEST['destination'] for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  #[Route(
    path: '/system-test/request-destination',
    name: 'system_test.request_destination',
    requirements: ['_access' => 'TRUE'],
  )]
  public function requestDestination(Request $request) {
    $response = new Response($request->request->get('destination'));
    return $response;
  }

  /**
   * Try to acquire a named lock and report the outcome.
   */
  #[Route(
    path: '/system-test/lock-acquire',
    name: 'system_test.lock_acquire',
    title: new TranslatableMarkup('Lock acquire'),
    requirements: ['_access' => 'TRUE'],
    options: ['no_cache' => TRUE],
  )]
  public function lockAcquire() {
    if ($this->lock->acquire('system_test_lock_acquire')) {
      $this->lock->release('system_test_lock_acquire');
      return ['#markup' => 'TRUE: Lock successfully acquired in \Drupal\system_test\Controller\SystemTestController::lockAcquire()'];
    }
    else {
      return ['#markup' => 'FALSE: Lock not acquired in \Drupal\system_test\Controller\SystemTestController::lockAcquire()'];
    }
  }

  /**
   * Try to acquire a specific lock, and then exit.
   */
  #[Route(
    path: '/system-test/lock-exit',
    name: 'system_test.lock_exit',
    title: new TranslatableMarkup('Lock acquire then exit'),
    requirements: ['_access' => 'TRUE'],
    options: ['no_cache' => TRUE],
  )]
  public function lockExit() {
    if ($this->lock->acquire('system_test_lock_exit', 900)) {
      echo 'TRUE: Lock successfully acquired in \Drupal\system_test\Controller\SystemTestController::lockExit()';
      // The shut-down function should release the lock.
      exit();
    }
    else {
      return ['#markup' => 'FALSE: Lock not acquired in system_test_lock_exit()'];
    }
  }

  /**
   * Creates a lock that will persist across requests.
   *
   * @param string $lock_name
   *   The name of the persistent lock to acquire.
   *
   * @return string
   *   The text to display.
   */
  #[Route(
    path: '/system-test/lock-persist/{lock_name}',
    name: 'system_test.lock_persist',
    title: new TranslatableMarkup('Persistent lock acquire'),
    requirements: ['_access' => 'TRUE'],
    options: ['no_cache' => TRUE],
  )]
  public function lockPersist($lock_name) {
    if ($this->persistentLock->acquire($lock_name)) {
      return ['#markup' => 'TRUE: Lock successfully acquired in SystemTestController::lockPersist()'];
    }
    else {
      return ['#markup' => 'FALSE: Lock not acquired in SystemTestController::lockPersist()'];
    }
  }

  /**
   * Set cache tag on the returned render array.
   */
  #[Route(
    path: '/system-test/cache_tags_page',
    name: 'system_test.cache_tags_page',
    requirements: ['_access' => 'TRUE'],
  )]
  public function system_test_cache_tags_page() {
    $build['main'] = [
      '#cache' => ['tags' => ['system_test_cache_tags_page']],
      '#pre_render' => [
        '\Drupal\system_test\Controller\SystemTestController::preRenderCacheTags',
      ],
      'message' => [
        '#markup' => 'Cache tags page example',
      ],
    ];
    return $build;
  }

  /**
   * Set cache max-age on the returned render array.
   */
  #[Route(
    path: '/system-test/cache_max_age_page',
    name: 'system_test.cache_max_age_page',
    requirements: ['_access' => 'TRUE'],
  )]
  public function system_test_cache_max_age_page() {
    $build['main'] = [
      '#cache' => ['max-age' => 90],
      'message' => [
        '#markup' => 'Cache max-age page example',
      ],
    ];
    return $build;
  }

  /**
   * Sets a cache tag on an element to help test #pre_render and cache tags.
   */
  public static function preRenderCacheTags($elements) {
    $elements['#cache']['tags'][] = 'pre_render';
    return $elements;
  }

  /**
   * Sets a header.
   */
  #[Route(
    path: '/system-test/set-header',
    name: 'system_test.set_header',
    requirements: ['_access' => 'TRUE'],
  )]
  public function setHeader(Request $request) {
    $query = $request->query->all();
    $response = new CacheableResponse();
    $response->headers->set($query['name'], $query['value']);
    $response->getCacheableMetadata()->addCacheContexts(['url.query_args:name', 'url.query_args:value']);
    $response->setContent((string) $this->t('The following header was set: %name: %value', [
      '%name' => $query['name'],
      '%value' => $query['value'],
    ]));

    return $response;
  }

  /**
   * A simple page callback that uses a plain Symfony response object.
   */
  #[Route(
    path: '/system-test/respond-response',
    name: 'system_test.respond_response',
    requirements: ['_access' => 'TRUE'],
  )]
  public function respondWithResponse(Request $request) {
    return new Response('test');
  }

  /**
   * A plain Symfony response with Cache-Control: public, max-age=60.
   */
  #[Route(
    path: '/system-test/respond-public-response',
    name: 'system_test.respond_public_response',
    requirements: ['_access' => 'TRUE'],
  )]
  public function respondWithPublicResponse() {
    return (new Response('test'))->setPublic()->setMaxAge(60);
  }

  /**
   * A simple page callback that uses a CacheableResponse object.
   */
  #[Route(
    path: '/system-test/respond-cacheable-response',
    name: 'system_test.respond_cacheable_response',
    requirements: ['_access' => 'TRUE'],
  )]
  public function respondWithCacheableResponse(Request $request) {
    return new CacheableResponse('test');
  }

  /**
   * A simple page callback which adds a register shutdown function.
   */
  #[Route(
    path: '/system-test/shutdown-functions/{arg1}/{arg2}',
    name: 'system_test.shutdown_functions',
    requirements: ['_access' => 'TRUE'],
  )]
  public function shutdownFunctions($arg1, $arg2) {
    drupal_register_shutdown_function('_system_test_first_shutdown_function', $arg1, $arg2);
    // If using PHP-FPM then fastcgi_finish_request() will have been fired
    // preventing further output to the browser which means that the escaping of
    // the exception message can not be tested.
    // @see _drupal_shutdown_function()
    // @see \Drupal\system\Tests\System\ShutdownFunctionsTest
    if (function_exists('fastcgi_finish_request') || ob_get_status()) {
      return ['#markup' => 'The response will flush before shutdown functions are called.'];
    }
    return [];
  }

  /**
   * Returns the title for system_test.info.yml's configure route.
   *
   * @param string $foo
   *   Any string for the {foo} slug.
   *
   * @return string
   *   The value of title.
   */
  public function configureTitle($foo) {
    return 'Bar.' . $foo;
  }

  /**
   * Simple argument echo.
   *
   * @param string $text
   *   Any string for the {text} slug.
   *
   * @return array
   *   A render array.
   */
  #[Route(
    path: '/system-test/echo/{text}',
    name: 'system_test.echo',
    requirements: ['_access' => 'TRUE'],
  )]
  #[Route(
    // cspell:disable-next-line
    path: '/system-test/Ȅchȏ/meφΩ/{text}',
    name: 'system_test.echo_utf8',
    requirements: ['_access' => 'TRUE'],
  )]
  public function simpleEcho($text) {
    return [
      '#plain_text' => $text,
    ];
  }

  /**
   * Shows permission-dependent content.
   *
   * @return array
   *   A render array.
   */
  #[Route(
    path: '/system-test/permission-dependent-content',
    name: 'system_test.permission_dependent_content',
    requirements: ['_access' => 'TRUE'],
  )]
  public function permissionDependentContent() {
    $build = [];

    // The content depends on the access result.
    $access = AccessResult::allowedIfHasPermission($this->currentUser, 'pet llamas');
    $this->renderer->addCacheableDependency($build, $access);

    // Build the content.
    if ($access->isAllowed()) {
      $build['allowed'] = ['#markup' => 'Permission to pet llamas: yes!'];
    }
    else {
      $build['forbidden'] = ['#markup' => 'Permission to pet llamas: no!'];
    }

    return $build;
  }

  /**
   * Returns the current date.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response object containing the current date.
   */
  #[Route(
    path: '/system-test/date',
    name: 'system_test.date',
    requirements: ['_access' => 'TRUE'],
    options: ['no_cache' => 'TRUE'],
  )]
  public function getCurrentDate() {
    // Uses specific time to test that the right timezone is used.
    $response = new Response(\Drupal::service('date.formatter')->format(1452702549));
    return $response;
  }

  /**
   * Returns a response with a test header set from the request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response object containing the test header.
   */
  #[Route(
    path: '/system-test/header',
    name: 'system_test.header',
    requirements: ['_access' => 'TRUE'],
  )]
  public function getTestHeader(Request $request) {
    $response = new Response();
    $response->headers->set('Test-Header', $request->headers->get('Test-Header'));
    return $response;
  }

  /**
   * Returns a cacheable response with a custom cache control.
   */
  #[Route(
    path: '/system-test/custom-cache-control',
    name: 'system_test.custom_cache_control',
    title: new TranslatableMarkup('Cacheable response with custom cache control'),
    requirements: ['_access' => 'TRUE'],
  )]
  public function getCacheableResponseWithCustomCacheControl() {
    return new CacheableResponse('Foo', 200, ['Cache-Control' => 'bar']);
  }

  /**
   * Returns a CacheableRedirectResponse with the given status code.
   */
  #[Route(
    path: '/system-test/redirect/cacheable/{status_code}',
    name: 'router_test.cacheable_redirect',
    requirements: ['_access' => 'TRUE', 'status_code' => '201|301|302|303|307|308'],
  )]
  public function respondWithCacheableRedirectResponse(int $status_code): CacheableRedirectResponse {
    return new CacheableRedirectResponse('/llamas', $status_code);
  }

  /**
   * Returns a LocalRedirectResponse with the given status code.
   */
  #[Route(
    path: '/system-test/redirect/local/{status_code}',
    name: 'router_test.local_redirect',
    requirements: ['_access' => 'TRUE', 'status_code' => '201|301|302|303|307|308'],
  )]
  public function respondWithLocalRedirectResponse(int $status_code): LocalRedirectResponse {
    return new LocalRedirectResponse('/llamas', $status_code);
  }

  /**
   * Returns a TrustedRedirectResponse with the given status code.
   */
  #[Route(
    path: '/system-test/redirect/trusted/{status_code}',
    name: 'router_test.trusted_redirect',
    requirements: ['_access' => 'TRUE', 'status_code' => '201|301|302|303|307|308'],
  )]
  public function respondWithTrustedRedirectResponse(int $status_code): TrustedRedirectResponse {
    return new TrustedRedirectResponse('/llamas', $status_code);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderCacheTags'];
  }

  /**
   * Use a plain Symfony response object to output the current install_profile.
   */
  #[Route(
    path: '/system-test/get-install-profile',
    name: 'system_test.install_profile',
    requirements: ['_access' => 'TRUE'],
  )]
  public function getInstallProfile() {
    $install_profile = \Drupal::installProfile() ?: 'NONE';
    return new Response('install_profile: ' . $install_profile);
  }

}
