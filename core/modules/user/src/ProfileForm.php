<?php

namespace Drupal\user;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the profile forms.
 *
 * @internal
 */
class ProfileForm extends AccountForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    if (!$this->entity->isNew() && $this->entity->hasLinkTemplate('cancel-form')) {
      $route_info = $this->entity->toUrl('cancel-form');
      if ($this->getRequest()->query->has('destination')) {
        $query = $route_info->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $route_info->setOption('query', $query);
      }
      $element['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel account'),
        '#access' => $this->entity->id() > 1 && $this->entity->access('delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];
      $element['delete']['#url'] = $route_info;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $account = $this->entity;
    $account->save();
    $form_state->setValue('uid', $account->id());

    $this->messenger()->addStatus($this->t('The changes have been saved.'));
  }

}
