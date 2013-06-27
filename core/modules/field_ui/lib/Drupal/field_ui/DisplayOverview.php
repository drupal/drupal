<?php

/**
 * @file
 * Contains \Drupal\field_ui\DisplayOverview.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\entity\EntityDisplayBaseInterface;
use Drupal\field\FieldInstanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field UI display overview form.
 */
class DisplayOverview extends DisplayOverviewBase implements ControllerInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_display_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow($field_id, FieldInstanceInterface $instance, EntityDisplayBaseInterface $entity_display, array $form, array &$form_state) {
    $field_row = parent::buildFieldRow($field_id, $instance, $entity_display, $form, $form_state);
    $display_options = $entity_display->getComponent($field_id);

    // Insert the label column.
    $label = array(
      'label' => array(
        '#type' => 'select',
        '#title' => t('Label display for @title', array('@title' => $instance['label'])),
        '#title_display' => 'invisible',
        '#options' => $this->getFieldLabelOptions(),
        '#default_value' => $display_options ? $display_options['label'] : 'above',
      ),
    );

    $label_position = array_search('plugin', array_keys($field_row));
    $field_row = array_slice($field_row, 0, $label_position, TRUE) + $label + array_slice($field_row, $label_position, count($field_row) - 1, TRUE);

    // Update the (invisible) title of the 'plugin' column.
    $field_row['plugin']['#title'] = t('Formatter for @title', array('@title' => $instance['label']));
    if (!empty($field_row['plugin']['settings_edit_form'])) {
      $plugin_type_info = $entity_display->getRenderer($field_id)->getPluginDefinition();
      $field_row['plugin']['settings_edit_form']['label']['#markup'] = t('Format settings:') . ' <span class="plugin-name">' . $plugin_type_info['label'] . '</span>';
    }

    return $field_row;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildExtraFieldRow($field_id, $extra_field, $entity_display) {
    $extra_field_row = parent::buildExtraFieldRow($field_id, $extra_field, $entity_display);

    // Insert an empty placeholder for the label column.
    $label = array(
      'empty_cell' => array(
        '#markup' => '&nbsp;'
      )
    );
    $label_position = array_search('plugin', array_keys($extra_field_row));
    $extra_field_row = array_slice($extra_field_row, 0, $label_position, TRUE) + $label + array_slice($extra_field_row, $label_position, count($extra_field_row) - 1, TRUE);

    return $extra_field_row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDisplay($mode) {
    return entity_get_display($this->entity_type, $this->bundle, $mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtraFields() {
    return field_info_extra_fields($this->entity_type, $this->bundle, 'display');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlugin($instance, $configuration) {
    $plugin = NULL;

    if ($configuration && $configuration['type'] != 'hidden') {
      $plugin = $this->pluginManager->getInstance(array(
        'field_definition' => $instance,
        'view_mode' => $this->mode,
        'configuration' => $configuration
      ));
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginOptions($field_type) {
    return parent::getPluginOptions($field_type) + array('hidden' => '- ' . t('Hidden') . ' -');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultPlugin($field_type) {
    return $this->fieldTypes[$field_type]['default_formatter'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModes() {
    return entity_get_view_modes($this->entity_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModeSettings() {
    return field_view_mode_settings($this->entity_type, $this->bundle);
  }

  /**
   * {@inheritdoc}
   */
  protected function saveDisplayModeSettings($display_mode_settings) {
    $bundle_settings = field_bundle_settings($this->entity_type, $this->bundle);
    $bundle_settings['view_modes'] = NestedArray::mergeDeep($bundle_settings['view_modes'], $display_mode_settings);
    field_bundle_settings($this->entity_type, $this->bundle, $bundle_settings);
  }

  /**
   * {@inheritdoc
   */
  protected function getTableHeader() {
    return array(
      t('Field'),
      t('Weight'),
      t('Parent'),
      t('Label'),
      array('data' => t('Format'), 'colspan' => 3),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverviewPath($mode) {
    return $this->entityManager->getAdminPath($this->entity_type, $this->bundle) . "/display/$mode";
  }

  /**
   * Returns an array of visibility options for field labels.
   *
   * @return array
   *   An array of visibility options.
   */
  protected function getFieldLabelOptions() {
    return array(
      'above' => t('Above'),
      'inline' => t('Inline'),
      'hidden' => '- ' . t('Hidden') . ' -',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSettingsForm(array &$settings_form, $plugin, FieldInstanceInterface $instance, array $form, array &$form_state) {
    $context = array(
      'formatter' => $plugin,
      'field' => $instance->getField(),
      'instance' => $instance,
      'view_mode' => $this->mode,
      'form' => $form,
    );
    drupal_alter('field_formatter_settings_form', $settings_form, $form_state, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSettingsSummary(array &$summary, $plugin, FieldInstanceInterface $instance) {
    $context = array(
      'formatter' => $plugin,
      'field' => $instance->getField(),
      'instance' => $instance,
      'view_mode' => $this->mode,
    );
    drupal_alter('field_formatter_settings_summary', $summary, $context);
  }

}
