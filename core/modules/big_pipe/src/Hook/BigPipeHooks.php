<?php

namespace Drupal\big_pipe\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for big_pipe.
 */
class BigPipeHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.big_pipe':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The BigPipe module sends pages with dynamic content in a way that allows browsers to show them much faster. For more information, see the <a href=":big_pipe-documentation">online documentation for the BigPipe module</a>.', [
          ':big_pipe-documentation' => 'https://www.drupal.org/documentation/modules/big_pipe',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Speeding up your site') . '</dt>';
        $output .= '<dd>' . $this->t('The module requires no configuration. Every part of the page contains metadata that allows BigPipe to figure this out on its own.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_page_attachments().
   *
   * @see \Drupal\big_pipe\Controller\BigPipeController::setNoJsCookie()
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    // Routes that don't use BigPipe also don't need no-JS detection.
    if (\Drupal::routeMatch()->getRouteObject()->getOption('_no_big_pipe')) {
      return;
    }
    $request = \Drupal::request();
    // BigPipe is only used when there is an actual session, so only add the
    // no-JS detection when there actually is a session. @see
    // \Drupal\big_pipe\Render\Placeholder\BigPipeStrategy.
    $session_exists = \Drupal::service('session_configuration')->hasSession($request);
    $page['#cache']['contexts'][] = 'session.exists';
    // Only do the no-JS detection while we don't know if there's no JS support:
    // avoid endless redirect loops.
    $has_big_pipe_nojs_cookie = $request->cookies->has(BigPipeStrategy::NOJS_COOKIE);
    $page['#cache']['contexts'][] = 'cookies:' . BigPipeStrategy::NOJS_COOKIE;
    if ($session_exists) {
      if (!$has_big_pipe_nojs_cookie) {
        // Let server set the BigPipe no-JS cookie.
        $page['#attached']['html_head'][] = [
          [
            // Redirect through a 'Refresh' meta tag if JavaScript is disabled.
            '#tag' => 'meta',
            '#noscript' => TRUE,
            '#attributes' => [
              'http-equiv' => 'Refresh',
              'content' => '0; URL=' . Url::fromRoute('big_pipe.nojs', [], [
                'query' => \Drupal::service('redirect.destination')->getAsArray(),
              ])->toString(),
            ],
          ],
          'big_pipe_detect_nojs',
        ];
      }
      else {
        // Let client delete the BigPipe no-JS cookie.
        $page['#attached']['html_head'][] = [
          [
            '#tag' => 'script',
            '#value' => 'document.cookie = "' . BigPipeStrategy::NOJS_COOKIE . '=1; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT"',
          ],
          'big_pipe_detect_js',
        ];
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'big_pipe_interface_preview' => [
        'variables' => [
          'callback' => NULL,
          'arguments' => NULL,
          'preview' => NULL,
        ],
      ],
    ];
  }

}
