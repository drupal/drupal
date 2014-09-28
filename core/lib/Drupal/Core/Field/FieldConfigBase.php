<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldConfigBase.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ThirdPartySettingsTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;

/**
 * Base class for configurable field definitions.
 */
abstract class FieldConfigBase extends ConfigEntityBase implements FieldConfigInterface {

  use ThirdPartySettingsTrait;

  /**
   * The field ID.
   *
   * The ID consists of 3 parts: the entity type, bundle and the field name.
   *
   * Example: node.article.body, user.user.field_main_image.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the field attached to the bundle by this field.
   *
   * @var string
   */
  public $field_name;

  /**
   * The field type.
   *
   * This property is denormalized from the field storage for optimization of
   * the "entity and render cache hits" critical paths. If not present in the
   * $values passed to create(), it is populated from the field storage in
   * postCreate(), and saved in config records so that it is present on
   * subsequent loads.
   *
   * @var string
   */
  public $field_type;

  /**
   * The name of the entity type the field is attached to.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The name of the bundle the field is attached to.
   *
   * @var string
   */
  public $bundle;

  /**
   * The human-readable label for the field.
   *
   * This will be used as the title of Form API elements for the field in entity
   * edit forms, or as the label for the field values in displayed entities.
   *
   * If not specified, this defaults to the field_name (mostly useful for fields
   * created in tests).
   *
   * @var string
   */
  public $label;

  /**
   * The field description.
   *
   * A human-readable description for the field when used with this bundle.
   * For example, the description will be the help text of Form API elements for
   * this field in entity edit forms.
   *
   * @var string
   */
  public $description = '';

  /**
   * Field-type specific settings.
   *
   * An array of key/value pairs. The keys and default values are defined by the
   * field type.
   *
   * @var array
   */
  public $settings = array();

  /**
   * Flag indicating whether the field is required.
   *
   * TRUE if a value for this field is required when used with this bundle,
   * FALSE otherwise. Currently, required-ness is only enforced at the Form API
   * level in entity edit forms, not during direct API saves.
   *
   * @var bool
   */
  public $required = FALSE;

  /**
   * Flag indicating whether the field is translatable.
   *
   * Defaults to TRUE.
   *
   * @var bool
   */
  public $translatable = TRUE;

  /**
   * Default field value.
   *
   * The default value is used when an entity is created, either:
   * - through an entity creation form; the form elements for the field are
   *   prepopulated with the default value.
   * - through direct API calls (i.e. $entity->save()); the default value is
   *   added if the $entity object provides no explicit entry (actual values or
   *   "the field is empty") for the field.
   *
   * The default value is expressed as a numerically indexed array of items,
   * each item being an array of key/value pairs matching the set of 'columns'
   * defined by the "field schema" for the field type, as exposed in
   * hook_field_schema(). If the number of items exceeds the cardinality of the
   * field, extraneous items will be ignored.
   *
   * This property is overlooked if the $default_value_function is non-empty.
   *
   * Example for a integer field:
   * @code
   * array(
   *   array('value' => 1),
   *   array('value' => 2),
   * )
   * @endcode
   *
   * @var array
   */
  public $default_value = array();

  /**
   * The name of a callback function that returns default values.
   *
   * The function will be called with the following arguments:
   * - \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being created.
   * - \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   * It should return an array of default values, in the same format as the
   * $default_value property.
   *
   * This property takes precedence on the list of fixed values specified in the
   * $default_value property.
   *
   * @var string
   */
  public $default_value_function = '';

  /**
   * The field storage object.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorage;

  /**
   * The data definition of a field item.
   *
   * @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition
   */
  protected $itemDefinition;

  /**
   * Flag indicating whether the bundle name can be renamed or not.
   *
   * @var bool
   */
  protected $bundleRenameAllowed = FALSE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle . '.' . $this->field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->field_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $bundle_entity_type_id = $this->entityManager()->getDefinition($this->entity_type)->getBundleEntityType();
    if ($bundle_entity_type_id != 'bundle') {
      // If the target entity type uses entities to manage its bundles then
      // depend on the bundle entity.
      $bundle_entity = $this->entityManager()->getStorage($bundle_entity_type_id)->load($this->bundle);
      $this->addDependency('entity', $bundle_entity->getConfigDependencyName());
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);
    // If it was not present in the $values passed to create(), (e.g. for
    // programmatic creation), populate the denormalized field_type property
    // from the field storage, so that it gets saved in the config record.
    if (empty($this->field_type)) {
      $this->field_type = $this->getFieldStorageDefinition()->getType();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Clear the cache.
    $this->entityManager()->clearCachedFieldDefinitions();

    // Invalidate the render cache for all affected entities.
    $entity_type = $this->getFieldStorageDefinition()->getTargetEntityTypeId();
    if ($this->entityManager()->hasHandler($entity_type, 'view_builder')) {
      $this->entityManager()->getViewBuilder($entity_type)->resetCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings + $this->getFieldStorageDefinition()->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    if (array_key_exists($setting_name, $this->settings)) {
      return $this->settings[$setting_name];
    }
    else {
      return $this->getFieldStorageDefinition()->getSetting($setting_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // A field can be enabled for translation only if translation is supported.
    return $this->translatable && $this->getFieldStorageDefinition()->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslatable($translatable) {
    $this->translatable = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue(ContentEntityInterface $entity) {
    // Allow custom default values function.
    if ($function = $this->default_value_function) {
      $value = call_user_func($function, $entity, $this);
    }
    else {
      $value = $this->default_value;
    }
    // Allow the field type to process default values.
    $field_item_list_class = $this->getClass();
    return $field_item_list_class::processDefaultValue($value, $entity, $this);
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Using the Serialize interface and serialize() / unserialize() methods
   * breaks entity forms in PHP 5.4.
   * @todo Investigate in https://drupal.org/node/2074253.
   */
  public function __sleep() {
    // Only serialize necessary properties, excluding those that can be
    // recalculated.
    $properties = get_object_vars($this);
    unset($properties['fieldStorage'], $properties['itemDefinition'], $properties['bundleRenameAllowed']);
    return array_keys($properties);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromItemType($item_type) {
    // Forward to the field definition class for creating new data definitions
    // via the typed manager.
    return BaseFieldDefinition::createFromItemType($item_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($type) {
    // Forward to the field definition class for creating new data definitions
    // via the typed manager.
    return BaseFieldDefinition::createFromDataType($type);
  }

  public function getDataType() {
    return 'list';
  }

  /**
   * {@inheritdoc}
   */
  public function isList() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    // Derive list class from the field type.
    $type_definition = \Drupal::service('plugin.manager.field.field_type')
      ->getDefinition($this->getType());
    return $type_definition['list_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return \Drupal::typedDataManager()->getDefaultConstraints($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraint($constraint_name) {
    $constraints = $this->getConstraints();
    return isset($constraints[$constraint_name]) ? $constraints[$constraint_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefinition() {
    if (!isset($this->itemDefinition)) {
      $this->itemDefinition = FieldItemDataDefinition::create($this)
        ->setSettings($this->getSettings());
    }
    return $this->itemDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue($value) {
    if (!is_array($value)) {
      $key = $this->getFieldStorageDefinition()->getPropertyNames()[0];
      // Convert to the multi value format to support fields with a cardinality
      // greater than 1.
      $value = array(
        array($key => $value),
      );
    }
    $this->default_value = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function allowBundleRename() {
    $this->bundleRenameAllowed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($bundle) {
    return $this;
  }

}
