<?php

/**
 * @file
 * Administrative page callbacks for the taxonomy module.
 */

use Drupal\taxonomy\Plugin\Core\Entity\Term;
use Drupal\taxonomy\Plugin\Core\Entity\Vocabulary;

/**
 * Page callback: provides the vocabulary creation form.
 */
function taxonomy_vocabulary_add() {
  $vocabulary = entity_create('taxonomy_vocabulary', array(
    // Default the new vocabulary to the site's default language. This is the
    // most likely default value until we have better flexible settings.
    'langcode' => language_default()->id,
  ));
  return Drupal::entityManager()->getForm($vocabulary);
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
    return taxonomy_vocabulary_confirm_reset_alphabetical($form, $form_state, $vocabulary->id());
  }

  $form_state['taxonomy']['vocabulary'] = $vocabulary;
  $parent_fields = FALSE;

  $page            = isset($_GET['page']) ? $_GET['page'] : 0;
  $page_increment  = config('taxonomy.settings')->get('terms_per_page_admin');  // Number of terms per page.
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
  $tree = taxonomy_get_tree($vocabulary->id(), 0, NULL, TRUE);
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
    $term_deltas[$term->id()] = isset($term_deltas[$term->id()]) ? $term_deltas[$term->id()] + 1 : 0;
    $key = 'tid:' . $term->id() . ':' . $term_deltas[$term->id()];

    // Keep track of the first term displayed on this page.
    if ($page_entries == 1) {
      $form['#first_tid'] = $term->id();
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
    $order = array_flip(array_keys($form_state['input']['terms'])); // Get the $_POST order.
    $current_page = array_merge($order, $current_page); // Update our form with the new order.
    foreach ($current_page as $key => $term) {
      // Verify this is a term for the current page and set at the current
      // depth.
      if (is_array($form_state['input']['terms'][$key]) && is_numeric($form_state['input']['terms'][$key]['term']['tid'])) {
        $current_page[$key]->depth = $form_state['input']['terms'][$key]['term']['depth'];
      }
      else {
        unset($current_page[$key]);
      }
    }
  }

  $errors = form_get_errors() != FALSE ? form_get_errors() : array();
  $destination = drupal_get_destination();
  $row_position = 0;
  // Build the actual form.
  $form['terms'] = array(
    '#type' => 'table',
    '#header' => array(t('Name'), t('Weight'), t('Operations')),
    '#empty' => t('No terms available. <a href="@link">Add term</a>.', array('@link' => url('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add'))),
    '#attributes' => array(
      'id' => 'taxonomy',
    ),
  );
  foreach ($current_page as $key => $term) {
    $form['terms'][$key]['#term'] = $term;
    $indentation = array();
    if (isset($term->depth) && $term->depth > 0) {
      $indentation = array(
        '#theme' => 'indentation',
        '#size' => $term->depth,
      );
    }
    $form['terms'][$key]['term'] = array(
      '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
      '#type' => 'link',
      '#title' => $term->label(),
      '#href' => "taxonomy/term/" . $term->id(),
    );
    if ($vocabulary->hierarchy != TAXONOMY_HIERARCHY_MULTIPLE && count($tree) > 1) {
      $parent_fields = TRUE;
      $form['terms'][$key]['term']['tid'] = array(
        '#type' => 'hidden',
        '#value' => $term->id(),
        '#attributes' => array(
          'class' => array('term-id'),
        ),
      );
      $form['terms'][$key]['term']['parent'] = array(
        '#type' => 'hidden',
        // Yes, default_value on a hidden. It needs to be changeable by the
        // javascript.
        '#default_value' => $term->parent->value,
        '#attributes' => array(
          'class' => array('term-parent'),
        ),
      );
      $form['terms'][$key]['term']['depth'] = array(
        '#type' => 'hidden',
        // Same as above, the depth is modified by javascript, so it's a
        // default_value.
        '#default_value' => $term->depth,
        '#attributes' => array(
          'class' => array('term-depth'),
        ),
      );
    }
    $form['terms'][$key]['weight'] = array(
      '#type' => 'weight',
      '#delta' => $delta,
      '#title_display' => 'invisible',
      '#title' => t('Weight for added term'),
      '#default_value' => $term->weight->value,
      '#attributes' => array(
        'class' => array('term-weight'),
      ),
    );
    $operations = array(
      'edit' => array(
        'title' => t('edit'),
        'href' => 'taxonomy/term/' . $term->id() . '/edit',
        'query' => $destination,
      ),
      'delete' => array(
        'title' => t('delete'),
        'href' => 'taxonomy/term/' . $term->id() . '/delete',
        'query' => $destination,
      ),
    );
    if (module_invoke('content_translation', 'translate_access', $term)) {
      $operations['translate'] = array(
        'title' => t('translate'),
        'href' => 'taxonomy/term/' . $term->id() . '/translations',
        'query' => $destination,
      );
    }
    $form['terms'][$key]['operations'] = array(
      '#type' => 'operations',
      '#links' => $operations,
    );

    $form['terms'][$key]['#attributes']['class'] = array();
    if ($parent_fields) {
      $form['terms'][$key]['#attributes']['class'][] = 'draggable';
    }

    // Add classes that mark which terms belong to previous and next pages.
    if ($row_position < $back_step || $row_position >= $page_entries - $forward_step) {
      $form['terms'][$key]['#attributes']['class'][] = 'taxonomy-term-preview';
    }

    if ($row_position !== 0 && $row_position !== count($tree) - 1) {
      if ($row_position == $back_step - 1 || $row_position == $page_entries - $forward_step - 1) {
        $form['terms'][$key]['#attributes']['class'][] = 'taxonomy-term-divider-top';
      }
      elseif ($row_position == $back_step || $row_position == $page_entries - $forward_step) {
        $form['terms'][$key]['#attributes']['class'][] = 'taxonomy-term-divider-bottom';
      }
    }

    // Add an error class if this row contains a form error.
    foreach ($errors as $error_key => $error) {
      if (strpos($error_key, $key) === 0) {
        $form['terms'][$key]['#attributes']['class'][] = 'error';
      }
    }
    $row_position++;
  }


  if ($parent_fields) {
    $form['terms']['#tabledrag'][] = array('match', 'parent', 'term-parent', 'term-parent', 'term-id', FALSE);
    $form['terms']['#tabledrag'][] = array('depth', 'group', 'term-depth', NULL, NULL, FALSE);
    $form['terms']['#attached']['library'][] = array('taxonomy', 'drupal.taxonomy');
    $form['terms']['#attached']['js'][] = array(
      'data' => array('taxonomy' => array('backStep' => $back_step, 'forwardStep' => $forward_step)),
      'type' => 'setting',
    );
  }
  $form['terms']['#tabledrag'][] = array('order', 'sibling', 'term-weight');

  if ($vocabulary->hierarchy != TAXONOMY_HIERARCHY_MULTIPLE && count($tree) > 1) {
    $form['actions'] = array('#type' => 'actions', '#tree' => FALSE);
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
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
  uasort($form_state['values']['terms'], 'drupal_sort_weight');

  $vocabulary = $form_state['taxonomy']['vocabulary'];
  // Update the current hierarchy type as we go.
  $hierarchy = TAXONOMY_HIERARCHY_DISABLED;

  $changed_terms = array();
  $tree = taxonomy_get_tree($vocabulary->id(), 0, NULL, TRUE);

  if (empty($tree)) {
    return;
  }

  // Build a list of all terms that need to be updated on previous pages.
  $weight = 0;
  $term = $tree[0];
  while ($term->id() != $form['#first_tid']) {
    if ($term->parent->value == 0 && $term->weight->value != $weight) {
      $term->weight->value = $weight;
      $changed_terms[$term->id()] = $term;
    }
    $weight++;
    $hierarchy = $term->parent->value != 0 ? TAXONOMY_HIERARCHY_SINGLE : $hierarchy;
    $term = $tree[$weight];
  }

  // Renumber the current page weights and assign any new parents.
  $level_weights = array();
  foreach ($form_state['values']['terms'] as $tid => $values) {
    if (isset($form['terms'][$tid]['#term'])) {
      $term = $form['terms'][$tid]['#term'];
      // Give terms at the root level a weight in sequence with terms on previous pages.
      if ($values['term']['parent'] == 0 && $term->weight->value != $weight) {
        $term->weight->value = $weight;
        $changed_terms[$term->id()] = $term;
      }
      // Terms not at the root level can safely start from 0 because they're all on this page.
      elseif ($values['term']['parent'] > 0) {
        $level_weights[$values['term']['parent']] = isset($level_weights[$values['term']['parent']]) ? $level_weights[$values['term']->parent->value] + 1 : 0;
        if ($level_weights[$values['term']['parent']] != $term->weight->value) {
          $term->weight->value = $level_weights[$values['term']['parent']];
          $changed_terms[$term->id()] = $term;
        }
      }
      // Update any changed parents.
      if ($values['term']['parent'] != $term->parent->value) {
        $term->parent->value = $values['term']['parent'];
        $changed_terms[$term->id()] = $term;
      }
      $hierarchy = $term->parent->value != 0 ? TAXONOMY_HIERARCHY_SINGLE : $hierarchy;
      $weight++;
    }
  }

  // Build a list of all terms that need to be updated on following pages.
  for ($weight; $weight < count($tree); $weight++) {
    $term = $tree[$weight];
    if ($term->parent->value == 0 && $term->weight->value != $weight) {
      $term->parent->value = $term->parent->value;
      $term->weight->value = $weight;
      $changed_terms[$term->id()] = $term;
    }
    $hierarchy = $term->parent->value != 0 ? TAXONOMY_HIERARCHY_SINGLE : $hierarchy;
  }

  // Save all updated terms.
  foreach ($changed_terms as $term) {
    $term->save();
  }

  // Update the vocabulary hierarchy to flat or single hierarchy.
  if ($vocabulary->hierarchy != $hierarchy) {
    $vocabulary->hierarchy = $hierarchy;
    $vocabulary->save();
  }
  drupal_set_message(t('The configuration options have been saved.'));
}

