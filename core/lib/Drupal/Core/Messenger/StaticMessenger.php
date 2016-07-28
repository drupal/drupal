<?php

namespace Drupal\Core\Messenger;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Render\Markup;

/**
 * Provides a messenger that stores messages for this request only.
 */
class StaticMessenger implements MessengerInterface {

  /**
   * The messages that have been set.
   *
   * @var array[]
   *   Keys are either self::TYPE_STATUS, self::TYPE_WARNING, or
   *   self::TYPE_ERROR. Values are arrays of arrays with the following keys:
   *   - message (string): the message.
   *   - safe (bool): whether the message is marked as safe markup.
   */
  protected $messages = [];

  /**
   * The page caching kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   The page caching kill switch.
   */
  public function __construct(KillSwitch $page_cache_kill_switch) {
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $type = self::TYPE_STATUS, $repeat = FALSE) {
    if (!isset($this->messages[$type])) {
      $this->messages[$type] = [];
    }

    // Convert strings which are safe to the simplest Markup objects.
    if (!($message instanceof Markup) && $message instanceof MarkupInterface) {
      $message = Markup::create((string) $message);
    }

    // Do not use strict type checking so that equivalent string and
    // MarkupInterface objects are detected.
    if ($repeat || !in_array($message, $this->messages[$type])) {
      $this->messages[$type][] = $message;
      $this->pageCacheKillSwitch->trigger();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    $messages = isset($this->messages) ? $this->messages : [];
    foreach ($messages as $type => $messages_by_type) {
      $messages[$type] = $messages_by_type;
    }

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessagesByType($type) {
    $messages = isset($this->messages) && isset($this->messages[$type]) ? $this->messages[$type] : [];

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMessages() {
    unset($this->messages);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMessagesByType($type) {
    unset($this->messages[$type]);

    return $this;
  }

}
