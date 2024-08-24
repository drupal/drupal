<?php

declare(strict_types=1);

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * An image toolkit operation that throws a \RuntimeException.
 */
#[ImageToolkitOperation(
  id: "failing",
  toolkit: "test",
  operation: "failing",
  label: new TranslatableMarkup("An image toolkit operation that throws a \\RuntimeException"),
  description: new TranslatableMarkup("An image toolkit operation that throws a \\RuntimeException.")
)]
class Failing extends OperationBase {

  /**
   * {@inheritdoc}
   */
  public function execute(array $arguments) {
    throw new \RuntimeException('Ahem, this image operation failed');
  }

}
