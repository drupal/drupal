<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for the EntityFormDisplay entity type.
 *
 * @internal
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
      $container->get('plugin.manager.field.widget'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_row = parent::buildFieldRow($field_definition, $form, $form_state);

    $field_name = $field_definition->getName();

    // Update the (invisible) title of the 'plugin' column.
    $field_row['plugin']['#title'] = $this->t('Formatter for @title', ['@title' => $field_definition->getLabel()]);
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
    return $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultPlugin($field_type) {
    return $this->fieldTypes[$field_type]['default_widget'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModes() {
    return $this->entityDisplayRepository->getFormModes($this->entity->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModeOptions() {
    return $this->entityDisplayRepository->getFormModeOptions($this->entity->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModesLink() {
    return [
      '#type' => 'link',
      '#title' => $this->t('Manage form modes'),
      '#url' => Url::fromRoute('entity.entity_form_mode.collection'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTableHeader() {
    return [
      $this->t('Field'),
      $this->t('Weight'),
      $this->t('Parent'),
      $this->t('Region'),
      ['data' => $this->t('Widget'), 'colspan' => 3],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverviewUrl($mode) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entity->getTargetEntityTypeId());
    return Url::fromRoute('entity.entity_form_display.' . $this->entity->getTargetEntityTypeId() . '.form_mode', [
      'form_mode_name' => $mode,
    ] + FieldUI::getRouteBundleParameter($entity_type, $this->entity->getTargetBundle()));
  }

  /**
   * {@inheritdoc}
   */
  protected function thirdPartySettingsForm(PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $settings_form = [];
    // Invoke hook_field_widget_third_party_settings_form(), keying resulting
    // subforms by module name.
    $this->moduleHandler->invokeAllWith(
      'field_widget_third_party_settings_form',
      function (callable $hook, string $module) use (&$settings_form, $plugin, $field_definition, &$form, $form_state) {
        $settings_form[$module] = $hook(
          $plugin,
          $field_definition,
          $this->entity->getMode(),
          $form,
          $form_state
        );
      }
    );
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSettingsSummary(array &$summary, PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition) {
    $context = [
      'widget' => $plugin,
      'field_definition' => $field_definition,
      'form_mode' => $this->entity->getMode(),
    ];
    $this->moduleHandler->alter('field_widget_settings_summary', $summary, $context);
  }

}
