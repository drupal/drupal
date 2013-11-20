<?php

/**
 * @file
 * Contains \Drupal\field_ui\DisplayOverview.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\NestedArray;
use Drupal\entity\EntityDisplayBaseInterface;
use Drupal\field\FieldInstanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field UI display overview form.
 */
class DisplayOverview extends DisplayOverviewBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_display_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $entity_type = NULL, $bundle = NULL) {
    if ($this->getRequest()->attributes->has('view_mode_name')) {
      $this->mode = $this->getRequest()->attributes->get('view_mode_name');
    }

    return parent::buildForm($form, $form_state, $entity_type, $bundle);
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
        '#title' => $this->t('Label display for @title', array('@title' => $instance->getFieldLabel())),
        '#title_display' => 'invisible',
        '#options' => $this->getFieldLabelOptions(),
        '#default_value' => $display_options ? $display_options['label'] : 'above',
      ),
    );

    $label_position = array_search('plugin', array_keys($field_row));
    $field_row = array_slice($field_row, 0, $label_position, TRUE) + $label + array_slice($field_row, $label_position, count($field_row) - 1, TRUE);

    // Update the (invisible) title of the 'plugin' column.
    $field_row['plugin']['#title'] = $this->t('Formatter for @title', array('@title' => $instance->getFieldLabel()));
    if (!empty($field_row['plugin']['settings_edit_form'])) {
      $plugin_type_info = $entity_display->getRenderer($field_id)->getPluginDefinition();
      $field_row['plugin']['settings_edit_form']['label']['#markup'] = $this->t('Format settings:') . ' <span class="plugin-name">' . $plugin_type_info['label'] . '</span>';
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
    return parent::getPluginOptions($field_type) + array('hidden' => '- ' . $this->t('Hidden') . ' -');
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
  protected function getDisplayType() {
    return 'entity_display';
  }

  /**
   * {@inheritdoc
   */
  protected function getTableHeader() {
    return array(
      $this->t('Field'),
      $this->t('Weight'),
      $this->t('Parent'),
      $this->t('Label'),
      array('data' => $this->t('Format'), 'colspan' => 3),
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
      'above' => $this->t('Above'),
      'inline' => $this->t('Inline'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
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
