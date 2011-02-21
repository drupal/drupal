<?php
// $Id: node_terms.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node terms'),
  'icon' => 'icon_node.png',
  'description' => t('Taxonomy terms of the referenced node.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
  'defaults' => array(
    'vid' => 0,
    'term_format' => 'term-links',
    'link' => TRUE,
    'term_delimiter' => ', ',
  ),
);

/**
 * Render the node_terms content type.
 */
function ctools_node_terms_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Get a shortcut to the node.
  $node = $context->data;

  if (empty($node->taxonomy)) {
    return;
  }

  $formatted_terms = '';
  if (empty($conf['vid']) && $conf['term_format'] == 'term-links') {
    // They want all terms, formatted as term links, so we can just use
    // taxonomy_link() directly.
    $terms = taxonomy_link('taxonomy terms', $node);
    $formatted_terms = theme('links', $terms);
  }
  else {
    // They want something special and custom, we'll have to do this ourselves.
    $terms = array();
    foreach ($context->data->taxonomy as $term) {
      if (empty($conf['vid']) || $term->vid == $conf['vid']) {
        if ($conf['term_format'] == 'term-links') {
          // We have to do this manually since taxonomy_link() doesn't let you
          // filter by vocabulary, so we just replicate it for the subset of
          // terms matching the requested vid.
          $terms['taxonomy_term_' . $term->tid] = array(
            'title' => $term->name,
            'href' => taxonomy_term_path($term),
            'attributes' => array('rel' => 'tag', 'title' => strip_tags($term->description)),
          );
        }
        elseif (empty($conf['link'])) {
          $terms[] = check_plain($term->name);
        }
        else {
          $terms[] = l($term->name, taxonomy_term_path($term), array('attributes' => array('rel' => 'tag', 'title' => strip_tags($term->description))));
        }
      }
    }

    switch ($conf['term_format']) {
      case 'term-links':
        // Since we didn't use taxonomy_link() directly, we need to invoke
        // hook_link_alter() for this to work as sites will expect.
        drupal_alter('link', $terms, $node);
        $formatted_terms = theme('links', $terms);
        break;

      case 'ul':
        $formatted_terms = theme('item_list', $terms);
        break;

      case 'inline-delimited':
        $delimiter = isset($conf['term_delimiter']) ? $conf['term_delimiter'] : ', ';
        $formatted_terms = implode($delimiter, $terms);
        break;

    }
  }

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'node_terms';
  $block->delta   = $node->nid;
  $block->title   = $type->title_label;
  $block->content = $formatted_terms;

  return $block;
}

/**
 * Returns an edit form for node terms display settings.
 *
 * The first question is if they want to display all terms or restrict it to a
 * specific taxonomy vocabulary.
 *
 * Then, they're presented with a set of radios to find out how they want the
 * terms formatted, which can be either be via theme('links'), a regular item
 * list (ul), or inline with a delimiter.  Depending on which radio they
 * choose, some other settings might appear. If they're doing either the ul or
 * inline, we ask if they want the terms to appear as links or not. If they
 * want it inline, we ask what delimiter they want to use.
 */
function ctools_node_terms_content_type_edit_form($form, &$form_state) {
  ctools_include('dependent');

  $conf = $form_state['conf'];

  $options = array();
  $options[0] = t('- All vocabularies -');
  foreach (taxonomy_get_vocabularies() as $vid => $vocabulary) {
    $options[$vid] = $vocabulary->name;
  }
  $form['vid'] = array(
    '#title' => t('Vocabulary'),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => $conf['vid'],
    '#description' => t('Optionally restrict the terms to a specific vocabulary, or allow terms from all vocabularies.'),
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
  );

  $form['term_format'] = array(
    '#type' => 'radios',
    '#title' => t('Term formatting'),
    '#options' => array(
      'term-links' => t("Taxonomy links (uses theme('links'))"),
      'ul' => t('Unordered list'),
      'inline-delimited' => t('Inline, delimited'),
    ),
    '#default_value' => $conf['term_format'],
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
  );

  $form['link'] = array(
    '#title' => t('Link to terms'),
    '#type' => 'checkbox',
    '#default_value' => $conf['link'],
    '#description' => t('Check here to make the terms link to the term paths.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:term_format' => array('inline-delimited', 'ul')),
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
  );

  $form['term_delimiter'] = array(
    '#type' => 'textfield',
    '#title' => t('Term delimiter'),
    '#default_value' => $conf['term_delimiter'],
    '#size' => 10,
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:term_format' => array('inline-delimited')),
  );
  return $form;
}

/**
 * Submit handler for the custom type settings form.
 */
function ctools_node_terms_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * Returns the administrative title for a type.
 */
function ctools_node_terms_content_type_admin_title($subtype, $conf, $context) {
  $placeholders['@s'] = $context->identifier;
  if (!empty($conf['vid'])) {
    $vocabulary = taxonomy_vocabulary_load($conf['vid']);
    $placeholders['@vocabulary'] = $vocabulary->name;
    return t('"@s" terms from @vocabulary', $placeholders);
  }
  return t('"@s" terms', $placeholders);
}
