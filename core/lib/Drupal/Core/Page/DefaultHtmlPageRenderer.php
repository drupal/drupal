<?php

/**
 * @file
 * Contains \Drupal\Core\Page\DefaultHtmlPageRenderer
 */

namespace Drupal\Core\Page;

/**
 * Default page rendering engine.
 */
class DefaultHtmlPageRenderer implements HtmlPageRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function render(HtmlPage $page) {
    $render = array(
      '#type' => 'html',
      '#page_object' => $page,
    );
    // drupal_render() will render the 'html' template, which will call
    // HtmlPage::getScripts(). But normally we can only run
    // drupal_process_attached() after drupal_render(). Hence any assets
    // attached to '#type' => 'html' will be lost. This is a work-around for
    // that limitation, until the HtmlPage object contains its assets — this is
    // an unfortunate intermediate consequence of the way HtmlPage dictates page
    // rendering and how that differs from how drupal_render() works.
    $render += element_info($render['#type']);
    drupal_process_attached($render);
    return drupal_render($render);
  }

  /**
   * Renders a page using a custom page theme hook and optional region content.
   *
   * Temporary shim to facilitate modernization progress for special front
   * controllers (install.php, update.php, authorize.php), maintenance mode, and
   * the exception handler.
   *
   * Do NOT use this method in your code. This method will be removed as soon
   * as architecturally possible.
   *
   * This is functionally very similar to DefaultHtmlFragmentRenderer::render()
   * but with the following important differences:
   *
   * - drupal_prepare_page() and hook_page_build() cannot be invoked on the
   *   maintenance and install pages, since possibly enabled page layout/block
   *   modules would replace the main page content with configured region
   *   content.
   * - This function composes a complete page render array including a page
   *   template theme suggestion (as opposed to the main page content only).
   * - The render cache and cache tags is skipped.
   *
   * @param array|string $main
   *   A render array or string containing the main page content.
   * @param string $title
   *   (optional) The page title.
   * @param string $theme
   *   (optional) The theme hook to use for rendering the page.  Defaults to
   *   'maintenance'. The given value will be appended with '_page' to compose
   *   the #theme property for #type 'page' currently; e.g., 'maintenance'
   *   becomes 'maintenance_page'. Ultimately this parameter will be converted
   *   into a page template theme suggestion; i.e., 'page__$theme'.
   * @param array $regions
   *   (optional) Additional region content to add to the page. The given array
   *   is added to the page render array, so this parameter may also be used to
   *   pass e.g. the #show_messages property for #type 'page'.
   *
   * @return string
   *   The rendered HTML page.
   *
   * @internal
   */
  public static function renderPage($main, $title = '', $theme = 'maintenance', array $regions = array()) {
    // Automatically convert the main page content into a render array.
    if (!is_array($main)) {
      $main = array('#markup' => $main);
    }
    $page = new HtmlPage('', array(), $title);
    $page_array = array(
      '#type' => 'page',
      // @todo Change into theme suggestions "page__$theme".
      '#theme' => $theme . '_page',
      '#title' => $title,
      'content' => array(
        'system_main' => $main,
      ),
    );
    // Append region content.
    $page_array += $regions;
    // Add default properties.
    $page_array += element_info('page');

    // hook_page_build() cannot be invoked on the maintenance and install pages,
    // because the application is in an unknown or special state.
    // In particular on the install page, invoking hook_page_build() directly
    // after e.g. Block module has been installed would *replace* the installer
    // output with the configured blocks of the installer theme (loaded from
    // default configuration of the installation profile).

    // Allow modules and themes to alter the page render array.
    // This allows e.g. themes to attach custom libraries.
    \Drupal::moduleHandler()->alter('page', $page_array);

    // @todo Move preparePage() before alter() above, so $page_array['#page'] is
    //   available in hook_page_alter(), so that HTML attributes can be altered.
    $page = \Drupal::service('html_fragment_renderer')->preparePage($page, $page_array);

    $page->setBodyTop(drupal_render_root($page_array['page_top']));
    $page->setBodyBottom(drupal_render_root($page_array['page_bottom']));
    $page->setContent(drupal_render_root($page_array));
    drupal_process_attached($page_array);
    if (isset($page_array['page_top'])) {
      drupal_process_attached($page_array['page_top']);
    }
    if (isset($page_array['page_bottom'])) {
      drupal_process_attached($page_array['page_bottom']);
    }

    return \Drupal::service('html_page_renderer')->render($page);
  }

}
