<?php

declare(strict_types=1);

namespace Drupal\views_test_rss\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for views_test_rss.
 */
class ViewsTestRssThemeHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_views_view_rss')]
  public function preprocessViewsViewRss(&$variables): void {
    $variables['channel_elements'][] = [
      '#type' => 'html_tag',
      '#tag' => 'copyright',
      '#value' => $this->t('Copyright 2019 Dries Buytaert'),
    ];
  }

}
