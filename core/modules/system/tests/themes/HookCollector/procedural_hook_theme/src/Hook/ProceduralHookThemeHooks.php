<?php

declare(strict_types=1);

namespace Drupal\procedural_hook_theme\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Confirms LegacyHook works with themes.
 */
class ProceduralHookThemeHooks {

  #[Hook('procedural_legacy_alter')]
  public function proceduralLegacyAlter(array &$args): void {
    $args[] = 'OOP theme hook executed.';
  }

}
