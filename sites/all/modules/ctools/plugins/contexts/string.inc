<?php
// $Id: string.inc,v 1.8 2010/01/29 20:18:02 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide a string context
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('String'),
  'description' => t('A context that is just a string.'),
  'context' => 'ctools_context_create_string',
  'keyword' => 'string',
  'no ui' => TRUE,
  'context name' => 'string',
  'convert list' => array(
    'raw' => t('Raw string'),
    'html_safe' => t('HTML-safe string'),
  ),
  'convert' => 'ctools_context_string_convert',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the string for this context.'),
  ),
);

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_string($empty, $data = NULL, $conf = FALSE) {
  // The input is expected to be an object as created by ctools_break_phrase
  // which contains a group of string.

  $context = new ctools_context('string');
  $context->plugin = 'string';

  if ($empty) {
    return $context;
  }

  if ($data !== FALSE ) {
    $context->data = $data;
    $context->title = check_plain($data);
    return $context;
  }
}

/**
 * Convert a context into a string.
 */
function ctools_context_string_convert($context, $type) {
  switch ($type) {
    case 'raw':
      return $context->data;
    case 'html_safe':
      return check_plain($context->data);
  }
}

