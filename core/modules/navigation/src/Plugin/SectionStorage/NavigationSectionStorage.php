<?php

namespace Drupal\navigation\Plugin\SectionStorage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
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
use Drupal\navigation\Form\LayoutForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides navigation section storage.
 *
 * @internal The navigation module is experimental.
 */
#[SectionStorage(id: "navigation",
  context_definitions: [
    "navigation" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Navigation flag"),
    ),
  ],
  handles_permission_check: TRUE,
)]
final class NavigationSectionStorage extends PluginBase implements SectionStorageInterface, SectionStorageLocalTaskProviderInterface, ContainerFactoryPluginInterface, CacheableDependencyInterface {

  const STORAGE_ID = 'navigation.block_layout';
  use ContextAwarePluginTrait;
  use LayoutBuilderRoutesTrait;
  use SectionListTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
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
  public function getStorageType(): string {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageId(): string {
    return self::STORAGE_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Navigation layout';
  }

  /**
   * Returns the name to be used to store in the config system.
   */
  protected function getConfigName(): string {
    return self::STORAGE_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getSections(): array {
    if (is_null($this->sections)) {
      $sections = $this->configFactory->get($this->getConfigName())->get('sections') ?: [];
      $this->setSections(array_map([Section::class, 'fromArray'], $sections));
    }
    return $this->sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function setSections(array $sections): static {
    $this->sections = array_values($sections);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save(): int {
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
  public function buildRoutes(RouteCollection $collection): void {
    $this->buildLayoutRoutes($collection, $this->getPluginDefinition(), '/admin/config/user-interface/navigation-block');
    $default_route = 'layout_builder.' . $this->getPluginDefinition()->id() . '.view';
    $route = $collection->get($default_route);
    // Use a form for editing the layout instead of a controller.
    $defaults = $route->getDefaults();
    $defaults['_form'] = LayoutForm::class;
    unset($defaults['_controller']);
    $route->setDefaults($defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function deriveContextsFromRoute($value, $definition, $name, array $defaults): array {
    return ['navigation' => new Context(new ContextDefinition('string'), 'navigation')];
  }

  /**
   * {@inheritdoc}
   */
  public function buildLocalTasks($base_plugin_definition): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutBuilderUrl($rel = 'view'): Url {
    return Url::fromRoute("layout_builder.{$this->getStorageType()}.$rel", ['id' => $this->getStorageId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(): Url {
    return $this->getLayoutBuilderUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface | bool {
    $result = AccessResult::allowedIfHasPermission($account, 'configure navigation layout');
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getContextsDuringPreview(): array {
    return $this->getContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(RefinableCacheableDependencyInterface $cacheability): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextMapping(): array {
    return ['navigation' => 'navigation'];
  }

}
