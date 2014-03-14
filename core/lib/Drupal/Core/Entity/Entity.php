<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Entity.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a base entity class.
 */
abstract class Entity implements EntityInterface {

  /**
   * The language code of the entity's default language.
   *
   * @var string
   */
  public $langcode = Language::LANGCODE_NOT_SPECIFIED;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Boolean indicating whether the entity should be forced to be new.
   *
   * @var bool
   */
  protected $enforceIsNew;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs an Entity object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   * @param string $entity_type
   *   The type of the entity to create.
   */
  public function __construct(array $values, $entity_type) {
    $this->entityTypeId = $entity_type;
    // Set initial values.
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return isset($this->uuid) ? $this->uuid : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || !$this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function enforceIsNew($value = TRUE) {
    $this->enforceIsNew = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = NULL;
    $entity_type = $this->getEntityType();
    // @todo Convert to is_callable() and call_user_func().
    if (($label_callback = $entity_type->getLabelCallback()) && function_exists($label_callback)) {
      $label = $label_callback($this);
    }
    elseif (($label_key = $entity_type->getKey('label')) && isset($this->{$label_key})) {
      $label = $this->{$label_key};
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'canonical') {
    if ($this->isNew()) {
      throw new EntityMalformedException(sprintf('The "%s" entity type has not been saved, and cannot have a URI.', $this->getEntityTypeId()));
    }

    // The links array might contain URI templates set in annotations.
    $link_templates = $this->linkTemplates();

    if (isset($link_templates[$rel])) {
      // If there is a template for the given relationship type, generate the path.
      $uri['route_name'] = $link_templates[$rel];
      $uri['route_parameters'] = $this->urlRouteParameters($rel);
    }
    else {
      $bundle = $this->bundle();
      // A bundle-specific callback takes precedence over the generic one for
      // the entity type.
      $bundles = \Drupal::entityManager()->getBundleInfo($this->getEntityTypeId());
      if (isset($bundles[$bundle]['uri_callback'])) {
        $uri_callback = $bundles[$bundle]['uri_callback'];
      }
      elseif ($entity_uri_callback = $this->getEntityType()->getUriCallback()) {
        $uri_callback = $entity_uri_callback;
      }

      // Invoke the callback to get the URI. If there is no callback, use the
      // default URI format.
      // @todo Convert to is_callable() and call_user_func().
      if (isset($uri_callback) && function_exists($uri_callback)) {
        $uri = $uri_callback($this);
      }
      else {
        return array();
      }
    }

    // Pass the entity data to url() so that alter functions do not need to
    // look up this entity again.
    $uri['options']['entity_type'] = $this->getEntityTypeId();
    $uri['options']['entity'] = $this;

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemPath($rel = 'canonical') {
    if ($uri = $this->urlInfo($rel)) {
      return $this->urlGenerator()->getPathFromRoute($uri['route_name'], $uri['route_parameters']);
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($rel) {
    $link_templates = $this->linkTemplates();
    return isset($link_templates[$rel]);
  }

  /**
   * Returns an array link templates.
   *
   * @return array
   *   An array of link templates containing route names.
   */
  protected function linkTemplates() {
    return $this->getEntityType()->getLinkTemplates();
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', $options = array()) {
    // While self::urlInfo() will throw an exception if the entity is new,
    // the expected result for a URL is always a string.
    if ($this->isNew() || !$uri = $this->urlInfo($rel)) {
      return '';
    }

    $options += $uri['options'];
    return $this->urlGenerator()->generateFromRoute($uri['route_name'], $uri['route_parameters'], $options);
  }

  /**
   * Returns an array of placeholders for this entity.
   *
   * Individual entity classes may override this method to add additional
   * placeholders if desired. If so, they should be sure to replicate the
   * property caching logic.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return array
   *   An array of URI placeholders.
   */
  protected function urlRouteParameters($rel) {
    // The entity ID is needed as a route parameter.
    $uri_route_parameters[$this->getEntityTypeId()] = $this->id();

    // The 'admin-form' link requires the bundle as a route parameter if the
    // entity type uses bundles.
    if ($rel == 'admin-form' && $this->getEntityType()->getBundleEntityType() != 'bundle') {
      $uri_route_parameters[$this->getEntityType()->getBundleEntityType()] = $this->bundle();
    }
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   *
   * Returns a list of URI relationships supported by this entity.
   *
   * @return array
   *   An array of link relationships supported by this entity.
   */
  public function uriRelationships() {
    return array_keys($this->linkTemplates());
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'create') {
      return \Drupal::entityManager()
        ->getAccessController($this->entityTypeId)
        ->createAccess($this->bundle(), $account);
    }
    return \Drupal::entityManager()
      ->getAccessController($this->entityTypeId)
      ->access($this, $operation, Language::LANGCODE_DEFAULT, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    $language = language_load($this->langcode);
    if (!$language) {
      // Make sure we return a proper language object.
      $language = new Language(array('id' => Language::LANGCODE_NOT_SPECIFIED));
    }
    return $language;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return \Drupal::entityManager()->getStorageController($this->entityTypeId)->save($this);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->isNew()) {
      \Drupal::entityManager()->getStorageController($this->entityTypeId)->delete(array($this->id() => $this));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $entity_type = $this->getEntityType();
    $duplicate->{$entity_type->getKey('id')} = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if ($entity_type->hasKey('uuid')) {
      // @todo Inject the UUID service into the Entity class once possible.
      $duplicate->{$entity_type->getKey('uuid')} = \Drupal::service('uuid')->generate();
    }
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return \Drupal::entityManager()->getDefinition($this->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    $this->onSaveOrDelete();
    if ($update) {
      $this->onUpdateBundleEntity();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageControllerInterface $storage_controller) {
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    foreach ($entities as $entity) {
      $entity->onSaveOrDelete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities) {
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    return array();
  }

  /**
   * Acts on an entity after it was saved or deleted.
   */
  protected function onSaveOrDelete() {
    $referenced_entities = array(
      $this->getEntityTypeId() => array($this->id() => $this),
    );

    foreach ($this->referencedEntities() as $referenced_entity) {
      $referenced_entities[$referenced_entity->getEntityTypeId()][$referenced_entity->id()] = $referenced_entity;
    }

    foreach ($referenced_entities as $entity_type => $entities) {
      if (\Drupal::entityManager()->hasController($entity_type, 'view_builder')) {
        \Drupal::entityManager()->getViewBuilder($entity_type)->resetCache($entities);
      }
    }
  }

  /**
   * Acts on entities of which this entity is a bundle entity type.
   */
  protected function onUpdateBundleEntity() {
    // If this entity is a bundle entity type of another entity type, and we're
    // updating an existing entity, and that other entity type has a view
    // builder class, then invalidate the render cache of entities for which
    // this entity is a bundle.
    $bundle_of = $this->getEntityType()->getBundleOf();
    $entity_manager = \Drupal::entityManager();
    if ($bundle_of !== FALSE && $entity_manager->hasController($bundle_of, 'view_builder')) {
      $entity_manager->getViewBuilder($bundle_of)->resetCache();
    }
  }

  /**
   * Wraps the URL generator.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   */
  protected function urlGenerator() {
    if (!$this->urlGenerator) {
      $this->urlGenerator = \Drupal::urlGenerator();
    }
    return $this->urlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Don't serialize the url generator.
    $this->urlGenerator = NULL;

    return array_keys(get_object_vars($this));
  }

}
