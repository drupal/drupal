<?php

/**
 * @file
 * Administrative page callbacks for the taxonomy module.
 */

use Drupal\taxonomy\Plugin\Core\Entity\Term;
use Drupal\taxonomy\Plugin\Core\Entity\Vocabulary;

/**
 * Form builder to list and manage vocabularies.
 *
 * @ingroup forms
 * @see taxonomy_overview_vocabularies_submit()
 * @see theme_taxonomy_overview_vocabularies()
 */
function taxonomy_overview_vocabularies($form) {
  $vocabularies = taxonomy_vocabulary_load_multiple();
  $form['#tree'] = TRUE;
  foreach ($vocabularies as $vocabulary) {
    $form[$vocabulary->vid]['#vocabulary'] = $vocabulary;
    $form[$vocabulary->vid]['name'] = array('#markup' => check_plain($vocabulary->name));
    $form[$vocabulary->vid]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $vocabulary->name)),
      '#title_display' => 'invisible',
      '#delta' => 10,
      '#default_value' => $vocabulary->weight,
    );
    $links = array();
    $links['edit'] = array(
      'title' => t('edit vocabulary'),
      'href' => "admin/structure/taxonomy/$vocabulary->machine_name/edit",
    );
    $links['list'] = array(
      'title' => t('list terms'),
      'href' => "admin/structure/taxonomy/$vocabulary->machine_name",
    );
    $links['add'] = array(
      'title' => t('add terms'),
      'href' => "admin/structure/taxonomy/$vocabulary->machine_name/add",
    );
    $form[$vocabulary->vid]['operations'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );
  }

  // Only make this form include a submit button and weight if more than one
  // vocabulary exists.
  if (count($vocabularies) > 1) {
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  }
  elseif (isset($vocabulary)) {
    unset($form[$vocabulary->vid]['weight']);
  }
  return $form;
}

/**
 * Submit handler for vocabularies overview. Updates changed vocabulary weights.
 *
 * @see taxonomy_overview_vocabularies()
 */
function taxonomy_overview_vocabularies_submit($form, &$form_state) {
  foreach ($form_state['values'] as $vid => $vocabulary) {
    if (is_numeric($vid) && $form[$vid]['#vocabulary']->weight != $form_state['values'][$vid]['weight']) {
      $form[$vid]['#vocabulary']->weight = $form_state['values'][$vid]['weight'];
      taxonomy_vocabulary_save($form[$vid]['#vocabulary']);
    }
  }
  drupal_set_message(t('The configuration options have been saved.'));
}

/**
 * Returns HTML for the vocabulary overview form as a sortable list of vocabularies.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see taxonomy_overview_vocabularies()
 * @ingroup themeable
 */
function theme_taxonomy_overview_vocabularies($variables) {
  $form = $variables['form'];

  $rows = array();

  foreach (element_children($form) as $key) {
    if (isset($form[$key]['name'])) {
      $vocabulary = &$form[$key];

      $row = array();
      $row[] = drupal_render($vocabulary['name']);
      if (isset($vocabulary['weight'])) {
        $vocabulary['weight']['#attributes']['class'] = array('vocabulary-weight');
        $row[] = drupal_render($vocabulary['weight']);
      }
      $row[] = drupal_render($vocabulary['operations']);
      $rows[] = array('data' => $row, 'class' => array('draggable'));
    }
  }

  $header = array(t('Vocabulary name'));
  if (isset($form['actions'])) {
    $header[] = t('Weight');
    drupal_add_tabledrag('taxonomy', 'order', 'sibling', 'vocabulary-weight');
  }
  $header[] = t('Operations');
  return theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No vocabularies available. <a href="@link">Add vocabulary</a>.', array('@link' => url('admin/structure/taxonomy/add'))), 'attributes' => array('id' => 'taxonomy'))) . drupal_render_children($form);
}

/**
 * Page callback: provides the vocabulary creation form.
 */
function taxonomy_vocabulary_add() {
  $vocabulary = entity_create('taxonomy_vocabulary', array(
    // Default the new vocabulary to the site's default language. This is the
    // most likely default value until we have better flexible settings.
    'langcode' => language_default()->langcode,
  ));
  return entity_get_form($vocabulary);
}

