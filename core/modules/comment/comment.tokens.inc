<?php

/**
 * @file
 * Builds placeholder replacement tokens for comment-related data.
 */

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;

/**
 * Implements hook_token_info().
 */
function comment_token_info() {
  $type = array(
    'name' => t('Comments'),
    'description' => t('Tokens for comments posted on the site.'),
    'needs-data' => 'comment',
  );

  // @todo Make this work per field. See http://drupal.org/node/2031903
  $entity['comment-count'] = array(
    'name' => t("Comment count"),
    'description' => t("The number of comments posted on an entity."),
  );
  $entity['comment-count-new'] = array(
    'name' => t("New comment count"),
    'description' => t("The number of comments posted on an entity since the reader last viewed it."),
  );

  // Core comment tokens
  $comment['cid'] = array(
    'name' => t("Comment ID"),
    'description' => t("The unique ID of the comment."),
  );
  $comment['hostname'] = array(
    'name' => t("IP Address"),
    'description' => t("The IP address of the computer the comment was posted from."),
  );
  $comment['name'] = array(
    'name' => t("Name"),
    'description' => t("The name left by the comment author."),
  );
  $comment['mail'] = array(
    'name' => t("Email address"),
    'description' => t("The email address left by the comment author."),
  );
  $comment['homepage'] = array(
    'name' => t("Home page"),
    'description' => t("The home page URL left by the comment author."),
  );
  $comment['title'] = array(
    'name' => t("Title"),
    'description' => t("The title of the comment."),
  );
  $comment['body'] = array(
    'name' => t("Content"),
    'description' => t("The formatted content of the comment itself."),
  );
  $comment['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the comment."),
  );
  $comment['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the comment's edit page."),
  );

  // Chained tokens for comments
  $comment['created'] = array(
    'name' => t("Date created"),
    'description' => t("The date the comment was posted."),
    'type' => 'date',
  );
  $comment['changed'] = array(
    'name' => t("Date changed"),
    'description' => t("The date the comment was most recently updated."),
    'type' => 'date',
  );
  $comment['parent'] = array(
    'name' => t("Parent"),
    'description' => t("The comment's parent, if comment threading is active."),
    'type' => 'comment',
  );
  $comment['entity'] = array(
    'name' => t("Entity"),
    'description' => t("The entity the comment was posted to."),
    'type' => 'entity',
  );
  // Support legacy comment node tokens, since tokens are embedded in user data
  // and can't be upgraded directly.
  $comment['node'] = array(
    'name' => t("Node"),
    'description' => t("DEPRECATED: The node the comment was posted to."),
    'type' => 'node',
  );
  $comment['author'] = array(
    'name' => t("Author"),
    'description' => t("The author of the comment, if they were logged in."),
    'type' => 'user',
  );

  return array(
    'types' => array('comment' => $type),
    'tokens' => array(
      'entity' => $entity,
      'comment' => $comment,
      // Support deprecated node tokens.
      'node' => $entity,
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function comment_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = language_load($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'comment' && !empty($data['comment'])) {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $data['comment'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the comment.
        case 'cid':
          $replacements[$original] = $comment->id();
          break;

        // Poster identity information for comments.
        case 'hostname':
          $replacements[$original] = $sanitize ? String::checkPlain($comment->getHostname()) : $comment->getHostname();
          break;

        case 'mail':
          $mail = $comment->getAuthorEmail();
          $replacements[$original] = $sanitize ? String::checkPlain($mail) : $mail;
          break;

        case 'homepage':
          $replacements[$original] = $sanitize ? check_url($comment->getHomepage()) : $comment->getHomepage();
          break;

        case 'title':
          $replacements[$original] = $sanitize ? Xss::filter($comment->getSubject()) : $comment->getSubject();
          break;

        case 'body':
          $replacements[$original] = $sanitize ? $comment->comment_body->processed : $comment->comment_body->value;
          break;

        // Comment related URLs.
        case 'url':
          $url_options['fragment']  = 'comment-' . $comment->id();
          $replacements[$original] = url('comment/' . $comment->id(), $url_options);
          break;

        case 'edit-url':
          $url_options['fragment'] = NULL;
          $replacements[$original] = url('comment/' . $comment->id() . '/edit', $url_options);
          break;

        // @todo Remove 'name' token in favour of 'author'. See
        //   https://drupal.org/node/920056.
        case 'name':
        case 'author':
          $name = $comment->getAuthorName();
          $replacements[$original] = $sanitize ? Xss::filter($name) : $name;
          break;

        case 'parent':
          if ($comment->hasParentComment()) {
            $parent = $comment->getParentComment();
            $replacements[$original] = $sanitize ? Xss::filter($parent->getSubject()) : $parent->getSubject();
          }
          break;

        case 'created':
          $replacements[$original] = format_date($comment->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $replacements[$original] = format_date($comment->getChangedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'entity':
          $entity = $comment->getCommentedEntity();
          $title = $entity->label();
          $replacements[$original] = $sanitize ? Xss::filter($title) : $title;
          break;

        case 'node':
          // Support legacy comment node tokens, since tokens are embedded in
          // user data and can't be upgraded directly.
          // @todo Remove in Drupal 9, see https://drupal.org/node/2031901.
          if ($comment->getCommentedEntityTypeId() == 'node') {
            $entity = $comment->getCommentedEntity();
            $title = $entity->label();
            $replacements[$original] = $sanitize ? Xss::filter($title) : $title;
          }
          else {
            $replacements[$original] = NULL;
          }
          break;
      }
    }

    // Chained token relationships.
    if ($entity_tokens = $token_service->findwithPrefix($tokens, 'entity')) {
      $entity = $comment->getCommentedEntity();
      $replacements += $token_service->generate($comment->getCommentedEntityTypeId(), $entity_tokens, array($comment->getCommentedEntityTypeId() => $entity), $options);
    }

    if (($node_tokens = $token_service->findwithPrefix($tokens, 'node')) && $comment->getCommentedEntityTypeId() == 'node') {
      $node = $comment->getCommentedEntity();
      $replacements += $token_service->generate('node', $node_tokens, array('node' => $node), $options);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $date_tokens, array('date' => $comment->getCreatedTime()), $options);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $date_tokens, array('date' => $comment->getChangedTime()), $options);
    }

    if (($parent_tokens = $token_service->findwithPrefix($tokens, 'parent')) && $parent = $comment->getParentComment()) {
      $replacements += $token_service->generate('comment', $parent_tokens, array('comment' => $parent), $options);
    }

    if (($author_tokens = $token_service->findwithPrefix($tokens, 'author')) && $account = $comment->getOwner()) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $account), $options);
    }
  }
  elseif (($type == 'entity' & !empty($data['entity'])) ||
    ($type == 'node' & !empty($data['node']))) {
    /** @var $entity \Drupal\Core\Entity\ContentEntityInterface */
    $entity = !empty($data['entity']) ? $data['entity'] : $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'comment-count':
          $count = 0;
          $fields = array_keys(\Drupal::service('comment.manager')->getFields($entity->getEntityTypeId()));
          $definitions = array_keys($entity->getFieldDefinitions());
          $valid_fields = array_intersect($fields, $definitions);
          foreach ($valid_fields as $field_name) {
            $count += $entity->get($field_name)->comment_count;
          }
          $replacements[$original] = $count;
          break;

        case 'comment-count-new':
          $replacements[$original] = \Drupal::service('comment.manager')->getCountNewComments($entity);
          break;
      }
    }
  }

  return $replacements;
}
