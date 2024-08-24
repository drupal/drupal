<?php

declare(strict_types=1);

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Builds an image toolkit operation.
 */
#[ImageToolkitOperation(
  id: "foo",
  toolkit: "test",
  operation: "blur",
  label: new TranslatableMarkup("Blur"),
  description: new TranslatableMarkup("Foo."),
)]
class Foo extends OperationBase {}
