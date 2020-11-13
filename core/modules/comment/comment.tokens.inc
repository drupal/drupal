<?php

/**
 * @file
 * Builds placeholder replacement tokens for comment-related data.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function comment_token_info() {
  $type = [
    'name' => t('Comments'),
    'description' => t('Tokens for comments posted on the site.'),
    'needs-data' => 'comment',
  ];

  $tokens = [];
  // Provides an integration for each entity type except comment.
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if ($entity_type_id == 'comment' || !$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      continue;
    }

    if (\Drupal::service('comment.manager')->getFields($entity_type_id)) {
      // Get the correct token type.
      $token_type = ($entity_type_id == 'taxonomy_term') ? 'term' : $entity_type_id;

      // @todo Make this work per field. See https://www.drupal.org/node/2031903.
      $tokens[$token_type]['comment-count'] = [
        'name' => t("Comment count"),
        'description' => t("The number of comments posted on an entity."),
      ];
      $tokens[$token_type]['comment-count-new'] = [
        'name' => t("New comment count"),
        'description' => t("The number of comments posted on an entity since the reader last viewed it."),
      ];
    }
  }

  // Core comment tokens
  $comment['cid'] = [
    'name' => t("Comment ID"),
    'description' => t("The unique ID of the comment."),
  ];
  $comment['hostname'] = [
    'name' => t("IP Address"),
    'description' => t("The IP address of the computer the comment was posted from."),
  ];
  $comment['mail'] = [
    'name' => t("Email address"),
    'description' => t("The email address left by the comment author."),
  ];
  $comment['homepage'] = [
    'name' => t("Home page"),
    'description' => t("The home page URL left by the comment author."),
  ];
  $comment['title'] = [
    'name' => t("Title"),
    'description' => t("The title of the comment."),
  ];
  $comment['body'] = [
    'name' => t("Content"),
    'description' => t("The formatted content of the comment itself."),
  ];
  $comment['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the comment is written in.'),
  ];
  $comment['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the comment."),
  ];
  $comment['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the comment's edit page."),
  ];

  // Chained tokens for comments
  $comment['created'] = [
    'name' => t("Date created"),
    'description' => t("The date the comment was posted."),
    'type' => 'date',
  ];
  $comment['changed'] = [
    'name' => t("Date changed"),
    'description' => t("The date the comment was most recently updated."),
    'type' => 'date',
  ];
  $comment['parent'] = [
    'name' => t("Parent"),
    'description' => t("The comment's parent, if comment threading is active."),
    'type' => 'comment',
  ];
  $comment['entity'] = [
    'name' => t("Entity"),
    'description' => t("The entity the comment was posted to."),
    'type' => 'entity',
  ];
  $comment['author'] = [
    'name' => t("Author"),
    'description' => t("The author name of the comment."),
    'type' => 'user',
  ];

  return [
    'types' => ['comment' => $type],
    'tokens' => [
      'comment' => $comment,
    ] + $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function comment_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

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
          $replacements[$original] = $comment->getHostname();
          break;

        case 'mail':
          $mail = $comment->getAuthorEmail();
          // Add the user cacheability metadata in case the author of the comment
          // is not the anonymous user.
          if ($comment->getOwnerId()) {
            $bubbleable_metadata->addCacheableDependency($comment->getOwner());
          }
          $replacements[$original] = $mail;
          break;

        case 'homepage':
          $replacements[$original] = UrlHelper::stripDangerousProtocols($comment->getHomepage());
          break;

        case 'title':
          $replacements[$original] = $comment->getSubject();
          break;

        case 'body':
          // "processed" returns a \Drupal\Component\Render\MarkupInterface via
          // check_markup().
          $replacements[$original] = $comment->comment_body->processed;
          break;

        case 'langcode':
          $replacements[$original] = $comment->language()->getId();
          break;

        // Comment related URLs.
        case 'url':
          $url_options['fragment'] = 'comment-' . $comment->id();
          $replacements[$original] = $comment->toUrl('canonical', $url_options)->toString();
          break;

        case 'edit-url':
          $url_options['fragment'] = NULL;
          $replacements[$original] = $comment->toUrl('edit-form', $url_options)->toString();
          break;

        case 'author':
          $name = $comment->getAuthorName();
          // Add the user cacheability metadata in case the author of the comment
          // is not the anonymous user.
          if ($comment->getOwnerId()) {
            $bubbleable_metadata->addCacheableDependency($comment->getOwner());
          }
          $replacements[$original] = $name;
          break;

        case 'parent':
          if ($comment->hasParentComment()) {
            $parent = $comment->getParentComment();
            $bubbleable_metadata->addCacheableDependency($parent);
            $replacements[$original] = $parent->getSubject();
          }
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($comment->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($comment->getChangedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'entity':
          $entity = $comment->getCommentedEntity();
          $bubbleable_metadata->addCacheableDependency($entity);
          $title = $entity->label();
          $replacements[$original] = $title;
          break;
      }
    }

    // Chained token relationships.
    if ($entity_tokens = $token_service->findwithPrefix($tokens, 'entity')) {
      $entity = $comment->getCommentedEntity();
      $replacements += $token_service->generate($comment->getCommentedEntityTypeId(), $entity_tokens, [$comment->getCommentedEntityTypeId() => $entity], $options, $bubbleable_metadata);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $date_tokens, ['date' => $comment->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $date_tokens, ['date' => $comment->getChangedTime()], $options, $bubbleable_metadata);
    }

    if (($parent_tokens = $token_service->findwithPrefix($tokens, 'parent')) && $parent = $comment->getParentComment()) {
      $replacements += $token_service->generate('comment', $parent_tokens, ['comment' => $parent], $options, $bubbleable_metadata);
    }

    if (($author_tokens = $token_service->findwithPrefix($tokens, 'author')) && $account = $comment->getOwner()) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $account], $options, $bubbleable_metadata);
    }
  }
  // Replacement tokens for any content entities that have comment field.
  elseif (!empty($data[$type]) && $data[$type] instanceof FieldableEntityInterface) {
    /** @var $entity \Drupal\Core\Entity\FieldableEntityInterface */
    $entity = $data[$type];

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
