<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * @file
 * Hooks provided by the Taxonomy module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on a newly created vocabulary.
 *
 * This hook runs after a new vocabulary object has just been instantiated. It
 * can be used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
 *   The vocabulary object.
 */
function hook_taxonomy_vocabulary_create(\Drupal\taxonomy\Entity\Vocabulary $vocabulary) {
  if (!isset($vocabulary->foo)) {
    $vocabulary->foo = NULL;
  }
}

/**
 * Act on taxonomy vocabularies when loaded.
 *
 * Modules implementing this hook can act on the vocabulary objects before they
 * are returned by entity_load_multiple().
 *
 * @param array $vocabularies
 *   An array of taxonomy vocabulary entities.
 */
function hook_taxonomy_vocabulary_load(array $vocabularies) {
  $result = db_select('mytable', 'm')
    ->fields('m', array('vid', 'foo'))
    ->condition('m.vid', array_keys($vocabularies), 'IN')
    ->execute();
  foreach ($result as $record) {
    $vocabularies[$record->vid]->foo = $record->foo;
  }
}

/**
 * Act on taxonomy vocabularies before they are saved.
 *
 * Modules implementing this hook can act on the vocabulary object before it is
 * inserted or updated.
 *
 * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
 *   A taxonomy vocabulary entity.
 */
function hook_taxonomy_vocabulary_presave(Drupal\taxonomy\Entity\Vocabulary $vocabulary) {
  $vocabulary->foo = 'bar';
}

/**
 * Act on taxonomy vocabularies when inserted.
 *
 * Modules implementing this hook can act on the vocabulary object when saved
 * to the database.
 *
 * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
 *   A taxonomy vocabulary entity.
 */
function hook_taxonomy_vocabulary_insert(Drupal\taxonomy\Entity\Vocabulary $vocabulary) {
  if ($vocabulary->id() == 'my_vocabulary') {
    $vocabulary->weight = 100;
  }
}

/**
 * Act on taxonomy vocabularies when updated.
 *
 * Modules implementing this hook can act on the vocabulary object when updated.
 *
 * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
 *   A taxonomy vocabulary entity.
 */
function hook_taxonomy_vocabulary_update(Drupal\taxonomy\Entity\Vocabulary $vocabulary) {
  db_update('mytable')
    ->fields(array('foo' => $vocabulary->foo))
    ->condition('vid', $vocabulary->id())
    ->execute();
}

/**
 * Act before taxonomy vocabulary deletion.
 *
 * This hook is invoked before entity_bundle_delete() is called and before
 * the vocabulary is actually removed.
 *
 * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
 *   The taxonomy vocabulary entity that is about to be deleted.
 *
 * @see hook_taxonomy_vocabulary_delete()
 */
function hook_taxonomy_vocabulary_predelete(Drupal\taxonomy\Entity\Vocabulary $vocabulary) {
  db_delete('mytable_index')
    ->condition('vid', $vocabulary->id())
    ->execute();
}

/**
 * Respond to taxonomy vocabulary deletion.
 *
 * This hook is invoked after entity_bundle_delete() has been called and after
 * the vocabulary has been removed.
 *
 * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
 *   The taxonomy vocabulary entity that has been deleted.
 *
 * @see hook_taxonomy_vocabulary_predelete()
 */
function hook_taxonomy_vocabulary_delete(Drupal\taxonomy\Entity\Vocabulary $vocabulary) {
  db_delete('mytable')
    ->condition('vid', $vocabulary->id())
    ->execute();
}

/**
 * Act on a newly created term.
 *
 * This hook runs after a new term object has just been instantiated. It can be
 * used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\taxonomy\Entity\Term $term
 *   The term object.
 */
function hook_taxonomy_term_create(\Drupal\taxonomy\Entity\Term $term) {
  if (!isset($term->foo)) {
    $term->foo = 'some_initial_value';
  }
}

/**
 * Act on taxonomy terms when loaded.
 *
 * Modules implementing this hook can act on the term objects returned by
 * entity_load_multiple().
 *
 * For performance reasons, information to be added to term objects should be
 * loaded in a single query for all terms where possible.
 *
 * Since terms are stored and retrieved from cache during a page request, avoid
 * altering properties provided by the {taxonomy_term_data} table, since this
 * may affect the way results are loaded from cache in subsequent calls.
 *
 * @param array $terms
 *   An array of taxonomy term entities, indexed by tid.
 */
