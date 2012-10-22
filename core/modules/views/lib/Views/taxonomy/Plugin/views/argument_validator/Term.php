<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\argument_validator\Term.
 */

namespace Views\taxonomy\Plugin\views\argument_validator;

use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Validate whether an argument is an acceptable node.
 *
 * @Plugin(
 *   id = "taxonomy_term",
 *   module = "taxonomy",
 *   title = @Translation("Taxonomy term")
 *   )
 */
class Term extends ArgumentValidatorPluginBase {

  public function init(ViewExecutable $view, &$argument, $options) {
    parent::init($view, $argument, $options);

    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_vocabulary_get_names();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['vocabularies'] = array('default' => array());
    $options['type'] = array('default' => 'tid');
    $options['transform'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $vocabularies = taxonomy_vocabulary_get_names();
    $options = array();
    foreach ($vocabularies as $voc) {
      $options[$voc->machine_name] = check_plain($voc->name);
    }

    $form['vocabularies'] = array(
      '#type' => 'checkboxes',
      '#prefix' => '<div id="edit-options-validate-argument-vocabulary-wrapper">',
      '#suffix' => '</div>',
      '#title' => t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vocabularies'],
      '#description' => t('If you wish to validate for specific vocabularies, check them; if none are checked, all terms will pass.'),
    );

    $form['type'] = array(
      '#type' => 'select',
      '#title' => t('Filter value type'),
      '#options' => array(
        'tid' => t('Term ID'),
        'tids' => t('Term IDs separated by , or +'),
        'name' => t('Term name'),
        'convert' => t('Term name converted to Term ID'),
      ),
      '#default_value' => $this->options['type'],
      '#description' => t('Select the form of this filter value; if using term name, it is generally more efficient to convert it to a term ID and use Taxonomy: Term ID rather than Taxonomy: Term Name" as the filter.'),
    );

    $form['transform'] = array(
      '#type' => 'checkbox',
      '#title' => t('Transform dashes in URL to spaces in term name filter values'),
      '#default_value' => $this->options['transform'],
    );
  }

  public function submitOptionsForm(&$form, &$form_state, &$options = array()) {
    // Filter unselected items so we don't unnecessarily store giant arrays.
    $options['vocabularies'] = array_filter($options['vocabularies']);
  }

  function validate_argument($argument) {
    $vocabularies = array_filter($this->options['vocabularies']);
    $type = $this->options['type'];
    $transform = $this->options['transform'];

    switch ($type) {
      case 'tid':
        if (!is_numeric($argument)) {
          return FALSE;
        }
        // @todo Deal with missing addTag('term access') that was removed when
        // the db_select that was replaced by the entity_load.
        $term = entity_load('taxonomy_term', $argument);
        if (!$term) {
          return FALSE;
        }
        $this->argument->validated_title = check_plain($term->name);
        return empty($vocabularies) || !empty($vocabularies[$term->vocabulary_machine_name]);

      case 'tids':
        // An empty argument is not a term so doesn't pass.
        if (empty($argument)) {
          return FALSE;
        }

        $tids = new stdClass();
        $tids->value = $argument;
        $tids = $this->breakPhrase($argument, $tids);
        if ($tids->value == array(-1)) {
          return FALSE;
        }

        $test = drupal_map_assoc($tids->value);
        $titles = array();

        // check, if some tids already verified
        static $validated_cache = array();
        foreach ($test as $tid) {
          if (isset($validated_cache[$tid])) {
            if ($validated_cache[$tid] === FALSE) {
              return FALSE;
            }
            else {
              $titles[] = $validated_cache[$tid];
              unset($test[$tid]);
            }
          }
        }

        // if unverified tids left - verify them and cache results
        if (count($test)) {
          $result = entity_load_multiple('taxonomy_term', $test);
          foreach ($result as $term) {
            if ($vocabularies && empty($vocabularies[$term->vocabulary_machine_name])) {
              $validated_cache[$term->id()] = FALSE;
              return FALSE;
            }

            $titles[] = $validated_cache[$term->id()] = check_plain($term->name);
            unset($test[$term->id()]);
          }
        }

        // Remove duplicate titles
        $titles = array_unique($titles);

        $this->argument->validated_title = implode($tids->operator == 'or' ? ' + ' : ', ', $titles);
        // If this is not empty, we did not find a tid.
        return empty($test);

      case 'name':
      case 'convert':
        $terms = entity_load_multiple_by_properties('taxonomy_term', array('name' => $argument));
        $term = reset($terms);
        if ($transform) {
          $term->name = str_replace(' ', '-', $term->name);
        }

        if ($term && (empty($vocabularies) || !empty($vocabularies[$term->vocabulary_machine_name]))) {
          if ($type == 'convert') {
            $this->argument->argument = $term->id();
          }
          $this->argument->validated_title = check_plain($term->name);
          return TRUE;
        }
        return FALSE;
    }
  }

  function process_summary_arguments(&$args) {
    $type = $this->options['type'];
    $transform = $this->options['transform'];
    $vocabularies = array_filter($this->options['vocabularies']);

    if ($type == 'convert') {
      $arg_keys = array_flip($args);

      $result = entity_load_multiple('taxonomy_term', $args);

      if ($transform) {
        foreach ($result as $term) {
          $term->name = str_replace(' ', '-', $term->name);
        }
      }

      foreach ($result as $tid => $term) {
        $args[$arg_keys[$tid]] = $term;
      }
    }
  }

}
