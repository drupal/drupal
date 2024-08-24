<?php

declare(strict_types=1);

namespace Drupal\image_test\Plugin\ImageToolkit;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkit;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a derivative of TestToolkit.
 */
#[ImageToolkit(
  id: "test:derived_toolkit",
  title: new TranslatableMarkup("A dummy toolkit, derivative of 'test'."),
)]
class DerivedToolkit extends TestToolkit {}
