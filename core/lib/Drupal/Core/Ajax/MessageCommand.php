<?php

namespace Drupal\Core\Ajax;

use Drupal\Core\Asset\AttachedAssets;

/**
 * AJAX command for a JavaScript Drupal.message() call.
 *
 * Developers should be extra careful if this command and
 * \Drupal\Core\Ajax\AnnounceCommand are included in the same response. Unless
 * the `announce` option is set to an empty string (''), this command will
 * result in the message being announced to screen readers. When combined with
 * AnnounceCommand, this may result in unexpected behavior. Manual testing with
 * a screen reader is strongly recommended.
 *
 * Here are examples of how to suppress announcements:
 * @code
 *   $command = new MessageCommand("I won't be announced", NULL, [
 *     'announce' => '',
 *   ]);
 * @endcode
 *
 * @see \Drupal\Core\Ajax\AnnounceCommand
 *
 * @ingroup ajax
 */
class MessageCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  /**
   * The message text.
   *
   * @var string
   */
  protected $message;

  /**
   * Whether to clear previous messages.
   *
   * @var bool
   */
  protected $clearPrevious;

  /**
   * The query selector for the element the message will appear in.
   *
   * @var string
   */
  protected $wrapperQuerySelector;

  /**
   * The options passed to Drupal.message().add().
   *
   * @var array
   */
  protected $options;

  /**
   * Constructs a MessageCommand object.
   *
   * @param string $message
   *   The text of the message.
   * @param string|null $wrapper_query_selector
   *   The query selector of the element to display messages in when they
   *   should be displayed somewhere other than the default.
   *   @see Drupal.Message.defaultWrapper()
   * @param array $options
   *   The options passed to Drupal.message().add().
   * @param bool $clear_previous
   *   If TRUE, previous messages will be cleared first.
   */
  public function __construct($message, $wrapper_query_selector = NULL, array $options = [], $clear_previous = TRUE) {
    $this->message = $message;
    $this->wrapperQuerySelector = $wrapper_query_selector;
    $this->options = $options;
    $this->clearPrevious = $clear_previous;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'message',
      'message' => $this->message,
      'messageWrapperQuerySelector' => $this->wrapperQuerySelector,
      'messageOptions' => $this->options,
      'clearPrevious' => $this->clearPrevious,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedAssets() {
    $assets = new AttachedAssets();
    $assets->setLibraries(['core/drupal.message']);
    return $assets;
  }

}
