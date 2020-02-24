<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 *
 * @internal
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workspace manager service.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new EntityTypeInfo instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('workspaces.manager')
    );
  }

  /**
   * Adds the "EntityWorkspaceConflict" constraint to eligible entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   An associative array of all entity type definitions, keyed by the entity
   *   type name. Passed by reference.
   *
   * @see hook_entity_type_build()
   */
  public function entityTypeBuild(array &$entity_types) {
    foreach ($entity_types as $entity_type) {
      if ($this->workspaceManager->isEntityTypeSupported($entity_type)) {
        $entity_type->addConstraint('EntityWorkspaceConflict');
        $entity_type->setRevisionMetadataKey('workspace', 'workspace');
      }
    }
  }

  /**
   * Removes the 'latest-version' link template provided by Content Moderation.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   An array of entity types.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Non-default workspaces display the active revision on the canonical
      // route of an entity, so the latest version route is no longer needed.
      $link_templates = $entity_type->get('links');
      unset($link_templates['latest-version']);
      $entity_type->set('links', $link_templates);
    }
  }

  /**
   * Alters field plugin definitions.
   *
   * @param array[] $definitions
   *   An array of field plugin definitions.
   *
   * @see hook_field_info_alter()
   */
  public function fieldInfoAlter(&$definitions) {
    if (isset($definitions['entity_reference'])) {
      $definitions['entity_reference']['constraints']['EntityReferenceSupportedNewEntities'] = [];
    }

    // Allow path aliases to be changed in workspace-specific pending revisions.
    if (isset($definitions['path'])) {
      unset($definitions['path']['constraints']['PathAlias']);
    }
  }

  /**
   * Provides custom base field definitions for a content entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name.
   *
   * @see hook_entity_base_field_info()
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    if ($this->workspaceManager->isEntityTypeSupported($entity_type)) {
      // Disable the BC layer to prevent a recursion, this only needs the
      // workspace key that is always set.
      $field_name = $entity_type->getRevisionMetadataKeys(FALSE)['workspace'];
      $fields[$field_name] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(new TranslatableMarkup('Workspace'))
        ->setDescription(new TranslatableMarkup('Indicates the workspace that this revision belongs to.'))
        ->setSetting('target_type', 'workspace')
        ->setInternal(TRUE)
        ->setTranslatable(FALSE)
        ->setRevisionable(TRUE);

      return $fields;
    }
  }

}
