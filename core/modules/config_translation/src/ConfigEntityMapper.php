<?php

namespace Drupal\config_translation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\locale\LocaleConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Configuration mapper for configuration entities.
 */
class ConfigEntityMapper extends ConfigNamesMapper {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Configuration entity type name.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Loaded entity instance to help produce the translation interface.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $entity;

  /**
   * The label for the entity type.
   *
   * @var string
   */
  protected $typeLabel;

  /**
   * Constructs a ConfigEntityMapper.
   *
   * @param string $plugin_id
   *   The config mapper plugin ID.
   * @param mixed $plugin_definition
   *   An array of plugin information as documented in
   *   ConfigNamesMapper::__construct() with the following additional keys:
   *   - entity_type: The name of the entity type this mapper belongs to.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\locale\LocaleConfigManager $locale_config_manager
   *   The locale configuration manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The mapper plugin discovery service.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translation manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct($plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config, LocaleConfigManager $locale_config_manager, ConfigMapperManagerInterface $config_mapper_manager, RouteProviderInterface $route_provider, TranslationInterface $translation_manager, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $config_factory, $typed_config, $locale_config_manager, $config_mapper_manager, $route_provider, $translation_manager, $language_manager);
    $this->setType($plugin_definition['entity_type']);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Note that we ignore the plugin $configuration because mappers have
    // nothing to configure in themselves.
    return new static (
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('locale.config_manager'),
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('router.route_provider'),
      $container->get('string_translation'),
      $container->get('entity.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function populateFromRouteMatch(RouteMatchInterface $route_match) {
    parent::populateFromRouteMatch($route_match);
    $entity = $route_match->getParameter($this->entityType);
    $this->setEntity($entity);
  }

  /**
   * Gets the entity instance for this mapper.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The configuration entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the entity instance for this mapper.
   *
   * This method can only be invoked when the concrete entity is known, that is
   * in a request for an entity translation path. After this method is called,
   * the mapper is fully populated with the proper display title and
   * configuration names to use to check permissions or display a translation
   * screen.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The configuration entity to set.
   *
   * @return bool
   *   TRUE, if the entity was set successfully; FALSE otherwise.
   */
  public function setEntity(ConfigEntityInterface $entity) {
    if (isset($this->entity)) {
      return FALSE;
    }

    $this->entity = $entity;

    // Add the list of configuration IDs belonging to this entity. We add on a
    // possibly existing list of names. This allows modules to alter the entity
    // page with more names if form altering added more configuration to an
    // entity. This is not a Drupal 8 best practice (ideally the configuration
    // would have pluggable components), but this may happen as well.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type_info */
    $entity_type_info = $this->entityManager->getDefinition($this->entityType);
    $this->addConfigName($entity_type_info->getConfigPrefix() . '.' . $entity->id());

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->entity->label() . ' ' . $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteParameters() {
    return [$this->entityType => $this->entity->id()];
  }

  /**
   * Set entity type for this mapper.
   *
   * This should be set in initialization. A mapper that knows its type but
   * not yet its names is still useful for router item and tab generation. The
   * concrete entity only turns out later with actual controller invocations,
   * when the setEntity() method is invoked before the rest of the methods are
   * used.
   *
   * @param string $entity_type
   *   The entity type to set.
   *
   * @return bool
   *   TRUE if the entity type was set correctly; FALSE otherwise.
   */
  public function setType($entity_type) {
    if (isset($this->entityType)) {
      return FALSE;
    }
    $this->entityType = $entity_type;
    return TRUE;
  }

  /**
   * Gets the entity type from this mapper.
   *
   * @return string
   */
  public function getType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    $entity_type_info = $this->entityManager->getDefinition($this->entityType);
    return $entity_type_info->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    $entityType = $this->entityManager->getDefinition($this->entityType);
    return $entityType->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [
      'list' => [
        'title' => $this->t('List'),
        'url' => Url::fromRoute('config_translation.entity_list', [
          'mapper_id' => $this->getPluginId(),
        ]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getContextualLinkGroup() {
    // @todo Contextual groups do not map to entity types in a predictable
    //   way. See https://www.drupal.org/node/2134841 to make them predictable.
    switch ($this->entityType) {
      case 'menu':
      case 'block':
        return $this->entityType;
      case 'view':
        return 'entity.view.edit_form';
      default:
        return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewRouteName() {
    return 'entity.' . $this->entityType . '.config_translation_overview';
  }

  /**
   * {@inheritdoc}
   */
  protected function processRoute(Route $route) {
    // Add entity upcasting information.
    $parameters = $route->getOption('parameters') ?: [];
    $parameters += [
      $this->entityType => [
        'type' => 'entity:' . $this->entityType,
      ]
    ];
    $route->setOption('parameters', $parameters);
  }

}
