<?php

namespace Drupal\menu_link_content\Plugin\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the menu link plugin for content menu links.
 */
class MenuLinkContent extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * Entities IDs to load.
   *
   * It is an array of entity IDs keyed by entity IDs.
   *
   * @var array
   */
  protected static $entityIdsToLoad = array();

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = array(
    'menu_name' => 1,
    'parent' => 1,
    'weight' => 1,
    'expanded' => 1,
    'enabled' => 1,
    'title' => 1,
    'description' => 1,
    'route_name' => 1,
    'route_parameters' => 1,
    'url' => 1,
    'options' => 1,
  );

  /**
   * The menu link content entity connected to this plugin instance.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected $entity;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new MenuLinkContent.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
      $entity_id = $this->pluginDefinition['metadata']['entity_id'];
      // Builds a list of entity IDs to take advantage of the more efficient
      // EntityStorageInterface::loadMultiple() in getEntity() at render time.
      static::$entityIdsToLoad[$entity_id] = $entity_id;
    }

    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Loads the entity associated with this menu link.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The menu link content entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the entity ID and UUID are both invalid or missing.
   */
  protected function getEntity() {
    if (empty($this->entity)) {
      $entity = NULL;
      $storage = $this->entityManager->getStorage('menu_link_content');
      if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
        $entity_id = $this->pluginDefinition['metadata']['entity_id'];
        // Make sure the current ID is in the list, since each plugin empties
        // the list after calling loadMultiple(). Note that the list may include
        // multiple IDs added earlier in each plugin's constructor.
        static::$entityIdsToLoad[$entity_id] = $entity_id;
        $entities = $storage->loadMultiple(array_values(static::$entityIdsToLoad));
        $entity = isset($entities[$entity_id]) ? $entities[$entity_id] : NULL;
        static::$entityIdsToLoad = array();
      }
      if (!$entity) {
        // Fallback to the loading by the UUID.
        $uuid = $this->getUuid();
        $loaded_entities = $storage->loadByProperties(array('uuid' => $uuid));
        $entity = reset($loaded_entities);
      }
      if (!$entity) {
        throw new PluginException("Entity not found through the menu link plugin definition and could not fallback on UUID '$uuid'");
      }
      // Clone the entity object to avoid tampering with the static cache.
      $this->entity = clone $entity;
      $the_entity = $this->entityManager->getTranslationFromContext($this->entity);
      /** @var \Drupal\menu_link_content\MenuLinkContentInterface $the_entity */
      $this->entity = $the_entity;
      $this->entity->setInsidePlugin();
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // We only need to get the title from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->getTitle();
    }
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // We only need to get the description from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->getDescription();
    }
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return $this->getEntity()->urlInfo('delete-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    return $this->getEntity()->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    return $this->getEntity()->urlInfo('drupal:content-translation-overview');
  }

  /**
   * Returns the unique ID representing the menu link.
   *
   * @return string
   *   The menu link ID.
   */
  protected function getUuid() {
    $this->getDerivativeId();
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Filter the list of updates to only those that are allowed.
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $this->pluginDefinition = $overrides + $this->getPluginDefinition();
    if ($persist) {
      $entity = $this->getEntity();
      foreach ($overrides as $key => $value) {
        $entity->{$key}->value = $value;
      }
      $this->entityManager->getStorage('menu_link_content')->save($entity);
    }

    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return $this->getEntity()->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    $this->getEntity()->delete();
  }

}
