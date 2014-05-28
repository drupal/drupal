<?php

/**
 * @file
 * Contains \Drupal\quickedit\Ajax\BaseCommand.
 */

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Base command that only exists to simplify Quick Edit's actual AJAX commands.
 */
class BaseCommand implements CommandInterface {

  /**
   * The name of the command.
   *
   * @var string
   */
  protected $command;

  /**
   * The data to pass on to the client side.
   *
   * @var string
   */
  protected $data;

  /**
   * Constructs a BaseCommand object.
   *
   * @param string $command
   *   The name of the command.
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($command, $data) {
    $this->command = $command;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return array(
      'command' => $this->command,
      'data' => $this->data,
    );
  }

}
