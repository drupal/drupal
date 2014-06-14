<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent.
 */

namespace Drupal\menu_link_content\Plugin\Menu;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Component\Plugin\Exception\PluginException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the menu link plugin for content menu link.s
 */
class MenuLinkContent extends MenuLinkBase implements ContainerFactoryPluginInterface {

  protected static $entityIdsToLoad = array();

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = array(
    'menu_name' => 1,
    'parent' => 1,
    'weight' => 1,
    'expanded' => 1,
    'hidden' => 1,
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
   * @var \Drupal\menu_link_content\Entity\MenuLinkContentInterface
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
      static::$entityIdsToLoad[$entity_id] = $entity_id;
    }

    $this->entityManager = $entity_manager;
    $this->langaugeManager = $language_manager;
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
   * @return \Drupal\menu_link_content\Entity\MenuLinkContentInterface
   *   The menu link content entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the entity ID and uuid are both invalid or missing.
   */
  protected function getEntity() {
    if (empty($this->entity)) {
      $entity = NULL;
      $storage = $this->entityManager->getStorage('menu_link_content');
      if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
        $entity_id = $this->pluginDefinition['metadata']['entity_id'];
        static::$entityIdsToLoad[$entity_id] = $entity_id;
        $entities = $storage->loadMultiple(array_values(static::$entityIdsToLoad));
        $entity = isset($entities[$entity_id]) ? $entities[$entity_id] : NULL;
        static::$entityIdsToLoad = array();
      }
      else {
        // Fallback to the loading by the uuid.
        $uuid = $this->getDerivativeId();
        $links = $storage->loadByProperties(array('uuid' => $uuid));
        $entity = reset($links);
      }
      if (!$entity) {
        throw new PluginException("Invalid entity ID or uuid");
      }
      // Clone the entity object to avoid tampering with the static cache.
      $this->entity = clone $entity;
      $this->entity = $this->entityManager->getTranslationFromContext($this->entity);
      $this->entity->setInsidePlugin();
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // We only need to get the title from the actual entity if it may be
    // a translation based on the current language context.  This can only
    // happen if the site configured to be multilingual.
    if ($this->langaugeManager->isMultilingual()) {
      return $this->getEntity()->getTitle();
    }
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if ($this->langaugeManager->isMultilingual()) {
      return $this->getEntity()->getDescription();
    }
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return array(
      'route_name' => 'menu_link_content.link_delete',
      'route_parameters' => array('menu_link_content' => $this->getEntity()->id()),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    return array(
      'route_name' => 'menu_link_content.link_edit',
      'route_parameters' => array('menu_link_content' => $this->getEntity()->id()),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    $entity_type = $this->getEntity()->getEntityType()->id();
    return array(
      'route_name' => 'content_translation.translation_overview_' . $entity_type,
      'route_parameters' => array(
        $entity_type => $this->getEntity()->id(),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
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
    // @todo: Flag this call if possible so we don't call the menu tree manager.
    $this->getEntity()->delete();
  }

}
