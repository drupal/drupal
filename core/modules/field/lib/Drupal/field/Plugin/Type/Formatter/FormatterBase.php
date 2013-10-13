<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\Formatter\FormatterBase.
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Core\Entity\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\field\Plugin\PluginSettingsBase;

/**
 * Base class for 'Field formatter' plugin implementations.
 */
abstract class FormatterBase extends PluginSettingsBase implements FormatterInterface {

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Entity\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The label display setting.
   *
   * @var string
   */
  protected $label;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   */
  public function __construct($plugin_id, array $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode) {
    parent::__construct(array(), $plugin_id, $plugin_definition);

    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->label = $label;
    $this->viewMode = $view_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items) {
    $addition = array();

    $elements = $this->viewElements($items);
    if ($elements) {
      $entity = $items->getEntity();
      $entity_type = $entity->entityType();
      $field_name = $this->fieldDefinition->getFieldName();
      $info = array(
        '#theme' => 'field',
        '#title' => $this->fieldDefinition->getFieldLabel(),
        '#access' => $items->access('view'),
        '#label_display' => $this->label,
        '#view_mode' => $this->viewMode,
        '#language' => $items->getLangcode(),
        '#field_name' => $field_name,
        '#field_type' => $this->fieldDefinition->getFieldType(),
        '#field_translatable' => $this->fieldDefinition->isFieldTranslatable(),
        '#entity_type' => $entity_type,
        '#bundle' => $entity->bundle(),
        '#object' => $entity,
        '#items' => $items->getValue(TRUE),
        '#formatter' => $this->getPluginId(),
        '#cache' => array('tags' => array())
      );

      // Gather cache tags from reference fields.
      foreach ($items as $item) {
        if (isset($item->format)) {
          $info['#cache']['tags']['filter_format'] = $item->format;
        }

        if (isset($item->entity)) {
          $info['#cache']['tags'][$item->entity->entityType()][] = $item->entity->id();
          $info['#cache']['tags'][$item->entity->entityType() . '_view'] = TRUE;
        }
      }

      $addition[$field_name] = array_merge($info, $elements);
    }

    return $addition;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) { }

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   The array of settings.
   */
  protected function getFieldSettings() {
    return $this->fieldDefinition->getFieldSettings();
  }

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getFieldSetting($setting_name) {
    return $this->fieldDefinition->getFieldSetting($setting_name);
  }

}
