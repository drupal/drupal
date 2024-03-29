<?php

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
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
  public function mainContentFallback() {
    return ['#markup' => $this->t('Content to test main content fallback')];
  }

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return string
   *   Empty string, we just test the setting of messages.
   */
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
   * Controller to return $_GET['destination'] for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
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
  public function requestDestination(Request $request) {
    $response = new Response($request->request->get('destination'));
    return $response;
  }

  /**
   * Try to acquire a named lock and report the outcome.
   */
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
   * Initialize authorize.php during testing.
   *
   * @see system_authorized_init()
   */
  public function authorizeInit($page_title) {
    $authorize_url = Url::fromUri('base:core/authorize.php', ['absolute' => TRUE])->toString();
    system_authorized_init('system_test_authorize_run', __DIR__ . '/../../system_test.module', [], $page_title);
    return new RedirectResponse($authorize_url);
  }

  /**
   * Sets a header.
   */
  public function setHeader(Request $request) {
    $query = $request->query->all();
    $response = new CacheableResponse();
    $response->headers->set($query['name'], $query['value']);
    $response->getCacheableMetadata()->addCacheContexts(['url.query_args:name', 'url.query_args:value']);
    $response->setContent($this->t('The following header was set: %name: %value', ['%name' => $query['name'], '%value' => $query['value']]));

    return $response;
  }

  /**
   * A simple page callback that uses a plain Symfony response object.
   */
  public function respondWithResponse(Request $request) {
    return new Response('test');
  }

  /**
   * A plain Symfony response with Cache-Control: public, max-age=60.
   */
  public function respondWithPublicResponse() {
    return (new Response('test'))->setPublic()->setMaxAge(60);
  }

  /**
   * A simple page callback that uses a CacheableResponse object.
   */
  public function respondWithCacheableResponse(Request $request) {
    return new CacheableResponse('test');
  }

  /**
   * A simple page callback which adds a register shutdown function.
   */
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
  public function getTestHeader(Request $request) {
    $response = new Response();
    $response->headers->set('Test-Header', $request->headers->get('Test-Header'));
    return $response;
  }

  /**
   * Returns a cacheable response with a custom cache control.
   */
  public function getCacheableResponseWithCustomCacheControl() {
    return new CacheableResponse('Foo', 200, ['Cache-Control' => 'bar']);
  }

  /**
   * Returns a CacheableRedirectResponse with the given status code.
   */
  public function respondWithCacheableRedirectResponse(int $status_code): CacheableRedirectResponse {
    return new CacheableRedirectResponse('/llamas', $status_code);
  }

  /**
   * Returns a LocalRedirectResponse with the given status code.
   */
  public function respondWithLocalRedirectResponse(int $status_code): LocalRedirectResponse {
    return new LocalRedirectResponse('/llamas', $status_code);
  }

  /**
   * Returns a TrustedRedirectResponse with the given status code.
   */
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
  public function getInstallProfile() {
    $install_profile = \Drupal::installProfile() ?: 'NONE';
    return new Response('install_profile: ' . $install_profile);
  }

}
