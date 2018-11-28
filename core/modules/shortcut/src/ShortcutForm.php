<?php

namespace Drupal\shortcut;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the shortcut entity forms.
 *
 * @internal
 */
class ShortcutForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\shortcut\ShortcutInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();
    $url = $entity->getUrl();
    // There's an edge case where a user can have permission to
    // 'link to any content', but has no right to access the linked page. So we
    // check the access before showing the link.
    if ($url->access()) {
      $view_link = \Drupal::l($entity->getTitle(), $url);
    }
    else {
      $view_link = $entity->getTitle();
    }

    if ($status == SAVED_UPDATED) {
      $message = $this->t('The shortcut %link has been updated.', ['%link' => $view_link]);
    }
    else {
      $message = $this->t('Added a shortcut for %title.', ['%title' => $view_link]);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirect(
      'entity.shortcut_set.customize_form',
      ['shortcut_set' => $entity->bundle()]
    );
  }

}
