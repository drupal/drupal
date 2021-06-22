<?php

// cSpell:disable
// phpcs:ignoreFile
namespace Drupal\Core\PhpFixes;


use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

/**
 * Adds basic `SessionUpdateTimestampHandlerInterface` behaviors to another `SessionHandlerInterface`.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class StrictSessionHandler extends AbstractSessionHandler
{
  private $handler;
  private $doDestroy;

  public function __construct(\SessionHandlerInterface $handler)
  {
    if ($handler instanceof \SessionUpdateTimestampHandlerInterface) {
      throw new \LogicException(sprintf('"%s" is already an instance of "SessionUpdateTimestampHandlerInterface", you cannot wrap it with "%s".', \get_class($handler), self::class));
    }

    $this->handler = $handler;
  }

  /**
   * @return bool
   */
  public function open($savePath, $sessionName) : bool
  {
    parent::open($savePath, $sessionName);

    return $this->handler->open($savePath, $sessionName);
  }

  /**
   * {@inheritdoc}
   */
  protected function doRead($sessionId)
  {
    return $this->handler->read($sessionId);
  }

  /**
   * @return bool
   */
  public function updateTimestamp($sessionId, $data) : bool
  {
    return $this->write($sessionId, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function doWrite($sessionId, $data)
  {
    return $this->handler->write($sessionId, $data);
  }

  /**
   * @return bool
   */
  public function destroy($sessionId) : bool
  {
    $this->doDestroy = true;
    $destroyed = parent::destroy($sessionId);

    return $this->doDestroy ? $this->doDestroy($sessionId) : $destroyed;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDestroy($sessionId)
  {
    $this->doDestroy = false;

    return $this->handler->destroy($sessionId);
  }

  /**
   * @return bool
   */
  public function close() : bool
  {
    return $this->handler->close();
  }

  /**
   * @return bool
   */
  public function gc($maxlifetime) :  int|false
  {
    return $this->handler->gc($maxlifetime);
  }
}
