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
  $links['mymodule'] = [
    '#theme' => 'links__comment__mymodule',
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

/**
 * @defgroup comment_field_access Comment field access operations
 * @{
 * The comment field access method supports the main entity field access
 * operations, such as 'view' and 'edit', but allows also some more complex
 * comment specific operations. The complexity derives from the fact that the
 * comment field is mixing the actions of viewing and posting when it is placed
 * as an entity display component.
 *
 * @see \Drupal\comment\CommentFieldItemList::access()
 *
 * The comment field access supports the following operations:
 *
 * @section view 'view'
 * Used to check the access when the field is placed on an entity view display.
 * Even if it's a standard entity field access operation, this is a composite
 * operation as involves at the same time viewing the list of existing comments
 * and accessing the post comment form beneath. This is a standard core
 * operation as is used typically when the field is a visible component of a
 * commented entity view display.
 *
 * @see \Drupal\Core\Entity\Entity\EntityViewDisplay::buildMultiple()
 *
 * @section create 'create'
 * Used to check whether the user can post comments. This operation is mainly
 * used to check the access to the comment form or to a page where the form is
 * located, if the field is configured to place the form on a different page.
 * Note that, the operation of replying to an existing comment is
 * 'reply to {comment id}', rather than 'create'.
 *
 * @section reply_to 'reply to {comment id}'
 * Used to check the access to a form that allows to post a reply to an existing
 * comment, with ID {comment id}. This operation is almost the same as 'create'
 * but unlike the later, the operation name contains also the parent comment ID,
 * providing rich context when the access decision is made. Is used typically to
 * check the access of the reply form route.
 *
 * @see \Drupal\comment\Controller\CommentController::replyFormAccess()
 *
 * @section view_comment_list 'view comment list'
 * Unlike 'view', this operation limit its scope only to view the field list
 * of comments. Such an operation will be allowed only if the user can access at
 * least one comment from the thread. This is typically used to assemble the
 * access check for 'view', as the later is a composite operation but also in
 * other places, like comment_node_search_result(), where the number of comments
 * is attached to the nodes search result and we need to check if the user is
 * able to view the comments.
 *
 * @see comment_node_search_result()
 *
 * @section edit 'edit'
 * This is a standard entity operation that allows access to the comment field
 * widget in the commented entity form.
 *
 * @see \Drupal\Core\Entity\Entity\EntityFormDisplay::buildForm()
 * @}
 */
