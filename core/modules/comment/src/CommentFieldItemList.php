<?php

namespace Drupal\comment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an item list class for comment fields.
 */
class CommentFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    // The Field API only applies the "field default value" to newly created
    // entities. In the specific case of the "comment status", though, we need
    // this default value to be also applied for existing entities created
    // before the comment field was added, which have no value stored for the
    // field.
    if ($index == 0 && empty($this->list)) {
      $field_default_value = $this->getFieldDefinition()->getDefaultValue($this->getEntity());
      return $this->appendItem($field_default_value[0]);
    }
    return parent::get($index);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    // For consistency with what happens in get(), we force offsetExists() to
    // be TRUE for delta 0.
    if ($offset === 0) {
      return TRUE;
    }
    return parent::offsetExists($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $account ?: \Drupal::currentUser();
    if ($operation === 'edit') {
      // Only users with administer comments permission can edit the comment
      // status field.
      $result = AccessResult::allowedIfHasPermission($account, 'administer comments');
      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation === 'view') {
      // This operation is only used by EntityViewDisplay::buildMultiple(). In
      // this case check both: 'view comment list'' or 'create' access, since
      // this field is considered a composition of listed comments and comment
      // form. The decision to show only comments, only comment form or both
      // will be made by CommentDefaultFormatter::viewElements() later. Uses
      // recursive calls on same method invoking lower operations.
      $result = $this->access('view comment list', $account, TRUE);
      if (!$result->isAllowed()) {
        $result = $result->orIf($this->access('create', $account, TRUE));
      }

      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation === 'view comment list') {
      // In contrast to 'view', this operation is used as the lowest operation
      // by various methods to check only a single permission on last comment.
      // If a user is able to view the last published comments, they are able
      // to view the whole list of comments.
      return $this->lastPublishedCommentAccess($account, $return_as_object);
    }

    $entity_type_manager = \Drupal::entityTypeManager();

    // Replying to an existing comment is also comment creation. Normalize the
    // operation name after extracting the parent comment ID.
    if (strpos($operation, 'reply to ') === 0) {
      $parent_comment_id = (int) substr($operation, 9);
      $parent_comment = $entity_type_manager->getStorage('comment')->load($parent_comment_id);
      if (!$parent_comment) {
        return AccessResult::forbidden('Cannot reply to a non-existing comment');
      }
      $operation = 'create';
    }
    if ($operation === 'create') {
      // In contrast to 'view', this operation is used as the lowest operation
      // by various methods to check only the single 'create' permission on the
      // comment entity.
      $bundle = $this->getSetting('comment_type');
      $access_control_handler = $entity_type_manager->getAccessControlHandler('comment');

      // The commented entity and, when replying, the parent comment are
      // valuable information when comment entity 'create access' handler makes
      // the decision.
      $commented_entity = $this->getEntity();
      $cache_metadata = (new CacheableMetadata())->addCacheableDependency($commented_entity);
      $context = ['commented_entity' => $commented_entity];
      if (isset($parent_comment)) {
        $cache_metadata->addCacheableDependency($parent_comment);
        $context += ['parent_comment' => $parent_comment];
      }

      /** @var \Drupal\Core\Access\AccessResult $result */
      $result = $access_control_handler->createAccess($bundle, $account, $context, TRUE);
      return $return_as_object ? $result->addCacheableDependency($cache_metadata) : $result->isAllowed();
    }
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * Checks the access on the last published comment.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param bool $return_as_object
   *   If the method's return is an access result object or a boolean.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result either as an access result object or a boolean,
   *   depending on $return_as_object
   */
  protected function lastPublishedCommentAccess(AccountInterface $account, bool $return_as_object) {
    // Load the last published comment in the thread.
    $last_published_comment_id = $this->first()->getValue()['cid'] ?? NULL;
    if ($last_published_comment_id) {
      if ($comment = \Drupal::entityTypeManager()->getStorage('comment')->load($last_published_comment_id)) {
        // Allow if access on comment is allowed.
        return $comment->access('view', $account, $return_as_object);
      }
    }
    // If there are no comments, make no opinion.
    return $return_as_object ? AccessResult::neutral() : FALSE;
  }

}
