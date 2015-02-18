<?php

/**
 * @file
 * Hooks provided by the Taxonomy module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on taxonomy vocabularies when loaded.
 *
 * Modules implementing this hook can act on the vocabulary objects before they
 * are returned by taxonomy_vocabulary_load_multiple().
 *
 * @param $vocabulary
 *   An array of taxonomy vocabulary objects.
 */
function hook_taxonomy_vocabulary_load($vocabularies) {
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
 * @param $vocabulary
 *   A taxonomy vocabulary object.
 */
function hook_taxonomy_vocabulary_presave($vocabulary) {
  $vocabulary->foo = 'bar';
}

/**
 * Act on taxonomy vocabularies when inserted.
 *
 * Modules implementing this hook can act on the vocabulary object when saved
 * to the database.
 *
 * @param $vocabulary
 *   A taxonomy vocabulary object.
 */
function hook_taxonomy_vocabulary_insert($vocabulary) {
  if ($vocabulary->machine_name == 'my_vocabulary') {
    $vocabulary->weight = 100;
  }
}

/**
 * Act on taxonomy vocabularies when updated.
 *
 * Modules implementing this hook can act on the vocabulary object when updated.
 *
 * @param $vocabulary
 *   A taxonomy vocabulary object.
 */
function hook_taxonomy_vocabulary_update($vocabulary) {
  db_update('mytable')
    ->fields(array('foo' => $vocabulary->foo))
    ->condition('vid', $vocabulary->vid)
    ->execute();
}

/**
 * Respond to the deletion of taxonomy vocabularies.
 *
 * Modules implementing this hook can respond to the deletion of taxonomy
 * vocabularies from the database.
 *
 * @param $vocabulary
 *   A taxonomy vocabulary object.
 */
function hook_taxonomy_vocabulary_delete($vocabulary) {
  db_delete('mytable')
    ->condition('vid', $vocabulary->vid)
    ->execute();
}

/**
 * Act on taxonomy terms when loaded.
 *
 * Modules implementing this hook can act on the term objects returned by
 * taxonomy_term_load_multiple().
 *
 * For performance reasons, information to be added to term objects should be
 * loaded in a single query for all terms where possible.
 *
 * Since terms are stored and retrieved from cache during a page request, avoid
 * altering properties provided by the {taxonomy_term_data} table, since this
 * may affect the way results are loaded from cache in subsequent calls.
 *
 * @param $terms
 *   An array of term objects, indexed by tid.
 */
function hook_taxonomy_term_load($terms) {
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
 * @param $term
 *   A term object.
 */
function hook_taxonomy_term_presave($term) {
  $term->foo = 'bar';
}

/**
 * Act on taxonomy terms when inserted.
 *
 * Modules implementing this hook can act on the term object when saved to
 * the database.
 *
 * @param $term
 *   A taxonomy term object.
 */
function hook_taxonomy_term_insert($term) {
  db_insert('mytable')
    ->fields(array(
      'tid' => $term->tid,
      'foo' => $term->foo,
    ))
    ->execute();
}

/**
 * Act on taxonomy terms when updated.
 *
 * Modules implementing this hook can act on the term object when updated.
 *
 * @param $term
 *   A taxonomy term object.
 */
function hook_taxonomy_term_update($term) {
  db_update('mytable')
    ->fields(array('foo' => $term->foo))
    ->condition('tid', $term->tid)
    ->execute();
}

/**
 * Respond to the deletion of taxonomy terms.
 *
 * Modules implementing this hook can respond to the deletion of taxonomy
 * terms from the database.
 *
 * @param $term
 *   A taxonomy term object.
 */
function hook_taxonomy_term_delete($term) {
  db_delete('mytable')
    ->condition('tid', $term->tid)
    ->execute();
}

/**
 * Act on a taxonomy term that is being assembled before rendering.
 *
 * The module may add elements to $term->content prior to rendering. The
 * structure of $term->content is a renderable array as expected by
 * drupal_render().
 *
 * @param $term
 *   The term that is being assembled for rendering.
 * @param $view_mode
 *   The $view_mode parameter from taxonomy_term_view().
 * @param $langcode
 *   The language code used for rendering.
 *
 * @see hook_entity_view()
 */
function hook_taxonomy_term_view($term, $view_mode, $langcode) {
  $term->content['my_additional_field'] = array(
    '#markup' => $additional_field,
    '#weight' => 10,
    '#theme' => 'mymodule_my_additional_field',
  );
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
 * hook_preprocess_taxonomy_term(). See drupal_render() and theme()
 * documentation respectively for details.
 *
 * @param $build
 *   A renderable array representing the term.
 *
 * @see hook_entity_view_alter()
 */
function hook_taxonomy_term_view_alter(&$build) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the term.
  $build['#post_render'][] = 'my_module_node_post_render';
}

/**
 * @} End of "addtogroup hooks".
 */
