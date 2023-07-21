<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Base class for config entity types with settings for form and view modes.
 */
abstract class EntityDisplayModeBase extends ConfigEntityBase implements EntityDisplayModeInterface {

  /**
   * The ID of the form or view mode.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the form or view mode.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of the form or view mode.
   *
   * @var string|null
   */
  protected ?string $description;

  /**
   * The entity type this form or view mode is used for.
   *
   * This is not to be confused with EntityDisplayModeBase::$entityType which is
   * inherited from Entity::$entityType.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * Whether or not this form or view mode has custom settings by default.
   *
   * If FALSE, entities displayed in this mode will reuse the 'default' display
   * settings by default (e.g. right after the module exposing the form or view
   * mode is enabled), but administrators can later use the Field UI to apply
   * custom display settings specific to the form or view mode.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Whether or not the rendered output of this view mode is cached by default.
   *
   * @var bool
   */
  protected $cache = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    /** @var \Drupal\Core\Entity\EntityDisplayModeInterface $a */
    /** @var \Drupal\Core\Entity\EntityDisplayModeInterface $b */
    // Sort by the type of entity the view mode is used for.
    $a_type = $a->getTargetType();
    $b_type = $b->getTargetType();
    $type_order = strnatcasecmp($a_type, $b_type);
    return $type_order != 0 ? $type_order : parent::sort($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType() {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetType($target_entity_type) {
    $this->targetEntityType = $target_entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $target_entity_type = \Drupal::entityTypeManager()->getDefinition($this->targetEntityType);
    $this->addDependency('module', $target_entity_type->getProvider());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    if ($rel === 'add-form') {
      $uri_route_parameters['entity_type_id'] = $this->getTargetType();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description ?? '';
  }

}
