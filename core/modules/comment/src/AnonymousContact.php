<?php

declare(strict_types=1);

namespace Drupal\comment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\OptionsEnumTrait;

/**
 * Defines the anonymous contact options for comments.
 */
enum AnonymousContact: int {

  use OptionsEnumTrait;

  // Anonymous posters cannot enter their contact information.
  case Forbidden = 0;

  // Anonymous posters may leave their contact information.
  case Allowed = 1;

  // Anonymous posters are required to leave their contact information.
  case Required = 2;

  /**
   * {@inheritdoc}
   */
  public function label(): string|\Stringable {
    return match ($this) {
      self::Forbidden => new TranslatableMarkup('Anonymous posters may not enter their contact information'),
      self::Allowed => new TranslatableMarkup('Anonymous posters may leave their contact information'),
      self::Required => new TranslatableMarkup('Anonymous posters must leave their contact information'),
    };
  }

}
