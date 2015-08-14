<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldConfigListBuilder.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides lists of field config entities.
 */
class FieldConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * The name of the entity type the listed fields are attached to.
   *
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * The name of the bundle the listed fields are attached to.
   *
   * @var string
   */
  protected $targetBundle;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($entity_type, $entity_manager->getStorage($entity_type->id()));

    $this->entityManager = $entity_manager;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('entity.manager'), $container->get('plugin.manager.field.field_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function render($target_entity_type_id = NULL, $target_bundle = NULL) {
    $this->targetEntityTypeId = $target_entity_type_id;
    $this->targetBundle = $target_bundle;

    $build = parent::render();
    $build['table']['#attributes']['id'] = 'field-overview';
    $build['table']['#empty'] = $this->t('No fields are present yet.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array_filter($this->entityManager->getFieldDefinitions($this->targetEntityTypeId, $this->targetBundle), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, array($this->entityType->getClass(), 'sort'));
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'label' => $this->t('Label'),
      'field_name' => array(
        'data' => $this->t('Machine name'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'field_type' => $this->t('Field type'),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_config) {
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_storage = $field_config->getFieldStorageDefinition();
    $route_parameters = array(
      'field_config' => $field_config->id(),
    ) + FieldUI::getRouteBundleParameter($this->entityManager->getDefinition($this->targetEntityTypeId), $this->targetBundle);

    $row = array(
      'id' => Html::getClass($field_config->getName()),
      'data' => array(
        'label' => SafeMarkup::checkPlain($field_config->getLabel()),
        'field_name' => $field_config->getName(),
        'field_type' => array(
          'data' => array(
            '#type' => 'link',
            '#title' => $this->fieldTypeManager->getDefinitions()[$field_storage->getType()]['label'],
            '#url' => Url::fromRoute("entity.field_config.{$this->targetEntityTypeId}_storage_edit_form", $route_parameters),
            '#options' => array('attributes' => array('title' => $this->t('Edit field settings.'))),
          ),
        ),
      ),
    );

    // Add the operations.
    $row['data'] = $row['data'] + parent::buildRow($field_config);

    if ($field_storage->isLocked()) {
      $row['data']['operations'] = array('data' => array('#markup' => $this->t('Locked')));
      $row['class'][] = 'menu-disabled';
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\field\FieldConfigInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('update') && $entity->hasLinkTemplate("{$entity->getTargetEntityTypeId()}-field-edit-form")) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo("{$entity->getTargetEntityTypeId()}-field-edit-form"),
      );
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate("{$entity->getTargetEntityTypeId()}-field-delete-form")) {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->urlInfo("{$entity->getTargetEntityTypeId()}-field-delete-form"),
      );
    }

    $operations['storage-settings'] = array(
      'title' => $this->t('Storage settings'),
      'weight' => 20,
      'attributes' => array('title' => $this->t('Edit storage settings.')),
      'url' => $entity->urlInfo("{$entity->getTargetEntityTypeId()}-storage-edit-form"),
    );
    $operations['edit']['attributes']['title'] = $this->t('Edit field settings.');
    $operations['delete']['attributes']['title'] = $this->t('Delete field.');

    return $operations;
  }

}
