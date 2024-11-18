<?php

declare(strict_types=1);

namespace Drupal\comment_test\Hook;

use Drupal\Core\Url;
use Drupal\comment\CommentInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for comment_test.
 */
class CommentTestHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if (\Drupal::languageManager()->isMultilingual()) {
      // Enable language handling for comment fields.
      $translation = $entity_types['comment']->get('translation');
      $translation['comment_test'] = TRUE;
      $entity_types['comment']->set('translation', $translation);
    }
  }

  /**
   * Implements hook_comment_links_alter().
   */
  #[Hook('comment_links_alter')]
  public function commentLinksAlter(array &$links, CommentInterface &$entity, array &$context): void {
    // Allow tests to enable or disable this alter hook.
    if (!\Drupal::state()->get('comment_test_links_alter_enabled', FALSE)) {
      return;
    }
    $links['comment_test'] = [
      '#theme' => 'links__comment__comment_test',
      '#attributes' => [
        'class' => [
          'links',
          'inline',
        ],
      ],
      '#links' => [
        'comment-report' => [
          'title' => t('Report'),
          'url' => Url::fromRoute('comment_test.report', [
            'comment' => $entity->id(),
          ], [
            'query' => [
              'token' => \Drupal::getContainer()->get('csrf_token')->get("comment/{$entity->id()}/report"),
            ],
          ]),
        ],
      ],
    ];
  }

}
