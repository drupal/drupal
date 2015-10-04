<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigNamesMapper.
 */

namespace Drupal\config_translation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\locale\LocaleConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Configuration mapper base implementation.
 */
class ConfigNamesMapper extends PluginBase implements ConfigMapperInterface, ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\locale\LocaleConfigManager
   */
  protected $localeConfigManager;

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The base route object that the mapper is attached to.
   *
   * @return \Symfony\Component\Routing\Route
   */
  protected $baseRoute;

  /**
   * The available routes.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * The language code of the language this mapper, if any.
   *
   * @var string|null
   */
  protected $langcode = NULL;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a ConfigNamesMapper.
   *
   * @param $plugin_id
   *   The config mapper plugin ID.
   * @param mixed $plugin_definition
   *   An array of plugin information with the following keys:
   *   - title: The title of the mapper, used for generating page titles.
   *   - base_route_name: The route name of the base route this mapper is
   *     attached to.
   *   - names: (optional) An array of configuration names.
   *   - weight: (optional) The weight of this mapper, used in mapper listings.
   *     Defaults to 20.
   *   - list_controller: (optional) Class name for list controller used to
   *     generate lists of this type of configuration.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\locale\LocaleConfigManager $locale_config_manager
   *   The locale configuration manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The mapper plugin discovery service.
   * @param \Drupal\Core\Routing\RouteProviderInterface
   *   The route provider.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Throws an exception if the route specified by the 'base_route_name' in
   *   the plugin definition could not be found by the route provider.
   */
  public function __construct($plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config, LocaleConfigManager $locale_config_manager, ConfigMapperManagerInterface $config_mapper_manager, RouteProviderInterface $route_provider, TranslationInterface $string_translation, LanguageManagerInterface $language_manager) {
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->routeProvider = $route_provider;

    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config;
    $this->localeConfigManager = $locale_config_manager;
    $this->configMapperManager = $config_mapper_manager;

    $this->stringTranslation = $string_translation;
    $this->languageManager = $language_manager;
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
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setRouteCollection(RouteCollection $collection) {
    $this->routeCollection = $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // A title from a *.config_translation.yml. Should be translated for
    // display in the current page language.
    return $this->t($this->pluginDefinition['title']);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteName() {
    return $this->pluginDefinition['base_route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteParameters() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRoute() {
    if ($this->routeCollection) {
      return $this->routeCollection->get($this->getBaseRouteName());
    }
    else {
      return $this->routeProvider->getRouteByName($this->getBaseRouteName());
    }
  }

  /**
   * Allows to process all config translation routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object to process.
   */
  protected function processRoute(Route $route) {
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath() {
    return Url::fromRoute($this->getBaseRouteName(), $this->getBaseRouteParameters())->getInternalPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewRouteName() {
    return 'config_translation.item.overview.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewRouteParameters() {
    return $this->getBaseRouteParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewRoute() {
    $route = new Route(
      $this->getBaseRoute()->getPath() . '/translate',
      array(
        '_controller' => '\Drupal\config_translation\Controller\ConfigTranslationController::itemPage',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_overview_access' => 'TRUE')
    );
    $this->processRoute($route);
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewPath() {
    return Url::fromRoute($this->getOverviewRouteName(), $this->getOverviewRouteParameters())->getInternalPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddRouteName() {
    return 'config_translation.item.add.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddRouteParameters() {
    // If sub-classes provide route parameters in getBaseRouteParameters(), they
    // probably also want to provide those for the add, edit, and delete forms.
    $parameters = $this->getBaseRouteParameters();
    $parameters['langcode'] = $this->langcode;
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddRoute() {
    $route = new Route(
      $this->getBaseRoute()->getPath() . '/translate/{langcode}/add',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationAddForm',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_form_access' => 'TRUE')
    );
    $this->processRoute($route);
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRouteName() {
    return 'config_translation.item.edit.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRouteParameters() {
    return $this->getAddRouteParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    $route = new Route(
      $this->getBaseRoute()->getPath() . '/translate/{langcode}/edit',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationEditForm',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_form_access' => 'TRUE')
    );
    $this->processRoute($route);
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRouteName() {
    return 'config_translation.item.delete.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRouteParameters() {
    return $this->getAddRouteParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    $route = new Route(
      $this->getBaseRoute()->getPath() . '/translate/{langcode}/delete',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationDeleteForm',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_form_access' => 'TRUE')
    );
    $this->processRoute($route);
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigNames() {
    return $this->pluginDefinition['names'];
  }

  /**
   * {@inheritdoc}
   */
  public function addConfigName($name) {
    $this->pluginDefinition['names'][] = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function populateFromRouteMatch(RouteMatchInterface $route_match) {
    $this->langcode = $route_match->getParameter('langcode');
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    return $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    $config_factory = $this->configFactory;
    $langcodes = array_map(function($name) use ($config_factory) {
      // Default to English if no language code was provided in the file.
      // Although it is a best practice to include a language code, if the
      // developer did not think about a multilingual use-case, we fall back
      // on assuming the file is English.
      return $config_factory->get($name)->get('langcode') ?: 'en';
    }, $this->getConfigNames());

    if (count(array_unique($langcodes)) > 1) {
      throw new \RuntimeException('A config mapper can only contain configuration for a single language.');
    }

    return reset($langcodes);
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigData() {
    $config_data = array();
    foreach ($this->getConfigNames() as $name) {
      $config_data[$name] = $this->configFactory->getEditable($name)->get();
    }
    return $config_data;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSchema() {
    foreach ($this->getConfigNames() as $name) {
      if (!$this->typedConfigManager->hasConfigSchema($name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslatable() {
    foreach ($this->getConfigNames() as $name) {
      if ($this->configMapperManager->hasTranslatable($name)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation(LanguageInterface $language) {
    foreach ($this->getConfigNames() as $name) {
      if ($this->localeConfigManager->hasTranslation($name, $language->getId())) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    return $this->t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return array(
      'translate' => array(
        'title' => $this->t('Translate'),
        'url' => Url::fromRoute($this->getOverviewRouteName(), $this->getOverviewRouteParameters()),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getContextualLinkGroup() {
    return NULL;
  }

}
