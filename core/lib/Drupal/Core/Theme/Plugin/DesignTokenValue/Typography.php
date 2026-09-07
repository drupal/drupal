<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\DesignTokenValue;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\Attribute\DesignTokenValue;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginBase;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Typography design token value.
 */
#[DesignTokenValue(
  id: 'typography',
  label: new TranslatableMarkup('Typography'),
)]
class Typography extends DesignTokenValuePluginBase {

  /**
   * The typography's properties.
   *
   * @var array|string[]
   */
  protected array $properties = [
    'fontFamily' => 'fontFamily',
    'fontSize' => 'dimension',
    'fontWeight' => 'fontWeight',
    'letterSpacing' => 'dimension',
    'lineHeight' => 'number',
  ];

  /**
   * The typography's font.
   */
  protected FontFamily $fontFamily;

  /**
   * The size of the typography.
   */
  protected Dimension $fontSize;

  /**
   * The weight of the typography.
   */
  protected FontWeight $fontWeight;

  /**
   * The horizontal spacing between characters.
   */
  protected Dimension $letterSpacing;

  /**
   * The vertical spacing between lines of typography.
   */
  protected Number $lineHeight;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    #[Autowire(service: 'plugin.manager.design_token_value')]
    protected DesignTokenValuePluginManagerInterface $designTokenValuePluginManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'fontFamily' => [],
      'fontSize' => [
        'value' => 0,
        'unit' => 'px',
      ],
      'fontWeight' => '',
      'letterSpacing' => [
        'value' => 0,
        'unit' => 'px',
      ],
      'lineHeight' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    parent::setConfiguration($configuration);

    foreach ($this->properties as $property => $type) {
      $propertyConfig = static::prepareConfiguration($this->configuration[$property]);
      $this->$property = $this->designTokenValuePluginManager->createInstance($type, $propertyConfig);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toDtcg(): mixed {
    $data = [];
    foreach ($this->properties as $property => $type) {
      $data[$property] = $this->$property->toDtcg();
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function toCss(): string {
    // letterSpacing is not allowed in the font shortcut.
    return implode(' ', [
      $this->fontWeight->toCss(),
      $this->fontSize->toCss() . '/' . $this->lineHeight->toCss(),
      $this->fontFamily->toCss(),
    ]);
  }

}
