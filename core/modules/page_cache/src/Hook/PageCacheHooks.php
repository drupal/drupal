<?php

namespace Drupal\page_cache\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for page_cache.
 */
class PageCacheHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.page_cache':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Internal Page Cache module caches pages for anonymous users in the database. For more information, see the <a href=":pagecache-documentation">online documentation for the Internal Page Cache module</a>.', [
          ':pagecache-documentation' => 'https://www.drupal.org/documentation/modules/internal_page_cache',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Speeding up your site') . '</dt>';
        $output .= '<dd>' . $this->t('Pages requested by anonymous users are stored the first time they are requested and then are reused. Depending on your site configuration and the amount of your web traffic tied to anonymous visitors, the caching system may significantly increase the speed of your site.') . '</dd>';
        $output .= '<dd>' . $this->t('Pages are usually identical for all anonymous users, while they can be personalized for each authenticated user. This is why entire pages can be cached for anonymous users, whereas they will have to be rebuilt for every authenticated user.') . '</dd>';
        $output .= '<dd>' . $this->t('To speed up your site for authenticated users, see the <a href=":dynamic_page_cache-help">Dynamic Page Cache module</a>.', [
          ':dynamic_page_cache-help' => \Drupal::moduleHandler()->moduleExists('dynamic_page_cache') ? Url::fromRoute('help.page', [
            'name' => 'dynamic_page_cache',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<dt>' . $this->t('Configuring the internal page cache') . '</dt>';
        $output .= '<dd>' . $this->t('On the <a href=":cache-settings">Performance page</a>, you can configure how long browsers and proxies may cache pages based on the Cache-Control header; this setting is ignored by the Internal Page Cache module, which caches pages permanently until invalidation, unless they carry an Expires header. There is no other configuration.', [
          ':cache-settings' => Url::fromRoute('system.performance_settings')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

}
