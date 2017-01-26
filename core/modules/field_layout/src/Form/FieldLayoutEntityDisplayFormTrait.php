<?php

namespace Drupal\field_layout\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;

/**
 * Provides shared code for entity display forms.
 *
 * Both EntityViewDisplayEditForm and EntityFormDisplayEditForm must maintain
 * their parent hierarchy, while being identically enhanced by Field Layout.
 * This trait contains the code they both share.
 */
trait FieldLayoutEntityDisplayFormTrait {

  /**
   * The field layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Overrides \Drupal\field_ui\Form\EntityDisplayFormBase::getRegions().
   */
  public function getRegions() {
    $regions = [];

    $layout_definition = $this->layoutPluginManager->getDefinition($this->getEntity()->getLayoutId());
    foreach ($layout_definition->getRegions() as $name => $region) {
      $regions[$name] = [
        'title' => $region['label'],
        'message' => $this->t('No field is displayed.'),
      ];
    }

    $regions['hidden'] = [
      'title' => $this->t('Disabled', [], ['context' => 'Plural']),
      'message' => $this->t('No field is hidden.'),
    ];

    return $regions;
  }

  /**
   * Overrides \Drupal\field_ui\Form\EntityDisplayFormBase::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['field_layouts'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout settings'),
    ];

    $layout_plugin = $this->getLayout($this->getEntity(), $form_state);

    $form['field_layouts']['field_layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a layout'),
      '#options' => $this->layoutPluginManager->getLayoutOptions(),
      '#default_value' => $layout_plugin->getPluginId(),
      '#ajax' => [
        'callback' => '::settingsAjax',
        'wrapper' => 'field-layout-settings-wrapper',
        'trigger_as' => ['name' => 'field_layout_change'],
      ],
    ];
    $form['field_layouts']['submit'] = [
      '#type' => 'submit',
      '#name' => 'field_layout_change',
      '#value' => $this->t('Change layout'),
      '#submit' => ['::settingsAjaxSubmit'],
      '#attributes' => ['class' => ['js-hide']],
      '#ajax' => [
        'callback' => '::settingsAjax',
        'wrapper' => 'field-layout-settings-wrapper',
      ],
    ];

    $form['field_layouts']['settings_wrapper'] = [
      '#type' => 'container',
      '#id' => 'field-layout-settings-wrapper',
      '#tree' => TRUE,
    ];

    if ($layout_plugin instanceof PluginFormInterface) {
      $form['field_layouts']['settings_wrapper']['layout_settings'] = [];
      $subform_state = SubformState::createForSubform($form['field_layouts']['settings_wrapper']['layout_settings'], $form, $form_state);
      $form['field_layouts']['settings_wrapper']['layout_settings'] = $layout_plugin->buildConfigurationForm($form['field_layouts']['settings_wrapper']['layout_settings'], $subform_state);
    }

    return $form;
  }

  /**
   * Gets the layout plugin for the currently selected field layout.
   *
   * @param \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface $entity
   *   The current form entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   *   The layout plugin.
   */
  protected function getLayout(EntityDisplayWithLayoutInterface $entity, FormStateInterface $form_state) {
    if (!$layout_plugin = $form_state->get('layout_plugin')) {
      $stored_layout_id = $entity->getLayoutId();
      // Use selected layout if it exists, falling back to the stored layout.
      $layout_id = $form_state->getValue('field_layout', $stored_layout_id);
      // If the current layout is the stored layout, use the stored layout
      // settings. Otherwise leave the settings empty.
      $layout_settings = $layout_id === $stored_layout_id ? $entity->getLayoutSettings() : [];

      $layout_plugin = $this->layoutPluginManager->createInstance($layout_id, $layout_settings);
      $form_state->set('layout_plugin', $layout_plugin);
    }
    return $layout_plugin;
  }

  /**
   * Ajax callback for the field layout settings form.
   */
  public static function settingsAjax($form, FormStateInterface $form_state) {
    return $form['field_layouts']['settings_wrapper'];
  }

  /**
   * Submit handler for the non-JS case.
   */
  public function settingsAjaxSubmit($form, FormStateInterface $form_state) {
    $form_state->set('layout_plugin', NULL);
    $form_state->setRebuild();
  }

  /**
   * Overrides \Drupal\field_ui\Form\EntityDisplayFormBase::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $layout_plugin = $this->getLayout($this->getEntity(), $form_state);
    if ($layout_plugin instanceof PluginFormInterface) {
      $subform_state = SubformState::createForSubform($form['field_layouts']['settings_wrapper']['layout_settings'], $form, $form_state);
      $layout_plugin->validateConfigurationForm($form['field_layouts']['settings_wrapper']['layout_settings'], $subform_state);
    }
  }

  /**
   * Overrides \Drupal\field_ui\Form\EntityDisplayFormBase::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity = $this->getEntity();
    $layout_plugin = $this->getLayout($entity, $form_state);
    if ($layout_plugin instanceof PluginFormInterface) {
      $subform_state = SubformState::createForSubform($form['field_layouts']['settings_wrapper']['layout_settings'], $form, $form_state);
      $layout_plugin->submitConfigurationForm($form['field_layouts']['settings_wrapper']['layout_settings'], $subform_state);
    }

    $entity->setLayout($layout_plugin);
  }

  /**
   * Gets the form entity.
   *
   * @return \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface
   *   The current form entity.
   */
  abstract public function getEntity();

}
