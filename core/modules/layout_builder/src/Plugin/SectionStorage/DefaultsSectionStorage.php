<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

use Drupal\Component\Plugin\Context\ContextInterface as ComponentContextInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\Entity\SampleEntityGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines the 'defaults' section storage type.
 *
 * DefaultsSectionStorage uses a positive weight because:
 * - It must be picked after
 *   \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage.
 * - The default weight is 0, so other custom implementations will also take
 *   precedence unless otherwise specified.
 *
 * @SectionStorage(
 *   id = "defaults",
 *   weight = 20,
 *   context_definitions = {
 *     "display" = @ContextDefinition("entity:entity_view_display"),
 *     "view_mode" = @ContextDefinition("string", default_value = "default"),
 *   },
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class DefaultsSectionStorage extends SectionStorageBase implements ContainerFactoryPluginInterface, DefaultsSectionStorageInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The sample entity generator.
   *
   * @var \Drupal\layout_builder\Entity\SampleEntityGeneratorInterface
   */
  protected $sampleEntityGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, SampleEntityGeneratorInterface $sample_entity_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->sampleEntityGenerator = $sample_entity_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('layout_builder.sample_entity_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionList() {
    return $this->getContextValue('display');
  }

  /**
   * Gets the entity storing the defaults.
   *
   * @return \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface
   *   The entity storing the defaults.
   */
  protected function getDisplay() {
    return $this->getSectionList();
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageId() {
    return $this->getDisplay()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return Url::fromRoute("entity.entity_view_display.{$this->getDisplay()->getTargetEntityTypeId()}.view_mode", $this->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutBuilderUrl($rel = 'view') {
    return Url::fromRoute("layout_builder.{$this->getStorageType()}.{$this->getDisplay()->getTargetEntityTypeId()}.$rel", $this->getRouteParameters());
  }

  /**
   * Provides the route parameters needed to generate a URL for this object.
   *
   * @return mixed[]
   *   An associative array of parameter names and values.
   */
  protected function getRouteParameters() {
    $display = $this->getDisplay();
    $entity_type = $this->entityTypeManager->getDefinition($display->getTargetEntityTypeId());
    $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $display->getTargetBundle());
    $route_parameters['view_mode_name'] = $display->getMode();
    return $route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRoutes(RouteCollection $collection) {
    foreach ($this->getEntityTypes() as $entity_type_id => $entity_type) {
      // Try to get the route from the current collection.
      if (!$entity_route = $collection->get($entity_type->get('field_ui_base_route'))) {
        continue;
      }

      $path = $entity_route->getPath() . '/display/{view_mode_name}/layout';

      $defaults = [];
      $defaults['entity_type_id'] = $entity_type_id;
      // If the entity type has no bundles and it doesn't use {bundle} in its
      // admin path, use the entity type.
      if (strpos($path, '{bundle}') === FALSE) {
        if (!$entity_type->hasKey('bundle')) {
          $defaults['bundle'] = $entity_type_id;
        }
        else {
          $defaults['bundle_key'] = $entity_type->getBundleEntityType();
        }
      }

      $requirements = [];
      $requirements['_field_ui_view_mode_access'] = 'administer ' . $entity_type_id . ' display';

      $options = $entity_route->getOptions();
      $options['_admin_route'] = FALSE;

      $this->buildLayoutRoutes($collection, $this->getPluginDefinition(), $path, $defaults, $requirements, $options, $entity_type_id, 'entity_view_display');

      // Set field_ui.route_enhancer to run on the manage layout form.
      if (isset($defaults['bundle_key'])) {
        $collection->get("layout_builder.defaults.$entity_type_id.view")
          ->setOption('_field_ui', TRUE)
          ->setDefault('bundle', '');
      }

      $route_names = [
        "entity.entity_view_display.{$entity_type_id}.default",
        "entity.entity_view_display.{$entity_type_id}.view_mode",
      ];
      foreach ($route_names as $route_name) {
        if (!$route = $collection->get($route_name)) {
          continue;
        }

        $route->addDefaults([
          'section_storage_type' => $this->getStorageType(),
          'section_storage' => '',
        ] + $defaults);
        $parameters['section_storage']['layout_builder_tempstore'] = TRUE;
        $parameters = NestedArray::mergeDeep($parameters, $route->getOption('parameters') ?: []);
        $route->setOption('parameters', $parameters);
      }
    }
  }

  /**
   * Returns an array of relevant entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types.
   */
  protected function getEntityTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return $entity_type->entityClassImplements(FieldableEntityInterface::class) && $entity_type->hasHandlerClass('form', 'layout_builder') && $entity_type->hasViewBuilderClass() && $entity_type->get('field_ui_base_route');
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getContextsDuringPreview() {
    $contexts = parent::getContextsDuringPreview();

    // During preview add a sample entity for the target entity type and bundle.
    $display = $this->getDisplay();
    $entity = $this->sampleEntityGenerator->get($display->getTargetEntityTypeId(), $display->getTargetBundle());

    $contexts['layout_builder.entity'] = EntityContext::fromEntity($entity);
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function deriveContextsFromRoute($value, $definition, $name, array $defaults) {
    $contexts = [];

    if ($entity = $this->extractEntityFromRoute($value, $defaults)) {
      $contexts['display'] = EntityContext::fromEntity($entity);
    }
    return $contexts;
  }

  /**
   * Extracts an entity from the route values.
   *
   * @param mixed $value
   *   The raw value from the route.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity for the route, or NULL if none exist.
   *
   * @see \Drupal\layout_builder\SectionStorageInterface::deriveContextsFromRoute()
   * @see \Drupal\Core\ParamConverter\ParamConverterInterface::convert()
   */
  private function extractEntityFromRoute($value, array $defaults) {
    // If a bundle is not provided but a value corresponding to the bundle key
    // is, use that for the bundle value.
    if (empty($defaults['bundle']) && isset($defaults['bundle_key']) && !empty($defaults[$defaults['bundle_key']])) {
      $defaults['bundle'] = $defaults[$defaults['bundle_key']];
    }

    if (is_string($value) && strpos($value, '.') !== FALSE) {
      [$entity_type_id, $bundle, $view_mode] = explode('.', $value, 3);
    }
    elseif (!empty($defaults['entity_type_id']) && !empty($defaults['bundle']) && !empty($defaults['view_mode_name'])) {
      $entity_type_id = $defaults['entity_type_id'];
      $bundle = $defaults['bundle'];
      $view_mode = $defaults['view_mode_name'];
      $value = "$entity_type_id.$bundle.$view_mode";
    }
    else {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('entity_view_display');
    // If the display does not exist, create a new one.
    if (!$display = $storage->load($value)) {
      $display = $storage->create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
    }
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getDisplay()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return $this->getDisplay()->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isOverridable() {
    return $this->getDisplay()->isOverridable();
  }

  /**
   * {@inheritdoc}
   */
  public function setOverridable($overridable = TRUE) {
    $this->getDisplay()->setOverridable($overridable);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->getDisplay()->setThirdPartySetting($module, $key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLayoutBuilderEnabled() {
    return $this->getDisplay()->isLayoutBuilderEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function enableLayoutBuilder() {
    $this->getDisplay()->enableLayoutBuilder();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disableLayoutBuilder() {
    $this->getDisplay()->disableLayoutBuilder();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    return $this->getDisplay()->getThirdPartySetting($module, $key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module) {
    return $this->getDisplay()->getThirdPartySettings($module);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    $this->getDisplay()->unsetThirdPartySetting($module, $key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return $this->getDisplay()->getThirdPartyProviders();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf($this->isLayoutBuilderEnabled())->addCacheableDependency($this);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(RefinableCacheableDependencyInterface $cacheability) {
    $cacheability->addCacheableDependency($this);
    return $this->isLayoutBuilderEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function setContext($name, ComponentContextInterface $context) {
    // Set the view mode context based on the display context.
    if ($name === 'display') {
      $this->setContextValue('view_mode', $context->getContextValue()->getMode());
    }
    parent::setContext($name, $context);
  }

}
