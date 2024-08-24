<?php

declare(strict_types=1);

namespace Drupal\session_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\session_test\Session\TestSessionBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller providing page callbacks for the action admin interface.
 */
class SessionTestController extends ControllerBase {

  /**
   * Prints the stored session value to the screen.
   *
   * @return string
   *   A notification message.
   */
  public function get() {
    return empty($_SESSION['session_test_value'])
      ? []
      : ['#markup' => $this->t('The current value of the stored session variable is: %val', ['%val' => $_SESSION['session_test_value']])];
  }

  /**
   * Prints the stored session value to the screen.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return string
   *   A notification message.
   */
  public function getFromSessionObject(Request $request) {
    $value = $request->getSession()->get("session_test_key");
    return empty($value)
      ? []
      : ['#markup' => $this->t('The current value of the stored session variable is: %val', ['%val' => $value])];
  }

  /**
   * Print the current session ID.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return string
   *   A notification message with session ID.
   */
  public function getId(Request $request) {
    // Set a value in $_SESSION, so that SessionManager::save() will start
    // a session.
    $_SESSION['test'] = 'test';

    $request->getSession()->save();

    return ['#markup' => 'session_id:' . session_id() . "\n"];
  }

  /**
   * Print the current session ID as read from the cookie.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   A notification message with session ID.
   */
  public function getIdFromCookie(Request $request) {
    return ['#markup' => 'session_id:' . $request->cookies->get(session_name()) . "\n", '#cache' => ['contexts' => ['cookies:' . session_name()]]];
  }

  /**
   * Stores a value in $_SESSION['session_test_value'].
   *
   * @param string $test_value
   *   A session value.
   *
   * @return string
   *   A notification message.
   */
  public function set($test_value) {
    $_SESSION['session_test_value'] = $test_value;

    return ['#markup' => $this->t('The current value of the stored session variable has been set to %val', ['%val' => $test_value])];
  }

  /**
   * Turns off session saving and then tries to save a value anyway.
   *
   * @param string $test_value
   *   A session value.
   *
   * @return string
   *   A notification message.
   */
  public function noSet($test_value) {
    \Drupal::service('session_handler.write_safe')->setSessionWritable(FALSE);
    $this->set($test_value);
    return ['#markup' => $this->t('session saving was disabled, and then %val was set', ['%val' => $test_value])];
  }

  /**
   * Sets a message to me displayed on the following page.
   *
   * @return string
   *   A notification message.
   */
  public function setMessage() {
    $this->messenger()->addStatus($this->t('This is a dummy message.'));
    return new Response((string) $this->t('A message was set.'));
    // Do not return anything, so the current request does not result in a themed
    // page with messages. The message will be displayed in the following request
    // instead.
  }

  /**
   * Sets a message but call drupal_save_session(FALSE).
   *
   * @return string
   *   A notification message.
   */
  public function setMessageButDoNotSave() {
    \Drupal::service('session_handler.write_safe')->setSessionWritable(FALSE);
    $this->setMessage();
    return ['#markup' => ''];
  }

  /**
   * Only available if current user is logged in.
   *
   * @return string
   *   A notification message.
   */
  public function isLoggedIn() {
    return ['#markup' => $this->t('User is logged in.')];
  }

  /**
   * Returns the trace recorded by test proxy session handlers as JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function traceHandler(Request $request) {
    // Start a session if necessary, set a value and then save and close it.
    $request->getSession()->start();
    if (empty($_SESSION['trace-handler'])) {
      $_SESSION['trace-handler'] = 1;
    }
    else {
      $_SESSION['trace-handler']++;
    }
    $request->getSession()->save();

    // Collect traces and return them in JSON format.
    $trace = \Drupal::service('session_test.session_handler_proxy_trace')->getArrayCopy();

    return new JsonResponse($trace);
  }

  /**
   * Returns the values stored in the active session and the user ID.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response object containing the session values and the user ID.
   */
  public function getSession(Request $request) {
    return new JsonResponse(['session' => $request->getSession()->all(), 'user' => $this->currentUser()->id()]);
  }

  /**
   * Sets a test value on the session.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $test_value
   *   A value to set on the session.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response object containing the session values and the user ID.
   */
  public function setSession(Request $request, $test_value) {
    $session = $request->getSession();
    $session->set('test_value', $test_value);
    return new JsonResponse(['session' => $session->all(), 'user' => $this->currentUser()->id()]);
  }

  /**
   * Sets the test flag in the session test bag.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function setSessionBagFlag(Request $request) {
    /** @var \Drupal\session_test\Session\TestSessionBag */
    $bag = $request->getSession()->getBag(TestSessionBag::BAG_NAME);
    $bag->setFlag();
    return new Response();
  }

  /**
   * Clears the test flag from the session test bag.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function clearSessionBagFlag(Request $request) {
    /** @var \Drupal\session_test\Session\TestSessionBag */
    $bag = $request->getSession()->getBag(TestSessionBag::BAG_NAME);
    $bag->clearFlag();
    return new Response();
  }

  /**
   * Prints a message if the flag in the session bag is set.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function hasSessionBagFlag(Request $request) {
    /** @var \Drupal\session_test\Session\TestSessionBag */
    $bag = $request->getSession()->getBag(TestSessionBag::BAG_NAME);
    return new Response(empty($bag->hasFlag())
      ? (string) $this->t('Flag is absent from session bag')
      : (string) $this->t('Flag is present in session bag')
    );
  }

  /**
   * Trigger an exception when the session is written.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function triggerWriteException(Request $request) {
    $session = $request->getSession();
    $session->set('test_value', 'Ensure session contains some data');

    // Move sessions table out of the way.
    $schema = \Drupal::database()->schema();
    $schema->renameTable('sessions', 'sessions_tmp');

    // There needs to be a session table, otherwise
    // InstallerRedirectTrait::shouldRedirectToInstaller() will instruct the
    // handleException::handleException to redirect to the installer.
    $schema->createTable('sessions', [
      'description' => "Fake sessions table missing some columns.",
      'fields' => [
        'sid' => [
          'description' => "A fake session ID column.",
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['sid'],
    ]);

    drupal_register_shutdown_function(function () {
      $schema = \Drupal::database()->schema();
      $schema->dropTable('sessions');
      $schema->renameTable('sessions_tmp', 'sessions');
    });

    return new Response();
  }

}
