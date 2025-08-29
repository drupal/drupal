<?php

declare(strict_types=1);

namespace Drupal\link;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\OptionsEnumTrait;

/**
 * Enumeration for link title visibility states.
 */
enum LinkTitleVisibility: int {

  use OptionsEnumTrait;

  case Disabled = 0;
  case Optional = 1;
  case Required = 2;

  /**
   * {@inheritdoc}
   */
  public function label(): string|\Stringable {
    return match ($this) {
      LinkTitleVisibility::Disabled => new TranslatableMarkup('Disabled'),
      LinkTitleVisibility::Optional => new TranslatableMarkup('Optional'),
      LinkTitleVisibility::Required => new TranslatableMarkup('Required'),
    };
  }

}
