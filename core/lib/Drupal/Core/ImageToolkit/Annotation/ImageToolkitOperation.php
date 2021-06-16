<?php

namespace Drupal\Core\ImageToolkit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for the image toolkit operation plugin.
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
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkit
 * @see \Drupal\image\Annotation\ImageEffect
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationBase
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationManager
 * @see plugin_api
 *
 * @Annotation
 */
class ImageToolkitOperation extends Plugin {

  /**
   * The plugin ID.
   *
   * There are no strict requirements as to the string to be used to identify
   * the plugin, since discovery of the appropriate operation plugin to be
   * used to apply an operation is based on the values of the 'toolkit' and
   * the 'operation' annotation values.
   *
   * However, it is recommended that the following patterns be used:
   * - '{toolkit}_{operation}' for the first implementation of an operation
   *   by a toolkit.
   * - '{module}_{toolkit}_{operation}' for overrides of existing
   *   implementations supplied by an alternative module, and for new
   *   module-supplied operations.
   *
   * @var string
   */
  public $id;

  /**
   * The id of the image toolkit plugin for which the operation is implemented.
   *
   * @var string
   */
  public $toolkit;

  /**
   * The machine name of the image toolkit operation implemented (e.g. "crop").
   *
   * @var string
   */
  public $operation;

  /**
   * The human-readable name of the image toolkit operation.
   *
   * The string should be wrapped in @Translation().
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the image toolkit operation.
   *
   * The string should be wrapped in @Translation().
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
