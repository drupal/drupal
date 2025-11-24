<?php

declare(strict_types=1);

namespace Drupal\block_content\Plugin\EntityReferenceSelection;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides specific selection control for the block_content entity type.
 */
#[EntityReferenceSelection(
  id: "default:block_content",
  label: new TranslatableMarkup("Block content selection"),
  group: "default",
  weight: 1,
  entity_types: ["block_content"],
)]
class BlockContentSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Only reusable blocks should be able to be referenced.
    $query->condition('reusable', TRUE);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    return array_filter($entities, static fn (BlockContentInterface $blockContent) => $blockContent->isReusable());
  }

}
