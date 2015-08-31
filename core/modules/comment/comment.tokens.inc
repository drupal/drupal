<?php

/**
 * @file
 * Builds placeholder replacement tokens for comment-related data.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function comment_token_info() {
  $type = array(
    'name' => t('Comments'),
    'description' => t('Tokens for comments posted on the site.'),
    'needs-data' => 'comment',
  );

  // @todo Make this work per field. See https://www.drupal.org/node/2031903.
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
  $comment['langcode'] = array(
    'name' => t('Language code'),
    'description' => t('The language code of the language the comment is written in.'),
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
  $comment['author'] = array(
    'name' => t("Author"),
    'description' => t("The author name of the comment."),
    'type' => 'user',
  );

  return array(
    'types' => array('comment' => $type),
    'tokens' => array(
      'entity' => $entity,
      'comment' => $comment,
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function comment_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
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
          $replacements[$original] = $sanitize ? Html::escape($comment->getHostname()) : $comment->getHostname();
          break;

        case 'mail':
          $mail = $comment->getAuthorEmail();
          // Add the user cacheability metadata in case the author of the comment
          // is not the anonymous user.
          if ($comment->getOwnerId()) {
            $bubbleable_metadata->addCacheableDependency($comment->getOwner());
          }
          $replacements[$original] = $sanitize ? Html::escape($mail) : $mail;
          break;

        case 'homepage':
          $replacements[$original] = $sanitize ? UrlHelper::filterBadProtocol($comment->getHomepage()) : $comment->getHomepage();
          break;

        case 'title':
          $replacements[$original] = $sanitize ? Xss::filter($comment->getSubject()) : $comment->getSubject();
          break;

        case 'body':
          $replacements[$original] = $sanitize ? $comment->comment_body->processed : $comment->comment_body->value;
          break;

        case 'langcode':
          $replacements[$original] = $sanitize ? Html::escape($comment->language()->getId()) : $comment->language()->getId();
          break;

        // Comment related URLs.
        case 'url':
          $url_options['fragment']  = 'comment-' . $comment->id();
          $replacements[$original] = $comment->url('canonical', $url_options);
          break;

        case 'edit-url':
          $url_options['fragment'] = NULL;
          $replacements[$original] = $comment->url('edit-form', $url_options);
          break;

        case 'author':
          $name = $comment->getAuthorName();
          // Add the user cacheability metadata in case the author of the comment
          // is not the anonymous user.
          if ($comment->getOwnerId()) {
            $bubbleable_metadata->addCacheableDependency($comment->getOwner());
          }
          $replacements[$original] = $sanitize ? Xss::filter($name) : $name;
          break;

        case 'parent':
          if ($comment->hasParentComment()) {
            $parent = $comment->getParentComment();
            $bubbleable_metadata->addCacheableDependency($parent);
            $replacements[$original] = $sanitize ? Xss::filter($parent->getSubject()) : $parent->getSubject();
          }
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($comment->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($comment->getChangedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'entity':
          $entity = $comment->getCommentedEntity();
          $bubbleable_metadata->addCacheableDependency($entity);
          $title = $entity->label();
          $replacements[$original] = $sanitize ? Xss::filter($title) : $title;
          break;
      }
    }

    // Chained token relationships.
    if ($entity_tokens = $token_service->findwithPrefix($tokens, 'entity')) {
      $entity = $comment->getCommentedEntity();
      $replacements += $token_service->generate($comment->getCommentedEntityTypeId(), $entity_tokens, array($comment->getCommentedEntityTypeId() => $entity), $options, $bubbleable_metadata);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $date_tokens, array('date' => $comment->getCreatedTime()), $options, $bubbleable_metadata);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $date_tokens, array('date' => $comment->getChangedTime()), $options, $bubbleable_metadata);
    }

    if (($parent_tokens = $token_service->findwithPrefix($tokens, 'parent')) && $parent = $comment->getParentComment()) {
      $replacements += $token_service->generate('comment', $parent_tokens, array('comment' => $parent), $options, $bubbleable_metadata);
    }

    if (($author_tokens = $token_service->findwithPrefix($tokens, 'author')) && $account = $comment->getOwner()) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $account), $options, $bubbleable_metadata);
    }
  }
  elseif ($type == 'entity' & !empty($data['entity'])) {
    /** @var $entity \Drupal\Core\Entity\FieldableEntityInterface */
    $entity = $data['entity'];

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
