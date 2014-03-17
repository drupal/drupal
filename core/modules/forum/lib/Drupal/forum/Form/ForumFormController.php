<?php

/**
 * @file
 * Contains \Drupal\forum\Form\ForumFormController.
 */

namespace Drupal\forum\Form;

use Drupal\Core\Cache\Cache;
use Drupal\taxonomy\TermFormController;

/**
 * Base form controller for forum term edit forms.
 */
class ForumFormController extends TermFormController {

  /**
   * Reusable type field to use in status messages.
   *
   * @var string
   */
  protected $forumFormType;

  /**
   * Reusable url stub to use in watchdog messages.
   *
   * @var string
   */
  protected $urlStub = 'forum';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $taxonomy_term = $this->entity;
    // Build the bulk of the form from the parent taxonomy term form.
    $form = parent::form($form, $form_state, $taxonomy_term);

    // Set the title and description of the name field.
    $form['name']['#title'] = $this->t('Forum name');
    $form['name']['#description'] = $this->t('Short but meaningful name for this collection of threaded discussions.');

    // Change the description.
    $form['description']['#description'] = $this->t('Description and guidelines for discussions within this forum.');

    // Re-use the weight field.
    $form['weight'] = $form['relations']['weight'];

    // Remove the remaining relations fields.
    unset($form['relations']);

    // Our parent field is different to the taxonomy term.
    $form['parent']['#tree'] = TRUE;
    $form['parent'][0] = $this->forumParentSelect($taxonomy_term->id(), $this->t('Parent'));

    $form['#theme'] = 'forum_form';
    $this->forumFormType = $this->t('forum');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, array &$form_state) {
    $term = parent::buildEntity($form, $form_state);

    // Assign parents from forum parent select field.
    $term->parent = array($form_state['values']['parent'][0]);

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $term = $this->entity;
    $term_storage = $this->entityManager->getStorageController('taxonomy_term');
    $status = $term_storage->save($term);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new @type %term.', array('%term' => $term->getName(), '@type' => $this->forumFormType)));
        watchdog('forum', 'Created new @type %term.', array('%term' => $term->getName(), '@type' => $this->forumFormType), WATCHDOG_NOTICE, l($this->t('edit'), 'admin/structure/forum/edit/' . $this->urlStub . '/' . $term->id()));
        $form_state['values']['tid'] = $term->id();
        break;

      case SAVED_UPDATED:
        drupal_set_message($this->t('The @type %term has been updated.', array('%term' => $term->getName(), '@type' => $this->forumFormType)));
        watchdog('taxonomy', 'Updated @type %term.', array('%term' => $term->getName(), '@type' => $this->forumFormType), WATCHDOG_NOTICE, l($this->t('edit'), 'admin/structure/forum/edit/' . $this->urlStub . '/' . $term->id()));
        // Clear the page and block caches to avoid stale data.
        Cache::invalidateTags(array('content' => TRUE));
        break;
    }

    $form_state['redirect_route']['route_name'] = 'forum.overview';
    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect_route'] = $this->entity->urlInfo('forum-delete-form');

    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $form_state['redirect_route']['options']['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
  }

  /**
   * Returns a select box for available parent terms.
   *
   * @param int $tid
   *   ID of the term that is being added or edited.
   * @param string $title
   *   Title for the select box.
   *
   * @return array
   *   A select form element.
   */
  protected function forumParentSelect($tid, $title) {
    // @todo Inject a taxonomy service when one exists.
    $parents = taxonomy_term_load_parents($tid);
    if ($parents) {
      $parent = array_shift($parents);
      $parent = $parent->id();
    }
    else {
      $parent = 0;
    }

    $vid = $this->configFactory->get('forum.settings')->get('vocabulary');
    // @todo Inject a taxonomy service when one exists.
    $children = taxonomy_get_tree($vid, $tid, NULL, TRUE);

    // A term can't be the child of itself, nor of its children.
    foreach ($children as $child) {
      $exclude[] = $child->tid;
    }
    $exclude[] = $tid;

    // @todo Inject a taxonomy service when one exists.
    $tree = taxonomy_get_tree($vid, 0, NULL, TRUE);
    $options[0] = '<' . $this->t('root') . '>';
    if ($tree) {
      foreach ($tree as $term) {
        if (!in_array($term->id(), $exclude)) {
          $options[$term->id()] = str_repeat(' -- ', $term->depth) . $term->getName();
        }
      }
    }

    $description = $this->t('Forums may be placed at the top (root) level, or inside another container or forum.');

    return array(
      '#type' => 'select',
      '#title' => $title,
      '#default_value' => $parent,
      '#options' => $options,
      '#description' => $description,
      '#required' => TRUE,
    );
  }

}
