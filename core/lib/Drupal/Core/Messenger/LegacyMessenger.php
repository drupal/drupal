<?php

namespace Drupal\Core\Messenger;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;

/**
 * Provides a LegacyMessenger implementation.
 *
 * This implementation is for handling messages in a backwards compatible way
 * using core's previous $_SESSION storage method.
 *
 * You should not instantiate a new instance of this class directly. Instead,
 * you should inject the "messenger" service into your own services or use
 * \Drupal::messenger() in procedural functions.
 *
 * @see https://www.drupal.org/node/2774931
 * @see https://www.drupal.org/node/2928994
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Messenger\Messenger instead.
 */
class LegacyMessenger implements MessengerInterface {

  /**
   * The messages.
   *
   * Note: this property must remain static because it must behave in a
   * persistent manner, similar to $_SESSION['messages']. Creating a new class
   * each time would destroy any previously set messages.
   *
   * @var array
   */
  protected static $messages;

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
    // Proxy to the Messenger service, if it exists.
    if ($messenger = $this->getMessengerService()) {
      return $messenger->addMessage($message, $type, $repeat);
    }

    if (!isset(static::$messages[$type])) {
      static::$messages[$type] = [];
    }

    if (!($message instanceof Markup) && $message instanceof MarkupInterface) {
      $message = Markup::create((string) $message);
    }

    // Do not use strict type checking so that equivalent string and
    // MarkupInterface objects are detected.
    if ($repeat || !in_array($message, static::$messages[$type])) {
      static::$messages[$type][] = $message;
    }

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
    // Proxy to the Messenger service, if it exists.
    if ($messenger = $this->getMessengerService()) {
      return $messenger->all();
    }

    return static::$messages;
  }

  /**
   * Returns the Messenger service.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface|null
   *   The Messenger service.
   */
  protected function getMessengerService() {
    // Use the Messenger service, if it exists.
    if (\Drupal::hasService('messenger')) {
      // Note: because the container has the potential to be rebuilt during
      // requests, this service cannot be directly stored on this class.
      /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
      $messenger = \Drupal::service('messenger');

      // Transfer any messages into the service.
      if (isset(static::$messages)) {
        foreach (static::$messages as $type => $messages) {
          foreach ($messages as $message) {
            // Force repeat to TRUE since this is merging existing messages to
            // the Messenger service and would have already checked this prior.
            $messenger->addMessage($message, $type, TRUE);
          }
        }
        static::$messages = NULL;
      }

      return $messenger;
    }

    // Otherwise, trigger an error.
    @trigger_error('Adding or retrieving messages prior to the container being initialized was deprecated in Drupal 8.5.0 and this functionality will be removed before Drupal 9.0.0. Please report this usage at https://www.drupal.org/node/2928994.', E_USER_DEPRECATED);

    // Prematurely creating $_SESSION['messages'] in this class' constructor
    // causes issues when the container attempts to initialize its own session
    // later down the road. This can only be done after it has been determined
    // the Messenger service is not available (i.e. no container). It is also
    // reasonable to assume that if the container becomes available in a
    // subsequent request, a new instance of this class will be created and
    // this code will never be reached. This is merely for BC purposes.
    if (!isset(static::$messages)) {
      // A "session" was already created, perhaps to simply allow usage of
      // the previous method core used to store messages, use it.
      if (isset($_SESSION)) {
        if (!isset($_SESSION['messages'])) {
          $_SESSION['messages'] = [];
        }
        static::$messages = &$_SESSION['messages'];
      }
      // Otherwise, just set an empty array.
      else {
        static::$messages = [];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function messagesByType($type) {
    // Proxy to the Messenger service, if it exists.
    if ($messenger = $this->getMessengerService()) {
      return $messenger->messagesByType($type);
    }

    return static::$messages[$type];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    // Proxy to the Messenger service, if it exists.
    if ($messenger = $this->getMessengerService()) {
      return $messenger->deleteAll();
    }

    $messages = static::$messages;
    static::$messages = NULL;
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByType($type) {
    // Proxy to the Messenger service, if it exists.
    if ($messenger = $this->getMessengerService()) {
      return $messenger->deleteByType($type);
    }

    $messages = static::$messages[$type];
    unset(static::$messages[$type]);
    return $messages;
  }

}
