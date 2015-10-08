<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid.
 */

namespace Drupal\taxonomy\Plugin\views\filter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a TaxonomyIndexTid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The term storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vocabularyStorage = $vocabulary_storage;
    $this->termStorage = $term_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('entity.manager')->getStorage('taxonomy_term')
    );
  }

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
    $options['limit'] = array('default' => TRUE);
    $options['vid'] = array('default' => '');
    $options['hierarchy'] = array('default' => FALSE);
    $options['error_message'] = array('default' => TRUE);

    return $options;
  }

  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $vocabularies = $this->vocabularyStorage->loadMultiple();
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
          '#title' => $this->t('Vocabulary'),
          '#options' => $options,
          '#description' => $this->t('Select which vocabulary to show terms for in the regular options.'),
          '#default_value' => $this->options['vid'],
        );
      }
    }

    $form['type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Selection type'),
      '#options' => array('select' => $this->t('Dropdown'), 'textfield' => $this->t('Autocomplete')),
      '#default_value' => $this->options['type'],
    );

    $form['hierarchy'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show hierarchy in dropdown'),
      '#default_value' => !empty($this->options['hierarchy']),
      '#states' => array(
        'visible' => array(
          ':input[name="options[type]"]' => array('value' => 'select'),
        ),
      ),
    );
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    if (empty($vocabulary) && $this->options['limit']) {
      $form['markup'] = array(
        '#markup' => '<div class="js-form-item form-item">' . $this->t('An invalid vocabulary is selected. Please change it in the options.') . '</div>',
      );
      return;
    }

    if ($this->options['type'] == 'textfield') {
      $terms = $this->value ? Term::loadMultiple(($this->value)) : array();
      $form['value'] = array(
        '#title' => $this->options['limit'] ? $this->t('Select terms from vocabulary @voc', array('@voc' => $vocabulary->label())) : $this->t('Select terms'),
        '#type' => 'textfield',
        '#default_value' => EntityAutocomplete::getEntityLabels($terms),
      );

      if ($this->options['limit']) {
        $form['value']['#type'] = 'entity_autocomplete';
        $form['value']['#target_type'] = 'taxonomy_term';
        $form['value']['#selection_settings']['target_bundles'] = array($vocabulary->id());
        $form['value']['#tags'] = TRUE;
        $form['value']['#process_default_value'] = FALSE;
      }
    }
    else {
      if (!empty($this->options['hierarchy']) && $this->options['limit']) {
        $tree = $this->termStorage->loadTree($vocabulary->id(), 0, NULL, TRUE);
        $options = array();

        if ($tree) {
          foreach ($tree as $term) {
            $choice = new \stdClass();
            $choice->option = array($term->id() => str_repeat('-', $term->depth) . \Drupal::entityManager()->getTranslationFromContext($term)->label());
            $options[] = $choice;
          }
        }
      }
      else {
        $options = array();
        $query = \Drupal::entityQuery('taxonomy_term')
          // @todo Sorting on vocabulary properties -
          //   https://www.drupal.org/node/1821274.
          ->sort('weight')
          ->sort('name')
          ->addTag('term_access');
        if ($this->options['limit']) {
          $query->condition('vid', $vocabulary->id());
        }
        $terms = Term::loadMultiple($query->execute());
        foreach ($terms as $term) {
          $options[$term->id()] = \Drupal::entityManager()->getTranslationFromContext($term)->label();
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
        '#title' => $this->options['limit'] ? $this->t('Select terms from vocabulary @voc', array('@voc' => $vocabulary->label())) : $this->t('Select terms'),
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

    $tids = array();
    foreach ($form_state->getValue(array('options', 'value')) as $value) {
      $tids[] = $value['target_id'];
    }
    $form_state->setValue(array('options', 'value'), $tids);
  }

  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }
    // We need to know the operator, which is normally set in
    // \Drupal\views\Plugin\views\filter\FilterPluginBase::acceptExposedInput(),
    // before we actually call the parent version of ourselves.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    // If view is an attachment and is inheriting exposed filters, then assume
    // exposed input has already been validated
    if (!empty($this->view->is_attachment) && $this->view->display_handler->usesExposed()) {
      $this->validated_exposed_input = (array) $this->view->exposed_raw_input[$this->options['expose']['identifier']];
    }

    // If we're checking for EMPTY or NOT, we don't need any input, and we can
    // say that our input conditions are met by just having the right operator.
    if ($this->operator == 'empty' || $this->operator == 'not empty') {
      return TRUE;
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

    foreach ($form_state->getValue($identifier) as $value) {
      $this->validated_exposed_input[] = $value['target_id'];
    }
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
      '#title' => $this->t('Display error message'),
      '#default_value' => !empty($this->options['error_message']),
    );
  }

  public function adminSummary() {
    // set up $this->valueOptions for the parent summary
    $this->valueOptions = array();

    if ($this->value) {
      $this->value = array_filter($this->value);
      $terms = Term::loadMultiple($this->value);
      foreach ($terms as $term) {
        $this->valueOptions[$term->id()] = \Drupal::entityManager()->getTranslationFromContext($term)->label();
      }
    }
    return parent::adminSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    // The result potentially depends on term access and so is just cacheable
    // per user.
    // @todo See https://www.drupal.org/node/2352175.
    $contexts[] = 'user';

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    $dependencies[$vocabulary->getConfigDependencyKey()][] = $vocabulary->getConfigDependencyName();

    foreach ($this->options['value'] as $tid) {
      $term = $this->termStorage->load($tid);
      $dependencies[$term->getConfigDependencyKey()][] = $term->getConfigDependencyName();
    }

    return $dependencies;
  }

}
