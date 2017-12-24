<?php

namespace Drupal\Core\Messenger;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * The messenger service.
 */
class Messenger implements MessengerInterface {

  /**
   * The flash bag.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface
   */
  protected $flashBag;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Messenger constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface $flash_bag
   *   The flash bag.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   The kill switch.
   */
  public function __construct(FlashBagInterface $flash_bag, KillSwitch $killSwitch) {
    $this->flashBag = $flash_bag;
    $this->killSwitch = $killSwitch;
  }

  /**
   * {@inheritdoc}
   */
  public function addError($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_ERROR, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $type = self::TYPE_STATUS, $repeat = FALSE) {
    if (!($message instanceof Markup) && $message instanceof MarkupInterface) {
      $message = Markup::create((string) $message);
    }

    // Do not use strict type checking so that equivalent string and
    // MarkupInterface objects are detected.
    if ($repeat || !in_array($message, $this->flashBag->peek($type))) {
      $this->flashBag->add($type, $message);
    }

    // Mark this page as being uncacheable.
    $this->killSwitch->trigger();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addStatus($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_STATUS, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function addWarning($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_WARNING, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function all() {
    return $this->flashBag->peekAll();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    return $this->flashBag->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByType($type) {
    // Flash bag gets and clears flash messages from the stack.
    return $this->flashBag->get($type);
  }

  /**
   * {@inheritdoc}
   */
  public function messagesByType($type) {
    return $this->flashBag->peek($type);
  }

}
