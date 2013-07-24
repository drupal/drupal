<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\Formatter\FormatterBase.
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\field\FieldInstanceInterface;
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
  public function view(EntityInterface $entity, $langcode, FieldInterface $items) {
    $addition = array();

    $elements = $this->viewElements($entity, $langcode, $items);
    if ($elements) {
      $entity_type = $entity->entityType();
      $field_name = $this->fieldDefinition->getFieldName();
      $info = array(
        '#theme' => 'field',
        '#title' => $this->fieldDefinition->getFieldLabel(),
        '#access' => $this->checkFieldAccess('view', $entity),
        '#label_display' => $this->label,
        '#view_mode' => $this->viewMode,
        '#language' => $langcode,
        '#field_name' => $field_name,
        '#field_type' => $this->fieldDefinition->getFieldType(),
        '#field_translatable' => $this->fieldDefinition->isFieldTranslatable(),
        '#entity_type' => $entity_type,
        '#bundle' => $entity->bundle(),
        '#object' => $entity,
        '#items' => $items->getValue(TRUE),
        '#formatter' => $this->getPluginId(),
      );

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
  public function prepareView(array $entities, $langcode, array $items) { }

  /**
   * Returns whether the currently logged in user has access to the field.
   *
   * @todo Remove this once Field API access is unified with entity field
   *   access: http://drupal.org/node/1994140.
   */
  protected function checkFieldAccess($op, $entity) {
    if ($this->fieldDefinition instanceof FieldInstanceInterface) {
      $field = $this->fieldDefinition->getField();
      return field_access($op, $field, $entity->entityType(), $entity);
    }
    else {
      return FALSE;
    }
  }

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
