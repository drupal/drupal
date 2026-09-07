<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\DesignTokenValue;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\Attribute\DesignTokenValue;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginBase;

/**
 * Font family design token value.
 */
#[DesignTokenValue(
  id: 'fontFamily',
  label: new TranslatableMarkup('Font family'),
)]
class FontFamily extends DesignTokenValuePluginBase {

  /**
   * The array of font names (ordered from most to least preferred).
   */
  protected array $value;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'value' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    parent::setConfiguration($configuration);

    if (is_array($this->configuration['value'])) {
      $this->value = $this->configuration['value'];
    }
    elseif (is_string($this->configuration['value'])) {
      $this->value = [$this->configuration['value']];
    }

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
    return implode(', ', array_map(Html::escape(...), $this->value));
  }

}
