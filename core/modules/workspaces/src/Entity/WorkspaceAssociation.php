<?php

namespace Drupal\workspaces\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Workspace association entity.
 *
 * @ContentEntityType(
 *   id = "workspace_association",
 *   label = @Translation("Workspace association"),
 *   label_collection = @Translation("Workspace associations"),
 *   label_singular = @Translation("workspace association"),
 *   label_plural = @Translation("workspace associations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count workspace association",
 *     plural = "@count workspace associations"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\workspaces\WorkspaceAssociationStorage"
 *   },
 *   base_table = "workspace_association",
 *   revision_table = "workspace_association_revision",
 *   internal = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *   }
 * )
 *
 * @internal
 *   This entity is marked internal because it should not be used directly to
 *   alter the workspace an entity belongs to.
 */
class WorkspaceAssociation extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['workspace'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('workspace'))
      ->setDescription(new TranslatableMarkup('The workspace of the referenced content.'))
      ->setSetting('target_type', 'workspace')
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['target_entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Content entity type ID'))
      ->setDescription(new TranslatableMarkup('The ID of the content entity type associated with this workspace.'))
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['target_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Content entity ID'))
      ->setDescription(new TranslatableMarkup('The ID of the content entity associated with this workspace.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['target_entity_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Content entity revision ID'))
      ->setDescription(new TranslatableMarkup('The revision ID of the content entity associated with this workspace.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

}
