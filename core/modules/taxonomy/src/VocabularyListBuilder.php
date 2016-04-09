<?php

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of taxonomy vocabulary entities.
 *
 * @see \Drupal\taxonomy\Entity\Vocabulary
 */
class VocabularyListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'vocabularies';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_overview_vocabularies';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit vocabulary');
    }

    $operations['list'] = array(
      'title' => t('List terms'),
      'weight' => 0,
      'url' => $entity->urlInfo('overview-form'),
    );
    $operations['add'] = array(
      'title' => t('Add terms'),
      'weight' => 10,
      'url' => Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $entity->id()]),
    );
    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Vocabulary name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are not multiple vocabularies, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();
    $build['table']['#empty'] = t('No vocabularies available. <a href=":link">Add vocabulary</a>.', array(':link' => \Drupal::url('entity.taxonomy_vocabulary.add_form')));
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['vocabularies']['#attributes'] = array('id' => 'taxonomy');
    $form['actions']['submit']['#value'] = t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message(t('The configuration options have been saved.'));
  }

}
