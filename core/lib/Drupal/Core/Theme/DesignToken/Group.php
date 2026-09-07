<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A group: distinguishable by not having a $value property.
 */
class Group implements DtcgInterface {

  /**
   * DTCG: $type.
   *
   * Actual data type which ensures consistency, validation, and tool
   * compatibility across platforms.
   */
  public readonly ?string $type;

  /**
   * Construct a DTCG group.
   *
   * @param string $provider
   *   The Drupal extension providing the group.
   * @param string $name
   *   The group's machine name.
   * @param \Drupal\Core\Theme\DesignToken\DtcgInterface[] $children
   *   The children (groups or tokens).
   * @param ?\Drupal\Core\Theme\DesignToken\Group $parent
   *   The parent group. Groups can be nested.
   * @param ?\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   A plain text description explaining the group's purpose.
   * @param ?string $type
   *   The group type if present.
   */
  public function __construct(
    public readonly string $provider,
    public readonly string $name,
    protected array $children = [],
    public readonly ?Group $parent = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    ?string $type = NULL,
  ) {
    $this->type = $type ?? $parent->type ?? NULL;
    assert(Inspector::assertAllObjects($children, DtcgInterface::class));
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
   * The group's children.
   *
   * @return array
   *   The group's children.
   */
  public function getChildren(): array {
    return $this->children;
  }

  /**
   * Sets the group children.
   *
   * @param \Drupal\Core\Theme\DesignToken\DtcgInterface[] $children
   *   The children to set.
   *
   * @return $this
   *   The group object.
   */
  public function setChildren(array $children): static {
    assert(Inspector::assertAllObjects($children, DtcgInterface::class));
    $this->children = $children;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toDtcg(): array {
    $data = [];
    if ($this->type) {
      $data['$type'] = $this->type;
    }
    if ($this->description) {
      $data['$description'] = $this->description->render();
    }
    foreach ($this->children as $key => $child) {
      $data[$key] = $child->toDtcg();
    }
    return $data;
  }

}
