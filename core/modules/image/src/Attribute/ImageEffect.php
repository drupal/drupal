<?php

declare(strict_types=1);

namespace Drupal\image\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an ImageEffect attribute for plugin discovery.
 *
 * Plugin Namespace: Plugin\ImageEffect
 *
 * For a working example, see
 * \Drupal\image\Plugin\ImageEffect\ResizeImageEffect
 *
 * @see hook_image_effect_info_alter()
 * @see \Drupal\image\ConfigurableImageEffectInterface
 * @see \Drupal\image\ConfigurableImageEffectBase
 * @see \Drupal\image\ImageEffectInterface
 * @see \Drupal\image\ImageEffectBase
 * @see \Drupal\image\ImageEffectManager
 * @see \Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ImageEffect extends Plugin {

  /**
   * Constructs an ImageEffect attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the image effect.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) A brief description of the image effect. This will be shown
   *   when adding or configuring this image effect.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
