<?php

/**
 * @file
 * Contains Drupal\views\Plugin\views\filter\LanguageFilter.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Provides filtering by language.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("language")
 */
class LanguageFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->value_options)) {
      $this->value_title = $this->t('Language');
      $this->value_options = $this->listLanguages(LanguageInterface::STATE_ALL |LanguageInterface::STATE_SITE_DEFAULT | PluginBase::INCLUDE_NEGOTIATED);
    }
  }
}
