<?php

declare(strict_types=1);

namespace Drupal\node_access_test_language\Hook;

use Drupal\node\NodeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_access_test_language.
 */
class NodeAccessTestLanguageHooks {

  /**
   * Implements hook_node_grants().
   *
   * This module defines a single grant realm. All users belong to this group.
   */
  #[Hook('node_grants')]
  public function nodeGrants($account, $operation): array {
    $grants['node_access_language_test'] = [7888];
    return $grants;
  }

  /**
   * Implements hook_node_access_records().
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords(NodeInterface $node): array {
    $grants = [];
    // Create grants for each translation of the node.
    foreach ($node->getTranslationLanguages() as $langcode => $language) {
      // If the translation is not marked as private, grant access.
      $translation = $node->getTranslation($langcode);
      $grants[] = [
        'realm' => 'node_access_language_test',
        'gid' => 7888,
        'grant_view' => empty($translation->field_private->value) ? 1 : 0,
        'grant_update' => 0,
        'grant_delete' => 0,
        'langcode' => $langcode,
      ];
    }
    return $grants;
  }

}
