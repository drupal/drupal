<?php

/**
 * @file
 * Administrative page callbacks for the Forum module.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Form constructor for the forum overview form.
 *
 * Returns a form for controlling the hierarchy of existing forums and
 * containers.
 *
 * @see forum_menu()
 * @ingroup forms
 */
function forum_overview($form, &$form_state) {
  module_load_include('inc', 'taxonomy', 'taxonomy.admin');
  $config = Drupal::config('forum.settings');

  $vid = $config->get('vocabulary');
  $vocabulary = entity_load('taxonomy_vocabulary', $vid);
  $form = taxonomy_overview_terms($form, $form_state, $vocabulary);

  foreach (element_children($form['terms']) as $key) {
    if (isset($form['terms'][$key]['#term'])) {
      $term = $form['terms'][$key]['#term'];
      $form['terms'][$key]['term']['#href'] = 'forum/' . $term->id();
      unset($form['terms'][$key]['operations']['#links']['delete']);
      if (in_array($form['terms'][$key]['#term']->id(), $config->get('containers'))) {
        $form['terms'][$key]['operations']['#links']['edit']['title'] = t('edit container');
        $form['terms'][$key]['operations']['#links']['edit']['href'] = 'admin/structure/forum/edit/container/' . $term->id();
        // We don't want the redirect from the link so we can redirect the
        // delete action.
        unset($form['terms'][$key]['operations']['#links']['edit']['query']['destination']);
      }
      else {
        $form['terms'][$key]['operations']['#links']['edit']['title'] = t('edit forum');
        $form['terms'][$key]['operations']['#links']['edit']['href'] = 'admin/structure/forum/edit/forum/' . $term->id();
        // We don't want the redirect from the link so we can redirect the
        // delete action.
        unset($form['terms'][$key]['operations']['#links']['edit']['query']['destination']);
      }
    }
  }

  // Remove the alphabetical reset.
  unset($form['actions']['reset_alphabetical']);

  // The form needs to have submit and validate handlers set explicitly.
  $form['#submit'] = array('taxonomy_overview_terms_submit'); // Use the existing taxonomy overview submit handler.
  $form['terms']['#empty'] = t('No containers or forums available. <a href="@container">Add container</a> or <a href="@forum">Add forum</a>.', array('@container' => url('admin/structure/forum/add/container'), '@forum' => url('admin/structure/forum/add/forum')));
  return $form;
}
