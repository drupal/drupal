<?php

declare(strict_types=1);

namespace Drupal\Core\Template;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for icon.
 *
 * @internal
 */
final class IconsTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('icon', [$this, 'getIconRenderable']),
    ];
  }

  /**
   * Get an icon renderable array.
   *
   * @param string|null $pack_id
   *   The icon set ID.
   * @param string|null $icon_id
   *   The icon ID.
   * @param array|null $settings
   *   An array of settings for the icon.
   *
   * @return array
   *   The icon renderable.
   */
  public function getIconRenderable(?string $pack_id, ?string $icon_id, ?array $settings = []): array {
    if (!$pack_id || !$icon_id) {
      return [];
    }

    return [
      '#type' => 'icon',
      '#pack_id' => $pack_id,
      '#icon_id' => $icon_id,
      '#settings' => $settings,
    ];
  }

}
