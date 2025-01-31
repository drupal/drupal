<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Attribute;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
   *
   * @see \Drupal\Component\Plugin\Discovery\AttributeBridgeDecorator::getDefinitions()
   */
  public function __construct(
    public readonly string $id,
    array|CKEditor5AspectsOfCKEditor5Plugin|null $ckeditor5 = NULL,
    array|DrupalAspectsOfCKEditor5Plugin|null $drupal = NULL,
    public readonly ?string $deriver = NULL,
  ) {
    // If either of the two aspects of the plugin definition is in array form,
    // then this is a YAML-defined CKEditor 5 plugin definition. To avoid errors
    // due to violating either Attribute class constructor, verify basic data
    // shape requirements here. This provides a better DX for YAML-defined
    // plugins, and avoids the need for a PHP IDE or debugger.
    // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::processDefinition()
    // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::validateCKEditor5Aspects()
    // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::validateDrupalAspects()
    if (!$drupal instanceof DrupalAspectsOfCKEditor5Plugin) {
      if ($drupal === NULL) {
        throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "drupal" key.', $id));
      }
      // TRICKY: $this->deriver is incorrect due to AttributeBridgeDecorator!
      // If there's no deriver, validate here. Otherwise: the base definition is
      // allowed to be incomplete; let CKEditor5PluginManager::processDefinition
      // perform the validation.
      // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::getDeriver()
      // @see \Drupal\Component\Plugin\Discovery\AttributeBridgeDecorator::getDefinitions()
      if (!isset($drupal['deriver'])) {
        if (isset($drupal['label']) && !is_string($drupal['label']) && !$drupal['label'] instanceof TranslatableMarkup) {
          throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition has a "drupal.label" value that is not a string nor a TranslatableMarkup instance.', $id));
        }
        if (!$ckeditor5 instanceof CKEditor5AspectsOfCKEditor5Plugin) {
          if ($ckeditor5 === NULL) {
            throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "ckeditor5" key.', $id));
          }
          if (!isset($ckeditor5['plugins'])) {
            throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "ckeditor5.plugins" key.', $id));
          }
        }
      }
    }

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
