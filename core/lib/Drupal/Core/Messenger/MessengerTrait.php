<?php

namespace Drupal\Core\Messenger;

/**
 * Provides a trait for the messenger service.
 */
trait MessengerTrait {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Sets the messenger.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function setMessenger(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * Gets the messenger.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger.
   */
  public function messenger() {
    if (!isset($this->messenger)) {
      $this->messenger = \Drupal::messenger();
    }
    return $this->messenger;
  }

}
