<?php

namespace Drupal\search\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for adding a search page.
 *
 * @internal
 */
class SearchPageAddForm extends SearchPageFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $search_plugin_id = NULL) {
    $this->entity->setPlugin($search_plugin_id);
    $definition = $this->entity->getPlugin()->getPluginDefinition();
    $this->entity->set('label', $definition['title']);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // If there is no default search page, make the added search the default.
    // TRICKY: ::getDefaultSearchPage() will return the first active search page
    // as the default if no explicit default is configured in `search.settings`.
    // That's why this must be checked *before* saving the form.
    $make_default = !$this->searchPageRepository->getDefaultSearchPage();

    parent::save($form, $form_state);

    if ($make_default) {
      $this->searchPageRepository->setDefaultSearchPage($this->entity);
    }

    $this->messenger()->addStatus($this->t('The %label search page has been added.', ['%label' => $this->entity->label()]));
  }

}
