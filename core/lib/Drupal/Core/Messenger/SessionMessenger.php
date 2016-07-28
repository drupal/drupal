<?php

namespace Drupal\Core\Messenger;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Render\Markup;

/**
 * Provides a session-based messenger.
 */
class SessionMessenger implements MessengerInterface {

  /**
   * The page caching kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Session\SessionManagerInterface
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
    if (!isset($_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type] = [];
    }

    // Convert strings which are safe to the simplest Markup objects.
    if (!($message instanceof Markup) && $message instanceof MarkupInterface) {
      $message = Markup::create((string) $message);
    }

    // Do not use strict type checking so that equivalent string and
    // \Drupal\Core\Render\Markup objects are detected.
    if ($repeat || !in_array($message, $_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type][] = $message;
      $this->pageCacheKillSwitch->trigger();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    $messages = isset($_SESSION['messages']) ? $_SESSION['messages'] : [];
    foreach ($messages as $type => $messages_by_type) {
      $messages[$type] = $messages_by_type;
    }

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessagesByType($type) {
    $messages = isset($_SESSION['messages']) && isset($_SESSION['messages'][$type]) ? $_SESSION['messages'][$type] : [];

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMessages() {
    unset($_SESSION['messages']);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMessagesByType($type) {
    unset($_SESSION['messages'][$type]);

    return $this;
  }

}
