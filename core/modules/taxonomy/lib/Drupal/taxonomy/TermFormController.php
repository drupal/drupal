<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermFormController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityFormControllerNG;
use Drupal\Core\Language\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for controller for taxonomy term edit forms.
 */
class TermFormController extends EntityFormControllerNG {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageControllerInterface
   */
  protected $vocabStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new TermFormController.
   *
   * @param \Drupal\taxonomy\VocabularyStorageControllerInterface $vocab_storage
   *   The vocabulary storage.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(VocabularyStorageControllerInterface $vocab_storage, ConfigFactory $config_factory) {
    $this->vocabStorage = $vocab_storage;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('taxonomy_vocabulary'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $term = $this->entity;
    $vocabulary = $this->vocabStorage->load($term->bundle());

    $parent = array_keys(taxonomy_term_load_parents($term->id()));
    $form_state['taxonomy']['parent'] = $parent;
    $form_state['taxonomy']['vocabulary'] = $vocabulary;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $term->name->value,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -5,
    );

    $form['description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $term->description->value,
      '#format' => $term->format->value,
      '#weight' => 0,
    );
    $language_configuration = $this->moduleHandler->moduleExists('language') ? language_get_default_configuration('taxonomy_term', $vocabulary->id()) : FALSE;
    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => Language::STATE_ALL,
      '#default_value' => $term->getUntranslated()->language()->id,
      '#access' => !empty($language_configuration['language_show']),
    );

    $form['relations'] = array(
      '#type' => 'details',
      '#title' => $this->t('Relations'),
      '#collapsed' => ($vocabulary->hierarchy != TAXONOMY_HIERARCHY_MULTIPLE),
      '#weight' => 10,
    );

    // taxonomy_get_tree and taxonomy_term_load_parents may contain large
    // numbers of items so we check for taxonomy.settings:override_selector
    // before loading the full vocabulary. Contrib modules can then intercept
    // before hook_form_alter to provide scalable alternatives.
    if (!$this->configFactory->get('taxonomy.settings')->get('override_selector')) {
      $parent = array_keys(taxonomy_term_load_parents($term->id()));
      $children = taxonomy_get_tree($vocabulary->id(), $term->id());

      // A term can't be the child of itself, nor of its children.
      foreach ($children as $child) {
        $exclude[] = $child->tid;
      }
      $exclude[] = $term->id();

      $tree = taxonomy_get_tree($vocabulary->id());
      $options = array('<' . $this->t('root') . '>');
      if (empty($parent)) {
        $parent = array(0);
      }
      foreach ($tree as $item) {
        if (!in_array($item->tid, $exclude)) {
          $options[$item->tid] = str_repeat('-', $item->depth) . $item->name;
        }
      }

      $form['relations']['parent'] = array(
        '#type' => 'select',
        '#title' => $this->t('Parent terms'),
        '#options' => $options,
        '#default_value' => $parent,
        '#multiple' => TRUE,
      );
    }

    $form['relations']['weight'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Weight'),
      '#size' => 6,
      '#default_value' => $term->weight->value,
      '#description' => $this->t('Terms are displayed in ascending order by weight.'),
      '#required' => TRUE,
    );

    $form['vid'] = array(
      '#type' => 'value',
      '#value' => $vocabulary->id(),
    );

    $form['tid'] = array(
      '#type' => 'value',
      '#value' => $term->id(),
    );

    if ($term->isNew()) {
      $form_state['redirect'] = current_path();
    }

    return parent::form($form, $form_state, $term);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    // Ensure numeric values.
    if (isset($form_state['values']['weight']) && !is_numeric($form_state['values']['weight'])) {
      form_set_error('weight', $this->t('Weight value must be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, array &$form_state) {
    $term = parent::buildEntity($form, $form_state);

    // Prevent leading and trailing spaces in term names.
    $term->name->value = trim($term->name->value);

    // Convert text_format field into values expected by
    // \Drupal\Core\Entity\Entity::save() method.
    $description = $form_state['values']['description'];
    $term->description->value = $description['value'];
    $term->format->value = $description['format'];

    // Assign parents with proper delta values starting from 0.
    $term->parent = array_keys($form_state['values']['parent']);

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $term = $this->entity;

    switch ($term->save()) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new term %term.', array('%term' => $term->label())));
        watchdog('taxonomy', 'Created new term %term.', array('%term' => $term->label()), WATCHDOG_NOTICE, l($this->t('edit'), 'taxonomy/term/' . $term->id() . '/edit'));
        break;
      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated term %term.', array('%term' => $term->label())));
        watchdog('taxonomy', 'Updated term %term.', array('%term' => $term->label()), WATCHDOG_NOTICE, l($this->t('edit'), 'taxonomy/term/' . $term->id() . '/edit'));
        // Clear the page and block caches to avoid stale data.
        Cache::invalidateTags(array('content' => TRUE));
        break;
    }

    $current_parent_count = count($form_state['values']['parent']);
    $previous_parent_count = count($form_state['taxonomy']['parent']);
    // Root doesn't count if it's the only parent.
    if ($current_parent_count == 1 && isset($form_state['values']['parent'][0])) {
      $current_parent_count = 0;
      $form_state['values']['parent'] = array();
    }

    // If the number of parents has been reduced to one or none, do a check on the
    // parents of every term in the vocabulary value.
    if ($current_parent_count < $previous_parent_count && $current_parent_count < 2) {
      taxonomy_check_vocabulary_hierarchy($form_state['taxonomy']['vocabulary'], $form_state['values']);
    }
    // If we've increased the number of parents and this is a single or flat
    // hierarchy, update the vocabulary immediately.
    elseif ($current_parent_count > $previous_parent_count && $form_state['taxonomy']['vocabulary']->hierarchy != TAXONOMY_HIERARCHY_MULTIPLE) {
      $form_state['taxonomy']['vocabulary']->hierarchy = $current_parent_count == 1 ? TAXONOMY_HIERARCHY_SINGLE : TAXONOMY_HIERARCHY_MULTIPLE;
      $form_state['taxonomy']['vocabulary']->save();
    }

    $form_state['values']['tid'] = $term->id();
    $form_state['tid'] = $term->id();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    if (isset($_GET['destination'])) {
      $destination = drupal_get_destination();
      unset($_GET['destination']);
    }
    $form_state['redirect'] = array('taxonomy/term/' . $this->entity->id() . '/delete', array('query' => $destination));
  }

}
