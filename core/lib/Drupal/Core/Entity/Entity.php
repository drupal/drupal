<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Entity.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

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
  protected $entityType;

  /**
   * Boolean indicating whether the entity should be forced to be new.
   *
   * @var bool
   */
  protected $enforceIsNew;

  /**
   * The route provider service.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

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
    $this->entityType = $entity_type;
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
  public function entityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    $label = NULL;
    $entity_info = $this->entityInfo();
    if (isset($entity_info['label_callback']) && function_exists($entity_info['label_callback'])) {
      $label = $entity_info['label_callback']($this, $langcode);
    }
    elseif (!empty($entity_info['entity_keys']['label']) && isset($this->{$entity_info['entity_keys']['label']})) {
      $label = $this->{$entity_info['entity_keys']['label']};
    }
    return $label;
  }

  /**
   * Returns the URI elements of the entity.
   *
   * URI templates might be set in the links array in an annotation, for
   * example:
   * @code
   * links = {
   *   "canonical" = "/node/{node}",
   *   "edit-form" = "/node/{node}/edit",
   *   "version-history" = "/node/{node}/revisions"
   * }
   * @endcode
   * or specified in a callback function set like:
   * @code
   * uri_callback = "contact_category_uri",
   * @endcode
   * If the path is not set in the links array, the uri_callback function is
   * used for setting the path. If this does not exist and the link relationship
   * type is canonical, the path is set using the default template:
   * entity/entityType/id.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return array
   *   An array containing the 'path' and 'options' keys used to build the URI
   *   of the entity, and matching the signature of url().
   */
  public function uri($rel = 'canonical') {
    $entity_info = $this->entityInfo();

    // The links array might contain URI templates set in annotations.
    $link_templates = isset($entity_info['links']) ? $entity_info['links'] : array();

    $template = NULL;
    if (isset($link_templates[$rel])) {
      try {
        $template = $this->routeProvider()->getRouteByName($link_templates[$rel])->getPath();
      }
      catch (RouteNotFoundException $e) {
        // Fall back to a non-template-based URI.
      }
    }
    if ($template) {
      // If there is a template for the given relationship type, do the
      // placeholder replacement and use that as the path.
      $replacements = $this->uriPlaceholderReplacements();
      $uri['path'] = str_replace(array_keys($replacements), array_values($replacements), $template);

      // @todo Remove this once http://drupal.org/node/1888424 is in and we can
      //   move the BC handling of / vs. no-/ to the generator.
      $uri['path'] = trim($uri['path'], '/');

      // Pass the entity data to url() so that alter functions do not need to
      // look up this entity again.
      $uri['options']['entity_type'] = $this->entityType;
      $uri['options']['entity'] = $this;
      return $uri;
    }

    $bundle = $this->bundle();
    // A bundle-specific callback takes precedence over the generic one for
    // the entity type.
    $bundles = entity_get_bundles($this->entityType);
    if (isset($bundles[$bundle]['uri_callback'])) {
      $uri_callback = $bundles[$bundle]['uri_callback'];
    }
    elseif (isset($entity_info['uri_callback'])) {
      $uri_callback = $entity_info['uri_callback'];
    }

    // Invoke the callback to get the URI. If there is no callback, use the
    // default URI format.
    if (isset($uri_callback) && function_exists($uri_callback)) {
      $uri = $uri_callback($this);
    }
    // Only use these defaults for a canonical link (that is, a link to self).
    // Other relationship types are not supported by this logic.
    elseif ($rel == 'canonical') {
      $uri = array(
        'path' => 'entity/' . $this->entityType . '/' . $this->id(),
      );
    }
    else {
      return array();
    }

    // Pass the entity data to url() so that alter functions do not need to
    // look up this entity again.
    $uri['options']['entity'] = $this;
    return $uri;
  }

  /**
   * Returns an array of placeholders for this entity.
   *
   * Individual entity classes may override this method to add additional
   * placeholders if desired. If so, they should be sure to replicate the
   * property caching logic.
   *
   * @return array
   *   An array of URI placeholders.
   */
  protected function uriPlaceholderReplacements() {
    if (empty($this->uriPlaceholderReplacements)) {
      $this->uriPlaceholderReplacements = array(
        '{entityType}' => $this->entityType(),
        '{bundle}' => $this->bundle(),
        '{id}' => $this->id(),
        '{uuid}' => $this->uuid(),
        '{' . $this->entityType() . '}' => $this->id(),
      );
    }
    return $this->uriPlaceholderReplacements;
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
    $entity_info = $this->entityInfo();
    return isset($entity_info['links']) ? array_keys($entity_info['links']) : array();
  }


  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'create') {
      return \Drupal::entityManager()
        ->getAccessController($this->entityType)
        ->createAccess($this->bundle(), $account);
    }
    return \Drupal::entityManager()
      ->getAccessController($this->entityType)
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
    return \Drupal::entityManager()->getStorageController($this->entityType)->save($this);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->isNew()) {
      \Drupal::entityManager()->getStorageController($this->entityType)->delete(array($this->id() => $this));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $entity_info = $this->entityInfo();
    $duplicate->{$entity_info['entity_keys']['id']} = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if (!empty($entity_info['entity_keys']['uuid'])) {
      // @todo Inject the UUID service into the Entity class once possible.
      $duplicate->{$entity_info['entity_keys']['uuid']} = \Drupal::service('uuid')->generate();
    }
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function entityInfo() {
    return \Drupal::entityManager()->getDefinition($this->entityType());
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
    $this->changed();
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
      $entity->changed();
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
   * {@inheritdoc}
   */
  public function changed() {
    $referenced_entities = array(
      $this->entityType() => array($this->id() => $this),
    );

    foreach ($this->referencedEntities() as $referenced_entity) {
      $referenced_entities[$referenced_entity->entityType()][$referenced_entity->id()] = $referenced_entity;
    }

    foreach ($referenced_entities as $entity_type => $entities) {
      if (\Drupal::entityManager()->hasController($entity_type, 'view_builder')) {
        \Drupal::entityManager()->getViewBuilder($entity_type)->resetCache($entities);
      }
    }
  }

  /**
   * Wraps the route provider service.
   *
   * @return \Drupal\Core\Routing\RouteProviderInterface
   *   The route provider.
   */
  protected function routeProvider() {
    if (!$this->routeProvider) {
      $this->routeProvider = \Drupal::service('router.route_provider');
    }
    return $this->routeProvider;
  }

}
