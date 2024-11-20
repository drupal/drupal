<?php

namespace Drupal\Core\Ajax;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Asset\AttachedAssets;

/**
 * AJAX command for a JavaScript Drupal.message() call.
 *
 * AJAX command that allows you to add messages from an Ajax response. The command will create a new Drupal.Message() object and call its addMessage() method.
 *
 * Usage examples:
 * Here are examples of how to suppress announcements:
 * @code
 *   $response = new AjaxResponse();
 *
 *   // A status message added in the default location.
 *   $response->addCommand(new MessageCommand('Your changes have been saved.'));
 *
 *   // A warning message added in the default location.
 *   $response->addCommand(new MessageCommand('There was a problem. Save your work.', NULL, ['type' => 'warning']));
 *
 *   // A status message added an alternate location.
 *   $response->addCommand(new MessageCommand('Hey look over here!', '#alternate-message-container'));
 *
 *   // An error added in an alternate location.
 *   $response->addCommand(new MessageCommand('Open the pod bay doors, HAL.',  '#alternate-message-container', ['type' => 'error']));
 * @endcode
 *
 * By default, previous messages in a location are cleared before the message
 * is added. If you would like to leave the previous messages in a location,
 * you may do so by setting the fourth parameter to FALSE:
 * Here are examples of how to suppress announcements:
 * @code
 *   $response->addCommand(new MessageCommand('Hey look over here.', NULL, ['type' => 'error'], FALSE));
 * @endcode
 *
 * Developers should take care when using MessageCommand and AnnounceCommand 
 * together in the same AJAX response. Unless the "announce" option is set to
 * an empty string (''), this command will result in the message being
 * announced to screen readers. When combined with AnnounceCommand, this may
 * result in unexpected behavior. Manual testing with a screen reader is
 * strongly recommended.
 *
 * If you wish to display a message without the text being announced to screen
 * readers, add options.announce = '' (i.e. an empty string):
 * @code
 *   $command = new MessageCommand("I won't be announced", NULL, [
 *     'announce' => '',
 *   ]);
 * @endcode
 *
 * If you wish to set the announcement priority to assertive, you can do that
 * this way: 
 * @code
 *   $response->addCommand(new MessageCommand('You added 3 cat pics.', '.js-media-library-messages', [
 *     'priority' => 'assertive',
 *   ]);
 * @endcode
 *
 * @see \Drupal\Core\Ajax\AnnounceCommand
 * @see https://www.drupal.org/docs/develop/drupal-apis/ajax-api/core-ajax-callback-commands#s-messagecommand
 *
 * @ingroup ajax
 */
class MessageCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  /**
   * The message text.
   *
   * @var string|\Drupal\Component\Render\MarkupInterface
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
   * @param string|\Drupal\Component\Render\MarkupInterface $message
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
      'message' => $this->message instanceof MarkupInterface
        ? (string) $this->message
        : Xss::filterAdmin($this->message),
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
