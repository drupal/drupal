<?php

declare(strict_types=1);

namespace Drupal\node;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\OptionsEnumTrait;

/**
 * Enumeration for node preview modes.
 */
enum NodePreviewMode: int {

  use OptionsEnumTrait;

  case Disabled = 0;
  case Optional = 1;
  case Required = 2;

  /**
   * {@inheritdoc}
   */
  public function label(): string|\Stringable {
    return match ($this) {
      self::Disabled => new TranslatableMarkup('Disabled'),
      self::Optional => new TranslatableMarkup('Optional'),
      self::Required => new TranslatableMarkup('Required'),
    };
  }

}
