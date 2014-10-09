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
    $insecure_session_name = $this->sessionManager->getInsecureName();
    $cookies = $this->requestStack->getCurrentRequest()->cookies;
    if (!$cookies->has($this->getName()) && !$cookies->has($insecure_session_name)) {
      $user = new UserSession();
      return '';
    }

    // Otherwise, if the session is still active, we have a record of the
    // client's session in the database. If it's HTTPS then we are either have a
    // HTTPS session or we are about to log in so we check the sessions table
    // for an anonymous session with the non-HTTPS-only cookie. The session ID
    // that is in the user's cookie is hashed before being stored in the
    // database as a security measure. Thus, we have to hash it to match the
    // database.
    if ($this->requestStack->getCurrentRequest()->isSecure()) {
      // Try to load a session using the HTTPS-only secure session id.
      $values = $this->connection->query("SELECT u.*, s.* FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE u.default_langcode = 1 AND s.ssid = :ssid", array(
        ':ssid' => Crypt::hashBase64($sid),
      ))->fetchAssoc();
      if (!$values) {
        // Fallback and try to load the anonymous non-HTTPS session. Use the
        // non-HTTPS session id as the key.
        if ($cookies->has($insecure_session_name)) {
          $insecure_session_id = $cookies->get($insecure_session_name);
          $args = array(':sid' => Crypt::hashBase64($insecure_session_id));
          $values = $this->connection->query("SELECT u.*, s.* FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE u.default_langcode = 1 AND s.sid = :sid AND s.uid = 0", $args)->fetchAssoc();
          if ($values) {
            $this->sessionSetObsolete($insecure_session_id);
          }
        }
      }
    }
    else {
      // Try to load a session using the non-HTTPS session id.
      $values = $this->connection->query("SELECT u.*, s.* FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE u.default_langcode = 1 AND s.sid = :sid", array(
        ':sid' => Crypt::hashBase64($sid),
      ))->fetchAssoc();
    }

    // We found the client's session record and they are an authenticated,
    // active user.
    if ($values && $values['uid'] > 0 && $values['status'] == 1) {
      // Add roles element to $user.
      $rids = $this->connection->query("SELECT ur.rid FROM {users_roles} ur WHERE ur.uid = :uid", array(
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

      // Either ssid or sid or both will be added from $key below.
      $fields = array(
        'uid' => $user->id(),
        'hostname' => $this->requestStack->getCurrentRequest()->getClientIP(),
        'session' => $value,
        'timestamp' => REQUEST_TIME,
      );
      // Use the session ID as 'sid' and an empty string as 'ssid' by default.
      // read() does not allow empty strings so that's a safe default.
      $key = array('sid' => Crypt::hashBase64($sid), 'ssid' => '');
      // On HTTPS connections, use the session ID as both 'sid' and 'ssid'.
      if ($this->requestStack->getCurrentRequest()->isSecure()) {
        $key['ssid'] = $key['sid'];
        // The "secure pages" setting allows a site to simultaneously use both
        // secure and insecure session cookies. If enabled and both cookies
        // are presented then use both keys. The session ID from the cookie is
        // hashed before being stored in the database as a security measure.
        if ($this->sessionManager->isMixedMode()) {
          $insecure_session_name = $this->sessionManager->getInsecureName();
          $cookies = $this->requestStack->getCurrentRequest()->cookies;
          if ($cookies->has($insecure_session_name)) {
            $key['sid'] = Crypt::hashBase64($cookies->get($insecure_session_name));
          }
        }
      }
      elseif ($this->sessionManager->isMixedMode()) {
        unset($key['ssid']);
      }
      $this->connection->merge('sessions')
        ->keys($key)
        ->fields($fields)
        ->execute();

      // Remove obsolete sessions.
      $this->cleanupObsoleteSessions();

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
    $is_https = $this->requestStack->getCurrentRequest()->isSecure();
    // Delete session data.
    $this->connection->delete('sessions')
      ->condition($is_https ? 'ssid' : 'sid', Crypt::hashBase64($sid))
      ->execute();

    // Reset $_SESSION and $user to prevent a new session from being started
    // in \Drupal\Core\Session\SessionManager::save().
    $_SESSION = array();
    $user = new AnonymousUserSession();

    // Unset the session cookies.
    $this->deleteCookie($this->getName());
    if ($is_https) {
      $this->deleteCookie($this->sessionManager->getInsecureName(), FALSE);
    }
    elseif ($this->sessionManager->isMixedMode()) {
      $this->deleteCookie('S' . $this->getName(), TRUE);
    }

    // Remove obsolete sessions.
    $this->cleanupObsoleteSessions();

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
   * @param bool $secure
   *   Force the secure value of the cookie.
   */
  protected function deleteCookie($name, $secure = NULL) {
    $cookies = $this->requestStack->getCurrentRequest()->cookies;
    if ($cookies->has($name) || (!$this->requestStack->getCurrentRequest()->isSecure() && $secure === TRUE)) {
      $params = session_get_cookie_params();
      if ($secure !== NULL) {
        $params['secure'] = $secure;
      }
      setcookie($name, '', REQUEST_TIME - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      $cookies->remove($name);
    }
  }

  /**
   * Mark a session for garbage collection upon session save.
   */
  protected function sessionSetObsolete($sid, $https = FALSE) {
    $this->obsoleteSessionIds[$sid] = $https ? 'ssid' : 'sid';
  }

  /**
   * Remove sessions marked for garbage collection.
   */
  protected function cleanupObsoleteSessions() {
    foreach ($this->obsoleteSessionIds as $sid => $key) {
      $this->connection->delete('sessions')
        ->condition($key, Crypt::hashBase64($sid))
        ->execute();
    }
  }

}
