<?php

namespace Drupal\Core\Ajax;

use Drupal\Component\Utility\UrlHelper;

/**
 * AJAX command for adjusting Drupal's JavaScript settings.
 *
 * The 'settings' command instructs the client either to use the given array as
 * the settings for ajax-loaded content or to extend drupalSettings with the
 * given array, depending on the value of the $merge parameter.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.settings()
 * defined in misc/ajax.js.
 *
 * @ingroup ajax
 */
class SettingsCommand implements CommandInterface {

  /**
   * An array of key/value pairs of JavaScript settings.
   *
   * This will be used for all commands after this if they do not include their
   * own settings array.
   *
   * @var array
   */
  protected $settings;

  /**
   * Whether the settings should be merged into the global drupalSettings.
   *
   * By default (FALSE), the settings that are passed to Drupal.attachBehaviors
   * will not include the global drupalSettings.
   *
   * @var bool
   */
  protected $merge;

  /**
   * Constructs a SettingsCommand object.
   *
   * @param array $settings
   *   An array of key/value pairs of JavaScript settings.
   * @param bool $merge
   *   Whether the settings should be merged into the global drupalSettings.
   */
  public function __construct(array $settings, $merge = FALSE) {
    $this->settings = $settings;
    $this->merge = $merge;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    if (isset($this->settings['ajax_page_state']['libraries'])) {
      $this->settings['ajax_page_state']['libraries'] = UrlHelper::compressQueryParameter($this->settings['ajax_page_state']['libraries']);
    }

    return [
      'command' => 'settings',
      'settings' => $this->settings,
      'merge' => $this->merge,
    ];
  }

}
