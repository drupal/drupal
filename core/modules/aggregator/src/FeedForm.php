<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedForm.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form handler for the aggregator feed edit forms.
 */
class FeedForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $feed = $this->entity;
    $insert = (bool) $feed->id();
    $feed->save();
    if ($insert) {
      drupal_set_message($this->t('The feed %feed has been updated.', array('%feed' => $feed->label())));
      $form_state->setRedirectUrl($feed->urlInfo('canonical'));
    }
    else {
      $this->logger('aggregator')->notice('Feed %feed added.', array('%feed' => $feed->label(), 'link' => $this->l($this->t('View'), new Url('aggregator.admin_overview'))));
      drupal_set_message($this->t('The feed %feed has been added.', array('%feed' => $feed->label())));
    }
  }

}
