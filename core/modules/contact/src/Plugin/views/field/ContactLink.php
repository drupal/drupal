<?php

/**
 * @file
 * Contains \Drupal\contact\Plugin\views\field\ContactLink.
 */

namespace Drupal\contact\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Defines a field that links to the user contact page, if access is permitted.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("contact_link")
 */
class ContactLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['text']['#title'] = $this->t('Link label');
    $form['text']['#required'] = TRUE;
    $form['text']['#default_value'] = empty($this->options['text']) ? $this->getDefaultLabel() : $this->options['text'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    return Url::fromRoute('entity.user.contact_form', ['user' => $this->getEntity($row)->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $entity = $this->getEntity($row);

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = $this->getUrlInfo($row);

    $title = $this->t('Contact %user', array('%user' => $entity->label()));
    $this->options['alter']['attributes'] = array('title' => $title);

    if (!empty($this->options['text'])) {
      return $this->options['text'];
    }
    else {
      return $title;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('contact');
  }

}
