<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Entity.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Defines a base entity class.
 */
abstract class Entity implements EntityInterface {

  use DependencySerializationTrait {
    __sleep as traitSleep;
  }

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
   * A typed data object wrapping this entity.
   *
   * @var \Drupal\Core\TypedData\ComplexDataInterface
   */
  protected $typedData;

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
   * Gets the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   */
  protected function entityManager() {
    return \Drupal::entityManager();
  }

  /**
   * Gets the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   */
  protected function languageManager() {
    return \Drupal::languageManager();
  }

  /**
   * Gets the UUID generator.
   *
   * @return \Drupal\Component\Uuid\UuidInterface
   */
  protected function uuidGenerator() {
    return \Drupal::service('uuid');
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

    return $this;
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
    if (($label_callback = $entity_type->getLabelCallback()) && is_callable($label_callback)) {
      $label = call_user_func($label_callback, $this);
    }
    elseif (($label_key = $entity_type->getKey('label')) && isset($this->{$label_key})) {
      $label = $this->{$label_key};
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'canonical', array $options = []) {
    if ($this->id() === NULL) {
      throw new EntityMalformedException(sprintf('The "%s" entity cannot have a URI as it does have an ID', $this->getEntityTypeId()));
    }

    // The links array might contain URI templates set in annotations.
    $link_templates = $this->linkTemplates();

    // Links pointing to the current revision point to the actual entity. So
    // instead of using the 'revision' link, use the 'canonical' link.
    if ($rel === 'revision' && $this instanceof RevisionableInterface && $this->isDefaultRevision()) {
      $rel = 'canonical';
    }

    if (isset($link_templates[$rel])) {
      $route_parameters = $this->urlRouteParameters($rel);
      $route_name = "entity.{$this->entityTypeId}." . str_replace(array('-', 'drupal:'), array('_', ''), $rel);
      $uri = new Url($route_name, $route_parameters);
    }
    else {
      $bundle = $this->bundle();
      // A bundle-specific callback takes precedence over the generic one for
      // the entity type.
      $bundles = $this->entityManager()->getBundleInfo($this->getEntityTypeId());
      if (isset($bundles[$bundle]['uri_callback'])) {
        $uri_callback = $bundles[$bundle]['uri_callback'];
      }
      elseif ($entity_uri_callback = $this->getEntityType()->getUriCallback()) {
        $uri_callback = $entity_uri_callback;
      }

      // Invoke the callback to get the URI. If there is no callback, use the
      // default URI format.
      if (isset($uri_callback) && is_callable($uri_callback)) {
        $uri = call_user_func($uri_callback, $this);
      }
      else {
        throw new UndefinedLinkTemplateException(SafeMarkup::format('No link template "@rel" found for the "@entity_type" entity type', array(
          '@rel' => $rel,
          '@entity_type' => $this->getEntityTypeId(),
        )));
      }
    }

    // Pass the entity data through as options, so that alter functions do not
    // need to look up this entity again.
    $uri
      ->setOption('entity_type', $this->getEntityTypeId())
      ->setOption('entity', $this);

    // Display links by default based on the current language.
    if ($rel !== 'collection') {
      $options += ['language' => $this->language()];
    }

    $uri_options = $uri->getOptions();
    $uri_options += $options;

    return $uri->setOptions($uri_options);
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($rel) {
    $link_templates = $this->linkTemplates();
    return isset($link_templates[$rel]);
  }

  /**
   * Gets an array link templates.
   *
   * @return array
   *   An array of link templates containing paths.
   */
  protected function linkTemplates() {
    return $this->getEntityType()->getLinkTemplates();
  }

  /**
   * {@inheritdoc}
   */
  public function link($text = NULL, $rel = 'canonical', array $options = []) {
    if (is_null($text)) {
      $text = $this->label();
    }
    $url = $this->urlInfo($rel);
    $options += $url->getOptions();
    $url->setOptions($options);
    return (new Link($text, $url))->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', $options = array()) {
    // While self::urlInfo() will throw an exception if the entity is new,
    // the expected result for a URL is always a string.
    if ($this->isNew() || !$this->hasLinkTemplate($rel)) {
      return '';
    }

    $uri = $this->urlInfo($rel);
    $options += $uri->getOptions();
    $uri->setOptions($options);
    return $uri->toString();
  }

  /**
   * Gets an array of placeholders for this entity.
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
    $uri_route_parameters = [];

    if ($rel != 'collection') {
      // The entity ID is needed as a route parameter.
      $uri_route_parameters[$this->getEntityTypeId()] = $this->id();
    }
    if ($rel === 'revision') {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
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
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'create') {
      return $this->entityManager()
        ->getAccessControlHandler($this->entityTypeId)
        ->createAccess($this->bundle(), $account, [], $return_as_object);
    }
    return  $this->entityManager()
      ->getAccessControlHandler($this->entityTypeId)
      ->access($this, $operation, LanguageInterface::LANGCODE_DEFAULT, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    if ($key = $this->getEntityType()->getKey('langcode')) {
      $langcode = $this->$key;
      $language = $this->languageManager()->getLanguage($langcode);
      if ($language) {
        return $language;
      }
    }
    // Make sure we return a proper language object.
    $langcode = !empty($this->langcode) ? $this->langcode : LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $language = new Language(array('id' => $langcode));
    return $language;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return $this->entityManager()->getStorage($this->entityTypeId)->save($this);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->isNew()) {
      $this->entityManager()->getStorage($this->entityTypeId)->delete(array($this->id() => $this));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $entity_type = $this->getEntityType();
    // Reset the entity ID and indicate that this is a new entity.
    $duplicate->{$entity_type->getKey('id')} = NULL;
    $duplicate->enforceIsNew();

    // Check if the entity type supports UUIDs and generate a new one if so.
    if ($entity_type->hasKey('uuid')) {
      $duplicate->{$entity_type->getKey('uuid')} = $this->uuidGenerator()->generate();
    }
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityManager()->getDefinition($this->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Check if this is an entity bundle.
    if ($this->getEntityType()->getBundleOf()) {
      // Throw an exception if the bundle ID is longer than 32 characters.
      if (Unicode::strlen($this->id()) > EntityTypeInterface::BUNDLE_MAX_LENGTH) {
        throw new ConfigEntityIdLengthException(SafeMarkup::format(
          'Attempt to create a bundle with an ID longer than @max characters: @id.', array(
            '@max' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
            '@id' => $this->id(),
          )
        ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->invalidateTagsOnSave($update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    static::invalidateTagsOnDelete($storage->getEntityType(), $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
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
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // @todo Add bundle-specific listing cache tag?
    //   https://www.drupal.org/node/2145751
    return [$this->entityTypeId . ':' . $this->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   *
   * @return static|null
   *   The entity object or NULL if there is no entity with the given ID.
   */
  public static function load($id) {
    $entity_manager = \Drupal::entityManager();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass(get_called_class()))->load($id);
  }

  /**
   * {@inheritdoc}
   *
   * @return static[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities are found.
   */
  public static function loadMultiple(array $ids = NULL) {
    $entity_manager = \Drupal::entityManager();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass(get_called_class()))->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   *
   * @return static
   *   The entity object.
   */
  public static function create(array $values = array()) {
    $entity_manager = \Drupal::entityManager();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass(get_called_class()))->create($values);
  }

