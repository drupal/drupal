<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Attribute;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * The CKEditor5Plugin attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class CKEditor5Plugin extends Plugin {

  /**
   * The CKEditor 5 aspects of the plugin definition.
   *
   * @var \Drupal\ckeditor5\Attribute\CKEditor5AspectsOfCKEditor5Plugin|null
   */
  public readonly ?CKEditor5AspectsOfCKEditor5Plugin $ckeditor5;

  /**
   * The Drupal aspects of the plugin definition.
   *
   * @var \Drupal\ckeditor5\Attribute\DrupalAspectsOfCKEditor5Plugin|null
   */
  public readonly ?DrupalAspectsOfCKEditor5Plugin $drupal;

  /**
   * Constructs a CKEditor5Plugin attribute.
   *
   * Overridden for compatibility with the AttributeBridgeDecorator, which
   * ensures YAML-defined CKEditor 5 plugin definitions are also processed by
   * attributes. Unfortunately it does not (yet) support nested attributes.
   * Force YAML-defined plugin definitions to be parsed by the attributes, to
   * ensure consistent handling of defaults.
   *
   * @see \Drupal\Component\Plugin\Discovery\AttributeBridgeDecorator::getDefinitions()
   *
   * @param string $id
   *   The plugin ID.
   * @param array|\Drupal\ckeditor5\Attribute\CKEditor5AspectsOfCKEditor5Plugin|null $ckeditor5
   *   (optional) The CKEditor 5 aspects of the plugin definition. Required
   *   unless set by deriver.
   * @param array|\Drupal\ckeditor5\Attribute\DrupalAspectsOfCKEditor5Plugin|null $drupal
   *   (optional) The Drupal aspects of the plugin definition. Required unless
   *   set by deriver.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    array|CKEditor5AspectsOfCKEditor5Plugin|null $ckeditor5 = NULL,
    array|DrupalAspectsOfCKEditor5Plugin|null $drupal = NULL,
    public readonly ?string $deriver = NULL,
  ) {
    $this->ckeditor5 = is_array($ckeditor5) ? new CKEditor5AspectsOfCKEditor5Plugin(...$ckeditor5) : $ckeditor5;
    $this->drupal = is_array($drupal) ? new DrupalAspectsOfCKEditor5Plugin(...$drupal) : $drupal;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass(): string {
    return $this->drupal?->getClass() ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class): void {
    $this->drupal?->setClass($class);
  }

  /**
   * {@inheritdoc}
   */
  public function get(): CKEditor5PluginDefinition {
    return new CKEditor5PluginDefinition([
      'id' => $this->id,
      'ckeditor5' => $this->ckeditor5?->get(),
      'drupal' => $this->drupal?->get(),
      'provider' => $this->getProvider(),
    ]);
  }

}
