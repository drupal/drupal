<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Formatter\FormatterBase.
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\PluginSettingsBase;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Base class for 'Field formatter' plugin implementations.
 */
abstract class FormatterBase extends PluginSettingsBase implements FormatterInterface {

  /**
   * The field definition.
   *
   * @var array
   */
  protected $field;

  /**
   * The field instance definition.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
   */
  protected $instance;

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
   * @param \Drupal\field\Plugin\Core\Entity\FieldInstance $instance
   *   The field instance to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   */
  public function __construct($plugin_id, array $plugin_definition, $instance, array $settings, $label, $view_mode) {
    parent::__construct(array(), $plugin_id, $plugin_definition);

    $this->instance = $instance;
    $this->field = field_info_field($instance['field_name']);
    $this->settings = $settings;
    $this->label = $label;
    $this->viewMode = $view_mode;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::view().
   */
  public function view(EntityInterface $entity, $langcode, array $items) {
    $field = $this->field;
    $instance = $this->instance;

    $addition = array();

    $elements = $this->viewElements($entity, $langcode, $items);
    if ($elements) {
      $entity_type = $entity->entityType();
      $info = array(
        '#theme' => 'field',
        '#title' => $instance['label'],
        '#access' => field_access('view', $field, $entity->entityType(), $entity),
        '#label_display' => $this->label,
        '#view_mode' => $this->viewMode,
        '#language' => $langcode,
        '#field_name' => $field['field_name'],
        '#field_type' => $field['type'],
        '#field_translatable' => $field['translatable'],
        '#entity_type' => $entity_type,
        '#bundle' => $entity->bundle(),
        '#object' => $entity,
        '#items' => $items,
        '#formatter' => $this->getPluginId(),
      );

      $addition[$field['field_name']] = array_merge($info, $elements);
    }

    return $addition;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    return array();
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsSummary().
   */
  public function settingsSummary() {
    return '';
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::prepareView().
   */
  public function prepareView(array $entities, $langcode, array &$items) { }

}
