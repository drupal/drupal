<?php

namespace Drupal\block_content;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Defines the translation handler for custom blocks.
 */
class BlockContentTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    $block_type = BlockContentType::load($entity->bundle());
    return t('<em>Edit @type</em> @title', ['@type' => $block_type->label(), '@title' => $entity->label()]);
  }

}
