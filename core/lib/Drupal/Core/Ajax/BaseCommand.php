<?php

namespace Drupal\Core\Ajax;

/**
 * Base command that only exists to simplify AJAX commands.
 */
class BaseCommand implements CommandInterface {

  /**
   * Constructs a BaseCommand object.
   *
   * @param string $command
   *   The name of the command.
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct(
    protected $command,
    protected $data,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => $this->command,
      'data' => $this->data,
    ];
  }

}
