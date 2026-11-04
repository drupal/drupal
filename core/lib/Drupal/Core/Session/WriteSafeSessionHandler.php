<?php

namespace Drupal\Core\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Proxy\SessionHandlerProxy;

/**
 * Wraps the session handler to prevent writes when not necessary or allowed.
 */
class WriteSafeSessionHandler extends SessionHandlerProxy implements \SessionHandlerInterface, WriteSafeSessionHandlerInterface, \SessionUpdateTimestampHandlerInterface {

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
   * @param \SessionHandlerInterface $handler
   *   The underlying session handler.
   * @param bool $sessionWritable
   *   Whether or not the session should be initially writable.
   */
  public function __construct(
    \SessionHandlerInterface $handler,
    protected $sessionWritable = TRUE,
  ) {
    parent::__construct($handler);
  }

  /**
   * {@inheritdoc}
   */
  public function read(#[\SensitiveParameter] string $session_id): string {
    $value = $this->handler->read($session_id);
    $this->readSessions[$session_id] = $value;
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function write(#[\SensitiveParameter] string $session_id, string $session_data): bool {
    // Only write the session when it has been modified.
    if (isset($this->readSessions[$session_id]) && $this->readSessions[$session_id] === $session_data) {
      return TRUE;
    }
    if ($this->isSessionWritable()) {
      return $this->handler->write($session_id, $session_data);
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
