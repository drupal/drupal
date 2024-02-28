<?php

declare(strict_types=1);

namespace Drupal\Core\ImageToolkit\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Plugin attribute for the image toolkit plugin.
 *
 * An image toolkit provides common image file manipulations like scaling,
 * cropping, and rotating.
 *
 * Plugin namespace: Plugin\ImageToolkit
 *
 * For a working example, see
 * \Drupal\system\Plugin\ImageToolkit\GDToolkit
 *
 * @see \Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation
 * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitBase
 * @see \Drupal\Core\ImageToolkit\ImageToolkitManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ImageToolkit extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly ?string $deriver = NULL,
  ) {}

}
