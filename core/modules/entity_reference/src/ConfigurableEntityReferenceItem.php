<?php

/**
 * @file
 * Contains \Drupal\entity_reference\ConfigurableEntityReferenceItem.
 */

namespace Drupal\entity_reference;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;

/**
 * Alternative plugin implementation of the 'entity_reference' field type.
 *
 * Replaces the Core 'entity_reference' entity field type implementation, this
 * supports configurable fields, auto-creation of referenced entities and more.
 *
 * Required settings are:
 *  - target_type: The entity type to reference.
 *
 * @see entity_reference_field_info_alter().
 */
class ConfigurableEntityReferenceItem extends EntityReferenceItem implements OptionsProviderInterface, PreconfiguredFieldUiOptionsInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    // The target bundle is handled by the 'target_bundles' property in the
    // 'handler_settings' instance setting.
    unset($settings['target_bundle']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return $this->getSettableValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    // Flatten options first, because "settable options" may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getSettableOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $field_definition = $this->getFieldDefinition();
    if (!$options = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($field_definition, $this->getEntity())->getReferenceableEntities()) {
      return array();
    }

    // Rebuild the array by changing the bundle key into the bundle label.
    $target_type = $field_definition->getSetting('target_type');
    $bundles = \Drupal::entityManager()->getBundleInfo($target_type);

    $return = array();
    foreach ($options as $bundle => $entity_ids) {
      // The label does not need sanitizing since it is used as an optgroup
      // which is only supported by select elements and auto-escaped.
      $bundle_label = $bundles[$bundle]['label'];
      $return[(string) $bundle_label] = $entity_ids;
    }

    return count($return) == 1 ? reset($return) : $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    // Remove the 'AllowedValuesConstraint' validation constraint because entity
    // reference fields already use the 'ValidReference' constraint.
    foreach ($constraints as $key => $constraint) {
      if ($constraint instanceof AllowedValuesConstraint) {
        unset($constraints[$key]);
      }
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['target_type'] = array(
      '#type' => 'select',
      '#title' => t('Type of item to reference'),
      '#options' => \Drupal::entityManager()->getEntityTypeLabels(TRUE),
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();

    // Get all selection plugins for this entity type.
    $selection_plugins = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionGroups($this->getSetting('target_type'));
    $handlers_options = array();
    foreach (array_keys($selection_plugins) as $selection_group_id) {
      // We only display base plugins (e.g., 'default', 'views', etc.) and not
      // entity type specific plugins (e.g., 'default:node', 'default:user',
      // etc.).
      if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
        $handlers_options[$selection_group_id] = Html::escape($selection_plugins[$selection_group_id][$selection_group_id]['label']);
      }
      elseif (array_key_exists($selection_group_id . ':' . $this->getSetting('target_type'), $selection_plugins[$selection_group_id])) {
        $selection_group_plugin = $selection_group_id . ':' . $this->getSetting('target_type');
        $handlers_options[$selection_group_plugin] = Html::escape($selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label']);
      }
    }

    $form = array(
      '#type' => 'container',
      '#process' => array(
        '_entity_reference_field_field_settings_ajax_process',
      ),
      '#element_validate' => array(array(get_class($this), 'fieldSettingsFormValidate')),
    );
    $form['handler'] = array(
      '#type' => 'details',
      '#title' => t('Reference type'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => array('_entity_reference_form_process_merge_parent'),
    );

    $form['handler']['handler'] = array(
      '#type' => 'select',
      '#title' => t('Reference method'),
      '#options' => $handlers_options,
      '#default_value' => $field->getSetting('handler'),
      '#required' => TRUE,
      '#ajax' => TRUE,
      '#limit_validation_errors' => array(),
    );
    $form['handler']['handler_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Change handler'),
      '#limit_validation_errors' => array(),
      '#attributes' => array(
        'class' => array('js-hide'),
      ),
      '#submit' => array('entity_reference_settings_ajax_submit'),
    );

    $form['handler']['handler_settings'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('entity_reference-settings')),
    );

    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($field);
    $form['handler']['handler_settings'] += $handler->buildConfigurationForm(array(), $form_state);

    return $form;
  }

  /**
   * Form element validation handler; Invokes selection plugin's validation.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($field);
    $handler->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    $options = array();

    // Add all the commonly referenced entity types as distinct pre-configured
    // options.
    $entity_types = \Drupal::entityManager()->getDefinitions();
    $common_references = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
      return $entity_type->isCommonReferenceTarget();
    });

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($common_references as $entity_type) {
      $options[$entity_type->id()] = [
        'label' => $entity_type->getLabel(),
        'field_storage_config' => [
          'settings' => [
            'target_type' => $entity_type->id(),
          ]
        ]
      ];
    }

    return $options;
  }

}
