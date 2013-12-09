<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldWidget\AutocompleteWidgetBase.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Parent plugin for entity reference autocomplete widgets.
 */
abstract class AutocompleteWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['match_operator'] = array(
      '#type' => 'radios',
      '#title' => t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => array(
        'STARTS_WITH' => t('Starts with'),
        'CONTAINS' => t('Contains'),
      ),
      '#description' => t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
    );
    $element['size'] = array(
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#min' => 1,
      '#required' => TRUE,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Autocomplete matching: @match_operator', array('@match_operator' => $this->getSetting('match_operator')));
    $summary[] = t('Textfield size: !size', array('!size' => $this->getSetting('size')));
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    global $user;

    $entity = $items->getEntity();

    // Prepare the autocomplete route parameters.
    $autocomplete_route_parameters = array(
      'type' => $this->getSetting('autocomplete_type'),
      'field_name' => $this->fieldDefinition->getName(),
      'entity_type' => $entity->entityType(),
      'bundle_name' => $entity->bundle(),
    );

    if ($entity_id = $entity->id()) {
      $autocomplete_route_parameters['entity_id'] = $entity_id;
    }

    $element += array(
      '#type' => 'textfield',
      '#maxlength' => 1024,
      '#default_value' => implode(', ', $this->getLabels($items)),
      '#autocomplete_route_name' => 'entity_reference.autocomplete',
      '#autocomplete_route_parameters' => $autocomplete_route_parameters,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#element_validate' => array(array($this, 'elementValidate')),
      // @todo: Use wrapper to get the user if exists or needed.
      '#autocreate_uid' => isset($entity->uid) ? $entity->uid : $user->id(),
    );

    return array('target_id' => $element);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, array &$form_state) {
    return $element['target_id'];
  }

  /**
   * Validates an element.
   */
  public function elementValidate($element, &$form_state, $form) { }

  /**
   * Gets the entity labels.
   */
  protected function getLabels(FieldItemListInterface $items) {
    if ($items->isEmpty()) {
      return array();
    }

    $entity_ids = array();
    $entity_labels = array();

    // Build an array of entity IDs.
    foreach ($items as $item) {
      $entity_ids[] = $item->target_id;
    }

    // Load those entities and loop through them to extract their labels.
    $entities = entity_load_multiple($this->getFieldSetting('target_type'), $entity_ids);

    foreach ($entities as $entity_id => $entity_item) {
      $label = $entity_item->label();
      $key = "$label ($entity_id)";
      // Labels containing commas or quotes must be wrapped in quotes.
      if (strpos($key, ',') !== FALSE || strpos($key, '"') !== FALSE) {
        $key = '"' . str_replace('"', '""', $key) . '"';
      }
      $entity_labels[] = $key;
    }
    return $entity_labels;
  }

  /**
   * Creates a new entity from a label entered in the autocomplete input.
   *
   * @param string $label
   *   The entity label.
   * @param int $uid
   *   The entity uid.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createNewEntity($label, $uid) {
    $entity_manager = \Drupal::entityManager();
    $target_type = $this->getFieldSetting('target_type');
    $target_bundles = $this->getSelectionHandlerSetting('target_bundles');

    // Get the bundle.
    if (!empty($target_bundles)) {
      $bundle = reset($target_bundles);
    }
    else {
      $bundles = entity_get_bundles($target_type);
      $bundle = reset($bundles);
    }

    $entity_info = $entity_manager->getDefinition($target_type);
    $bundle_key = $entity_info['entity_keys']['bundle'];
    $label_key = $entity_info['entity_keys']['label'];

    return $entity_manager->getStorageController($target_type)->create(array(
      $label_key => $label,
      $bundle_key => $bundle,
      'uid' => $uid,
    ));
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * Checks whether a content entity is referenced.
   *
   * @return bool
   */
  protected function isContentReferenced() {
    $target_type = $this->getFieldSetting('target_type');
    $target_type_info = \Drupal::entityManager()->getDefinition($target_type);
    return is_subclass_of($target_type_info['class'], '\Drupal\Core\Entity\ContentEntityInterface');
  }

}
