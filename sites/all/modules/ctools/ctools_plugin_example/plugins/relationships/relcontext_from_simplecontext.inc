<?php
// $Id: relcontext_from_simplecontext.inc,v 1.2 2010/01/29 19:53:48 merlinofchaos Exp $


/**
 * @file
 *
 * Sample relationship plugin.
 *
 * We take a simplecontext, look in it for what we need to make a relcontext, and make it.
 * In the real world, this might be getting a taxonomy id from a node, for example.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Relcontext from simplecontext"),
  'keyword' => 'relcontext',
  'description' => t('Adds a relcontext from existing simplecontext.'),
  'required context' => new ctools_context_required(t('Simplecontext'), 'simplecontext'),
  'context' => 'ctools_relcontext_from_simplecontext_context',
  'settings form' => 'ctools_relcontext_from_simplecontext_settings_form',
);

/**
 * Return a new context based on an existing context.
 */
function ctools_relcontext_from_simplecontext_context($context = NULL, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data)) {
    return ctools_context_create_empty('relcontext', NULL);
  }

  // You should do error-checking here.

  // Create the new context from some element of the parent context.
  // In this case, we'll pass in the whole context so it can be used to
  // create the relcontext.
  return ctools_context_create('relcontext', $context);
}

/**
 * Settings form for the relationship.
 */
function ctools_relcontext_from_simplecontext_settings_form($conf) {
  // We won't configure it in this case.
  return array();
}

