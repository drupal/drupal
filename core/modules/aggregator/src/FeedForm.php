<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedForm.
 */

namespace Drupal\aggregator;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the aggregator feed edit forms.
 */
class FeedForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $feed = $this->entity;

    // @todo: convert to a language selection widget defined in the base field.
    //   Blocked on https://drupal.org/node/2226493 which adds a generic
    //   language widget.
    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $feed->language()->id,
      '#languages' => LanguageInterface::STATE_ALL,
      '#weight' => -4,
    );

    return parent::form($form, $form_state, $feed);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $feed = $this->buildEntity($form, $form_state);
    // Check for duplicate titles.
    $feed_storage = $this->entityManager->getStorage('aggregator_feed');
    $result = $feed_storage->getFeedDuplicates($feed);
    foreach ($result as $item) {
      if (strcasecmp($item->label(), $feed->label()) == 0) {
        $form_state->setErrorByName('title', $this->t('A feed named %feed already exists. Enter a unique title.', array('%feed' => $feed->label())));
      }
      if (strcasecmp($item->getUrl(), $feed->getUrl()) == 0) {
        $form_state->setErrorByName('url', $this->t('A feed with this URL %url already exists. Enter a unique URL.', array('%url' => $feed->getUrl())));
      }
    }
    parent::validate($form, $form_state);
  }

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
      $this->logger('aggregator')->notice('Feed %feed added.', array('%feed' => $feed->label(), 'link' => l($this->t('View'), 'admin/config/services/aggregator')));
      drupal_set_message($this->t('The feed %feed has been added.', array('%feed' => $feed->label())));
    }
  }

}
