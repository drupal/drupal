<?php

declare(strict_types=1);

namespace Drupal\Core\ImageToolkit\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Plugin attribute for the image toolkit plugin.
 *
 * An image toolkit operation plugin provides a self-contained image
 * manipulation routine, for a specific image toolkit. Examples of image
 * toolkit operations are scaling, cropping, rotating, etc.
 *
 * Plugin namespace: Plugin\ImageToolkit\Operation
 *
 * For a working example, see
 * \Drupal\system\Plugin\ImageToolkit\Operation\gd\Crop
 *
 * @see \Drupal\Core\ImageToolkit\Attribute\ImageToolkit
 * @see \Drupal\image\Attribute\ImageEffect
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationBase
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationManager
 * @see plugin_api
 *
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ImageToolkitOperation extends Plugin {

  /**
   * Constructs a new ImageToolkitOperation instance.
   *
   * @param string $id
   *   The plugin ID.
   *   There are no strict requirements as to the string to be used to identify
   *   the plugin, since discovery of the appropriate operation plugin to be
   *   used to apply an operation is based on the values of the 'toolkit' and
   *   the 'operation' annotation values.
   *   However, it is recommended that the following patterns be used:
   *    - '{toolkit}_{operation}' for the first implementation of an operation
   *      by a toolkit.
   *    - '{module}_{toolkit}_{operation}' for overrides of existing
   *      implementations supplied by an alternative module, and for new
   *      module-supplied operations.
   * @param string $toolkit
   *   The id of the image toolkit plugin for which the operation is
   *   implemented.
   * @param string $operation
   *   The machine name of the image toolkit operation implemented
   *   (e.g. "crop").
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the image toolkit operation.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The description of the image toolkit operation.
   * @param class-string|null $deriver
   *   (optional) The deriver class for the image toolkit operation.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $toolkit,
    public readonly string $operation,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
