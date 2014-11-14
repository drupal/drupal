<?php

/**
 * @file
 * Contains \Drupal\Core\Render\BareHtmlPageRendererInterface.
 */

namespace Drupal\Core\Render;

/**
 * Bare HTML page renderer.
 *
 * By "bare HTML page", we mean that the following hooks that allow for "normal"
 * pages are not invoked:
 * - hook_page_attachments()
 * - hook_page_attachments_alter()
 * - hook_page_top()
 * - hook_page_bottom()
 *
 * Examples of bare HTML pages are:
 * - install.php
 * - update.php
 * - authorize.php
 * - maintenance mode
 * - exception handlers
 *
 * i.e. use this when rendering HTML pages in limited environments. Otherwise,
 * use a @code _content @endcode route, this will cause a main content renderer
 * (\Drupal\Core\Render\MainContent\MainContentRendererInterface) to be used,
 * and in case of a HTML request that will be
 * \Drupal\Core\Render\MainContent\HtmlRenderer.
 *
 * In fact, this is not only *typically* used in a limited environment, it even
 * *must* be used in a limited environment: when using the bare HTML page
 * renderer, use as little state/additional services as possible, because the
 * same safeguards aren't present (precisely because this is intended to be used
 * in a limited environment).
 *
 * Currently, there are two types of bare pages available:
 * 1. install (hook_preprocess_install_page(), install-page.html.twig)
 * 2. maintenance (hook_preprocess_maintenance_page(), maintenance-page.html.twig)
 *
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 */
interface BareHtmlPageRendererInterface {

  /**
   * Renders a "maintenance" page, styled as such.
   *
   * @param string|array $content
   *   The main content to render in the 'content' region.
   * @param string $title
   *   The title for this maintenance page.
   * @param array $page_additions
   *   Additional regions to add to the page. May also be used to pass the
   *   #show_messages property for #type 'page'.
   *
   * @return string
   *   The rendered HTML page.
   */
  public function renderMaintenancePage($content, $title, array $page_additions = []);

  /**
   * Renders an "install" page, styled as such.
   *
   * @param string|array $content
   *   The main content to render in the 'content' region.
   * @param string $title
   *   The title for this maintenance page.
   * @param array $page_additions
   *   Additional regions to add to the page. May also be used to pass the
   *   #show_messages property for #type 'page'.
   *
   * @return string
   *   The rendered HTML page.
   */
  public function renderInstallPage($content, $title, array $page_additions = []);

}
