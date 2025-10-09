<?php

declare(strict_types=1);

namespace Drupal\workspaces_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Plugin\Field\FieldType\FieldTestItem;

/**
 * Defines the 'revision_test_field' field type.
 *
 * This field type records entity revision status during preSave() for
 * testing workspace entity save operations.
 */
#[FieldType(
  id: "revision_test_field",
  label: new TranslatableMarkup("Revision Test Field"),
  description: new TranslatableMarkup("A test field that tracks entity revision status during saves."),
)]
class RevisionTestItem extends FieldTestItem {

  /**
   * {@inheritdoc}
   */
  public function preSave(): void {
    parent::preSave();

    /** @var \Drupal\Core\Entity\RevisionableInterface|\Drupal\Core\Entity\EntityPublishedInterface $entity */
    $entity = $this->getEntity();

    // Track the revision sequence of field pre-save for this entity.
    // @see \Drupal\workspaces_test\Hook\WorkspacesTestHooks::entityPresave()
    $sequence_key = $entity->getEntityTypeId() . '.' . $entity->uuid() . '.field_revision_sequence';
    $sequence = \Drupal::keyValue('ws_test')->get($sequence_key, []);
    $sequence[] = [
      'is_new' => $entity->isNew(),
      'is_new_revision' => $entity->isNewRevision(),
      'is_published' => $entity->isPublished(),
      'is_default_revision' => $entity->isDefaultRevision(),
    ];
    \Drupal::keyValue('ws_test')->set($sequence_key, $sequence);
  }

}
