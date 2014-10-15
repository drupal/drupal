<?php

/**
 * @file
 * Contains \Drupal\field_ui\FormDisplayOverview.
 */

namespace Drupal\field_ui;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field UI form display overview form.
 */
class FormDisplayOverview extends DisplayOverviewBase {

  /**
   * {@inheritdoc}
   */
  protected $displayContext = 'form';

  /**
   * Stores the module manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManager $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The widget or formatter plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use for invoking hooks.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, FieldTypePluginManager $field_type_manager, PluginManagerBase $plugin_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_manager, $field_type_manager, $plugin_manager, $config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.widget'),
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_form_display_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $form_mode_name = 'default') {
    return parent::buildForm($form, $form_state, $entity_type_id, $bundle, $form_mode_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, EntityDisplayInterface $entity_display, array $form, FormStateInterface $form_state) {
    $field_row = parent::buildFieldRow($field_definition, $entity_display, $form, $form_state);

    $field_name = $field_definition->getName();

    // Update the (invisible) title of the 'plugin' column.
    $field_row['plugin']['#title'] = $this->t('Formatter for @title', array('@title' => $field_definition->getLabel()));
    if (!empty($field_row['plugin']['settings_edit_form']) && ($plugin = $entity_display->getRenderer($field_name))) {
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
  protected function getPlugin(FieldDefinitionInterface $field_definition, $configuration) {
    $plugin = NULL;

    if ($configuration && $configuration['type'] != 'hidden') {
      $plugin = $this->pluginManager->getInstance(array(
        'field_definition' => $field_definition,
        'form_mode' => $this->mode,
        'configuration' => $configuration
      ));
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginOptions(FieldDefinitionInterface $field_definition) {
    return parent::getPluginOptions($field_definition) + array('hidden' => '- ' . t('Hidden') . ' -');
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
    return $this->entityManager->getFormModes($this->entity_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayType() {
    return 'entity_form_display';
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
  protected function getOverviewRoute($mode) {
    return Url::fromRoute('field_ui.form_display_overview_form_mode_' . $this->entity_type, [
      $this->bundleEntityType => $this->bundle,
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
        $this->mode,
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
      'form_mode' => $this->mode,
    );
    $this->moduleHandler->alter('field_widget_settings_summary', $summary, $context);
  }

}
