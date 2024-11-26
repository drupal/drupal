<?php

declare(strict_types=1);

namespace Drupal\navigation\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\TopBarRegion;

/**
 * The top bar item attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class TopBarItem extends Plugin {

  /**
   * Constructs a new TopBarItem instance.
   *
   * @param string $id
   *   The top bar item ID.
   * @param \Drupal\navigation\TopBarRegion $region
   *   The region where the top bar item belongs to.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the top bar item.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TopBarRegion $region,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
