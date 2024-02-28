<?php

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Builds an image toolkit operation.
 */
#[ImageToolkitOperation(
  id: "bar",
  toolkit: "test",
  operation: "invert",
  label: new TranslatableMarkup("Invert"),
  description: new TranslatableMarkup("Bar.")
)]
class Bar extends OperationBase {}
