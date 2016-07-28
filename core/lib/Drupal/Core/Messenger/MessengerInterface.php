<?php

namespace Drupal\Core\Messenger;

/**
 * Stores runtime messages sent out to individual users on the page.
 *
 * An example for these messages is for example: "Content X got saved".
 */
interface MessengerInterface {

  /**
   * A status message.
   */
  const TYPE_STATUS = 'status';

  /**
   * A warning.
   */
  const TYPE_WARNING = 'warning';

  /**
   * An error.
   */
  const TYPE_ERROR = 'error';

  /**
   * Adds a new message to the queue.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   (optional) The translated message to be displayed to the user. For
   *   consistency with other messages, it should begin with a capital letter
   *   and end with a period.
   * @param string $type
   *   (optional) The message's type. Either self::TYPE_STATUS,
   *   self::TYPE_WARNING, or self::TYPE_ERROR.
   * @param bool $repeat
   *   (optional) If this is FALSE and the message is already set, then the
   *   message won't be repeated. Defaults to FALSE.
   *
   * @return $this
   */
  public function addMessage($message, $type = self::TYPE_STATUS, $repeat = FALSE);

  /**
   * Gets all messages.
   *
   * @return string[][]|\Drupal\Component\Render\MarkupInterface[][]
   *   Keys are message types and values are indexed arrays of messages. Message
   *   types are either self::TYPE_STATUS, self::TYPE_WARNING, or
   *   self::TYPE_ERROR.
   */
  public function getMessages();

  /**
   * Gets all messages of a certain type.
   *
   * @param string $type
   *   The messages' type. Either self::TYPE_STATUS, self::TYPE_WARNING,
   *   or self::TYPE_ERROR.
   *
   * @return string[]|\Drupal\Component\Render\MarkupInterface[]
   */
  public function getMessagesByType($type);

  /**
   * Deletes all messages.
   *
   * @return $this
   */
  public function deleteMessages();

  /**
   * Deletes all messages of a certain type.
   *
   * @param string $type
   *   The messages' type. Either self::TYPE_STATUS, self::TYPE_WARNING, or
   *   self::TYPE_ERROR.
   *
   * @return $this
   */
  public function deleteMessagesByType($type);

}