/**
 * Form builder for the taxonomy terms overview.
 *
 * Display a tree of all the terms in a vocabulary, with options to edit
 * each one. The form is made drag and drop by the theme function.
 *
 * @param Drupal\taxonomy\Plugin\Core\Entity\Vocabulary $vocabulary
 *   The taxonomy vocabulary entity to list terms for.
 *
 * @ingroup forms
 * @see taxonomy_overview_terms_submit()
 * @see theme_taxonomy_overview_terms()
 */
function taxonomy_overview_terms($form, &$form_state, Vocabulary $vocabulary) {
  global $pager_page_array, $pager_total, $pager_total_items;

  // Check for confirmation forms.
  if (isset($form_state['confirm_reset_alphabetical'])) {
    return taxonomy_vocabulary_confirm_reset_alphabetical($form, $form_state, $vocabulary->vid);
  }

  $form_state['taxonomy']['vocabulary'] = $vocabulary;
  $form['#tree'] = TRUE;
  $form['#parent_fields'] = FALSE;

  $page            = isset($_GET['page']) ? $_GET['page'] : 0;
  $page_increment  = variable_get('taxonomy_terms_per_page_admin', 100);  // Number of terms per page.
  $page_entries    = 0;   // Elements shown on this page.
  $before_entries  = 0;   // Elements at the root level before this page.
  $after_entries   = 0;   // Elements at the root level after this page.
  $root_entries    = 0;   // Elements at the root level on this page.

  // Terms from previous and next pages are shown if the term tree would have
  // been cut in the middle. Keep track of how many extra terms we show on each
  // page of terms.
  $back_step    = NULL;
  $forward_step = 0;

  // An array of the terms to be displayed on this page.
  $current_page = array();

  $delta = 0;
  $term_deltas = array();
  $tree = taxonomy_get_tree($vocabulary->vid, 0, NULL, TRUE);
  $term = current($tree);
  do {
    // In case this tree is completely empty.
    if (empty($term)) {
      break;
    }
    $delta++;
    // Count entries before the current page.
    if ($page && ($page * $page_increment) > $before_entries && !isset($back_step)) {
      $before_entries++;
      continue;
    }
    // Count entries after the current page.
    elseif ($page_entries > $page_increment && isset($complete_tree)) {
      $after_entries++;
      continue;
    }

    // Do not let a term start the page that is not at the root.
    if (isset($term->depth) && ($term->depth > 0) && !isset($back_step)) {
      $back_step = 0;
      while ($pterm = prev($tree)) {
        $before_entries--;
        $back_step++;
        if ($pterm->depth == 0) {
          prev($tree);
          continue 2; // Jump back to the start of the root level parent.
       }
      }
    }
    $back_step = isset($back_step) ? $back_step : 0;

    // Continue rendering the tree until we reach the a new root item.
    if ($page_entries >= $page_increment + $back_step + 1 && $term->depth == 0 && $root_entries > 1) {
      $complete_tree = TRUE;
      // This new item at the root level is the first item on the next page.
      $after_entries++;
      continue;
    }
    if ($page_entries >= $page_increment + $back_step) {
      $forward_step++;
    }

    // Finally, if we've gotten down this far, we're rendering a term on this page.
    $page_entries++;
    $term_deltas[$term->tid] = isset($term_deltas[$term->tid]) ? $term_deltas[$term->tid] + 1 : 0;
    $key = 'tid:' . $term->tid . ':' . $term_deltas[$term->tid];

    // Keep track of the first term displayed on this page.
    if ($page_entries == 1) {
      $form['#first_tid'] = $term->tid;
    }
    // Keep a variable to make sure at least 2 root elements are displayed.
    if ($term->parents[0] == 0) {
      $root_entries++;
    }
    $current_page[$key] = $term;
  } while ($term = next($tree));

  // Because we didn't use a pager query, set the necessary pager variables.
  $total_entries = $before_entries + $page_entries + $after_entries;
  $pager_total_items[0] = $total_entries;
  $pager_page_array[0] = $page;
  $pager_total[0] = ceil($total_entries / $page_increment);

  // If this form was already submitted once, it's probably hit a validation
  // error. Ensure the form is rebuilt in the same order as the user submitted.
  if (!empty($form_state['input'])) {
    $order = array_flip(array_keys($form_state['input'])); // Get the $_POST order.
    $current_page = array_merge($order, $current_page); // Update our form with the new order.
    foreach ($current_page as $key => $term) {
      // Verify this is a term for the current page and set at the current depth.
      if (is_array($form_state['input'][$key]) && is_numeric($form_state['input'][$key]['tid'])) {
        $current_page[$key]->depth = $form_state['input'][$key]['depth'];
      }
      else {
        unset($current_page[$key]);
      }
    }
  }

  // Build the actual form.
  foreach ($current_page as $key => $term) {
    // Save the term for the current page so we don't have to load it a second time.
    $form[$key]['#term'] = (array) $term;
    if (isset($term->parents)) {
      $form[$key]['#term']['parent'] = $term->parent = $term->parents[0];
      unset($form[$key]['#term']['parents'], $term->parents);
    }

    $form[$key]['view'] = array('#type' => 'link', '#title' => $term->label(), '#href' => "taxonomy/term/$term->tid");
    if ($vocabulary->hierarchy != TAXONOMY_HIERARCHY_MULTIPLE && count($tree) > 1) {
      $form['#parent_fields'] = TRUE;
      $form[$key]['tid'] = array(
        '#type' => 'hidden',
        '#value' => $term->tid
      );
      $form[$key]['parent'] = array(
        '#type' => 'hidden',
        // Yes, default_value on a hidden. It needs to be changeable by the javascript.
        '#default_value' => $term->parent,
      );
      $form[$key]['depth'] = array(
        '#type' => 'hidden',
        // Same as above, the depth is modified by javascript, so it's a default_value.
        '#default_value' => $term->depth,
      );
      $form[$key]['weight'] = array(
        '#type' => 'weight',
        '#delta' => $delta,
        '#title_display' => 'invisible',
        '#title' => t('Weight for added term'),
        '#default_value' => $term->weight,
      );
    }
    $operations = array(
      'edit' => array('title' => t('edit'), 'href' => 'taxonomy/term/' . $term->tid . '/edit', 'query' => drupal_get_destination()),
      'delete' => array('title' => t('delete'), 'href' => 'taxonomy/term/' . $term->tid . '/delete', 'query' => drupal_get_destination()),
    );
    $form[$key]['operations'] = array(
      '#type' => 'operations',
      '#links' => $operations,
    );
  }

  $form['#total_entries'] = $total_entries;
  $form['#page_increment'] = $page_increment;
  $form['#page_entries'] = $page_entries;
  $form['#back_step'] = $back_step;
  $form['#forward_step'] = $forward_step;
  $form['#empty_text'] = t('No terms available. <a href="@link">Add term</a>.', array('@link' => url('admin/structure/taxonomy/' . $vocabulary->machine_name . '/add')));

  if ($vocabulary->hierarchy != TAXONOMY_HIERARCHY_MULTIPLE && count($tree) > 1) {
    $form['actions'] = array('#type' => 'actions', '#tree' => FALSE);
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save')
    );
    $form['actions']['reset_alphabetical'] = array(
      '#type' => 'submit',
      '#value' => t('Reset to alphabetical')
    );
    $form_state['redirect'] = array(current_path(), (isset($_GET['page']) ? array('query' => array('page' => $_GET['page'])) : array()));
  }

  return $form;
}

