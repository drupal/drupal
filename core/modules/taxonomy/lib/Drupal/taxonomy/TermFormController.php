<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermFormController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Base for controller for taxonomy term edit forms.
 */
class TermFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $term) {
    $vocabulary = taxonomy_vocabulary_load($term->vid);

    $parent = array_keys(taxonomy_term_load_parents($term->tid));
    $form_state['taxonomy']['parent'] = $parent;
    $form_state['taxonomy']['vocabulary'] = $vocabulary;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $term->name,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -5,
    );

    $form['description'] = array(
      '#type' => 'text_format',
      '#title' => t('Description'),
      '#default_value' => $term->description,
      '#format' => $term->format,
      '#weight' => 0,
    );
    $language_configuration = module_invoke('language', 'get_default_configuration', 'vocabulary', $vocabulary->machine_name);
    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Language'),
      '#languages' => LANGUAGE_ALL,
      '#default_value' => $term->langcode,
      '#access' => !is_null($language_configuration['language_hidden']) && !$language_configuration['language_hidden'],
    );

    $form['vocabulary_machine_name'] = array(
      '#type' => 'value',
      '#value' => isset($term->vocabulary_machine_name) ? $term->vocabulary_machine_name : $vocabulary->name,
    );

    $form['relations'] = array(
      '#type' => 'fieldset',
      '#title' => t('Relations'),
      '#collapsible' => TRUE,
      '#collapsed' => ($vocabulary->hierarchy != TAXONOMY_HIERARCHY_MULTIPLE),
      '#weight' => 10,
    );

    // taxonomy_get_tree and taxonomy_term_load_parents may contain large numbers of
    // items so we check for taxonomy_override_selector before loading the
    // full vocabulary. Contrib modules can then intercept before
    // hook_form_alter to provide scalable alternatives.
    if (!variable_get('taxonomy_override_selector', FALSE)) {
      $parent = array_keys(taxonomy_term_load_parents($term->tid));
      $children = taxonomy_get_tree($vocabulary->vid, $term->tid);

      // A term can't be the child of itself, nor of its children.
      foreach ($children as $child) {
        $exclude[] = $child->tid;
      }
      $exclude[] = $term->tid;

      $tree = taxonomy_get_tree($vocabulary->vid);
      $options = array('<' . t('root') . '>');
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
        '#title' => t('Parent terms'),
        '#options' => $options,
        '#default_value' => $parent,
        '#multiple' => TRUE,
      );
    }

    $form['relations']['weight'] = array(
      '#type' => 'textfield',
      '#title' => t('Weight'),
      '#size' => 6,
      '#default_value' => $term->weight,
      '#description' => t('Terms are displayed in ascending order by weight.'),
      '#required' => TRUE,
    );

    $form['vid'] = array(
      '#type' => 'value',
      '#value' => $vocabulary->vid,
    );

    $form['tid'] = array(
      '#type' => 'value',
      '#value' => $term->tid,
    );

    if (empty($term->tid)) {
      $form_state['redirect'] = current_path();
    }

    return parent::form($form, $form_state, $term);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    // Ensure numeric values.
    if (isset($form_state['values']['weight']) && !is_numeric($form_state['values']['weight'])) {
      form_set_error('weight', t('Weight value must be numeric.'));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    $term = parent::submit($form, $form_state);

    // Prevent leading and trailing spaces in term names.
    $term->name = trim($term->name);

    // Convert text_format field into values expected by taxonomy_term_save().
    $description = $form_state['values']['description'];
    $term->description = $description['value'];
    $term->format = $description['format'];

    return $term;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $term = $this->getEntity($form_state);

    $status = taxonomy_term_save($term);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(t('Created new term %term.', array('%term' => $term->label())));
        watchdog('taxonomy', 'Created new term %term.', array('%term' => $term->label()), WATCHDOG_NOTICE, l(t('edit'), 'taxonomy/term/' . $term->tid . '/edit'));
        break;
      case SAVED_UPDATED:
        drupal_set_message(t('Updated term %term.', array('%term' => $term->label())));
        watchdog('taxonomy', 'Updated term %term.', array('%term' => $term->label()), WATCHDOG_NOTICE, l(t('edit'), 'taxonomy/term/' . $term->tid . '/edit'));
        // Clear the page and block caches to avoid stale data.
        cache_invalidate(array('content' => TRUE));
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
      taxonomy_vocabulary_save($form_state['taxonomy']['vocabulary']);
    }

    $form_state['values']['tid'] = $term->tid;
    $form_state['tid'] = $term->tid;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    if (isset($_GET['destination'])) {
      $destination = drupal_get_destination();
      unset($_GET['destination']);
    }
    $term = $this->getEntity($form_state);
    $form_state['redirect'] = array('taxonomy/term/' . $term->tid . '/delete', array('query' => $destination));
  }
}
