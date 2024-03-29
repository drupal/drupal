<?php

namespace Drupal\Core\Session;

/**
 * Wraps the session handler to prevent writes when not necessary or allowed.
 */
class WriteSafeSessionHandler implements \SessionHandlerInterface, WriteSafeSessionHandlerInterface {

  /**
   * @var \SessionHandlerInterface
   */
  protected $wrappedSessionHandler;

  /**
   * Whether or not the session is enabled for writing.
   *
   * @var bool
   */
  protected $sessionWritable;

  /**
   * The read sessions.
   *
   * @var array
   *   Session data keyed by the session ID.
   */
  private $readSessions;

  /**
   * Constructs a new write safe session handler.
   *
   * @param \SessionHandlerInterface $wrapped_session_handler
   *   The underlying session handler.
   * @param bool $session_writable
   *   Whether or not the session should be initially writable.
   */
  public function __construct(\SessionHandlerInterface $wrapped_session_handler, $session_writable = TRUE) {
    $this->wrappedSessionHandler = $wrapped_session_handler;
    $this->sessionWritable = $session_writable;
  }

  /**
   * {@inheritdoc}
   */
  public function close(): bool {
    return $this->wrappedSessionHandler->close();
  }

  /**
   * {@inheritdoc}
   */
  public function destroy($session_id): bool {
    return $this->wrappedSessionHandler->destroy($session_id);
  }

  /**
   * {@inheritdoc}
   */
  public function gc($max_lifetime): int|FALSE {
    return $this->wrappedSessionHandler->gc($max_lifetime);
  }

  /**
   * {@inheritdoc}
   */
  public function open($save_path, $session_id): bool {
    return $this->wrappedSessionHandler->open($save_path, $session_id);
  }

  /**
   * {@inheritdoc}
   */
  public function read($session_id): string|FALSE {
    $value = $this->wrappedSessionHandler->read($session_id);
    $this->readSessions[$session_id] = $value;
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function write($session_id, $session_data): bool {
    // Only write the session when it has been modified.
    if (isset($this->readSessions[$session_id]) && $this->readSessions[$session_id] === $session_data) {
      return TRUE;
    }
    if ($this->isSessionWritable()) {
      return $this->wrappedSessionHandler->write($session_id, $session_data);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionWritable($flag) {
    $this->sessionWritable = (bool) $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function isSessionWritable() {
    return $this->sessionWritable;
  }

}
