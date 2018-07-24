<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\SectionListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines the 'overrides' section storage type.
 *
 * @SectionStorage(
 *   id = "overrides",
 * )
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class OverridesSectionStorage extends SectionStorageBase implements ContainerFactoryPluginInterface, OverridesSectionStorageInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\layout_builder\SectionListInterface|\Drupal\Core\Field\FieldItemListInterface
   */
  protected $sectionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setSectionList(SectionListInterface $section_list) {
    if (!$section_list instanceof FieldItemListInterface) {
      throw new \InvalidArgumentException('Overrides expect a field-based section list');
    }

    return parent::setSectionList($section_list);
  }

  /**
   * Gets the entity storing the overrides.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity storing the overrides.
   */
  protected function getEntity() {
    return $this->getSectionList()->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageId() {
    $entity = $this->getEntity();
    return $entity->getEntityTypeId() . '.' . $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function extractIdFromRoute($value, $definition, $name, array $defaults) {
    if (strpos($value, '.') !== FALSE) {
      return $value;
    }

    if (isset($defaults['entity_type_id']) && !empty($defaults[$defaults['entity_type_id']])) {
      $entity_type_id = $defaults['entity_type_id'];
      $entity_id = $defaults[$entity_type_id];
      return $entity_type_id . '.' . $entity_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionListFromId($id) {
    if (strpos($id, '.') !== FALSE) {
      list($entity_type_id, $entity_id) = explode('.', $id, 2);
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
      if ($entity instanceof FieldableEntityInterface && $entity->hasField('layout_builder__layout')) {
        return $entity->get('layout_builder__layout');
      }
    }
    throw new \InvalidArgumentException(sprintf('The "%s" ID for the "%s" section storage type is invalid', $id, $this->getStorageType()));
  }

  /**
   * {@inheritdoc}
   */
  public function buildRoutes(RouteCollection $collection) {
    foreach ($this->getEntityTypes() as $entity_type_id => $entity_type) {
      $defaults = [];
      $defaults['entity_type_id'] = $entity_type_id;

      $requirements = [];
      if ($this->hasIntegerId($entity_type)) {
        $requirements[$entity_type_id] = '\d+';
      }

      $options = [];
      // Ensure that upcasting is run in the correct order.
      $options['parameters']['section_storage'] = [];
      $options['parameters'][$entity_type_id]['type'] = 'entity:' . $entity_type_id;

      $template = $entity_type->getLinkTemplate('canonical') . '/layout';
      $this->buildLayoutRoutes($collection, $this->getPluginDefinition(), $template, $defaults, $requirements, $options, $entity_type_id);
    }
  }

  /**
   * Determines if this entity type's ID is stored as an integer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type.
   *
   * @return bool
   *   TRUE if this entity type's ID key is always an integer, FALSE otherwise.
   */
  protected function hasIntegerId(EntityTypeInterface $entity_type) {
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id());
    return $field_storage_definitions[$entity_type->getKey('id')]->getType() === 'integer';
  }

  /**
   * Returns an array of relevant entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types.
   */
  protected function getEntityTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return $entity_type->entityClassImplements(FieldableEntityInterface::class) && $entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical');
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSectionStorage() {
    // @todo Expand to work for all view modes in
    //   https://www.drupal.org/node/2907413.
    return LayoutBuilderEntityViewDisplay::collectRenderDisplay($this->getEntity(), 'full');
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return $this->getEntity()->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutBuilderUrl($rel = 'view') {
    $entity = $this->getEntity();
    $route_parameters[$entity->getEntityTypeId()] = $entity->id();
    return Url::fromRoute("layout_builder.{$this->getStorageType()}.{$this->getEntity()->getEntityTypeId()}.$rel", $route_parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    $entity = $this->getEntity();
    $contexts['layout_builder.entity'] = EntityContext::fromEntity($entity);
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getEntity()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    return $this->getEntity()->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $default_section_storage = $this->getDefaultSectionStorage();
    $result = AccessResult::allowedIf($default_section_storage->isLayoutBuilderEnabled())->addCacheableDependency($default_section_storage);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
