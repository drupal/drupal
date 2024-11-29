<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for icon definition.
 *
 * @internal
 *   This API is experimental.
 */
interface IconDefinitionInterface {

  /**
   * Create an icon definition.
   *
   * @param string $pack_id
   *   The id of the icon pack.
   * @param string $icon_id
   *   The id of the icon.
   * @param string $template
   *   The icon template from definition.
   * @param string|null $source
   *   The source, url or path of the icon.
   * @param string|null $group
   *   The group of the icon.
   * @param array $data
   *   The additional data of the icon. Used by extractors to dynamically add
   *   any needed value.
   *
   * @return self
   *   The icon definition.
   */
  public static function create(
    string $pack_id,
    string $icon_id,
    string $template,
    ?string $source = NULL,
    ?string $group = NULL,
    array $data = [],
  ): self;

  /**
   * Create an icon full id.
   *
   * @param string $pack_id
   *   The id of the icon pack.
   * @param string $icon_id
   *   The id of the icon.
   *
   * @return string
   *   The icon full id.
   */
  public static function createIconId(string $pack_id, string $icon_id): string;

  /**
   * Get icon id and pack id from an icon full id.
   *
   * @param string $icon_full_id
   *   The id of the icon including the pack.
   *
   * @return array|null
   *   The icon data as keyed with `pack_id` and `icon_id`.
   */
  public static function getIconDataFromId(string $icon_full_id): ?array;

  /**
   * Get the icon renderable element array.
   *
   * Shortcut to use icon element quickly without check if the icon id is valid,
   * then the element will simply be empty.
   *
   * @param string $icon_full_id
   *   The id of the icon including the pack.
   * @param array $settings
   *   Settings to pass to the renderable for context. Can be indexed by the
   *   icon pack id for lookup.
   *
   * @return array|null
   *   The icon renderable.
   */
  public static function getRenderable(string $icon_full_id, array $settings = []): ?array;

  /**
   * Get the icon label as human friendly.
   *
   * @return string
   *   The icon label.
   */
  public function getLabel(): string;

  /**
   * Get the full icon id.
   *
   * @return string
   *   The icon id as pack_id:icon_id.
   */
  public function getId(): string;

  /**
   * Get the icon id.
   *
   * @return string
   *   The icon id as icon_id.
   */
  public function getIconId(): string;

  /**
   * Get the icon Pack id.
   *
   * @return string
   *   The icon Pack id.
   */
  public function getPackId(): string;

  /**
   * Get the icon source, path or url.
   *
   * @return string|null
   *   The icon source.
   */
  public function getSource(): ?string;

  /**
   * Get the icon Group.
   *
   * @return string|null
   *   The icon Group.
   */
  public function getGroup(): ?string;

  /**
   * Get the icon Twig template.
   *
   * @return string
   *   The icon template.
   */
  public function getTemplate(): string;

  /**
   * Get the icon pack label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The icon pack label.
   */
  public function getPackLabel(): ?TranslatableMarkup;

  /**
   * Get the icon Twig library.
   *
   * @return string|null
   *   The icon library.
   */
  public function getLibrary(): ?string;

  /**
   * Get all icon data.
   *
   * Icon data is injected by extractors and can be used to set any values
   * needed for the extractor loadIcon() method.
   * The data is then injected in the Twig template of the icon.
   *
   * @return array
   *   All the icon data.
   */
  public function getAllData(): array;

  /**
   * Get a specific icon data.
   *
   * @param string $key
   *   The data key to retrieve.
   *
   * @return mixed
   *   The icon specific data if exist or null. The data being added as an array
   *   by extractors, there is no specific type enforced.
   */
  public function getData(string $key): mixed;

}
