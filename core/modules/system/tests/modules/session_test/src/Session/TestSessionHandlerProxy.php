<?php

namespace Drupal\session_test\Session;

/**
 * Provides a test session handler proxy.
 */
class TestSessionHandlerProxy implements \SessionHandlerInterface {

  /**
   * The decorated session handler.
   *
   * @var \SessionHandlerInterface
   */
  protected $sessionHandler;

  /**
   * An optional argument.
   *
   * @var mixed
   */
  protected $optionalArgument;

  /**
   * Constructs a new TestSessionHandlerProxy object.
   *
   * @param \SessionHandlerInterface $session_handler
   *   The decorated session handler.
   * @param mixed $optional_argument
   *   (optional) An optional argument.
   */
  public function __construct(\SessionHandlerInterface $session_handler, $optional_argument = NULL) {
    $this->sessionHandler = $session_handler;
    $this->optionalArgument = $optional_argument;
  }

  /**
   * {@inheritdoc}
   */
  public function open($save_path, $name): bool {
    $trace = \Drupal::service('session_test.session_handler_proxy_trace');
    $trace[] = ['BEGIN', $this->optionalArgument, __FUNCTION__];
    $result = $this->sessionHandler->open($save_path, $name);
    $trace[] = ['END', $this->optionalArgument, __FUNCTION__];
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function close(): bool {
    $trace = \Drupal::service('session_test.session_handler_proxy_trace');
    $trace[] = ['BEGIN', $this->optionalArgument, __FUNCTION__];
    $result = $this->sessionHandler->close();
    $trace[] = ['END', $this->optionalArgument, __FUNCTION__];
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function read($session_id): string|FALSE {
    $trace = \Drupal::service('session_test.session_handler_proxy_trace');
    $trace[] = ['BEGIN', $this->optionalArgument, __FUNCTION__, $session_id];
    $result = $this->sessionHandler->read($session_id);
    $trace[] = ['END', $this->optionalArgument, __FUNCTION__, $session_id];
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function write($session_id, $session_data): bool {
    $trace = \Drupal::service('session_test.session_handler_proxy_trace');
    $trace[] = ['BEGIN', $this->optionalArgument, __FUNCTION__, $session_id];
    $result = $this->sessionHandler->write($session_id, $session_data);
    $trace[] = ['END', $this->optionalArgument, __FUNCTION__, $session_id];
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy($session_id): bool {
    return $this->sessionHandler->destroy($session_id);
  }

  /**
   * {@inheritdoc}
   */
  public function gc($max_lifetime): int|FALSE {
    return $this->sessionHandler->gc($max_lifetime);
  }

}
