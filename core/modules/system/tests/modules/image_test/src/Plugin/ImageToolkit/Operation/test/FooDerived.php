<?php

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Builds an image toolkit operation.
 */
#[ImageToolkitOperation(
  id: "foo_derived",
  toolkit: "test:derived_toolkit",
  operation: "blur",
  label: new TranslatableMarkup("Blur Derived"),
  description: new TranslatableMarkup("Foo derived.")
)]
class FooDerived extends OperationBase {}
