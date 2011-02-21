<?php
// $Id: views_plugin_argument_validate_taxonomy_term.inc,v 1.6.4.8 2010/12/14 22:32:11 dereine Exp $
/**
 * @file
 * Contains the 'taxonomy term' argument validator plugin.
 */

/**
 * Validate whether an argument is an acceptable node.
 */
class views_plugin_argument_validate_taxonomy_term extends views_plugin_argument_validate {
  function init(&$view, &$argument, &$options) {
    parent::init($view, $argument, $options);

    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_get_vocabularies();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['vocabularies'] = array('default' => array());
    $options['type'] = array('default' => 'tid');
    $options['transform'] = array('default' => FALSE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $vocabularies = taxonomy_get_vocabularies();
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
      '#title' => t('Argument type'),
      '#options' => array(
        'tid' => t('Term ID'),
        'tids' => t('Term IDs separated by , or +'),
        'name' => t('Term name'),
        'convert' => t('Term name converted to Term ID'),
      ),
      '#default_value' => $this->options['type'],
      '#description' => t('Select the form of this argument; if using term name, it is generally more efficient to convert it to a term ID and use Taxonomy: Term ID rather than Taxonomy: Term Name" as an argument.'),
    );

    $form['transform'] = array(
      '#type' => 'checkbox',
      '#title' => t('Transform dashes in URL to spaces in term name arguments'),
      '#default_value' => $this->options['transform'],
    );
  }

  function options_submit(&$form, &$form_state, &$options) {
    // filter trash out of the options so we don't store giant unnecessary arrays
    $options['vocabularies'] = array_filter($options['vocabularies']);
  }

  function convert_options(&$options) {
    if (!isset($options['vocabularies']) && !empty($this->argument->options['validate_argument_vocabulary'])) {
      $options['vocabularies'] = $this->argument->options['validate_argument_vocabulary'];
      $options['type'] = $this->argument->options['validate_argument_type'];
      $options['transform'] = isset($this->argument->options['validate_argument_transform']) ? $this->argument->options['validate_argument_transform'] : FALSE;
    }
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

        $query = db_select('taxonomy_term_data', 'td');
        $query->leftJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
        $query->fields('td');
        $query->fields('tv', array('machine_name'));
        $query->condition('td.tid', $argument);
        $query->addTag('term_access');
        $term = $query->execute()->fetchObject();
        if (!$term) {
          return FALSE;
        }
        return empty($vocabularies) || !empty($vocabularies[$term->machine_name]);

      case 'tids':
        $tids = new stdClass();
        $tids->value = $argument;
        $tids = views_break_phrase($argument, $tids);
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
          $query = db_select('taxonomy_term_data', 'td');
          $query->leftJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
          $query->fields('td');
          $query->fields('tv', array('machine_name'));
          $query->condition('td.tid', $test);

          $result = $query->execute();

          foreach ($result as $term) {
            if ($vocabularies && empty($vocabularies[$term->machine_name])) {
              $validated_cache[$term->tid] = FALSE;
              return FALSE;
            }

            $titles[] = $validated_cache[$term->tid] = check_plain($term->name);
            unset($test[$term->tid]);
          }
        }

        // Remove duplicate titles
        $titles = array_unique($titles);

        $this->argument->validated_title = implode($tids->operator == 'or' ? ' + ' : ', ', $titles);
        // If this is not empty, we did not find a tid.
        return empty($test);

      case 'name':
      case 'convert':
        $query = db_select('taxonomy_term_data', 'td');
        $query->leftJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
        $query->fields('td');
        $query->fields('tv', array('machine_name'));
        if (!empty($vocabularies)) {
          $query->condition('tv.machine_name', $vocabularies);
        }
        if ($transform) {
          $query->where("replace(td.name, ' ', '-') = :name", array(':name' => $argument));
        }
        else {
          $query->condition('td.name', $argument);
        }
        $term = $query->execute()->fetchObject();

        if ($term && (empty($vocabularies) || !empty($vocabularies[$term->machine_name]))) {
          if ($type == 'convert') {
            $this->argument->argument = $term->tid;
            $this->argument->validated_title = check_plain($term->name);
          }
          return TRUE;
        }
        return FALSE;
    }
  }
}
