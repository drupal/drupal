<?php

namespace Drupal\Core\Render;

use Drupal\Component\Utility\UrlHelper;

/**
 * Default bare HTML page renderer.
 */
class BareHtmlPageRenderer implements BareHtmlPageRendererInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The HTML response attachments processor service.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface
   */
  protected $htmlResponseAttachmentsProcessor;

  /**
   * Constructs a new BareHtmlPageRenderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Render\AttachmentsResponseProcessorInterface $html_response_attachments_processor
   *   The HTML response attachments processor service.
   */
  public function __construct(RendererInterface $renderer, AttachmentsResponseProcessorInterface $html_response_attachments_processor) {
    $this->renderer = $renderer;
    $this->htmlResponseAttachmentsProcessor = $html_response_attachments_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function renderBarePage(array $content, $title, $page_theme_property, array $page_additions = []) {
    $attributes = [
      'class' => [
        str_replace('_', '-', $page_theme_property),
      ],
    ];
    $html = [
      '#type' => 'html',
      '#attributes' => $attributes,
      'page' => [
        '#type' => 'page',
        '#theme' => $page_theme_property,
        '#title' => $title,
        'content' => $content,
      ] + $page_additions,
    ];

    // For backwards compatibility.
    // @todo In Drupal 9, add a $show_messages function parameter.
    if (!isset($page_additions['#show_messages']) || $page_additions['#show_messages'] === TRUE) {
      $html['page']['highlighted'] = ['#type' => 'status_messages'];
    }

    // Add the bare minimum of attachments from the system module and the
    // current maintenance theme.
    $this->systemPageAttachments($html['page']);
    $this->renderer->renderRoot($html);

    $response = new HtmlResponse();
    $response->setContent($html);
    // Process attachments, because this does not go via the regular render
    // pipeline, but will be sent directly.
    $response = $this->htmlResponseAttachmentsProcessor->processAttachments($response);

    return $response;
  }

  /**
   * Helper for system_page_attachments.
   *
   * SystemPageAttachment needs to be on BareHtmlPageRenderer.
   * When BareHtmlPageRenderer is called, the system module is not available.
   * PageAttachmentsHook can inject BareHtmlPageRenderer to use for
   * system_page_attachments.
   *
   * @param array $page
   *   The page to attach to.
   */
  public function systemPageAttachments(array &$page): void {
    // Ensure the same CSS is loaded in template_preprocess_maintenance_page().
    $page['#attached']['library'][] = 'system/base';
    if (\Drupal::service('router.admin_context')->isAdminRoute()) {
      $page['#attached']['library'][] = 'system/admin';
    }

    // Attach libraries used by this theme.
    $active_theme = \Drupal::theme()->getActiveTheme();
    foreach ($active_theme->getLibraries() as $library) {
      $page['#attached']['library'][] = $library;
    }

    // Attach favicon.
    if (theme_get_setting('features.favicon')) {
      $favicon = theme_get_setting('favicon.url');
      $type = theme_get_setting('favicon.mimetype');
      $page['#attached']['html_head_link'][][] = [
        'rel' => 'icon',
        'href' => UrlHelper::stripDangerousProtocols($favicon),
        'type' => $type,
      ];
    }

    // Get the major Drupal version.
    [$version] = explode('.', \Drupal::VERSION);

    // Attach default meta tags.
    $meta_default = [
      // Make sure the Content-Type comes first because the IE browser may be
      // vulnerable to XSS via encoding attacks from any content that comes
      // before this META tag, such as a TITLE tag.
      'system_meta_content_type' => [
        '#tag' => 'meta',
        '#attributes' => [
          'charset' => 'utf-8',
        ],
        // Security: This always has to be output first.
        '#weight' => -1000,
      ],
      // Show Drupal and the major version number in the META GENERATOR tag.
      'system_meta_generator' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'Generator',
          'content' => 'Drupal ' . $version . ' (https://www.drupal.org)',
        ],
      ],
      // Attach default mobile meta tags for responsive design.
      'MobileOptimized' => [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'MobileOptimized',
          'content' => 'width',
        ],
      ],
      'HandheldFriendly' => [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'HandheldFriendly',
          'content' => 'true',
        ],
      ],
      'viewport' => [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'viewport',
          'content' => 'width=device-width, initial-scale=1.0',
        ],
      ],
    ];
    foreach ($meta_default as $key => $value) {
      $page['#attached']['html_head'][] = [$value, $key];
    }

    // Handle setting the "active" class on links by:
    // - loading the active-link library if the current user is authenticated;
    // - applying a response filter if the current user is anonymous.
    // @see \Drupal\Core\Link
    // @see \Drupal\Core\Utility\LinkGenerator::generate()
    // @see \Drupal\Core\Theme\ThemePreprocess::preprocessLinks()
    // @see \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter
    $page['#cache']['contexts'][] = 'user.roles:authenticated';
    if (\Drupal::currentUser()->isAuthenticated()) {
      $page['#attached']['library'][] = 'core/drupal.active-link';
    }
  }

}
