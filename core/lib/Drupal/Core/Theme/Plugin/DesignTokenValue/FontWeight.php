<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\DesignTokenValue;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\Attribute\DesignTokenValue;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginBase;
use Drupal\Core\Theme\DesignToken\Enum\FontWeightAlias;

/**
 * Font weight design token value.
 */
#[DesignTokenValue(
  id: 'fontWeight',
  label: new TranslatableMarkup('Font weight'),
)]
class FontWeight extends DesignTokenValuePluginBase {

  /**
   * The font weight value.
   */
  protected int $value;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      // 0 is not an authorized value, this forces to set an authorized value.
      'value' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    parent::setConfiguration($configuration);

    if ($this->configuration['value'] instanceof FontWeightAlias) {
      $this->value = $this->configuration['value']->value();
      return $this;
    }
    if (is_string($this->configuration['value'])) {
      $this->value = FontWeightAlias::from($this->configuration['value'])->value();
      return $this;
    }
    $this->value = (int) $this->configuration['value'];
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
