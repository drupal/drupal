<?php

/**
 * @file
 * Contains \Drupal\contact\ContactFormListBuilder.
 */

namespace Drupal\contact;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of contact form entities.
 *
 * @see \Drupal\contact\Entity\ContactForm
 */
class ContactFormListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['form'] = t('Form');
    $header['recipients'] = t('Recipients');
    $header['selected'] = t('Selected');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['form'] = $this->getLabel($entity);
    // Special case the personal form.
    if ($entity->id() == 'personal') {
      $row['recipients'] = t('Selected user');
      $row['selected'] = t('No');
    }
    else {
      $row['recipients'] = SafeMarkup::checkPlain(implode(', ', $entity->getRecipients()));
      $default_form = \Drupal::config('contact.settings')->get('default_form');
      $row['selected'] = ($default_form == $entity->id() ? t('Yes') : t('No'));
    }
    return $row + parent::buildRow($entity);
  }

}
