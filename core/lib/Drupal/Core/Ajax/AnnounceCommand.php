<?php

namespace Drupal\Core\Ajax;

use Drupal\Core\Asset\AttachedAssets;

/**
 * AJAX command for a JavaScript Drupal.announce() call.
 *
 * @ingroup ajax
 */
class AnnounceCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  /**
   * The assertive priority attribute value.
   *
   * @var string
   */
  const PRIORITY_ASSERTIVE = 'assertive';

  /**
   * The polite priority attribute value.
   *
   * @var string
   */
  const PRIORITY_POLITE = 'polite';

  /**
   * The text to be announced.
   *
   * @var string
   */
  protected $text;

  /**
   * The priority that will be used for the announcement.
   *
   * @var string
   */
  protected $priority;

  /**
   * Constructs an AnnounceCommand object.
   *
   * @param string $text
   *   The text to be announced.
   * @param string|null $priority
   *   (optional) The priority that will be used for the announcement. Defaults
   *   to NULL which will not set a 'priority' in the response sent to the
   *   client and therefore the JavaScript Drupal.announce() default of 'polite'
   *   will be used for the message.
   */
  public function __construct($text, $priority = NULL) {
    $this->text = $text;
    $this->priority = $priority;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = [
      'command' => 'announce',
      'text' => $this->text,
    ];
    if ($this->priority !== NULL) {
      $render['priority'] = $this->priority;
    }
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedAssets() {
    $assets = new AttachedAssets();
    $assets->setLibraries(['core/drupal.announce']);
    return $assets;
  }

}