/**
 * Form builder for the term delete form.
 *
 * @ingroup forms
 * @see taxonomy_term_confirm_delete_submit()
 */
function taxonomy_term_confirm_delete($form, &$form_state, Term $term) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['tid'] = array('#type' => 'value', '#value' => $term->id());

  $form_state['taxonomy']['vocabulary'] = taxonomy_vocabulary_load($term->bundle());
  $form['type'] = array('#type' => 'value', '#value' => 'term');
  $form['name'] = array('#type' => 'value', '#value' => $term->label());
  $form['vid'] = array('#type' => 'value', '#value' => $term->bundle());
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
  entity_delete_multiple('taxonomy_term', array($form_state['values']['tid']));
  taxonomy_check_vocabulary_hierarchy($form_state['taxonomy']['vocabulary'], $form_state['values']);
  drupal_set_message(t('Deleted term %name.', array('%name' => $form_state['values']['name'])));
  watchdog('taxonomy', 'Deleted term %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE);
  if (!isset($_GET['destination'])) {
    $form_state['redirect'] = 'admin/structure/taxonomy';
  }
  cache_invalidate_tags(array('content' => TRUE));
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
      array('%title' => $vocabulary->label())),
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
  $form_state['taxonomy']['vocabulary']->delete();
  drupal_set_message(t('Deleted vocabulary %name.', array('%name' => $form_state['values']['name'])));
  watchdog('taxonomy', 'Deleted vocabulary %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/structure/taxonomy';
  cache_invalidate_tags(array('content' => TRUE));
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
  $form['name'] = array('#type' => 'value', '#value' => $vocabulary->name);
  $form['reset_alphabetical'] = array('#type' => 'value', '#value' => TRUE);
  return confirm_form($form,
    t('Are you sure you want to reset the vocabulary %title to alphabetical order?',
      array('%title' => $vocabulary->label())),
    'admin/structure/taxonomy/manage/' . $vocabulary->id(),
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
  $form_state['redirect'] = 'admin/structure/taxonomy/manage/' . $form_state['values']['vid'];
}
