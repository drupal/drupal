<?php

/**
 * @file
 * Contains \Drupal\Core\Session\SessionManager.
 */

namespace Drupal\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\WriteCheckSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Manages user sessions.
 *
 * This class implements the custom session management code inherited from
 * Drupal 7 on top of the corresponding Symfony component. Regrettably the name
 * NativeSessionStorage is not quite accurate. In fact the responsibility for
 * storing and retrieving session data has been extracted from it in Symfony 2.1
 * but the class name was not changed.
 *
 * @todo
 *   In fact the NativeSessionStorage class already implements all of the
 *   functionality required by a typical Symfony application. Normally it is not
 *   necessary to subclass it at all. In order to reach the point where Drupal
 *   can use the Symfony session management unmodified, the code implemented
 *   here needs to be extracted either into a dedicated session handler proxy
 *   (e.g. sid-hashing) or relocated to the authentication subsystem.
 */
class SessionManager extends NativeSessionStorage implements SessionManagerInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * Whether a lazy session has been started.
   *
   * @var bool
   */
  protected $startedLazy;

  /**
   * The write safe session handler.
   *
   * @todo: The write safe session handler should be exposed in the
   *   container and this reference should be removed once all database queries
   *   are removed from the session manager class.
   * @see https://www.drupal.org/node/2372389
   *
   * @var \Drupal\Core\Session\WriteSafeSessionHandlerInterface
   */
  protected $writeSafeHandler;

  /**
   * Constructs a new session manager instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Session\MetadataBag $metadata_bag
   *   The session metadata bag.
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration interface.
   */
  public function __construct(RequestStack $request_stack, Connection $connection, MetadataBag $metadata_bag, SessionConfigurationInterface $session_configuration) {
    $options = array();
    $this->sessionConfiguration = $session_configuration;
    $this->requestStack = $request_stack;
    $this->connection = $connection;

    // Register the default session handler.
    // @todo Extract session storage from session handler into a service.
    $save_handler = new SessionHandler($this->requestStack, $this->connection);
    $write_check_handler = new WriteCheckSessionHandler($save_handler);
    $this->writeSafeHandler = new WriteSafeSessionHandler($write_check_handler);

    parent::__construct($options, $this->writeSafeHandler, $metadata_bag);

    // @todo When not using the Symfony Session object, the list of bags in the
    //   NativeSessionStorage will remain uninitialized. This will lead to
    //   errors in NativeSessionHandler::loadSession. Remove this after
    //   https://drupal.org/node/2229145, when we will be using the Symfony
    //   session object (which registers an attribute bag with the
    //   manager upon instantiation).
    $this->bags = array();
  }

  /**
   * {@inheritdoc}
   */
  public function start() {
    global $user;

    if (($this->started || $this->startedLazy) && !$this->closed) {
      return $this->started;
    }

    $request = $this->requestStack->getCurrentRequest();
    $this->setOptions($this->sessionConfiguration->getOptions($request));

    if ($this->sessionConfiguration->hasSession($request)) {
      // If a session cookie exists, initialize the session. Otherwise the
      // session is only started on demand in save(), making
      // anonymous users not use a session cookie unless something is stored in
      // $_SESSION. This allows HTTP proxies to cache anonymous pageviews.
      $result = $this->startNow();
    }

    if (empty($result)) {
      $user = new AnonymousUserSession();

      // Randomly generate a session identifier for this request. This is
      // necessary because \Drupal\user\TempStoreFactory::get() wants to know
      // the future session ID of a lazily started session in advance.
      //
      // @todo: With current versions of PHP there is little reason to generate
      //   the session id from within application code. Consider using the
      //   default php session id instead of generating a custom one:
      //   https://www.drupal.org/node/2238561
      $this->setId(Crypt::randomBytesBase64());

      // Initialize the session global and attach the Symfony session bags.
      $_SESSION = array();
      $this->loadSession();

      // NativeSessionStorage::loadSession() sets started to TRUE, reset it to
      // FALSE here.
      $this->started = FALSE;
      $this->startedLazy = TRUE;

      $result = FALSE;
    }
    date_default_timezone_set(drupal_get_user_timezone());

    return $result;
  }

  /**
   * Forcibly start a PHP session.
   *
   * @return boolean
   *   TRUE if the session is started.
   */
  protected function startNow() {
    if ($this->isCli()) {
      return FALSE;
    }

    if ($this->startedLazy) {
      // Save current session data before starting it, as PHP will destroy it.
      $session_data = $_SESSION;
    }

    $result = parent::start();

    // Restore session data.
    if ($this->startedLazy) {
      $_SESSION = $session_data;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    global $user;

    if ($this->isCli()) {
      // We don't have anything to do if we are not allowed to save the session.
      return;
    }

    if ($user->isAnonymous() && $this->isSessionObsolete()) {
      // There is no session data to store, destroy the session if it was
      // previously started.
      if ($this->getSaveHandler()->isActive()) {
        session_destroy();
      }
    }
    else {
      // There is session data to store. Start the session if it is not already
      // started.
      if (!$this->getSaveHandler()->isActive()) {
        $this->startNow();
      }
      // Write the session data.
      parent::save();
    }

    $this->startedLazy = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function regenerate($destroy = FALSE, $lifetime = NULL) {
    global $user;

    // Nothing to do if we are not allowed to change the session.
    if ($this->isCli()) {
      return;
    }

    // We do not support the optional $destroy and $lifetime parameters as long
    // as #2238561 remains open.
    if ($destroy || isset($lifetime)) {
      throw new \InvalidArgumentException('The optional parameters $destroy and $lifetime of SessionManager::regenerate() are not supported currently');
    }

    if ($this->isStarted()) {
      $old_session_id = $this->getId();
    }
    session_id(Crypt::randomBytesBase64());

    $this->getMetadataBag()->clearCsrfTokenSeed();

    if (isset($old_session_id)) {
      $params = session_get_cookie_params();
      $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
      setcookie($this->getName(), $this->getId(), $expire, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      $this->migrateStoredSession($old_session_id);
    }

    if (!$this->isStarted()) {
      // Start the session when it doesn't exist yet.
      // Preserve the logged in user, as it will be reset to anonymous
      // by \Drupal\Core\Session\SessionHandler::read().
      $account = $user;
      $this->startNow();
      $user = $account;
    }
    date_default_timezone_set(drupal_get_user_timezone());
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uid) {
    // Nothing to do if we are not allowed to change the session.
    if (!$this->writeSafeHandler->isSessionWritable() || $this->isCli()) {
      return;
    }
    $this->connection->delete('sessions')
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->writeSafeHandler->isSessionWritable();
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->writeSafeHandler->setSessionWritable(FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->writeSafeHandler->setSessionWritable(TRUE);
    return $this;
  }

  /**
   * Returns whether the current PHP process runs on CLI.
   *
   * Command line clients do not support cookies nor sessions.
   *
   * @return bool
   */
  protected function isCli() {
    return PHP_SAPI === 'cli';
  }

  /**
   * Determines whether the session contains user data.
   *
   * @return bool
   *   TRUE when the session does not contain any values and therefore can be
   *   destroyed.
   */
  protected function isSessionObsolete() {
    $used_session_keys = array_filter($this->getSessionDataMask());
    return empty($used_session_keys);
  }

  /**
   * Returns a map specifying which session key is containing user data.
   *
   * @return array
   *   An array where keys correspond to the session keys and the values are
   *   booleans specifying whether the corresponding session key contains any
   *   user data.
   */
  protected function getSessionDataMask() {
    if (empty($_SESSION)) {
      return array();
    }

    // Start out with a completely filled mask.
    $mask = array_fill_keys(array_keys($_SESSION), TRUE);

    // Ignore the metadata bag, it does not contain any user data.
    $mask[$this->metadataBag->getStorageKey()] = FALSE;

    // Ignore attribute bags when they do not contain any data.
    foreach ($this->bags as $bag) {
      $key = $bag->getStorageKey();
      $mask[$key] = empty($_SESSION[$key]);
    }

    return array_intersect_key($mask, $_SESSION);
  }

  /**
   * Migrates the current session to a new session id.
   *
   * @param string $old_session_id
   *   The old session id. The new session id is $this->getId() unless
   *   $new_insecure_session_id is not empty.
   */
  protected function migrateStoredSession($old_session_id) {
    $fields = array('sid' => Crypt::hashBase64($this->getId()));
    $this->connection->update('sessions')
      ->fields($fields)
      ->condition('sid', Crypt::hashBase64($old_session_id))
      ->execute();
  }

}
