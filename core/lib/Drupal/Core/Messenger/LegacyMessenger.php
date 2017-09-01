<?php

namespace Drupal\Core\Messenger;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Render\Markup;

/**
 * A legacy implementation of the messenger interface.
 *
 * @internal
 */
class LegacyMessenger implements MessengerInterface {

  /**
   * The page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * LegacyMessenger constructor.
   *
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   (optional) The page cache kill switch.
   */
  public function __construct(KillSwitch $killSwitch) {
    $this->killSwitch = $killSwitch;
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $type = self::TYPE_STATUS, $repeat = FALSE) {
    $this->setMessage($message, $type, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function addStatus($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_STATUS);
  }

  /**
   * {@inheritdoc}
   */
  public function addError($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_ERROR);
  }

  /**
   * {@inheritdoc}
   */
  public function addWarning($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_WARNING);
  }

  /**
   * {@inheritdoc}
   */
  public function all() {
    return $this->getMessages(NULL, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function messagesByType($type) {
    return $this->getMessages($type, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    return $this->getMessages(NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByType($type) {
    return $this->getMessages($type, TRUE);
  }

  /**
   * Sets a message to display to the user.
   *
   * Messages are stored in a session variable and displayed in the page template
   * via the $messages theme variable.
   *
   * Example usage:
   * @code
   * drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
   * @endcode
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   (optional) The translated message to be displayed to the user. For
   *   consistency with other messages, it should begin with a capital letter and
   *   end with a period.
   * @param string $type
   *   (optional) The message's type. Defaults to 'status'. These values are
   *   supported:
   *   - 'status'
   *   - 'warning'
   *   - 'error'
   * @param bool $repeat
   *   (optional) If this is FALSE and the message is already set, then the
   *   message won't be repeated. Defaults to FALSE.
   *
   * @return array|null
   *   A multidimensional array with keys corresponding to the set message types.
   *   The indexed array values of each contain the set messages for that type,
   *   and each message is an associative array with the following format:
   *   - safe: Boolean indicating whether the message string has been marked as
   *     safe. Non-safe strings will be escaped automatically.
   *   - message: The message string.
   *   So, the following is an example of the full return array structure:
   *   @code
   *     array(
   *       'status' => array(
   *         array(
   *           'safe' => TRUE,
   *           'message' => 'A <em>safe</em> markup string.',
   *         ),
   *         array(
   *           'safe' => FALSE,
   *           'message' => "$arbitrary_user_input to escape.",
   *         ),
   *       ),
   *     );
   *   @endcode
   *   If there are no messages set, the function returns NULL.
   *
   * @internal
   */
  private function setMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    if (isset($message)) {
      if (!isset($_SESSION['messages'][$type])) {
        $_SESSION['messages'][$type] = [];
      }

      // Convert strings which are safe to the simplest Markup objects.
      if (!($message instanceof Markup) && $message instanceof MarkupInterface) {
        $message = Markup::create((string) $message);
      }

      // Do not use strict type checking so that equivalent string and
      // MarkupInterface objects are detected.
      if ($repeat || !in_array($message, $_SESSION['messages'][$type])) {
        $_SESSION['messages'][$type][] = $message;
      }

      // Mark this page as being uncacheable.
      $this->killSwitch->trigger();
    }

    // Messages not set when DB connection fails.
    return isset($_SESSION['messages']) ? $_SESSION['messages'] : NULL;
  }

  /**
   * Returns all messages that have been set with drupal_set_message().
   *
   * @param string $type
   *   (optional) Limit the messages returned by type. Defaults to NULL, meaning
   *   all types. These values are supported:
   *   - NULL
   *   - 'status'
   *   - 'warning'
   *   - 'error'
   * @param bool $clear_queue
   *   (optional) If this is TRUE, the queue will be cleared of messages of the
   *   type specified in the $type parameter. Otherwise the queue will be left
   *   intact. Defaults to TRUE.
   *
   * @return array
   *   An associative, nested array of messages grouped by message type, with
   *   the top-level keys as the message type. The messages returned are
   *   limited to the type specified in the $type parameter, if any. If there
   *   are no messages of the specified type, an empty array is returned. See
   *   drupal_set_message() for the array structure of individual messages.
   *
   * @see drupal_set_message()
   * @see status-messages.html.twig
   *
   * @internal
   */
  private function getMessages($type = NULL, $clear_queue = TRUE) {
    if ($messages = $this->setMessage()) {
      if ($type) {
        if ($clear_queue) {
          unset($_SESSION['messages'][$type]);
        }
        if (isset($messages[$type])) {
          return [$type => $messages[$type]];
        }
      }
      else {
        if ($clear_queue) {
          unset($_SESSION['messages']);
        }
        return $messages;
      }
    }
    return [];
  }

}
