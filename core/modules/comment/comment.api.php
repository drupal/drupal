<?php

/**
 * @file
 * Hooks provided by the Comment module.
 */

use Drupal\comment\CommentInterface;
use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the links of a comment.
 *
 * @param array &$links
 *   A renderable array representing the comment links.
 * @param \Drupal\comment\CommentInterface $entity
 *   The comment being rendered.
 * @param array &$context
 *   Various aspects of the context in which the comment links are going to be
 *   displayed, with the following keys:
 *   - 'view_mode': the view mode in which the comment is being viewed
 *   - 'langcode': the language in which the comment is being viewed
 *   - 'commented_entity': the entity to which the comment is attached
 *
 * @see \Drupal\comment\CommentViewBuilder::renderLinks()
 * @see \Drupal\comment\CommentViewBuilder::buildLinks()
 */
function hook_comment_links_alter(array &$links, CommentInterface $entity, array &$context) {
  $links['my_module'] = [
    '#theme' => 'links__comment__my_module',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'comment-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('comment_test.report', ['comment' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("comment/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
