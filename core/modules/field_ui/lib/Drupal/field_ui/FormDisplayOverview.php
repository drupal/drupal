<?php

/**
 * @file
 * Contains \Drupal\field_ui\FormDisplayOverview.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\NestedArray;
use Drupal\entity\EntityDisplayBaseInterface;
use Drupal\field\FieldInstanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field UI form display overview form.
 */
class FormDisplayOverview extends DisplayOverviewBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.entity.field.field_type'),
      $container->get('plugin.manager.field.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_form_display_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow($field_id, FieldInstanceInterface $instance, EntityDisplayBaseInterface $entity_display, array $form, array &$form_state) {
    $field_row = parent::buildFieldRow($field_id, $instance, $entity_display, $form, $form_state);

    // Update the (invisible) title of the 'plugin' column.
    $field_row['plugin']['#title'] = $this->t('Formatter for @title', array('@title' => $instance['label']));
    if (!empty($field_row['plugin']['settings_edit_form']) && ($plugin = $entity_display->getRenderer($field_id))) {
      $plugin_type_info = $plugin->getPluginDefinition();
      $field_row['plugin']['settings_edit_form']['label']['#markup'] = $this->t('Widget settings:') . ' <span class="plugin-name">' . $plugin_type_info['label'] . '</span>';
    }

    return $field_row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDisplay($mode) {
    return entity_get_form_display($this->entity_type, $this->bundle, $mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtraFields() {
    return field_info_extra_fields($this->entity_type, $this->bundle, 'form');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlugin($instance, $configuration) {
    $plugin = NULL;

    if ($configuration && $configuration['type'] != 'hidden') {
      $plugin = $this->pluginManager->getInstance(array(
        'field_definition' => $instance,
        'form_mode' => $this->mode,
        'configuration' => $configuration
      ));
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultPlugin($field_type) {
    return $this->fieldTypes[$field_type]['default_widget'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModes() {
    return entity_get_form_modes($this->entity_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModeSettings() {
    return field_form_mode_settings($this->entity_type, $this->bundle);
  }

  /**
   * {@inheritdoc}
   */
  protected function saveDisplayModeSettings($display_mode_settings) {
    $bundle_settings = field_bundle_settings($this->entity_type, $this->bundle);
    $bundle_settings['form_modes'] = NestedArray::mergeDeep($bundle_settings['form_modes'], $display_mode_settings);
    field_bundle_settings($this->entity_type, $this->bundle, $bundle_settings);
  }

  /**
   * {@inheritdoc
   */
  protected function getTableHeader() {
    return array(
      $this->t('Field'),
      $this->t('Weight'),
      $this->t('Parent'),
      array('data' => $this->t('Widget'), 'colspan' => 3),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverviewPath($mode) {
    return $this->entityManager->getAdminPath($this->entity_type, $this->bundle) . "/form-display/$mode";
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSettingsForm(array &$settings_form, $plugin, FieldInstanceInterface $instance, array $form, array &$form_state) {
    $context = array(
      'widget' => $plugin,
      'field' => $instance->getField(),
      'instance' => $instance,
      'form_mode' => $this->mode,
      'form' => $form,
    );
    drupal_alter('field_widget_settings_form', $settings_form, $form_state, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSettingsSummary(array &$summary, $plugin, FieldInstanceInterface $instance) {
    $context = array(
      'widget' => $plugin,
      'field' => $instance->getField(),
      'instance' => $instance,
      'form_mode' => $this->mode,
    );
    drupal_alter('field_widget_settings_summary', $summary, $context);
  }

}
