<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A token: distinguishable by containing a $value property.
 */
class DesignToken extends PluginDefinition implements DtcgInterface {

  /**
   * DTCG: $type.
   *
   * Actual data type which ensures consistency, validation, and tool
   * compatibility across platforms.
   */
  public readonly ?string $type;

  /**
   * Construct a DTCG token.
   *
   * @param string $provider
   *   The Drupal extension providing the token.
   * @param string $name
   *   The token's machine name.
   * @param \Drupal\Core\Theme\DesignToken\DesignTokenValueInterface $value
   *   The token value.
   * @param ?\Drupal\Core\Theme\DesignToken\Group $parent
   *   The parent group.
   * @param ?\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   A plain text description explaining the token's purpose.
   * @param ?string $type
   *   The token type if present.
   */
  public function __construct(
    string $provider,
    public readonly string $name,
    public readonly DesignTokenValueInterface $value,
    public readonly ?Group $parent = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    ?string $type = NULL,
  ) {
    $this->type = $type ?? $parent->type ?? NULL;
    $this->provider = $provider;
    // Spaces are allowed path but not for plugin IDs.
    $this->id = \strtr($this->getPath(), [' ' => '_']);
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): ?string {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(): string {
    return $this->parent ? ($this->parent->getPath() . '.' . $this->name) : $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function toDtcg(): array {
    $data = [
      '$type' => $this->type,
      '$value' => $this->value->toDtcg(),
    ];
    if ($this->description) {
      $data['$description'] = $this->description->render();
    }
    return $data;
  }

}
