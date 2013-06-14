<?php

/**
 * @file
 * Contains \Drupal\views\Ajax\SetFormCommand.
 */

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for setting a form in the views edit modal.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.viewsSetForm.
 */
class SetFormCommand implements CommandInterface {

  /**
   * The rendered output of the form.
   *
   * @var string
   */
  protected $output;

  /**
   * The title of the form.
   *
   * @var string
   */
  protected $title;

  /**
   * The URL of the form.
   *
   * @var string
   */
  protected $url;

  /**
   * Constructs a \Drupal\views\Ajax\ReplaceTitleCommand object.
   *
   * @param string $output
   *   The form to display in the modal.
   * @param string $title
   *   The title of the form.
   * @param string $url
   *   (optional) An optional URL of the form.
   */
  public function __construct($output, $title, $url = NULL) {
    $this->output = $output;
    $this->title = $title;
    $this->url = $url;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    $command = array(
      'command' => 'viewsSetForm',
      'output' => $this->output,
      'title' => $this->title,
    );
    if (isset($this->url)) {
      $command['url'] = $this->url;
    }
    return $command;
  }

}
