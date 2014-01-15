<?php

/**
 * @file
 * Contains \Drupal\Core\Page\DefaultHtmlPageRenderer
 */

namespace Drupal\Core\Page;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;

/**
 * Default page rendering engine.
 */
class DefaultHtmlPageRenderer implements HtmlPageRendererInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a new DefaultHtmlPageRenderer.
   *
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager service.
   */
  public function __construct(LanguageManager $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function render(HtmlFragment $fragment, $status_code = 200) {
    $page = new HtmlPage('', $fragment->getTitle());

    $page_content['main'] = array(
      '#markup' => $fragment->getContent(),
    );
    $page_content['#title'] = $page->getTitle();

    $page_array = drupal_prepare_page($page_content);

    $page = $this->preparePage($page, $page_array);

    $page->setBodyTop(drupal_render($page_array['page_top']));
    $page->setBodyBottom(drupal_render($page_array['page_bottom']));
    $page->setContent(drupal_render($page_array));

    $page->setStatusCode($status_code);

    return $page;
  }

  /**
   * {@inheritdoc}
   */
  public function renderPage(HtmlPage $page) {
    $render = array(
      '#theme' => 'html',
      '#page_object' => $page,
    );
    return drupal_render($render);
  }

  /**
   * Enhances a page object based on a render array.
   *
   * @param \Drupal\Core\Page\HtmlPage $page
   *   The page object to enhance.
   * @param array $page_array
   *   The page array to extract onto the page object.
   *
   * @return \Drupal\Core\Page\HtmlPage
   *   The modified page object.
   */
  public function preparePage(HtmlPage $page, &$page_array) {
    // @todo Remove this one drupal_get_title() has been eliminated.
    if (!$page->hasTitle()) {
      $title = drupal_get_title();
      // drupal_set_title() already ensured security, so not letting the
      // title pass through would cause double escaping.
      $page->setTitle($title, PASS_THROUGH);
    }

    $page_array['#page'] = $page;

    // HTML element attributes.
    $language_interface = $this->languageManager->getCurrentLanguage();
    $html_attributes = $page->getHtmlAttributes();
    $html_attributes['lang'] = $language_interface->id;
    $html_attributes['dir'] = $language_interface->direction ? 'rtl' : 'ltr';

    return $page;
  }

}
