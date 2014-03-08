<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldWidget\AutocompleteWidgetBase.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\user\EntityOwnerInterface;
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
    $entity = $items->getEntity();

    // Prepare the autocomplete route parameters.
    $autocomplete_route_parameters = array(
      'type' => $this->getSetting('autocomplete_type'),
      'field_name' => $this->fieldDefinition->getName(),
      'entity_type' => $entity->getEntityTypeId(),
      'bundle_name' => $entity->bundle(),
    );

    if ($entity_id = $entity->id()) {
      $autocomplete_route_parameters['entity_id'] = $entity_id;
    }

    $element += array(
      '#type' => 'textfield',
      '#maxlength' => 1024,
      '#default_value' => implode(', ', $this->getLabels($items, $delta)),
      '#autocomplete_route_name' => 'entity_reference.autocomplete',
      '#autocomplete_route_parameters' => $autocomplete_route_parameters,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#element_validate' => array(array($this, 'elementValidate')),
      '#autocreate_uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id(),
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
  protected function getLabels(FieldItemListInterface $items, $delta) {
    if ($items->isEmpty()) {
      return array();
    }

    $entity_labels = array();

    // Load those entities and loop through them to extract their labels.
    $entities = entity_load_multiple($this->getFieldSetting('target_type'), $this->getEntityIds($items, $delta));

    foreach ($entities as $entity_id => $entity_item) {
      $label = $entity_item->label();
      $key = "$label ($entity_id)";
      // Labels containing commas or quotes must be wrapped in quotes.
      $key = Tags::encode($key);
      $entity_labels[] = $key;
    }
    return $entity_labels;
  }

  /**
   * Builds an array of entity IDs for which to get the entity labels.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of a field item in the array of subelements (0, 1, 2, etc).
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds(FieldItemListInterface $items, $delta) {
    $entity_ids = array();

    foreach ($items as $item) {
      $entity_ids[] = $item->target_id;
    }

    return $entity_ids;
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

    $entity_type = $entity_manager->getDefinition($target_type);
    $bundle_key = $entity_type->getKey('bundle');
    $label_key = $entity_type->getKey('label');

    $entity = $entity_manager->getStorageController($target_type)->create(array(
      $label_key => $label,
      $bundle_key => $bundle,
    ));

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
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
    return $target_type_info->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface');
  }

}
