<?php

namespace Drupal\layout_builder\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder\LayoutSectionBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'layout_section' formatter.
 *
 * @internal
 *
 * @FieldFormatter(
 *   id = "layout_section",
 *   label = @Translation("Layout Section"),
 *   field_types = {
 *     "layout_section"
 *   }
 * )
 */
class LayoutSectionFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The layout section builder.
   *
   * @var \Drupal\layout_builder\LayoutSectionBuilder
   */
  protected $builder;

  /**
   * Constructs a LayoutSectionFormatter object.
   *
   * @param \Drupal\layout_builder\LayoutSectionBuilder $builder
   *   The layout section builder.
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct(LayoutSectionBuilder $builder, $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    $this->builder = $builder;
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('layout_builder.builder'),
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\layout_builder\SectionStorageInterface $items */
    foreach ($items->getSections() as $delta => $section) {
      $elements[$delta] = $section->toRenderArray();
    }

    return $elements;
  }

}
