<?php

/**
 * @file
 * Contains \Drupal\contact\Plugin\views\field\ContactLink.
 */

namespace Drupal\contact\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Annotation\PluginID;
use Drupal\user\Plugin\views\field\Link;

/**
 * Defines a field that links to the user contact page, if access is permitted.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("contact_link")
 */
class ContactLink extends Link {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['text']['#title'] = t('Link label');
    $form['text']['#required'] = TRUE;
    $form['text']['#default_value'] = empty($this->options['text']) ? t('contact') : $this->options['text'];
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    // The access logic is implemented per row.
    return TRUE;
  }


  /**
   * {@inheritdoc}
   */
  public function render_link(EntityInterface $entity, \stdClass $values) {

    if (empty($entity)) {
      return;
    }

    // Check access when we pull up the user account so we know
    // if the user has made the contact page available.
    $uid = $entity->id();

    $path = "user/$uid/contact";
    if (!_contact_personal_tab_access($entity->getBCEntity())) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $path;

    $title = t('Contact %user', array('%user' => $entity->name->value));
    $this->options['alter']['attributes'] = array('title' => $title);

    if (!empty($this->options['text'])) {
      return $this->options['text'];
    }
    else {
      return $title;
    }
  }

}
