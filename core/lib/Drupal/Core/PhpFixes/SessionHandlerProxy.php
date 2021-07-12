<?php

// cSpell:disable
// phpcs:ignoreFile
namespace Drupal\Core\PhpFixes;


use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

/**
 * @author Drak <drak@zikula.org>
 */
class SessionHandlerProxy extends AbstractProxy implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
  protected $handler;

  public function __construct(\SessionHandlerInterface $handler)
  {
    $this->handler = $handler;
    $this->wrapper = ($handler instanceof \SessionHandler);
    $this->saveHandlerName = $this->wrapper ? ini_get('session.save_handler') : 'user';
  }

  /**
   * @return \SessionHandlerInterface
   */
  public function getHandler()
  {
    return $this->handler;
  }

  // \SessionHandlerInterface

  /**
   * @return bool
   */
  public function open($savePath, $sessionName) : bool
  {
    return (bool) $this->handler->open($savePath, $sessionName);
  }

  /**
   * @return bool
   */
  public function close() : bool
  {
    return (bool) $this->handler->close();
  }

  /**
   * @return string
   */
  public function read($sessionId) : string
  {
    return (string) $this->handler->read($sessionId);
  }

  /**
   * @return bool
   */
  public function write($sessionId, $data): bool
  {
    return (bool) $this->handler->write($sessionId, $data);
  }

  /**
   * @return bool
   */
  public function destroy($sessionId) : bool
  {
    return (bool) $this->handler->destroy($sessionId);
  }

  /**
   * @return bool
   */
  public function gc($maxlifetime) : int
  {
    return (bool) $this->handler->gc($maxlifetime);
  }

  /**
   * @return bool
   */
  public function validateId($sessionId): bool
  {
    return !$this->handler instanceof \SessionUpdateTimestampHandlerInterface || $this->handler->validateId($sessionId);
  }

  /**
   * @return bool
   */
  public function updateTimestamp($sessionId, $data) : bool
  {
    return $this->handler instanceof \SessionUpdateTimestampHandlerInterface ? $this->handler->updateTimestamp($sessionId, $data) : $this->write($sessionId, $data);
  }
}
