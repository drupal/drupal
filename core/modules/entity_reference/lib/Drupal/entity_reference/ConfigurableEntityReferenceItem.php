<?php

/**
 * @file
 * Contains \Drupal\entity_reference\ConfigurableEntityReferenceItem.
 */

namespace Drupal\entity_reference;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\FieldInterface;
use Drupal\Core\Field\ConfigEntityReferenceItemBase;
use Drupal\Core\Field\ConfigFieldItemInterface;

/**
 * Alternative plugin implementation of the 'entity_reference' field type.
 *
 * Replaces the Core 'entity_reference' entity field type implementation, this
 * supports configurable fields, auto-creation of referenced entities and more.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - target_type: The entity type to reference.
 *
 * @see entity_reference_field_info_alter().
 *
 */
class ConfigurableEntityReferenceItem extends ConfigEntityReferenceItemBase implements ConfigFieldItemInterface {

  /**
   * Definitions of the contained properties.
   *
   * @see ConfigurableEntityReferenceItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $settings = $this->definition->getSettings();
    $target_type = $settings['target_type'];

    // Definitions vary by entity type and bundle, so key them accordingly.
    $key = $target_type . ':';
    $key .= isset($settings['target_bundle']) ? $settings['target_bundle'] : '';

    if (!isset(static::$propertyDefinitions[$key])) {
      // Call the parent to define the target_id and entity properties.
      parent::getPropertyDefinitions();

      // Only add the revision ID property if the target entity type supports
      // revisions.
      $target_type_info = \Drupal::entityManager()->getDefinition($target_type);
      if (!empty($target_type_info['entity_keys']['revision']) && !empty($target_type_info['revision_table'])) {
        static::$propertyDefinitions[$key]['revision_id'] = DataDefinition::create('integer')
          ->setLabel(t('Revision ID'))
          ->setConstraints(array('Range' => array('min' => 0)));
      }
    }

    return static::$propertyDefinitions[$key];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    $target_type = $field->getSetting('target_type');
    $target_type_info = \Drupal::entityManager()->getDefinition($target_type);

    if (is_subclass_of($target_type_info['class'], '\Drupal\Core\Entity\ContentEntityInterface')) {
      $columns = array(
        'target_id' => array(
          'description' => 'The ID of the target entity.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'revision_id' => array(
          'description' => 'The revision ID of the target entity.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ),
      );
    }
    else {
      $columns = array(
        'target_id' => array(
          'description' => 'The ID of the target entity.',
          'type' => 'varchar',
          'length' => '255',
        ),
      );
    }

    $schema = array(
      'columns' => $columns,
      'indexes' => array(
        'target_id' => array('target_id'),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    $element['target_type'] = array(
      '#type' => 'select',
      '#title' => t('Type of item to reference'),
      '#options' => \Drupal::entityManager()->getEntityTypeLabels(),
      '#default_value' => $this->getFieldSetting('target_type'),
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
    $selection_plugins = \Drupal::service('plugin.manager.entity_reference.selection')->getSelectionGroups($this->getFieldSetting('target_type'));
    $handler_groups = array_keys($selection_plugins);

    $handlers = \Drupal::service('plugin.manager.entity_reference.selection')->getDefinitions();
    $handlers_options = array();
    foreach ($handlers as $plugin_id => $plugin) {
      // We only display base plugins (e.g. 'default', 'views', ...) and not
      // entity type specific plugins (e.g. 'default_node', 'default_user',
      // ...).
      if (in_array($plugin_id, $handler_groups)) {
        $handlers_options[$plugin_id] = check_plain($plugin['label']);
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
