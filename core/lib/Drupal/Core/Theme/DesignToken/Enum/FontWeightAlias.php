<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken\Enum;

/**
 * Enumeration of the font weight token value possible aliases.
 */
enum FontWeightAlias: string {

  case Thin = 'thin';
  case Hairline = 'hairline';
  case ExtraLight = 'extra-light';
  case UltraLight = 'ultra-light';
  case Light = 'light';
  case Normal = 'normal';
  case Regular = 'regular';
  case Book = 'book';
  case Medium = 'medium';
  case SemiBold = 'semi-bold';
  case DemiBold = 'demi-bold';
  case Bold = 'bold';
  case ExtraBold = 'extra-bold';
  case UltraBold = 'ultra-bold';
  case Black = 'black';
  case Heavy = 'heavy';
  case ExtraBlack = 'extra-black';
  case UltraBlack = 'ultra-black';

  /**
   * Gets value from the alias.
   */
  public function value(): int {
    return match ($this) {
      self::Thin => 100,
      self::Hairline => 100,
      self::ExtraLight => 200,
      self::UltraLight => 200,
      self::Light => 300,
      self::Normal => 400,
      self::Regular => 400,
      self::Book => 400,
      self::Medium => 500,
      self::SemiBold => 600,
      self::DemiBold => 600,
      self::Bold => 700,
      self::ExtraBold => 800,
      self::UltraBold => 800,
      self::Black => 900,
      self::Heavy => 900,
      self::ExtraBlack => 950,
      self::UltraBlack => 950,
    };
  }

}
