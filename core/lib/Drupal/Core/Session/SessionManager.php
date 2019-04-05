<?php

namespace Drupal\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
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

  use DependencySerializationTrait;

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
   * @todo: This reference should be removed once all database queries
   *   are removed from the session manager class.
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
   * @param \Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy|Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler|\SessionHandlerInterface|null $handler
   *   The object to register as a PHP session handler.
   *   @see \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage::setSaveHandler()
   */
  public function __construct(RequestStack $request_stack, Connection $connection, MetadataBag $metadata_bag, SessionConfigurationInterface $session_configuration, $handler = NULL) {
    $options = [];
    $this->sessionConfiguration = $session_configuration;
    $this->requestStack = $request_stack;
    $this->connection = $connection;

    parent::__construct($options, $handler, $metadata_bag);

    // @todo When not using the Symfony Session object, the list of bags in the
    //   NativeSessionStorage will remain uninitialized. This will lead to
    //   errors in NativeSessionHandler::loadSession. Remove this after
    //   https://www.drupal.org/node/2229145, when we will be using the Symfony
    //   session object (which registers an attribute bag with the
    //   manager upon instantiation).
    $this->bags = [];
  }

  /**
   * {@inheritdoc}
   */
  public function start() {
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
      // Randomly generate a session identifier for this request. This is
      // necessary because \Drupal\Core\TempStore\SharedTempStoreFactory::get()
      // wants to know the future session ID of a lazily started session in
      // advance.
      //
      // @todo: With current versions of PHP there is little reason to generate
      //   the session id from within application code. Consider using the
      //   default php session id instead of generating a custom one:
      //   https://www.drupal.org/node/2238561
      $this->setId(Crypt::randomBytesBase64());

      // Initialize the session global and attach the Symfony session bags.
      $_SESSION = [];
      $this->loadSession();

      // NativeSessionStorage::loadSession() sets started to TRUE, reset it to
      // FALSE here.
      $this->started = FALSE;
      $this->startedLazy = TRUE;

      $result = FALSE;
    }

    return $result;
  }

  /**
   * Forcibly start a PHP session.
   *
   * @return bool
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
      $this->loadSession();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if ($this->isCli()) {
      // We don't have anything to do if we are not allowed to save the session.
      return;
    }

    if ($this->isSessionObsolete()) {
      // There is no session data to store, destroy the session if it was
      // previously started.
      if ($this->getSaveHandler()->isActive()) {
        $this->destroy();
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
    // Nothing to do if we are not allowed to change the session.
    if ($this->isCli()) {
      return;
    }

    // We do not support the optional $destroy and $lifetime parameters as long
    // as #2238561 remains open.
    if ($destroy || isset($lifetime)) {
      throw new \InvalidArgumentException('The optional parameters $destroy and $lifetime of SessionManager::regenerate() are not supported currently');
    }

    // Only migrate the session if the session is really started and not only
    // lazy started.
    if ($this->started) {
      $old_session_id = $this->getId();
      // Save and close the old session. Call the parent method to avoid issue
      // with session destruction due to the session being considered obsolete.
      parent::save();
      // Ensure the session is reloaded correctly.
      $this->startedLazy = TRUE;
    }
    session_id(Crypt::randomBytesBase64());

    $this->getMetadataBag()->clearCsrfTokenSeed();

    if (isset($old_session_id)) {
      $params = session_get_cookie_params();
      $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
      setcookie($this->getName(), $this->getId(), $expire, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      $this->migrateStoredSession($old_session_id);
    }

    $this->startNow();
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
  public function destroy() {
    session_destroy();

    // Unset the session cookies.
    $session_name = $this->getName();
    $cookies = $this->requestStack->getCurrentRequest()->cookies;
    // setcookie() can only be called when headers are not yet sent.
    if ($cookies->has($session_name) && !headers_sent()) {
      $params = session_get_cookie_params();
      setcookie($session_name, '', REQUEST_TIME - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      $cookies->remove($session_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setWriteSafeHandler(WriteSafeSessionHandlerInterface $handler) {
    $this->writeSafeHandler = $handler;
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
      return [];
    }

    // Start out with a completely filled mask.
    $mask = array_fill_keys(array_keys($_SESSION), TRUE);

    // Ignore the metadata bag, it does not contain any user data.
    $mask[$this->metadataBag->getStorageKey()] = FALSE;

    // Ignore attribute bags when they do not contain any data.
    foreach ($this->bags as $bag) {
      $key = $bag->getStorageKey();
      $mask[$key] = !empty($_SESSION[$key]);
    }

    return array_intersect_key($mask, $_SESSION);
  }

  /**
   * Migrates the current session to a new session id.
   *
   * @param string $old_session_id
   *   The old session ID. The new session ID is $this->getId().
   */
  protected function migrateStoredSession($old_session_id) {
    $fields = ['sid' => Crypt::hashBase64($this->getId())];
    $this->connection->update('sessions')
      ->fields($fields)
      ->condition('sid', Crypt::hashBase64($old_session_id))
      ->execute();
  }

  /**
   * Checks if the session is started.
   *
   * Beginning with symfony/http-foundation 3.4.24, the session will no longer
   * save unless this method returns true. The parent method returns true if
   * $this->started is true, but we need the session to also save if we lazy
   * started, so we override isStarted() here.
   *
   * @return bool
   *   True if started, false otherwise
   */
  public function isStarted() {
    return parent::isStarted() || $this->startedLazy;
  }

}
