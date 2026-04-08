<?php

declare(strict_types=1);

namespace Drupal\image;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;

/**
 * Image style flush and options utilities.
 */
class ImageDerivativeUtilities {

  use StringTranslationTrait;

  /**
   * Clears cached versions of a specific file in all styles.
   *
   * @param string $path
   *   The Drupal file path to the original image.
   */
  public function pathFlush(string $path): void {
    $styles = ImageStyle::loadMultiple();
    foreach ($styles as $style) {
      $style->flush($path);
    }
  }

  /**
   * Gets an array of image styles suitable for using as select list options.
   *
   * @param bool $include_empty
   *   If TRUE a '- None -' option will be inserted in the options array.
   *
   * @return string[]
   *   Array of image styles where the key is the machine name and the value is
   *   the label.
   */
  public function styleOptions(bool $include_empty = TRUE): array {
    $styles = ImageStyle::loadMultiple();
    $options = [];
    if ($include_empty && !empty($styles)) {
      $options[''] = $this->t('- None -');
    }
    foreach ($styles as $name => $style) {
      $options[$name] = $style->label();
    }

    if (empty($options)) {
      $options[''] = $this->t('No defined styles');
    }
    return $options;
  }

}
