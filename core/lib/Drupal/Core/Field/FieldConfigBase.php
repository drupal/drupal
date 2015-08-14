<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldConfigBase.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;

/**
 * Base class for configurable field definitions.
 */
abstract class FieldConfigBase extends ConfigEntityBase implements FieldConfigInterface {

  /**
   * The field ID.
   *
   * The ID consists of 3 parts: the entity type, bundle and the field name.
   *
   * Example: node.article.body, user.user.field_main_image.
   *
   * @var string
   */
  protected $id;

  /**
   * The field name.
   *
   * @var string
   */
  protected $field_name;

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
  protected $field_type;

  /**
   * The name of the entity type the field is attached to.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The name of the bundle the field is attached to.
   *
   * @var string
   */
  protected $bundle;

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
  protected $label;

  /**
   * The field description.
   *
   * A human-readable description for the field when used with this bundle.
   * For example, the description will be the help text of Form API elements for
   * this field in entity edit forms.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Field-type specific settings.
   *
   * An array of key/value pairs. The keys and default values are defined by the
   * field type.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * Flag indicating whether the field is required.
   *
   * TRUE if a value for this field is required when used with this bundle,
   * FALSE otherwise. Currently, required-ness is only enforced at the Form API
   * level in entity edit forms, not during direct API saves.
   *
   * @var bool
   */
  protected $required = FALSE;

  /**
   * Flag indicating whether the field is translatable.
   *
   * Defaults to TRUE.
   *
   * @var bool
   */
  protected $translatable = TRUE;

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
   * This property is overlooked if the $default_value_callback is non-empty.
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
   * - \Drupal\Core\Entity\FieldableEntityInterface $entity
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
  protected $default_value_callback = '';

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
   * Array of constraint options keyed by constraint plugin ID.
   *
   * @var array
   */
  protected $constraints = [];

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
    // Add dependencies from the field type plugin. We can not use
    // self::calculatePluginDependencies() because instantiation of a field item
    // plugin requires a parent entity.
    /** @var $field_type_manager \Drupal\Core\Field\FieldTypePluginManagerInterface */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $definition = $field_type_manager->getDefinition($this->getType());
    $this->addDependency('module', $definition['provider']);
    // Plugins can declare additional dependencies in their definition.
    if (isset($definition['config_dependencies'])) {
      $this->addDependencies($definition['config_dependencies']);
    }
    // Let the field type plugin specify its own dependencies.
    // @see \Drupal\Core\Field\FieldItemInterface::calculateDependencies()
    $this->addDependencies($definition['class']::calculateDependencies($this));

    // Create dependency on the bundle.
    $bundle_config_dependency = $this->entityManager()->getDefinition($this->entity_type)->getBundleConfigDependency($this->bundle);
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $definition = $field_type_manager->getDefinition($this->getType());
    $changed = $definition['class']::onDependencyRemoval($this, $dependencies);
    return $changed;
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
  public function getLabel() {
    return $this->label();
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
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
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
  public function getSettings() {
    return $this->settings + $this->getFieldStorageDefinition()->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
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
  public function setSetting($setting_name, $value) {
    $this->settings[$setting_name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * [@inheritdoc}
   */
  public function setRequired($required) {
    $this->required = $required;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue(FieldableEntityInterface $entity) {
    // Allow custom default values function.
    if ($callback = $this->default_value_callback) {
      $value = call_user_func($callback, $entity, $this);
    }
    else {
      $value = $this->default_value;
    }
    // Normalize into the "array keyed by delta" format.
    if (isset($value) && !is_array($value)) {
      $properties = $this->getFieldStorageDefinition()->getPropertyNames();
      $property = reset($properties);
      $value = array(
        array($property => $value),
      );
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
   * @todo Investigate in https://www.drupal.org/node/2074253.
   */
  public function __sleep() {
    // Only serialize necessary properties, excluding those that can be
    // recalculated.
    $properties = get_object_vars($this);
    unset($properties['fieldStorage'], $properties['itemDefinition'], $properties['bundleRenameAllowed'], $properties['original']);
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

  /**
   * {@inheritdoc}
   */
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
    return \Drupal::typedDataManager()->getDefaultConstraints($this) + $this->constraints;
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

  /**
   * {@inheritdoc}
   */
  public function setConstraints(array $constraints) {
    $this->constraints = $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function addConstraint($constraint_name, $options = NULL) {
    $this->constraints[$constraint_name] = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropertyConstraints($name, array $constraints) {
    $item_constraints = $this->getItemDefinition()->getConstraints();
    $item_constraints['ComplexData'][$name] = $constraints;
    $this->getItemDefinition()->setConstraints($item_constraints);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addPropertyConstraints($name, array $constraints) {
    $item_constraints = $this->getItemDefinition()->getConstraint('ComplexData') ?: [];
    if (isset($item_constraints[$name])) {
      // Add the new property constraints, overwriting as required.
      $item_constraints[$name] = $constraints + $item_constraints[$name];
    }
    else {
      $item_constraints[$name] = $constraints;
    }
    $this->getItemDefinition()->addConstraint('ComplexData', $item_constraints);
    return $this;
  }

}
