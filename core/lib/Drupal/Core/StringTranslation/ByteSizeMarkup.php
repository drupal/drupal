<?php

declare(strict_types=1);

namespace Drupal\Core\StringTranslation;

use Drupal\Component\Utility\Bytes;

/**
 * A class to generate translatable markup for the given byte count.
 */
final class ByteSizeMarkup {

  /**
   * This class should not be instantiated.
   */
  private function __construct() {}

  /**
   * Gets the TranslatableMarkup object for the provided size.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translatable markup.
   *
   * @throws \LogicException
   *   Thrown when an invalid unit size is used.
   */
  public static function create(float|int $size, ?string $langcode = NULL, ?TranslationInterface $stringTranslation = NULL): TranslatableMarkup {
    $options = ['langcode' => $langcode];
    $absolute_size = abs($size);
    if ($absolute_size < Bytes::KILOBYTE) {
      return new PluralTranslatableMarkup($size, '1 byte', '@count bytes', [], $options, $stringTranslation);
    }
    // Create a multiplier to preserve the sign of $size.
    $sign = $absolute_size / $size;
    foreach (['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] as $unit) {
      $absolute_size /= Bytes::KILOBYTE;
      $rounded_size = round($absolute_size, 2);
      if ($rounded_size < Bytes::KILOBYTE) {
        break;
      }
    }

    $args = ['@size' => $rounded_size * $sign];
    // At this point $markup must be set.
    return match ($unit) {
      'KB' => new TranslatableMarkup('@size KB', $args, $options, $stringTranslation),
      'MB' => new TranslatableMarkup('@size MB', $args, $options, $stringTranslation),
      'GB' => new TranslatableMarkup('@size GB', $args, $options, $stringTranslation),
      'TB' => new TranslatableMarkup('@size TB', $args, $options, $stringTranslation),
      'PB' => new TranslatableMarkup('@size PB', $args, $options, $stringTranslation),
      'EB' => new TranslatableMarkup('@size EB', $args, $options, $stringTranslation),
      'ZB' => new TranslatableMarkup('@size ZB', $args, $options, $stringTranslation),
      'YB' => new TranslatableMarkup('@size YB', $args, $options, $stringTranslation),
      default => throw new \LogicException("Unexpected unit value"),
    };
  }

}
