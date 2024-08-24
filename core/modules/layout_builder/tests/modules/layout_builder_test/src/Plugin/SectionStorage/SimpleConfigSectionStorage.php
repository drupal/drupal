<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Plugin\SectionStorage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\layout_builder\Attribute\SectionStorage;
use Drupal\layout_builder\Plugin\SectionStorage\SectionStorageLocalTaskProviderInterface;
use Drupal\layout_builder\Routing\LayoutBuilderRoutesTrait;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionListTrait;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides section storage utilizing simple config.
 */
#[SectionStorage(id: "test_simple_config", context_definitions: [
  "config_id" => new ContextDefinition(
    data_type: "string",
    label: new TranslatableMarkup("Configuration ID"),
  ),
])]
class SimpleConfigSectionStorage extends PluginBase implements SectionStorageInterface, SectionStorageLocalTaskProviderInterface, ContainerFactoryPluginInterface {

  use ContextAwarePluginTrait;
  use LayoutBuilderRoutesTrait;
  use SectionListTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An array of sections.
   *
   * @var \Drupal\layout_builder\Section[]|null
   */
  protected $sections;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageId() {
    return $this->getContextValue('config_id');
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getStorageId();
  }

  /**
   * Returns the name to be used to store in the config system.
   */
  protected function getConfigName(): string {
    return 'layout_builder_test.' . $this->getStorageType() . '.' . $this->getStorageId();
  }

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    if (is_null($this->sections)) {
      $sections = $this->configFactory->get($this->getConfigName())->get('sections') ?: [];
      $this->setSections(array_map([Section::class, 'fromArray'], $sections));
    }
    return $this->sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function setSections(array $sections) {
    $this->sections = array_values($sections);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $sections = array_map(function (Section $section) {
      return $section->toArray();
    }, $this->getSections());

    $config = $this->configFactory->getEditable($this->getConfigName());
    $return = $config->get('sections') ? SAVED_UPDATED : SAVED_NEW;
    $config->set('sections', $sections)->save();
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRoutes(RouteCollection $collection) {
    $this->buildLayoutRoutes($collection, $this->getPluginDefinition(), 'layout-builder-test-simple-config/{id}');
  }

  /**
   * {@inheritdoc}
   */
  public function deriveContextsFromRoute($value, $definition, $name, array $defaults) {
    $contexts['config_id'] = new Context(new ContextDefinition('string'), $value ?: $defaults['id']);
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLocalTasks($base_plugin_definition) {
    $type = $this->getStorageType();
    $local_tasks = [];
    $local_tasks["layout_builder.$type.view"] = $base_plugin_definition + [
      'route_name' => "layout_builder.$type.view",
      'title' => $this->t('Layout'),
      'base_route' => "layout_builder.$type.view",
    ];
    $local_tasks["layout_builder.$type.view__child"] = $base_plugin_definition + [
      'route_name' => "layout_builder.$type.view",
      'title' => $this->t('Layout'),
      'parent_id' => "layout_builder_ui:layout_builder.$type.view",
    ];
    $local_tasks["layout_builder.$type.discard_changes"] = $base_plugin_definition + [
      'route_name' => "layout_builder.$type.discard_changes",
      'title' => $this->t('Discard changes'),
      'parent_id' => "layout_builder_ui:layout_builder.$type.view",
      'weight' => 5,
    ];
    return $local_tasks;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutBuilderUrl($rel = 'view') {
    return Url::fromRoute("layout_builder.{$this->getStorageType()}.$rel", ['id' => $this->getStorageId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return $this->getLayoutBuilderUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getContextsDuringPreview() {
    return $this->getContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(RefinableCacheableDependencyInterface $cacheability) {
    return TRUE;
  }

}
