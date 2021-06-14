<?php

namespace Drupal\path;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the path alias edit forms.
 *
 * @internal
 */
class PathAliasForm extends ContentEntityForm {

  /**
   * The path_alias entity.
   *
   * @var \Drupal\path_alias\PathAliasInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('The alias has been saved.'));
    $form_state->setRedirect('entity.path_alias.collection');
  }

}
