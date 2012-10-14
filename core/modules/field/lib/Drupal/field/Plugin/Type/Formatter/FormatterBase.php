<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Formatter\FormatterBase.
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\PluginSettingsBase;
use Drupal\field\FieldInstance;

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
   * @var Drupal\field\FieldInstance
   */
  protected $instance;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The formatter weight.
   *
   * @var int
   */
  protected $weight;

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
   * @param Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The Discovery class that holds access to the formatter implementation
   *   definition.
   * @param Drupal\field\FieldInstance $instance
   *   The field instance to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param int $weight
   *   The formatter weight.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   */
  public function __construct($plugin_id, DiscoveryInterface $discovery, $instance, array $settings, $weight, $label, $view_mode) {
    parent::__construct(array(), $plugin_id, $discovery);

    $this->instance = $instance;
    $this->field = field_info_field($instance['field_name']);
    $this->settings = $settings;
    $this->weight = $weight;
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
        '#weight' => $this->weight,
        '#title' => $instance['label'],
        '#access' => field_access($field, 'view', $entity_type, $entity),
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
