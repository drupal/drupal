<?php

declare(strict_types=1);

namespace Drupal\locale\Model;

use Drupal\Core\Utility\OptionsEnumTrait;

/**
 * Which translations can be overwritten.
 */
enum Overwrite: string {

  use OptionsEnumTrait;

  // All translations can be overwritten.
  case All = 'all';

  // Default Translations can be overwritten.
  case NonCustomized = 'non_customized';

  // Translations cannot be overwritten.
  case None = 'none';

  /**
   * {@inheritdoc}
   */
  public function label(): \Stringable {
    return match ($this) {
      self::None => t("Don't overwrite existing translations."),
      self::NonCustomized => t('Only overwrite imported translations, customized translations are kept.'),
      self::All => t('Overwrite existing translations.'),
    };
  }

}
