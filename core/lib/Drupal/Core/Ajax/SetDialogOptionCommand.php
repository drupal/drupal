<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\SetDialogOptionCommand.
 */

namespace Drupal\Core\Ajax;

/**
 * Defines an AJAX command that sets jQuery UI dialog properties.
 *
 * @ingroup ajax
 */
class SetDialogOptionCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $selector;

  /**
   * A jQuery UI dialog option name.
   *
   * @var string
   */
  protected $optionName;

  /**
   * A jQuery UI dialog option value.
   *
   * @var mixed
   */
  protected $optionValue;

  /**
   * Constructs a SetDialogOptionCommand object.
   *
   * @param string $selector
   *   The selector of the dialog whose title will be set. If set to an empty
   *   value, the default modal dialog will be selected.
   * @param string $option_name
   *   The name of the option to set. May be any jQuery UI dialog option.
   *   See http://api.jqueryui.com/dialog.
   * @param mixed $option_value
   *   The value of the option to be passed to the dialog.
   */
  public function __construct($selector, $option_name, $option_value) {
    $this->selector = $selector ? $selector : '#drupal-modal';
    $this->optionName = $option_name;
    $this->optionValue = $option_value;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return array(
      'command' => 'setDialogOption',
      'selector' => $this->selector,
      'optionName' => $this->optionName,
      'optionValue' => $this->optionValue,
    );
  }

}
