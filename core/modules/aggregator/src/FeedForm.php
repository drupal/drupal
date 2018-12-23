<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form handler for the aggregator feed edit forms.
 *
 * @internal
 */
class FeedForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $feed = $this->entity;
    $status = $feed->save();
    $label = $feed->label();
    $view_link = $feed->toLink($label, 'canonical')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The feed %feed has been updated.', ['%feed' => $view_link]));
      $form_state->setRedirectUrl($feed->toUrl('canonical'));
    }
    else {
      $this->logger('aggregator')->notice('Feed %feed added.', ['%feed' => $feed->label(), 'link' => $this->l($this->t('View'), new Url('aggregator.admin_overview'))]);
      $this->messenger()->addStatus($this->t('The feed %feed has been added.', ['%feed' => $view_link]));
    }
  }

}
