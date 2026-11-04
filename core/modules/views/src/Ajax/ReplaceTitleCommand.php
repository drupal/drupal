<?php

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for replacing the page title.
 *
 * This command is implemented in
 * Drupal.AjaxCommands.prototype.viewsReplaceTitle.
 */
class ReplaceTitleCommand implements CommandInterface {

  /**
   * Constructs a \Drupal\views\Ajax\ReplaceTitleCommand object.
   *
   * @param string $title
   *   The title of the page.
   */
  public function __construct(
    protected $title,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'viewsReplaceTitle',
      'title' => $this->title,
      'siteName' => \Drupal::config('system.site')->get('name'),
    ];
  }

}
