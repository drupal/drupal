<?php

/**
 * @file
 * Definition of views_handler_filter_term_node_tid.
 */

namespace Drupal\taxonomy\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Tags;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("taxonomy_index_tid")
 */
class TaxonomyIndexTid extends ManyToOne {

  // Stores the exposed input for this filter.
  var $validated_exposed_input = NULL;

  /**
   * Overrides \Drupal\views\Plugin\views\filter\ManyToOne::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->definition['vocabulary'])) {
      $this->options['vid'] = $this->definition['vocabulary'];
    }
  }

  public function hasExtraOptions() { return TRUE; }

  public function getValueOptions() { /* don't overwrite the value options */ }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = array('default' => 'textfield');
    $options['limit'] = array('default' => TRUE, 'bool' => TRUE);
    $options['vid'] = array('default' => '');
    $options['hierarchy'] = array('default' => 0);
    $options['error_message'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    $options = array();
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    if ($this->options['limit']) {
      // We only do this when the form is displayed.
      if (empty($this->options['vid'])) {
        $first_vocabulary = reset($vocabularies);
        $this->options['vid'] = $first_vocabulary->id();
      }

      if (empty($this->definition['vocabulary'])) {
        $form['vid'] = array(
          '#type' => 'radios',
          '#title' => t('Vocabulary'),
          '#options' => $options,
          '#description' => t('Select which vocabulary to show terms for in the regular options.'),
          '#default_value' => $this->options['vid'],
        );
      }
    }

    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Selection type'),
      '#options' => array('select' => t('Dropdown'), 'textfield' => t('Autocomplete')),
      '#default_value' => $this->options['type'],
    );

    $form['hierarchy'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show hierarchy in dropdown'),
      '#default_value' => !empty($this->options['hierarchy']),
      '#states' => array(
        'visible' => array(
          ':input[name="options[type]"]' => array('value' => 'select'),
        ),
      ),
    );
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    $vocabulary = entity_load('taxonomy_vocabulary', $this->options['vid']);
    if (empty($vocabulary) && $this->options['limit']) {
      $form['markup'] = array(
        '#markup' => '<div class="form-item">' . t('An invalid vocabulary is selected. Please change it in the options.') . '</div>',
      );
      return;
    }

    if ($this->options['type'] == 'textfield') {
      $default = '';
      if ($this->value) {
        $terms = Term::loadMultiple(($this->value));
        foreach ($terms as $term) {
          if ($default) {
            $default .= ', ';
          }
          $default .= String::checkPlain(\Drupal::entityManager()->getTranslationFromContext($term)->label());
        }
      }

      $form['value'] = array(
        '#title' => $this->options['limit'] ? t('Select terms from vocabulary @voc', array('@voc' => $vocabulary->label())) : t('Select terms'),
        '#type' => 'textfield',
        '#default_value' => $default,
      );

      if ($this->options['limit']) {
        $form['value']['#autocomplete_route_name'] = 'taxonomy.autocomplete_vid';
        $form['value']['#autocomplete_route_parameters'] = array('taxonomy_vocabulary' => $vocabulary->id());
      }
    }
    else {
      if (!empty($this->options['hierarchy']) && $this->options['limit']) {
        $tree = taxonomy_get_tree($vocabulary->id(), 0, NULL, TRUE);
        $options = array();

        if ($tree) {
          foreach ($tree as $term) {
            $choice = new \stdClass();
            $choice->option = array($term->id() => str_repeat('-', $term->depth) . String::checkPlain(\Drupal::entityManager()->getTranslationFromContext($term)->label()));
            $options[] = $choice;
          }
        }
      }
      else {
        $options = array();
        $query = \Drupal::entityQuery('taxonomy_term')
          // @todo Sorting on vocabulary properties http://drupal.org/node/1821274
          ->sort('weight')
          ->sort('name')
          ->addTag('term_access');
        if ($this->options['limit']) {
          $query->condition('vid', $vocabulary->id());
        }
        $terms = Term::loadMultiple($query->execute());
        foreach ($terms as $term) {
          $options[$term->id()] = String::checkPlain(\Drupal::entityManager()->getTranslationFromContext($term)->label());
        }
      }

      $default_value = (array) $this->value;

      if ($exposed = $form_state->get('exposed')) {
        $identifier = $this->options['expose']['identifier'];

        if (!empty($this->options['expose']['reduce'])) {
          $options = $this->reduceValueOptions($options);

          if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
            $default_value = array();
          }
        }

        if (empty($this->options['expose']['multiple'])) {
          if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
            $default_value = 'All';
          }
          elseif (empty($default_value)) {
            $keys = array_keys($options);
            $default_value = array_shift($keys);
          }
          // Due to #1464174 there is a chance that array('') was saved in the admin ui.
          // Let's choose a safe default value.
          elseif ($default_value == array('')) {
            $default_value = 'All';
          }
          else {
            $copy = $default_value;
            $default_value = array_shift($copy);
          }
        }
      }
      $form['value'] = array(
        '#type' => 'select',
        '#title' => $this->options['limit'] ? t('Select terms from vocabulary @voc', array('@voc' => $vocabulary->label())) : t('Select terms'),
        '#multiple' => TRUE,
        '#options' => $options,
        '#size' => min(9, count($options)),
        '#default_value' => $default_value,
      );

      $user_input = $form_state->getUserInput();
      if ($exposed && isset($identifier) && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $default_value;
        $form_state->setUserInput($user_input);
      }
    }

    if (!$form_state->get('exposed')) {
      // Retain the helper option
      $this->helper->buildOptionsForm($form, $form_state);
    }
  }

  protected function valueValidate($form, FormStateInterface $form_state) {
    // We only validate if they've chosen the text field style.
    if ($this->options['type'] != 'textfield') {
      return;
    }

    $values = Tags::explode($form_state->getValue('options', 'value'));
    if ($tids = $this->validate_term_strings($form['value'], $values, $form_state)) {
      $form_state->setValue(array('options', 'value'), $tids);
    }
  }

  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // If view is an attachment and is inheriting exposed filters, then assume
    // exposed input has already been validated
    if (!empty($this->view->is_attachment) && $this->view->display_handler->usesExposed()) {
      $this->validated_exposed_input = (array) $this->view->exposed_raw_input[$this->options['expose']['identifier']];
    }

    // If it's non-required and there's no value don't bother filtering.
    if (!$this->options['expose']['required'] && empty($this->validated_exposed_input)) {
      return FALSE;
    }

    $rc = parent::acceptExposedInput($input);
    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }

    return $rc;
  }

  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];

    // We only validate if they've chosen the text field style.
    if ($this->options['type'] != 'textfield') {
      if ($form_state->getValue($identifier) != 'All')  {
        $this->validated_exposed_input = (array) $form_state->getValue($identifier);
      }
      return;
    }

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    $values = Tags::explode($form_state->getValue($identifier));

    $tids = $this->validate_term_strings($form[$identifier], $values, $form_state);
    if ($tids) {
      $this->validated_exposed_input = $tids;
    }
  }

  /**
   * Validate the user string. Since this can come from either the form
   * or the exposed filter, this is abstracted out a bit so it can
   * handle the multiple input sources.
   *
   * @param $form
   *   The form which is used, either the views ui or the exposed filters.
   * @param $values
   *   The taxonomy names which will be converted to tids.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The taxonomy ids fo all validated terms.
   */
  function validate_term_strings(&$form, $values, FormStateInterface $form_state) {
    if (empty($values)) {
      return array();
    }

    $tids = array();
    $names = array();
    $missing = array();
    foreach ($values as $value) {
      $missing[strtolower($value)] = TRUE;
      $names[] = $value;
    }

    if (!$names) {
      return FALSE;
    }

    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $names)
      ->condition('vid', $this->options['vid'])
      ->addTag('term_access');
    $terms = Term::loadMultiple($query->execute());
    foreach ($terms as $term) {
      unset($missing[strtolower(\Drupal::entityManager()->getTranslationFromContext($term)->label())]);
      $tids[] = $term->id();
    }

    if ($missing && !empty($this->options['error_message'])) {
      $form_state->setError($form, format_plural(count($missing), 'Unable to find term: @terms', 'Unable to find terms: @terms', array('@terms' => implode(', ', array_keys($missing)))));
    }
    elseif ($missing && empty($this->options['error_message'])) {
      $tids = array(0);
    }

    return $tids;
  }

  protected function valueSubmit($form, FormStateInterface $form_state) {
    // prevent array_filter from messing up our arrays in parent submit.
  }

  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    if ($this->options['type'] != 'select') {
      unset($form['expose']['reduce']);
    }
    $form['error_message'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display error message'),
      '#default_value' => !empty($this->options['error_message']),
    );
  }

  public function adminSummary() {
    // set up $this->value_options for the parent summary
    $this->value_options = array();

    if ($this->value) {
      $this->value = array_filter($this->value);
      $terms = Term::loadMultiple($this->value);
      foreach ($terms as $term) {
        $this->value_options[$term->id()] = String::checkPlain(\Drupal::entityManager()->getTranslationFromContext($term)->label());
      }
    }
    return parent::adminSummary();
  }

}
