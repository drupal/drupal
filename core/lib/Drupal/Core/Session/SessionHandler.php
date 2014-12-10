<?php

/**
 * @file
 * Contains \Drupal\Core\Session\SessionHandler.
 */

namespace Drupal\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

/**
 * Default session handler.
 */
class SessionHandler extends AbstractProxy implements \SessionHandlerInterface {

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * An associative array of obsolete sessions with session id as key, and db-key as value.
   *
   * @var array
   */
  protected $obsoleteSessionIds = array();

  /**
   * Constructs a new SessionHandler instance.
   *
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(SessionManagerInterface $session_manager, RequestStack $request_stack, Connection $connection) {
    $this->sessionManager = $session_manager;
    $this->requestStack = $request_stack;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function open($save_path, $name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function read($sid) {
    global $user;

    // Handle the case of first time visitors and clients that don't store
    // cookies (eg. web crawlers).
    $cookies = $this->requestStack->getCurrentRequest()->cookies;
    if (empty($sid) || !$cookies->has($this->getName())) {
      $user = new UserSession();
      return '';
    }

    $values = $this->connection->query("SELECT u.*, s.* FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE u.default_langcode = 1 AND s.sid = :sid", array(
      ':sid' => Crypt::hashBase64($sid),
    ))->fetchAssoc();

    // We found the client's session record and they are an authenticated,
    // active user.
    if ($values && $values['uid'] > 0 && $values['status'] == 1) {
      // Add roles element to $user.
      $rids = $this->connection->query("SELECT ur.roles_target_id as rid FROM {user__roles} ur WHERE ur.entity_id = :uid", array(
        ':uid' => $values['uid'],
      ))->fetchCol();
      $values['roles'] = array_merge(array(DRUPAL_AUTHENTICATED_RID), $rids);
      $user = new UserSession($values);
    }
    elseif ($values) {
      // The user is anonymous or blocked. Only preserve two fields from the
      // {sessions} table.
      $user = new UserSession(array(
        'session' => $values['session'],
        'access' => $values['access'],
      ));
    }
    else {
      // The session has expired.
      $user = new UserSession();
    }

    return $user->session;
  }

  /**
   * {@inheritdoc}
   */
  public function write($sid, $value) {
    global $user;

    // The exception handler is not active at this point, so we need to do it
    // manually.
    try {
      if (!$this->sessionManager->isEnabled()) {
        // We don't have anything to do if we are not allowed to save the
        // session.
        return TRUE;
      }

      $fields = array(
        'uid' => $user->id(),
        'hostname' => $this->requestStack->getCurrentRequest()->getClientIP(),
        'session' => $value,
        'timestamp' => REQUEST_TIME,
      );
      $this->connection->merge('sessions')
        ->keys(array('sid' => Crypt::hashBase64($sid)))
        ->fields($fields)
        ->execute();

      // Likewise, do not update access time more than once per 180 seconds.
      if ($user->isAuthenticated() && REQUEST_TIME - $user->getLastAccessedTime() > Settings::get('session_write_interval', 180)) {
        /** @var \Drupal\user\UserStorageInterface $storage */
        $storage = \Drupal::entityManager()->getStorage('user');
        $storage->updateLastAccessTimestamp($user, REQUEST_TIME);
      }
      return TRUE;
    }
    catch (\Exception $exception) {
      require_once DRUPAL_ROOT . '/core/includes/errors.inc';
      // If we are displaying errors, then do so with no possibility of a
      // further uncaught exception being thrown.
      if (error_displayable()) {
        print '<h1>Uncaught exception thrown in session handler.</h1>';
        print '<p>' . Error::renderExceptionSafe($exception) . '</p><hr />';
      }
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function close() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy($sid) {
    global $user;

    // Nothing to do if we are not allowed to change the session.
    if (!$this->sessionManager->isEnabled()) {
      return TRUE;
    }
    // Delete session data.
    $this->connection->delete('sessions')
      ->condition('sid', Crypt::hashBase64($sid))
      ->execute();

    // Reset $_SESSION and $user to prevent a new session from being started
    // in \Drupal\Core\Session\SessionManager::save().
    $_SESSION = array();
    $user = new AnonymousUserSession();

    // Unset the session cookies.
    $this->deleteCookie($this->getName());

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function gc($lifetime) {
    // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
    // value. For example, if you want user sessions to stay in your database
    // for three weeks before deleting them, you need to set gc_maxlifetime
    // to '1814400'. At that value, only after a user doesn't log in after
    // three weeks (1814400 seconds) will his/her session be removed.
    $this->connection->delete('sessions')
      ->condition('timestamp', REQUEST_TIME - $lifetime, '<')
      ->execute();
    return TRUE;
  }

  /**
   * Deletes a session cookie.
   *
   * @param string $name
   *   Name of session cookie to delete.
   */
  protected function deleteCookie($name) {
    $cookies = $this->requestStack->getCurrentRequest()->cookies;
    if ($cookies->has($name)) {
      $params = session_get_cookie_params();
      setcookie($name, '', REQUEST_TIME - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      $cookies->remove($name);
    }
  }

}
