<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\workspaces\Entity\Handler\BlockContentWorkspaceHandler;
use Drupal\workspaces\Entity\Handler\DefaultWorkspaceHandler;
use Drupal\workspaces\Entity\Handler\IgnoredWorkspaceHandler;
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

  public function __construct(
    protected readonly WorkspaceInformationInterface $workspaceInfo,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspaces.information')
    );
  }

  /**
   * Adds workspace support info to eligible entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   An associative array of all entity type definitions, keyed by the entity
   *   type name. Passed by reference.
   *
   * @see hook_entity_type_build()
   */
  public function entityTypeBuild(array &$entity_types) {
    foreach ($entity_types as $entity_type) {
      if ($entity_type->hasHandlerClass('workspace')) {
        continue;
      }

      // Revisionable and publishable entity types are always supported.
      if ($entity_type->entityClassImplements(EntityPublishedInterface::class) && $entity_type->isRevisionable()) {
        $entity_type->setHandlerClass('workspace', DefaultWorkspaceHandler::class);

        // Support for custom blocks has to be determined on a per-entity
        // basis.
        if ($entity_type->id() === 'block_content') {
          $entity_type->setHandlerClass('workspace', BlockContentWorkspaceHandler::class);
        }
      }

      // The 'file' entity type is allowed to perform CRUD operations inside a
      // workspace without being tracked.
      if ($entity_type->id() === 'file') {
        $entity_type->setHandlerClass('workspace', IgnoredWorkspaceHandler::class);
      }

      // Internal entity types are allowed to perform CRUD operations inside a
      // workspace.
      if ($entity_type->isInternal()) {
        $entity_type->setHandlerClass('workspace', IgnoredWorkspaceHandler::class);
      }
    }
  }

  /**
   * Adds Workspace configuration to appropriate entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   An array of entity types.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($entity_types as $entity_type) {
      if (!$this->workspaceInfo->isEntityTypeSupported($entity_type)) {
        continue;
      }

      // Workspace-support status has been declared in the "build" phase, now we
      // can use that information and add additional configuration in the
      // "alter" phase.
      $entity_type->addConstraint('EntityWorkspaceConflict');
      $entity_type->setRevisionMetadataKey('workspace', 'workspace');

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
    if ($this->workspaceInfo->isEntityTypeSupported($entity_type)) {
      $field_name = $entity_type->getRevisionMetadataKey('workspace');
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
