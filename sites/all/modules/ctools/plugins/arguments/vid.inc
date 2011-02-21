<?php
// $Id: vid.inc,v 1.6 2010/01/29 20:18:01 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide an argument handler for a vocabulary id
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Vocabulary: ID"),
  // keyword to use for %substitution
  'keyword' => 'vocabulary',
  'description' => t('Creates a vocabulary context from a vocabulary ID argument.'),
  'context' => 'ctools_vid_context',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the vocabulary ID for this argument'),
  ),
);

/**
 * Discover if this argument gives us the vocabulary we crave.
 */
function ctools_vid_context($arg = NULL, $conf = NULL, $empty = FALSE) {
  // If unset it wants a generic, unfilled context.
  if ($empty) {
    return ctools_context_create_empty('vocabulary');
  }

  if (!is_numeric($arg)) {
    return NULL;
  }

  $vocabulary = taxonomy_vocabulary_load($arg);
  if (!$vocabulary) {
    return NULL;
  }

  return ctools_context_create('vocabulary', $vocabulary);
}

