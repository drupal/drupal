<?php

declare(strict_types=1);

namespace Drupal\node_no_default_author\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_no_default_author.
 */
class NodeNoDefaultAuthorHooks {

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() === 'node') {
      $fields['uid']->setDefaultValueCallback(static::class . '::noDefaultAuthor');
    }
  }

  /**
   * An empty callback to set for the default value callback of uid.
   */
  public static function noDefaultAuthor(): void {
  }

}
