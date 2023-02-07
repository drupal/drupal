<?php

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

/**
 * An image toolkit operation that throws a \RuntimeException.
 *
 * @ImageToolkitOperation(
 *   id = "failing",
 *   toolkit = "test",
 *   operation = "failing",
 *   label = @Translation("An image toolkit operation that throws a \\RuntimeException"),
 *   description = @Translation("An image toolkit operation that throws a \\RuntimeException.")
 * )
 */
class Failing extends OperationBase {

  /**
   * {@inheritdoc}
   */
  public function execute(array $arguments) {
    throw new \RuntimeException('Ahem, this image operation failed');
  }

}
