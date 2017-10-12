<?php

namespace Drupal\forum\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for container term edit forms.
 *
 * @internal
 */
class ContainerForm extends ForumForm {

  /**
   * Reusable url stub to use in watchdog messages.
   *
   * @var string
   */
  protected $urlStub = 'container';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Build the bulk of the form from the parent forum form.
    $form = parent::form($form, $form_state);

    // Set the title and description of the name field.
    $form['name']['#title'] = $this->t('Container name');
    $form['name']['#description'] = $this->t('Short but meaningful name for this collection of related forums.');

    // Alternate description for the container parent.
    $form['parent'][0]['#description'] = $this->t('Containers are usually placed at the top (root) level, but may also be placed inside another container or forum.');
    $this->forumFormType = $this->t('forum container');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $entity->forum_container = TRUE;
    return $entity;
  }

}