/**
 * Submit handler for terms overview form.
 *
 * Rather than using a textfield or weight field, this form depends entirely
 * upon the order of form elements on the page to determine new weights.
 *
 * Because there might be hundreds or thousands of taxonomy terms that need to
 * be ordered, terms are weighted from 0 to the number of terms in the
 * vocabulary, rather than the standard -10 to 10 scale. Numbers are sorted
 * lowest to highest, but are not necessarily sequential. Numbers may be skipped
 * when a term has children so that reordering is minimal when a child is
 * added or removed from a term.
 *
 * @see taxonomy_overview_terms()
 */
function taxonomy_overview_terms_submit($form, &$form_state) {
  if ($form_state['triggering_element']['#value'] == t('Reset to alphabetical')) {
    // Execute the reset action.
    if ($form_state['values']['reset_alphabetical'] === TRUE) {
      return taxonomy_vocabulary_confirm_reset_alphabetical_submit($form, $form_state);
    }
    // Rebuild the form to confirm the reset action.
    $form_state['rebuild'] = TRUE;
    $form_state['confirm_reset_alphabetical'] = TRUE;
    return;
  }

  // Sort term order based on weight.
  uasort($form_state['values'], 'drupal_sort_weight');

  $vocabulary = $form_state['taxonomy']['vocabulary'];
  // Update the current hierarchy type as we go.
  $hierarchy = TAXONOMY_HIERARCHY_DISABLED;

  $changed_terms = array();
  $tree = taxonomy_get_tree($vocabulary->vid);

  if (empty($tree)) {
    return;
  }

  // Build a list of all terms that need to be updated on previous pages.
  $weight = 0;
  $term = (array) $tree[0];
  while ($term['tid'] != $form['#first_tid']) {
    if ($term['parents'][0] == 0 && $term['weight'] != $weight) {
      $term['parent'] = $term['parents'][0];
      $term['weight'] = $weight;
      $changed_terms[$term['tid']] = $term;
    }
    $weight++;
    $hierarchy = $term['parents'][0] != 0 ? TAXONOMY_HIERARCHY_SINGLE : $hierarchy;
    $term = (array) $tree[$weight];
  }

  // Renumber the current page weights and assign any new parents.
  $level_weights = array();
  foreach ($form_state['values'] as $tid => $values) {
    if (isset($form[$tid]['#term'])) {
      $term = $form[$tid]['#term'];
      // Give terms at the root level a weight in sequence with terms on previous pages.
      if ($values['parent'] == 0 && $term['weight'] != $weight) {
        $term['weight'] = $weight;
        $changed_terms[$term['tid']] = $term;
      }
      // Terms not at the root level can safely start from 0 because they're all on this page.
      elseif ($values['parent'] > 0) {
        $level_weights[$values['parent']] = isset($level_weights[$values['parent']]) ? $level_weights[$values['parent']] + 1 : 0;
        if ($level_weights[$values['parent']] != $term['weight']) {
          $term['weight'] = $level_weights[$values['parent']];
          $changed_terms[$term['tid']] = $term;
        }
      }
      // Update any changed parents.
      if ($values['parent'] != $term['parent']) {
        $term['parent'] = $values['parent'];
        $changed_terms[$term['tid']] = $term;
      }
      $hierarchy = $term['parent'] != 0 ? TAXONOMY_HIERARCHY_SINGLE : $hierarchy;
      $weight++;
    }
  }

  // Build a list of all terms that need to be updated on following pages.
  for ($weight; $weight < count($tree); $weight++) {
    $term = (array) $tree[$weight];
    if ($term['parents'][0] == 0 && $term['weight'] != $weight) {
      $term['parent'] = $term['parents'][0];
      $term['weight'] = $weight;
      $changed_terms[$term['tid']] = $term;
    }
    $hierarchy = $term['parents'][0] != 0 ? TAXONOMY_HIERARCHY_SINGLE : $hierarchy;
  }

  // Save all updated terms.
  foreach ($changed_terms as $changed) {
    $term = (object) $changed;
    // Update term_hierachy and term_data directly since we don't have a
    // fully populated term object to save.
    db_update('taxonomy_term_hierarchy')
      ->fields(array('parent' => $term->parent))
      ->condition('tid', $term->tid, '=')
      ->execute();

    db_update('taxonomy_term_data')
      ->fields(array('weight' => $term->weight))
      ->condition('tid', $term->tid, '=')
      ->execute();
  }

  // Update the vocabulary hierarchy to flat or single hierarchy.
  if ($vocabulary->hierarchy != $hierarchy) {
    $vocabulary->hierarchy = $hierarchy;
    taxonomy_vocabulary_save($vocabulary);
  }
  drupal_set_message(t('The configuration options have been saved.'));
}

