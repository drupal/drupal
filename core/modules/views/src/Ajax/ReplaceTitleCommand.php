<?php

/**
 * @file
 * Contains \Drupal\views\Ajax\ReplaceTitleCommand.
 */

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for replacing the page title.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.viewsReplaceTitle.
 */
class ReplaceTitleCommand implements CommandInterface {

  /**
   * The page title to replace.
   *
   * @var string
   */
  protected $title;

  /**
   * Constructs a \Drupal\views\Ajax\ReplaceTitleCommand object.
   *
   * @param string $title
   *   The title of the page.
   */
  public function __construct($title) {
    $this->title = $title;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return array(
      'command' => 'viewsReplaceTitle',
      'selector' => $this->title,
    );
  }

}
