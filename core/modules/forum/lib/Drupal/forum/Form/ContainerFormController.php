<?php

/**
 * @file
 * Contains \Drupal\forum\Form\ContainerFormController.
 */

namespace Drupal\forum\Form;

/**
 * Base form controller for container term edit forms.
 */
class ContainerFormController extends ForumFormController {

  /**
   * Reusable url stub to use in watchdog messages.
   *
   * @var string
   */
  protected $urlStub = 'container';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $taxonomy_term = $this->entity;
    // Build the bulk of the form from the parent forum form.
    $form = parent::form($form, $form_state, $taxonomy_term);

    // Set the title and description of the name field.
    $form['name']['#title'] = t('Container name');
    $form['name']['#description'] = t('Short but meaningful name for this collection of related forums.');

    // Alternate description for the container parent.
    $form['parent'][0]['#description'] = t('Containers are usually placed at the top (root) level, but may also be placed inside another container or forum.');
    $this->forumFormType = t('forum container');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $is_new = $this->entity->isNew();
    $term = parent::save($form, $form_state);
    if ($is_new) {
      // Update config item to track the container terms.
      $containers = $this->config->get('containers');
      $containers[] = $term->id();
      $this->config->set('containers', $containers)->save();
    }
  }

}
