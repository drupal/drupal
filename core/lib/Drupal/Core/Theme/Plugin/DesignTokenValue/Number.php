<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\DesignTokenValue;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\Attribute\DesignTokenValue;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginBase;

/**
 * Number design token value.
 */
#[DesignTokenValue(
  id: 'number',
  label: new TranslatableMarkup('Number'),
)]
class Number extends DesignTokenValuePluginBase {

  /**
   * Numbers can be positive, negative and have fractions.
   */
  protected int|float $value;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'value' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    parent::setConfiguration($configuration);

    $this->value = $this->configuration['value'];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toDtcg(): mixed {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function toCss(): string {
    return (string) $this->value;
  }

}
