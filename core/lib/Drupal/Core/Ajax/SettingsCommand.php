<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\SettingsCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for adjusting Drupal's JavaScript settings.
 *
 * The 'settings' command instructs the client either to use the given array as
 * the settings for ajax-loaded content or to extend drupalSettings with the
 * given array, depending on the value of the $merge parameter.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.settings()
 * defined in misc/ajax.js.
 */
class SettingsCommand implements CommandInterface {

  /**
   * An array of key/value pairs of JavaScript settings.
   *
   * This will be utilized for all commands after this if they do not include
   * their own settings array.
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
   * @var boolean
   */
  protected $merge;

  /**
   * Constructs a SettingsCommand object.
   *
   * @param array $settings
   *   An array of key/value pairs of JavaScript settings.
   * @param boolean $merge
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

    return array(
      'command' => 'settings',
      'settings' => $this->settings,
      'merge' => $this->merge,
    );
  }

}
