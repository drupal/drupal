<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\DesignTokenValue;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\Attribute\DesignTokenValue;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginBase;
use Drupal\Core\Theme\DesignToken\Enum\DimensionUnit;
use Drupal\Core\Theme\DesignToken\Enum\DimensionUnitInterface;

/**
 * Dimension design token value.
 */
#[DesignTokenValue(
  id: 'dimension',
  label: new TranslatableMarkup('Dimension'),
)]
class Dimension extends DesignTokenValuePluginBase {

  /**
   * An integer or floating-point value representing the numeric value.
   */
  protected int|float $value;

  /**
   * Unit of distance.
   */
  protected DimensionUnitInterface $unit;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'value' => 0,
      'unit' => DimensionUnit::Px,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    parent::setConfiguration($configuration);

    $this->value = $this->configuration['value'];
    $this->unit = $this->configuration['unit'] instanceof DimensionUnitInterface ? $this->configuration['unit'] : DimensionUnit::from($this->configuration['unit']);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toDtcg(): mixed {
    return [
      'value' => $this->value,
      'unit' => $this->unit->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function toCss(): string {
    return $this->value . $this->unit->value;
  }

}