function hook_taxonomy_term_load(array $terms) {
  $result = db_select('mytable', 'm')
    ->fields('m', array('tid', 'foo'))
    ->condition('m.tid', array_keys($terms), 'IN')
    ->execute();
  foreach ($result as $record) {
    $terms[$record->tid]->foo = $record->foo;
  }
}

/**
 * Act on taxonomy terms before they are saved.
 *
 * Modules implementing this hook can act on the term object before it is
 * inserted or updated.
 *
 * @param \Drupal\taxonomy\Term $term
 *   A taxonomy term entity.
 */
function hook_taxonomy_term_presave(Drupal\taxonomy\Term $term) {
  $term->foo = 'bar';
}

/**
 * Act on taxonomy terms when inserted.
 *
 * Modules implementing this hook can act on the term object when saved to
 * the database.
 *
 * @param \Drupal\taxonomy\Term $term
 *   A taxonomy term entity.
 */
function hook_taxonomy_term_insert(Drupal\taxonomy\Term $term) {
  db_insert('mytable')
    ->fields(array(
      'tid' => $term->id(),
      'foo' => $term->foo,
    ))
    ->execute();
}

/**
 * Act on taxonomy terms when updated.
 *
 * Modules implementing this hook can act on the term object when updated.
 *
 * @param \Drupal\taxonomy\Term $term
 *   A taxonomy term entity.
 */
function hook_taxonomy_term_update(Drupal\taxonomy\Term $term) {
  db_update('mytable')
    ->fields(array('foo' => $term->foo))
    ->condition('tid', $term->id())
    ->execute();
}

/**
 * Act before taxonomy term deletion.
 *
 * This hook is invoked from taxonomy term deletion before field values are
 * deleted and before the term is actually removed from the database.
 *
 * @param \Drupal\taxonomy\Term $term
 *   The taxonomy term entity that is about to be deleted.
 */
function hook_taxonomy_term_predelete(Drupal\taxonomy\Term $term) {
  db_delete('mytable_index')
    ->condition('tid', $term->id())
    ->execute();
}

/**
 * Respond to taxonomy term deletion.
 *
 * This hook is invoked from taxonomy term deletion after field values are
 * deleted and after the term has been removed from the database.
 *
 * @param \Drupal\taxonomy\Term $term
 *   The taxonomy term entity that has been deleted.
 */
function hook_taxonomy_term_delete(Drupal\taxonomy\Term $term) {
  db_delete('mytable')
    ->condition('tid', $term->id())
    ->execute();
}

/**
 * Act on a taxonomy term that is being assembled before rendering.
 *
 * The module may add elements to a taxonomy term's renderable array array prior
 * to rendering.

 * @param array &$build
 *   A renderable array representing the taxonomy term content.
 * @param \Drupal\taxonomy\Entity\Term $term
 *   The term that is being assembled for rendering.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the term
 *   components.
 * @param $view_mode
 *   The $view_mode parameter from taxonomy_term_view().
 * @param $langcode
 *   The language code used for rendering.
 *
 * @see hook_entity_view()
 */
function hook_taxonomy_term_view(array &$build, \Drupal\taxonomy\Entity\Term $term, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode, $langcode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // vocabulary in hook_entity_extra_field_info().
  if ($display->getComponent('mymodule_addition')) {
    $build['mymodule_addition'] = array(
      '#markup' => mymodule_addition($term),
      '#theme' => 'mymodule_my_additional_field',
    );
  }
}

/**
 * Alter the results of taxonomy_term_view().
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * taxonomy term content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the term rather than the
 * structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement
 * hook_preprocess_HOOK() for taxonomy-term.html.twig. See drupal_render() and
 * _theme() documentation respectively for details.
 *
 * @param array &$build
 *   A renderable array representing the taxonomy term content.
 * @param \Drupal\taxonomy\Entity\Term $term
 *   The taxonomy term being rendered.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the term
 *   components.
 *
 * @see hook_entity_view_alter()
 */
function hook_taxonomy_term_view_alter(array &$build, \Drupal\taxonomy\Entity\Term $term, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the term.
  $build['#post_render'][] = 'my_module_taxonomy_term_post_render';
}

/**
 * @} End of "addtogroup hooks".
 */
