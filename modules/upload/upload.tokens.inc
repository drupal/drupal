<?php
// $Id$

/**
 * @file
 * Builds placeholder replacement tokens for uploaded files attached to nodes.
 */

/**
 * Implements hook_token_info().
 */
function upload_token_info() {
  $results['tokens']['node'] = array(
  'upload' => array(
    'name' => t('File attachment'),
    'description' => t('The first file attached to a node, if one exists.'),
    'type' => 'file',
    )
  );
  return $results;
}

/**
 * Implements hook_tokens().
 */
function upload_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();

  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      if ($name == 'upload') {
        $upload = array_shift($node->files);
        $replacements[$original] = file_create_url($upload->filepath);
      }
    }

    if (($upload_tokens = token_find_with_prefix($tokens, 'upload')) && !empty($node->files) && $upload = array_shift($node->files)) {
      $replacements += token_generate('file', $upload_tokens, array('file' => $upload), $options);
    }
  }

  return $replacements;
}
