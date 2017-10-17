<?php

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'path' entity field type.
 *
 * @FieldType(
 *   id = "path",
 *   label = @Translation("Path"),
 *   description = @Translation("An entity field containing a path alias and related data."),
 *   no_ui = TRUE,
 *   default_widget = "path",
 *   list_class = "\Drupal\path\Plugin\Field\FieldType\PathFieldItemList",
 *   constraints = {"PathAlias" = {}},
 * )
 */
class PathItem extends FieldItemBase {

  /**
   * Whether the alias has been loaded from the alias storage service yet.
   *
   * @var bool
   */
  protected $isLoaded = FALSE;

  /**
   * Whether the alias is currently being set.
   *
   * @var bool
   */
  protected $isLoading = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['alias'] = DataDefinition::create('string')
      ->setLabel(t('Path alias'));
    $properties['pid'] = DataDefinition::create('integer')
      ->setLabel(t('Path id'));
    $properties['langcode'] = DataDefinition::create('string')
      ->setLabel(t('Language Code'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    $this->ensureLoaded();
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureLoaded();
    return parent::getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureLoaded();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensureLoaded();
    return parent::getIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->alias = trim($this->alias);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    // Also ensure that existing values are loaded when setting a value, this
    // ensures that it is possible to set a new value immediately after loading
    // an entity.
    $this->ensureLoaded();
    parent::__set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    // Also ensure that existing values are loaded when setting a value, this
    // ensures that it is possible to set a new value immediately after loading
    // an entity.
    $this->ensureLoaded();
    return parent::set($property_name, $value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    $this->ensureLoaded();
    return parent::get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Also ensure that existing values are loaded when setting a value, this
    // ensures that it is possible to set a new value immediately after loading
    // an entity.
    $this->ensureLoaded();
    return parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    if (!$update) {
      if ($this->alias) {
        $entity = $this->getEntity();
        if ($path = \Drupal::service('path.alias_storage')->save('/' . $entity->urlInfo()->getInternalPath(), $this->alias, $this->getLangcode())) {
          $this->pid = $path['pid'];
        }
      }
    }
    else {
      // Delete old alias if user erased it.
      if ($this->pid && !$this->alias) {
        \Drupal::service('path.alias_storage')->delete(['pid' => $this->pid]);
      }
      // Only save a non-empty alias.
      elseif ($this->alias) {
        $entity = $this->getEntity();
        \Drupal::service('path.alias_storage')->save('/' . $entity->urlInfo()->getInternalPath(), $this->alias, $this->getLangcode(), $this->pid);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['alias'] = str_replace(' ', '-', strtolower($random->sentences(3)));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'alias';
  }

  /**
   * Ensures the alias properties are loaded if available.
   *
   * This ensures that the properties will always be loaded and act like
   * non-computed fields when calling ::__get() and getValue().
   *
   * @todo: Determine if this should be moved to the base class in
   *   https://www.drupal.org/node/2392845.
   */
  protected function ensureLoaded() {
    // Make sure to avoid a infinite loop if setValue() has be called from this
    // block which calls ensureLoaded().
    if (!$this->isLoaded && !$this->isLoading) {
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        // @todo Support loading languge neutral aliases in
        //   https://www.drupal.org/node/2511968.
        $alias = \Drupal::service('path.alias_storage')->load([
          'source' => '/' . $entity->toUrl()->getInternalPath(),
          'langcode' => $this->getLangcode(),
        ]);
        if ($alias) {
          $this->isLoading = TRUE;
          $this->setValue($alias);
          $this->isLoading = FALSE;
        }
        else {
          // If there is no existing alias, default the langcode to the current
          // language.
          // @todo Set the langcode to not specified for untranslatable fields
          //   in https://www.drupal.org/node/2689459.
          $this->langcode = $this->getLangcode();
        }
      }
      $this->isLoaded = TRUE;
    }
  }

}
