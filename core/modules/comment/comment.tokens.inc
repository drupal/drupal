<?php

/**
 * @file
 * Builds placeholder replacement tokens for comment-related data.
 */

/**
 * Implements hook_token_info().
 */
function comment_token_info() {
  $type = array(
    'name' => t('Comments'),
    'description' => t('Tokens for comments posted on the site.'),
    'needs-data' => 'comment',
  );

  // Comment-related tokens for nodes
  $node['comment-count'] = array(
    'name' => t("Comment count"),
    'description' => t("The number of comments posted on a node."),
  );
  $node['comment-count-new'] = array(
    'name' => t("New comment count"),
    'description' => t("The number of comments posted on a node since the reader last viewed it."),
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
  $comment['node'] = array(
    'name' => t("Node"),
    'description' => t("The node the comment was posted to."),
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
      'node' => $node,
      'comment' => $comment,
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function comment_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $token_service = Drupal::token();

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
    $comment = $data['comment'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the comment.
        case 'cid':
          $replacements[$original] = $comment->id();
          break;

        // Poster identity information for comments
        case 'hostname':
          $replacements[$original] = $sanitize ? check_plain($comment->hostname->value) : $comment->hostname->value;
          break;

        case 'name':
          $name = ($comment->uid->target_id == 0) ? config('user.settings')->get('anonymous') : $comment->name->value;
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;

        case 'mail':
          if ($comment->uid->target_id != 0) {
            $mail = $comment->uid->entity->getEmail();
          }
          else {
            $mail = $comment->mail->value;
          }
          $replacements[$original] = $sanitize ? check_plain($mail) : $mail;
          break;

        case 'homepage':
          $replacements[$original] = $sanitize ? check_url($comment->homepage->value) : $comment->homepage->value;
          break;

        case 'title':
          $replacements[$original] = $sanitize ? filter_xss($comment->subject->value) : $comment->subject->value;
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

        // Default values for the chained tokens handled below.
        case 'author':
          $replacements[$original] = $sanitize ? filter_xss($comment->name->value) : $comment->name->value;
          break;

        case 'parent':
          if (!empty($comment->pid->target_id)) {
            $parent = comment_load($comment->pid->target_id);
            $replacements[$original] = $sanitize ? filter_xss($parent->subject) : $parent->subject;
          }
          break;

        case 'created':
          $replacements[$original] = format_date($comment->created->value, 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $replacements[$original] = format_date($comment->changed->value, 'medium', '', NULL, $langcode);
          break;

        case 'node':
          $node = $comment->nid->entity;
          $title = $node->label();
          $replacements[$original] = $sanitize ? filter_xss($title) : $title;
          break;
      }
    }

    // Chained token relationships.
    if ($node_tokens = $token_service->findwithPrefix($tokens, 'node')) {
      $node = $comment->nid->entity;
      $replacements += $token_service->generate('node', $node_tokens, array('node' => $node), $options);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $date_tokens, array('date' => $comment->created->value), $options);
    }

    if ($date_tokens = $token_service->findwithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $date_tokens, array('date' => $comment->changed->value), $options);
    }

    if (($parent_tokens = $token_service->findwithPrefix($tokens, 'parent')) && $parent = $comment->pid->entity) {
      $replacements += $token_service->generate('comment', $parent_tokens, array('comment' => $parent), $options);
    }

    if (($author_tokens = $token_service->findwithPrefix($tokens, 'author')) && $account = $comment->uid->entity) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $account->getBCEntity()), $options);
    }
  }
  elseif ($type == 'node' & !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch($name) {
        case 'comment-count':
          $replacements[$original] = $node->comment_count;
          break;

        case 'comment-count-new':
          $replacements[$original] = comment_num_new($node->id());
          break;
      }
    }
  }

  return $replacements;
}