  /**
   * Invalidates an entity's cache tags upon save.
   *
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  protected function invalidateTagsOnSave($update) {
    // An entity was created or updated: invalidate its list cache tags. (An
    // updated entity may start to appear in a listing because it now meets that
    // listing's filtering requirements. A newly created entity may start to
    // appear in listings because it did not exist before.)
    $tags = $this->getEntityType()->getListCacheTags();
    if ($this->hasLinkTemplate('canonical')) {
      // Creating or updating an entity may change a cached 403 or 404 response.
      $tags = Cache::mergeTags($tags, ['4xx-response']);
    }
    if ($update) {
      // An existing entity was updated, also invalidate its unique cache tag.
      $tags = Cache::mergeTags($tags, $this->getCacheTags());
    }
    Cache::invalidateTags($tags);
  }

  /**
   * Invalidates an entity's cache tags upon delete.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    $tags = $entity_type->getListCacheTags();
    foreach ($entities as $entity) {
      // An entity was deleted: invalidate its own cache tag, but also its list
      // cache tags. (A deleted entity may cause changes in a paged list on
      // other pages than the one it's on. The one it's on is handled by its own
      // cache tag, but subsequent list pages would not be invalidated, hence we
      // must invalidate its list cache tags as well.)
      $tags = Cache::mergeTags($tags, $entity->getCacheTags());
    }
    Cache::invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalId() {
    // By default, entities do not support renames and do not have original IDs.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalId($id) {
    // By default, entities do not support renames and do not have original IDs.
    // If the specified ID is anything except NULL, this should mark this entity
    // as no longer new.
    if ($id !== NULL) {
      $this->enforceIsNew(FALSE);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedData() {
    if (!isset($this->typedData)) {
      $class = \Drupal::typedDataManager()->getDefinition('entity')['class'];
      $this->typedData = $class::createFromEntity($this);
    }
    return $this->typedData;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->typedData = NULL;
    return $this->traitSleep();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return $this->getEntityType()->getConfigDependencyKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyName() {
    return $this->getEntityTypeId() . ':' . $this->bundle() . ':' . $this->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigTarget() {
    // For content entities, use the UUID for the config target identifier.
    // This ensures that references to the target can be deployed reliably.
    return $this->uuid();
  }

}
