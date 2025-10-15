<?php

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command that sets the browser URL without refreshing the page.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.setBrowserUrl.
 */
class SetBrowserUrl implements CommandInterface {

  /**
   * Constructs a new command instance.
   *
   * @param string $url
   *   The URL to be set in the browser.
   */
  public function __construct(protected string $url) {
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'setBrowserUrl',
      'url' => $this->url,
    ];
  }

}
