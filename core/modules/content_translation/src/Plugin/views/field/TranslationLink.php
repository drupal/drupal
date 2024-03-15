<?php

namespace Drupal\content_translation\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Provides a translation link for an entity.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("content_translation_link")]
class TranslationLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'drupal:content-translation-overview';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Translate');
  }

}