/**
 * Returns HTML for a terms overview form as a sortable list of terms.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see taxonomy_overview_terms()
 * @ingroup themeable
 */
function theme_taxonomy_overview_terms($variables) {
  $form = $variables['form'];

  $page_increment  = $form['#page_increment'];
  $page_entries    = $form['#page_entries'];
  $back_step     = $form['#back_step'];
  $forward_step  = $form['#forward_step'];

  // Add drag and drop if parent fields are present in the form.
  if ($form['#parent_fields']) {
    drupal_add_tabledrag('taxonomy', 'match', 'parent', 'term-parent', 'term-parent', 'term-id', FALSE);
    drupal_add_tabledrag('taxonomy', 'depth', 'group', 'term-depth', NULL, NULL, FALSE);
    drupal_add_library('taxonomy', 'drupal.taxonomy');
    drupal_add_js(array('taxonomy' => array('backStep' => $back_step, 'forwardStep' => $forward_step)), 'setting');
  }
  drupal_add_tabledrag('taxonomy', 'order', 'sibling', 'term-weight');

  $errors = form_get_errors() != FALSE ? form_get_errors() : array();
  $rows = array();
  foreach (element_children($form) as $key) {
    if (isset($form[$key]['#term'])) {
      $term = &$form[$key];

      $row = array();
      $row[] = (isset($term['#term']['depth']) && $term['#term']['depth'] > 0 ? theme('indentation', array('size' => $term['#term']['depth'])) : ''). drupal_render($term['view']);
      if ($form['#parent_fields']) {
        $term['tid']['#attributes']['class'] = array('term-id');
        $term['parent']['#attributes']['class'] = array('term-parent');
        $term['depth']['#attributes']['class'] = array('term-depth');
        $row[0] .= drupal_render($term['parent']) . drupal_render($term['tid']) . drupal_render($term['depth']);
      }
      $term['weight']['#attributes']['class'] = array('term-weight');
      $row[] = drupal_render($term['weight']);
      $row[] = drupal_render($term['operations']);
      $row = array('data' => $row);
      $rows[$key] = $row;
    }
  }

  // Add necessary classes to rows.
  $row_position = 0;
  foreach ($rows as $key => $row) {
    $rows[$key]['class'] = array();
    if (isset($form['#parent_fields'])) {
      $rows[$key]['class'][] = 'draggable';
    }

    // Add classes that mark which terms belong to previous and next pages.
    if ($row_position < $back_step || $row_position >= $page_entries - $forward_step) {
      $rows[$key]['class'][] = 'taxonomy-term-preview';
    }

    if ($row_position !== 0 && $row_position !== count($rows) - 1) {
      if ($row_position == $back_step - 1 || $row_position == $page_entries - $forward_step - 1) {
        $rows[$key]['class'][] = 'taxonomy-term-divider-top';
      }
      elseif ($row_position == $back_step || $row_position == $page_entries - $forward_step) {
        $rows[$key]['class'][] = 'taxonomy-term-divider-bottom';
      }
    }

    // Add an error class if this row contains a form error.
    foreach ($errors as $error_key => $error) {
      if (strpos($error_key, $key) === 0) {
        $rows[$key]['class'][] = 'error';
      }
    }
    $row_position++;
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => $form['#empty_text'], 'colspan' => '3'));
  }

  $header = array(t('Name'), t('Weight'), t('Operations'));
  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'taxonomy')));
  $output .= drupal_render_children($form);
  $output .= theme('pager');

  return $output;
}

