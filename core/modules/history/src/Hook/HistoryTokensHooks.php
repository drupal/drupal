<?php

namespace Drupal\history\Hook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\history\HistoryManager;

/**
 * Token hook implementations for history.
 */
class HistoryTokensHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $tokens = [];
    // Check if the comment manager service exists before processing.
    $commentManager = \Drupal::hasService('comment.manager') ? \Drupal::service('comment.manager') : NULL;
    // Provides an integration for each entity type except comment.
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type_id == 'comment' || !$entity_type->entityClassImplements(ContentEntityInterface::class)) {
        continue;
      }
      if ($commentManager && $commentManager->getFields($entity_type_id)) {
        // Get the correct token type.
        $token_type = $entity_type_id == 'taxonomy_term' ? 'term' : $entity_type_id;
        $tokens[$token_type]['comment-count-new'] = [
          'name' => $this->t("New comment count"),
          'description' => $this->t("The number of comments posted on an entity since the reader last viewed it."),
        ];
      }
    }
    return ['tokens' => $tokens];
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $replacements = [];
    if ($type != 'comment' || empty($data['comment'])) {
      if (!empty($data[$type]) && $data[$type] instanceof FieldableEntityInterface) {
        $entity = $data[$type];
        foreach ($tokens as $name => $original) {
          switch ($name) {
            case 'comment-count-new':
              $replacements[$original] = \Drupal::service(HistoryManager::class)->getCountNewComments($entity);
              break;
          }
        }
      }
    }
    return $replacements;
  }

}
