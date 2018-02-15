<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\Entity\LayoutBuilderSampleEntityGenerator;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\SectionListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines the 'defaults' section storage type.
 *
 * @SectionStorage(
 *   id = "defaults",
 * )
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
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
   * {@inheritdoc}
   *
   * @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface
   */
  protected $sectionList;

  /**
   * The sample entity generator.
   *
   * @var \Drupal\layout_builder\Entity\LayoutBuilderSampleEntityGenerator
   */
  protected $sampleEntityGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, LayoutBuilderSampleEntityGenerator $sample_entity_generator) {
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
  public function setSectionList(SectionListInterface $section_list) {
    if (!$section_list instanceof LayoutEntityDisplayInterface) {
      throw new \InvalidArgumentException('Defaults expect a display-based section list');
    }

    return parent::setSectionList($section_list);
  }

  /**
   * Gets the entity storing the overrides.
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
  public function getLayoutBuilderUrl() {
    return Url::fromRoute("layout_builder.{$this->getStorageType()}.{$this->getDisplay()->getTargetEntityTypeId()}.view", $this->getRouteParameters());
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

      $path = $entity_route->getPath() . '/display-layout/{view_mode_name}';

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

      $this->buildLayoutRoutes($collection, $this->getPluginDefinition(), $path, $defaults, $requirements, $options, $entity_type_id);

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
      return $entity_type->entityClassImplements(FieldableEntityInterface::class) && $entity_type->hasViewBuilderClass() && $entity_type->get('field_ui_base_route');
    });
  }

  /**
   * {@inheritdoc}
   */
  public function extractIdFromRoute($value, $definition, $name, array $defaults) {
    if (is_string($value) && strpos($value, '.') !== FALSE) {
      return $value;
    }

    // If a bundle is not provided but a value corresponding to the bundle key
    // is, use that for the bundle value.
    if (empty($defaults['bundle']) && isset($defaults['bundle_key']) && !empty($defaults[$defaults['bundle_key']])) {
      $defaults['bundle'] = $defaults[$defaults['bundle_key']];
    }

    if (!empty($defaults['entity_type_id']) && !empty($defaults['bundle']) && !empty($defaults['view_mode_name'])) {
      return $defaults['entity_type_id'] . '.' . $defaults['bundle'] . '.' . $defaults['view_mode_name'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionListFromId($id) {
    if (strpos($id, '.') === FALSE) {
      throw new \InvalidArgumentException(sprintf('The "%s" ID for the "%s" section storage type is invalid', $id, $this->getStorageType()));
    }

    $storage = $this->entityTypeManager->getStorage('entity_view_display');
    // If the display does not exist, create a new one.
    if (!$display = $storage->load($id)) {
      list($entity_type_id, $bundle, $view_mode) = explode('.', $id, 3);
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
  public function getContexts() {
    $display = $this->getDisplay();
    $entity = $this->sampleEntityGenerator->get($display->getTargetEntityTypeId(), $display->getTargetBundle());
    $context_label = new TranslatableMarkup('@entity being viewed', ['@entity' => $entity->getEntityType()->getLabel()]);

    // @todo Use EntityContextDefinition after resolving
    //   https://www.drupal.org/node/2932462.
    $contexts = [];
    $contexts['layout_builder.entity'] = new Context(new ContextDefinition("entity:{$entity->getEntityTypeId()}", $context_label), $entity);
    return $contexts;
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

}
