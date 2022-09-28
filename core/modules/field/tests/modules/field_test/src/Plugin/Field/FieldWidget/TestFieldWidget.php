<?php

namespace Drupal\field_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'test_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "test_field_widget",
 *   label = @Translation("Test widget"),
 *   field_types = {
 *     "test_field",
 *     "hidden_test_field",
 *     "test_field_with_preconfigured_options"
 *   },
 *   weight = -10
 * )
 */
class TestFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'test_widget_setting' => 'dummy test string',
      'role' => 'anonymous',
      'role2' => 'anonymous',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['test_widget_setting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field test field widget setting'),
      '#description' => $this->t('A dummy form element to simulate field widget setting.'),
      '#default_value' => $this->getSetting('test_widget_setting'),
      '#required' => FALSE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('@setting: @value', ['@setting' => 'test_widget_setting', '@value' => $this->getSetting('test_widget_setting')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?? '',
    ];
    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    foreach (['role', 'role2'] as $setting) {
      if (!empty($role_id = $this->getSetting($setting))) {
        // Create a dependency on the role config entity referenced in settings.
        $dependencies['config'][] = "user.role.$role_id";
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    // Only the setting 'role' is resolved here. When the dependency related to
    // this setting is removed, is expected that the widget component will be
    // update accordingly in the display entity. The 'role2' setting is
    // deliberately left out from being updated. When the dependency
    // corresponding to this setting is removed, is expected that the widget
    // component will be disabled in the display entity.
    if (!empty($role_id = $this->getSetting('role'))) {
      if (!empty($dependencies['config']["user.role.$role_id"])) {
        $this->setSetting('role', 'anonymous');
        $changed = TRUE;
      }
    }

    return $changed;
  }

}
