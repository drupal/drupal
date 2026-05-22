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
      [
        'data' => $this->t('Machine name'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM, 'machine-name'],
      ],
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
        $settings_form[$module] = ($settings_form[$module] ?? []) + ($hook(
          $plugin,
          $field_definition,
          $this->entity->getMode(),
          $form,
          $form_state
        ) ?? []);
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

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Custom display settings.
    if ($this->entity->getMode() == 'default') {
      // Only show the settings if there is at least one custom display mode.
      $display_mode_options = $this->getDisplayModeOptions();
      // Unset default option.
      unset($display_mode_options['default']);
      if ($display_mode_options) {
        $form['modes'] = [
          '#type' => 'details',
          '#title' => $this->t('Custom display settings'),
        ];
        // Prepare default values for the 'Custom display settings' checkboxes.
        $default = [];
        if ($enabled_displays = array_filter($this->getDisplayStatuses())) {
          $default = array_keys(array_intersect_key($display_mode_options, $enabled_displays));
        }
        natcasesort($display_mode_options);
        $form['modes']['display_modes_custom'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Use custom display settings for the following @display_context modes', ['@display_context' => $this->displayContext]),
          '#options' => $display_mode_options,
          '#default_value' => $default,
        ];
        // Provide link to manage display modes.
        $form['modes']['display_modes_link'] = $this->getDisplayModesLink();
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $form_values = $form_state->getValues();

    // Handle the 'display modes' checkboxes if present.
    if ($this->entity->getMode() == 'default' && !empty($form_values['display_modes_custom'])) {
      $display_modes = $this->getDisplayModes();
      $current_statuses = $this->getDisplayStatuses();

      $statuses = [];
      foreach ($form_values['display_modes_custom'] as $mode => $value) {
        if (!empty($value) && empty($current_statuses[$mode])) {
          // If no display exists for the newly enabled form mode, initialize
          // it with those from the 'default' form mode, which were used so
          // far.
          if (!$this->entityTypeManager->getStorage($this->entity->getEntityTypeId())->load($this->entity->getTargetEntityTypeId() . '.' . $this->entity->getTargetBundle() . '.' . $mode)) {
            $display = $this->getEntityDisplay($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle(), 'default')->createCopy($mode);
            $display->save();
          }

          $display_mode_label = $display_modes[$mode]['label'];
          $url = $this->getOverviewUrl($mode);
          $this->messenger()
            ->addStatus($this->t('The %display_mode mode now uses custom display settings. You might want to <a href=":url">configure them</a>.', [
              '%display_mode' => $display_mode_label,
              ':url' => $url->toString(),
            ]));
        }
        $statuses[$mode] = !empty($value);
      }

      $this->saveDisplayStatuses($statuses);
    }
  }

}
