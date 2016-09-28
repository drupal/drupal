<?php

namespace Drupal\taxonomy;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base for handler for taxonomy term edit forms.
 */
class TermForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $term = $this->entity;
    $vocab_storage = $this->entityManager->getStorage('taxonomy_vocabulary');
    $taxonomy_storage = $this->entityManager->getStorage('taxonomy_term');
    $vocabulary = $vocab_storage->load($term->bundle());

    $parent = array_keys($taxonomy_storage->loadParents($term->id()));
    $form_state->set(['taxonomy', 'parent'], $parent);
    $form_state->set(['taxonomy', 'vocabulary'], $vocabulary);

    $form['relations'] = array(
      '#type' => 'details',
      '#title' => $this->t('Relations'),
      '#open' => $vocabulary->getHierarchy() == VocabularyInterface::HIERARCHY_MULTIPLE,
      '#weight' => 10,
    );

    // \Drupal\taxonomy\TermStorageInterface::loadTree() and
    // \Drupal\taxonomy\TermStorageInterface::loadParents() may contain large
    // numbers of items so we check for taxonomy.settings:override_selector
    // before loading the full vocabulary. Contrib modules can then intercept
    // before hook_form_alter to provide scalable alternatives.
    if (!$this->config('taxonomy.settings')->get('override_selector')) {
      $parent = array_keys($taxonomy_storage->loadParents($term->id()));
      $children = $taxonomy_storage->loadTree($vocabulary->id(), $term->id());

      // A term can't be the child of itself, nor of its children.
      foreach ($children as $child) {
        $exclude[] = $child->tid;
      }
      $exclude[] = $term->id();

      $tree = $taxonomy_storage->loadTree($vocabulary->id());
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
      '#default_value' => $term->getWeight(),
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

    return parent::form($form, $form_state, $term);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Ensure numeric values.
    if ($form_state->hasValue('weight') && !is_numeric($form_state->getValue('weight'))) {
      $form_state->setErrorByName('weight', $this->t('Weight value must be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $term = parent::buildEntity($form, $form_state);

    // Prevent leading and trailing spaces in term names.
    $term->setName(trim($term->getName()));

    // Assign parents with proper delta values starting from 0.
    $term->parent = array_keys($form_state->getValue('parent'));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $term = $this->entity;

    $result = $term->save();

    $edit_link = $term->link($this->t('Edit'), 'edit-form');
    $view_link = $term->link($term->getName());
    switch ($result) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new term %term.', array('%term' => $view_link)));
        $this->logger('taxonomy')->notice('Created new term %term.', array('%term' => $term->getName(), 'link' => $edit_link));
        break;
      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated term %term.', array('%term' => $view_link)));
        $this->logger('taxonomy')->notice('Updated term %term.', array('%term' => $term->getName(), 'link' => $edit_link));
        break;
    }

    $current_parent_count = count($form_state->getValue('parent'));
    $previous_parent_count = count($form_state->get(['taxonomy', 'parent']));
    // Root doesn't count if it's the only parent.
    if ($current_parent_count == 1 && $form_state->hasValue(array('parent', 0))) {
      $current_parent_count = 0;
      $form_state->setValue('parent', array());
    }

    // If the number of parents has been reduced to one or none, do a check on the
    // parents of every term in the vocabulary value.
    $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
    if ($current_parent_count < $previous_parent_count && $current_parent_count < 2) {
      taxonomy_check_vocabulary_hierarchy($vocabulary, $form_state->getValues());
    }
    // If we've increased the number of parents and this is a single or flat
    // hierarchy, update the vocabulary immediately.
    elseif ($current_parent_count > $previous_parent_count && $vocabulary->getHierarchy() != VocabularyInterface::HIERARCHY_MULTIPLE) {
      $vocabulary->setHierarchy($current_parent_count == 1 ? VocabularyInterface::HIERARCHY_SINGLE : VocabularyInterface::HIERARCHY_MULTIPLE);
      $vocabulary->save();
    }

    $form_state->setValue('tid', $term->id());
    $form_state->set('tid', $term->id());
  }

}
