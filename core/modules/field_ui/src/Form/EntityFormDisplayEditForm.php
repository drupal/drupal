<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\EntityFormDisplayEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for the EntityFormDisplay entity type.
 */
class EntityFormDisplayEditForm extends EntityDisplayFormBase {

  /**
   * {@inheritdoc}
   */
  protected $displayContext = 'form';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_row = parent::buildFieldRow($field_definition, $form, $form_state);

    $field_name = $field_definition->getName();

    // Update the (invisible) title of the 'plugin' column.
    $field_row['plugin']['#title'] = $this->t('Formatter for @title', array('@title' => $field_definition->getLabel()));
    if (!empty($field_row['plugin']['settings_edit_form']) && ($plugin = $this->entity->getRenderer($field_name))) {
      $plugin_type_info = $plugin->getPluginDefinition();
      $field_row['plugin']['settings_edit_form']['label']['#markup'] = $this->t('Widget settings:') . ' <span class="plugin-name">' . $plugin_type_info['label'] . '</span>';
    }

    return $field_row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDisplay($entity_type_id, $bundle, $mode) {
    return entity_get_form_display($entity_type_id, $bundle, $mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlugin(FieldDefinitionInterface $field_definition, $configuration) {
    $plugin = NULL;

    if ($configuration && $configuration['type'] != 'hidden') {
      $plugin = $this->pluginManager->getInstance(array(
        'field_definition' => $field_definition,
        'form_mode' => $this->entity->mode,
        'configuration' => $configuration
      ));
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultPlugin($field_type) {
    return isset($this->fieldTypes[$field_type]['default_widget']) ? $this->fieldTypes[$field_type]['default_widget'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModes() {
    return $this->entityManager->getFormModes($this->entity->targetEntityType);
  }

  /**
   * {@inheritdoc}
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
  protected function getOverviewUrl($mode) {
    $entity_type = $this->entityManager->getDefinition($this->entity->targetEntityType);
    $field_entity_type = $entity_type->getBundleEntityType() != 'bundle'?  $entity_type->getBundleEntityType() : $entity_type->id();
    return Url::fromRoute('entity.entity_form_display.' . $field_entity_type . '.form_mode', [
      $this->bundleEntityTypeId => $this->entity->bundle,
      'form_mode_name' => $mode,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function thirdPartySettingsForm(PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $settings_form = array();
    // Invoke hook_field_widget_third_party_settings_form(), keying resulting
    // subforms by module name.
    foreach ($this->moduleHandler->getImplementations('field_widget_third_party_settings_form') as $module) {
      $settings_form[$module] = $this->moduleHandler->invoke($module, 'field_widget_third_party_settings_form', array(
        $plugin,
        $field_definition,
        $this->entity->mode,
        $form,
        $form_state,
      ));
    }
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSettingsSummary(array &$summary, PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition) {
    $context = array(
      'widget' => $plugin,
      'field_definition' => $field_definition,
      'form_mode' => $this->entity->mode,
    );
    $this->moduleHandler->alter('field_widget_settings_summary', $summary, $context);
  }

}
