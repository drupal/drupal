<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\Exception\IconDefinitionInvalidDataException;
use function Symfony\Component\String\u;

/**
 * Handle an icon definition.
 *
 * @internal
 *   This API is experimental.
 */
class IconDefinition implements IconDefinitionInterface {

  public const ICON_SEPARATOR = ':';

  /**
   * Constructor for IconDefinition.
   *
   * @param string $pack_id
   *   The id of the icon pack.
   * @param string $icon_id
   *   The id of the icon.
   * @param string $template
   *   The template of the icon.
   * @param string|null $source
   *   The source, url or path of the icon.
   * @param string|null $group
   *   The group of the icon.
   * @param array $data
   *   The additional data of the icon.
   */
  private function __construct(
    private string $pack_id,
    private string $icon_id,
    private string $template,
    private ?string $source,
    private ?string $group,
    private array $data,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(
    string $pack_id,
    string $icon_id,
    string $template,
    ?string $source = NULL,
    ?string $group = NULL,
    array $data = [],
  ): self {
    $errors = [];
    if (0 === strlen($pack_id)) {
      $errors[] = 'Empty pack_id provided!';
    }
    if (0 === strlen($icon_id)) {
      $errors[] = 'Empty icon_id provided!';
    }
    if (0 === strlen($template)) {
      $errors[] = 'Empty template provided!';
    }

    if (count($errors)) {
      throw new IconDefinitionInvalidDataException(implode(' ', $errors));
    }

    // Cleanup of data that do not need to be passed.
    unset($data['config']['sources'], $data['relative_path'], $data['absolute_path']);

    return new self($pack_id, $icon_id, $template, $source, $group, $data);
  }

  /**
   * {@inheritdoc}
   */
  public static function createIconId(string $pack_id, string $icon_id): string {
    return sprintf('%s%s%s', $pack_id, self::ICON_SEPARATOR, $icon_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function getIconDataFromId(string $icon_full_id): ?array {
    $icon_data = explode(self::ICON_SEPARATOR, $icon_full_id, 2);
    if (count($icon_data) < 2) {
      return NULL;
    }

    return [
      'pack_id' => $icon_data[0],
      'icon_id' => $icon_data[1],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getRenderable(string $icon_full_id, array $settings = []): ?array {
    if (!$icon_data = self::getIconDataFromId($icon_full_id)) {
      return NULL;
    }

    if (isset($settings[$icon_data['pack_id']])) {
      $settings = $settings[$icon_data['pack_id']];
    }

    return [
      '#type' => 'icon',
      '#pack_id' => $icon_data['pack_id'],
      '#icon_id' => $icon_data['icon_id'],
      '#settings' => $settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return self::humanize($this->icon_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return sprintf('%s%s%s', $this->pack_id, self::ICON_SEPARATOR, $this->icon_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getIconId(): string {
    return $this->icon_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackId(): string {
    return $this->pack_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(): ?string {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): ?string {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(): string {
    return $this->template;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackLabel(): ?TranslatableMarkup {
    return $this->data['label'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary(): ?string {
    return $this->data['library'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllData(): array {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key): mixed {
    return $this->data[$key] ?? NULL;
  }

  /**
   * Humanize a text for admin display.
   *
   * @param string $text
   *   The text to humanize.
   *
   * @return string
   *   The human friendly text.
   */
  public static function humanize(string $text): string {
    return (string) u($text)->snake()->replace('_', ' ')->title(allWords: TRUE);
  }

}
