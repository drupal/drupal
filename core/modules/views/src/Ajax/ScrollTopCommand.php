<?php

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\ScrollTopCommand as CoreScrollTopCommand;

/**
 * Provides an AJAX command for scrolling to the top of an element.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.viewsScrollTop.
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0.
 *   Use \Drupal\Core\Ajax\ScrollTopCommand
 *
 * @see https://www.drupal.org/node/3344141
 */
class ScrollTopCommand extends CoreScrollTopCommand {
}
