<?php

namespace Drupal\dynamic_page_cache\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for dynamic_page_cache.
 */
class DynamicPageCacheHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.dynamic_page_cache':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Internal Dynamic Page Cache module caches pages for all users in the database, handling dynamic content correctly. For more information, see the <a href=":dynamic_page_cache-documentation">online documentation for the Internal Dynamic Page Cache module</a>.', [
          ':dynamic_page_cache-documentation' => 'https://www.drupal.org/documentation/modules/dynamic_page_cache',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Speeding up your site') . '</dt>';
        $output .= '<dd>' . $this->t('Pages which are suitable for caching are cached the first time they are requested, then the cached version is served for all later requests. Dynamic content is handled automatically so that both cache correctness and hit ratio is maintained.') . '</dd>';
        $output .= '<dd>' . $this->t('The module requires no configuration. Every part of the page contains metadata that allows Internal Dynamic Page Cache to figure this out on its own.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

}
