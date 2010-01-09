<?php
// $Id: comment.tokens.inc,v 1.10 2010/01/09 21:54:00 webchick Exp $

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
  $comment['pid'] = array(
    'name' => t("Parent ID"),
    'description' => t("The unique ID of the comment's parent, if comment threading is active."),
  );
  $comment['nid'] = array(
    'name' => t("Node ID"),
    'description' => t("The unique ID of the node the comment was posted to."),
  );
  $comment['uid'] = array(
    'name' => t("User ID"),
    'description' => t("The unique ID of the user who posted the comment."),
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
  $url_options = array('absolute' => TRUE);
  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'comment' && !empty($data['comment'])) {
    $comment = $data['comment'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the comment.
        case 'cid':
          $replacements[$original] = $comment->cid;
          break;

        case 'nid':
          $replacements[$original] = $comment->nid;
          break;

        case 'uid':
          $replacements[$original] = $comment->uid;
          break;

        case 'pid':
          $replacements[$original] = $comment->pid;
          break;

        // Poster identity information for comments
        case 'hostname':
          $replacements[$original] = $sanitize ? check_plain($comment->hostname) : $comment->hostname;
          break;

        case 'name':
          $name = ($comment->uid == 0) ? variable_get('anonymous', t('Anonymous')) : $comment->name;
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;

        case 'mail':
          if ($comment->uid != 0) {
            $account = user_load($comment->uid);
            $mail = $account->mail;
          }
          else {
            $mail = $comment->mail;
          }
          $replacements[$original] = $sanitize ? check_plain($mail) : $mail;
          break;

        case 'homepage':
          $replacements[$original] = $sanitize ? filter_xss_bad_protocol($comment->homepage) : $comment->homepage;
          break;

        case 'title':
          $replacements[$original] = $sanitize ? filter_xss($comment->subject) : $comment->subject;
          break;

        case 'body':
          $replacements[$original] = $sanitize ? check_markup($comment->comment, $comment->format, '', TRUE) : $comment->comment;
          break;

        // Comment related URLs.
        case 'url':
          $url_options['fragment']  = 'comment-' . $comment->cid;
          $replacements[$original] = url('comment/' . $comment->cid, $url_options);
          break;

        case 'edit-url':
          $replacements[$original] = url('comment/' . $comment->cid . '/edit', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $replacements[$original] = $sanitize ? filter_xss($comment->name) : $comment->name;
          break;

        case 'parent':
          if (!empty($comment->pid)) {
            $parent = comment_load($comment->pid);
            $replacements[$original] = $sanitize ? filter_xss($parent->subject) : $parent->subject;
          }
          break;

        case 'created':
          $replacements[$original] = format_date($comment->created, 'medium', '', NULL, $language_code);
          break;

        case 'changed':
          $replacements[$original] = format_date($comment->changed, 'medium', '', NULL, $language_code);
          break;

        case 'node':
          $node = node_load($comment->nid);
          $title = $node->title;
          $replacements[$original] = $sanitize ? filter_xss($title) : $title;
          break;
      }
    }

    // Chained token relationships.
    if ($node_tokens = token_find_with_prefix($tokens, 'node')) {
      $node = node_load($comment->nid);
      $replacements += token_generate('node', $node_tokens, array('node' => $node), $options);
    }

    if ($date_tokens = token_find_with_prefix($tokens, 'created')) {
      $replacements += token_generate('date', $date_tokens, array('date' => $comment->created), $options);
    }

    if (($parent_tokens = token_find_with_prefix($tokens, 'parent')) && $parent = comment_load($comment->pid)) {
      $replacements += token_generate('comment', $parent_tokens, array('comment' => $parent), $options);
    }

    if (($author_tokens = token_find_with_prefix($tokens, 'author')) && $account = user_load($comment->uid)) {
      $replacements += token_generate('user', $author_tokens, array('user' => $account), $options);
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
          $replacements[$original] = comment_num_new($node->nid);
          break;
      }
    }
  }

  return $replacements;
}