/**
 * Returns a rendered edit form to create a new term associated to the given vocabulary.
 */
function taxonomy_term_add($vocabulary) {
  $term = entity_create('taxonomy_term', array('vid' => $vocabulary->vid, 'vocabulary_machine_name' => $vocabulary->machine_name));
  if (module_exists('language')) {
    $term->langcode = language_get_default_langcode('taxonomy_term', $vocabulary->machine_name);
  }
  return entity_get_form($term);
}

/**
 * Form builder for the term delete form.
 *
 * @ingroup forms
 * @see taxonomy_term_confirm_delete_submit()
 */
function taxonomy_term_confirm_delete($form, &$form_state, Term $term) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['tid'] = array('#type' => 'value', '#value' => $term->tid);

  $form_state['taxonomy']['vocabulary'] = taxonomy_vocabulary_load($term->vid);;
  $form['type'] = array('#type' => 'value', '#value' => 'term');
  $form['name'] = array('#type' => 'value', '#value' => $term->name);
  $form['vocabulary_machine_name'] = array('#type' => 'value', '#value' => $term->vocabulary_machine_name);
  $form['delete'] = array('#type' => 'value', '#value' => TRUE);
  return confirm_form($form,
    t('Are you sure you want to delete the term %title?',
    array('%title' => $term->label())),
    'admin/structure/taxonomy',
    t('Deleting a term will delete all its children if there are any. This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}

/**
 * Submit handler to delete a term after confirmation.
 *
 * @see taxonomy_term_confirm_delete()
 */
function taxonomy_term_confirm_delete_submit($form, &$form_state) {
  taxonomy_term_delete($form_state['values']['tid']);
  taxonomy_check_vocabulary_hierarchy($form_state['taxonomy']['vocabulary'], $form_state['values']);
  drupal_set_message(t('Deleted term %name.', array('%name' => $form_state['values']['name'])));
  watchdog('taxonomy', 'Deleted term %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE);
  if (!isset($_GET['destination'])) {
    $form_state['redirect'] = 'admin/structure/taxonomy';
  }
  cache_invalidate(array('content' => TRUE));
  return;
}

/**
 * Form builder for the vocabulary delete confirmation form.
 *
 * @ingroup forms
 * @see taxonomy_vocabulary_confirm_delete_submit()
 */
function taxonomy_vocabulary_confirm_delete($form, &$form_state, $vid) {
  $vocabulary = taxonomy_vocabulary_load($vid);

  // Always provide entity id in the same form key as in the entity edit form.
  $form['vid'] = array('#type' => 'value', '#value' => $vid);

  $form_state['taxonomy']['vocabulary'] = $vocabulary;
  $form['#id'] = 'taxonomy_vocabulary_confirm_delete';
  $form['type'] = array('#type' => 'value', '#value' => 'vocabulary');
  $form['name'] = array('#type' => 'value', '#value' => $vocabulary->name);
  $form['#submit'] = array('taxonomy_vocabulary_confirm_delete_submit');
  return confirm_form($form,
    t('Are you sure you want to delete the vocabulary %title?',
    array('%title' => $vocabulary->name)),
    'admin/structure/taxonomy',
    t('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}

/**
 * Submit handler to delete a vocabulary after confirmation.
 *
 * @see taxonomy_vocabulary_confirm_delete()
 */
function taxonomy_vocabulary_confirm_delete_submit($form, &$form_state) {
  $status = taxonomy_vocabulary_delete($form_state['values']['vid']);
  drupal_set_message(t('Deleted vocabulary %name.', array('%name' => $form_state['values']['name'])));
  watchdog('taxonomy', 'Deleted vocabulary %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/structure/taxonomy';
  cache_invalidate(array('content' => TRUE));
  return;
}

/**
 * Form builder to confirm resetting a vocabulary to alphabetical order.
 *
 * @ingroup forms
 * @see taxonomy_vocabulary_confirm_reset_alphabetical_submit()
 */
function taxonomy_vocabulary_confirm_reset_alphabetical($form, &$form_state, $vid) {
  $vocabulary = taxonomy_vocabulary_load($vid);

  $form['type'] = array('#type' => 'value', '#value' => 'vocabulary');
  $form['vid'] = array('#type' => 'value', '#value' => $vid);
  $form['machine_name'] = array('#type' => 'value', '#value' => $vocabulary->machine_name);
  $form['name'] = array('#type' => 'value', '#value' => $vocabulary->name);
  $form['reset_alphabetical'] = array('#type' => 'value', '#value' => TRUE);
  return confirm_form($form,
                  t('Are you sure you want to reset the vocabulary %title to alphabetical order?',
                  array('%title' => $vocabulary->name)),
                  'admin/structure/taxonomy/' . $vocabulary->machine_name,
                  t('Resetting a vocabulary will discard all custom ordering and sort items alphabetically.'),
                  t('Reset to alphabetical'),
                  t('Cancel'));
}

/**
 * Submit handler to reset a vocabulary to alphabetical order after confirmation.
 *
 * @see taxonomy_vocabulary_confirm_reset_alphabetical()
 */
function taxonomy_vocabulary_confirm_reset_alphabetical_submit($form, &$form_state) {
  db_update('taxonomy_term_data')
    ->fields(array('weight' => 0))
    ->condition('vid', $form_state['values']['vid'])
    ->execute();
  drupal_set_message(t('Reset vocabulary %name to alphabetical order.', array('%name' => $form_state['values']['name'])));
  watchdog('taxonomy', 'Reset vocabulary %name to alphabetical order.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/structure/taxonomy/' . $form_state['values']['machine_name'];
}
