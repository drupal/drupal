<?php
// $Id: token.inc,v 1.4 2010/12/31 22:46:50 merlinofchaos Exp $

/**
 * @file
 *  Provide a global context to allow for token support.
 */

$plugin = array(
  'title' => t('Token'),
  'description' => t('A context that contains token replacements from token.module.'),
  'context' => 'ctools_context_create_token',  // func to create context
  'context name' => 'token',
  'keyword' => 'token',
  'convert list' => 'ctools_context_token_convert_list',
  'convert' => 'ctools_context_token_convert',
);

/**
 * Create a context from manual configuration.
 */
function ctools_context_create_token($empty, $data = NULL, $conf = FALSE) {
  $context = new ctools_context('token');
  $context->plugin = 'token';

  return $context;
}

/**
 * Implementation of hook_ctools_context_convert_list().
 */
function ctools_context_token_convert_list() {
  $tokens = token_info();
  foreach ($tokens['types'] as $type => $type_info) {
    if (empty($type_info['needs-data'])) {
      $real_type = isset($type_info['type']) ? $type_info['type'] : $type;
      foreach ($tokens['tokens'][$real_type] as $id => $info) {
        $key = "$type:$id";
        if (!isset($list[$key])) {
          $list[$key] = $type_info['name'] . ': ' . $info['name'];
        }
      }
    }
  }

  return $list;
}

/**
 * Implementation of hook_ctools_context_converter_alter().
 */
function ctools_context_token_convert($context, $token) {
  $tokens = token_info();
  list($type, $token) = explode(':', $token, 2);
  $real_type = isset($tokens['types'][$type]['type']) ? $tokens['types'][$type]['type'] : $type;
  if (isset($tokens['tokens'][$real_type][$token])) {
    $values = token_generate($type, array($token => $token));
    if (isset($values[$token])) {
      return $values[$token];
    }
  }
}
