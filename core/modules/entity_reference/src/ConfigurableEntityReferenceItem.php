<?php

/**
 * @file
 * Contains \Drupal\entity_reference\ConfigurableEntityReferenceItem.
 */

namespace Drupal\entity_reference;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AllowedValuesInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;
use Drupal\field\FieldConfigInterface;

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
class ConfigurableEntityReferenceItem extends EntityReferenceItem implements AllowedValuesInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    // The target bundle is handled by the 'target_bundles' property in the
    // 'handler_settings' instance setting.
    unset($settings['target_bundle']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultInstanceSettings() {
    return array(
      'handler_settings' => array(),
    ) + parent::defaultInstanceSettings();
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
    if (!$options = \Drupal::service('plugin.manager.entity_reference.selection')->getSelectionHandler($field_definition, $this->getEntity())->getReferenceableEntities()) {
      return array();
    }

    // Rebuild the array by changing the bundle key into the bundle label.
    $target_type = $field_definition->getSetting('target_type');
    $bundles = \Drupal::entityManager()->getBundleInfo($target_type);

    $return = array();
    foreach ($options as $bundle => $entity_ids) {
      $bundle_label = String::checkPlain($bundles[$bundle]['label']);
      $return[$bundle_label] = $entity_ids;
    }

    return count($return) == 1 ? reset($return) : $return;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $target_type = $settings['target_type'];

    // Call the parent to define the target_id and entity properties.
    $properties = parent::propertyDefinitions($field_definition);

    // Only add the revision ID property if the target entity type supports
    // revisions.
    $target_type_info = \Drupal::entityManager()->getDefinition($target_type);
    if ($target_type_info->hasKey('revision') && $target_type_info->getRevisionTable()) {
      $properties['revision_id'] = DataDefinition::create('integer')
        ->setLabel(t('Revision ID'))
        ->setSetting('unsigned', TRUE);
    }

    return $properties;
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
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $target_type = $field_definition->getSetting('target_type');
    $target_type_info = \Drupal::entityManager()->getDefinition($target_type);

    if ($target_type_info->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface') && $field_definition instanceof FieldConfigInterface) {
      $schema['columns']['revision_id'] = array(
        'description' => 'The revision ID of the target entity.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => FALSE,
      );
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array &$form_state, $has_data) {
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
  public function instanceSettingsForm(array $form, array &$form_state) {
    $instance = $form_state['instance'];

    // Get all selection plugins for this entity type.
    $selection_plugins = \Drupal::service('plugin.manager.entity_reference.selection')->getSelectionGroups($this->getSetting('target_type'));
    $handler_groups = array_keys($selection_plugins);

    $handlers = \Drupal::service('plugin.manager.entity_reference.selection')->getDefinitions();
    $handlers_options = array();
    foreach ($handlers as $plugin_id => $plugin) {
      // We only display base plugins (e.g. 'default', 'views', ...) and not
      // entity type specific plugins (e.g. 'default_node', 'default_user',
      // ...).
      if (in_array($plugin_id, $handler_groups)) {
        $handlers_options[$plugin_id] = String::checkPlain($plugin['label']);
      }
    }

    $form = array(
      '#type' => 'container',
      '#attached' => array(
        'css' => array(drupal_get_path('module', 'entity_reference') . '/css/entity_reference.admin.css'),
      ),
      '#process' => array(
        '_entity_reference_field_instance_settings_ajax_process',
      ),
      '#element_validate' => array(array(get_class($this), 'instanceSettingsFormValidate')),
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
      '#default_value' => $instance->getSetting('handler'),
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

    $handler = \Drupal::service('plugin.manager.entity_reference.selection')->getSelectionHandler($instance);
    $form['handler']['handler_settings'] += $handler->settingsForm($instance);

    return $form;
  }

  /**
   * Form element validation handler; Stores the new values in the form state.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function instanceSettingsFormValidate(array $form, array &$form_state) {
    if (isset($form_state['values']['instance'])) {
      unset($form_state['values']['instance']['settings']['handler_submit']);
      $form_state['instance']->settings = $form_state['values']['instance']['settings'];
    }
  }

}
