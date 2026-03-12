<?php

declare(strict_types=1);

namespace Drupal\comment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\OptionsEnumTrait;

/**
 * Defines the comment field status options.
 */
enum CommentingStatus: int {

  use OptionsEnumTrait;

  case Open = 2;
  case Closed = 1;
  case Hidden = 0;

  /**
   * {@inheritdoc}
   */
  public function label(): string|\Stringable {
    return match ($this) {
      self::Open => new TranslatableMarkup('Open'),
      self::Closed => new TranslatableMarkup('Closed'),
      self::Hidden => new TranslatableMarkup('Hidden'),
    };
  }

}
