<?php

/**
 * @file
 * Contains \Drupal\Core\Page\DefaultHtmlFragmentRenderer
 */

namespace Drupal\Core\Page;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;

/**
 * Default page rendering engine.
 */
class DefaultHtmlFragmentRenderer implements HtmlFragmentRendererInterface {

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
    // Converts the given HTML fragment which represents the main content region
    // of the page into a render array.
    $page_content['main'] = array(
      '#markup' => $fragment->getContent(),
      '#cache' => array('tags' => $fragment->getCacheTags()),
    );
    $page_content['#title'] = $fragment->getTitle();

    // Build the full page array by calling drupal_prepare_page(), which invokes
    // hook_page_build(). This adds the other regions to the page.
    $page_array = drupal_prepare_page($page_content);

    // Build the HtmlPage object.
    $page = new HtmlPage('', array(), $fragment->getTitle());
    $page = $this->preparePage($page, $page_array);
    $page->setBodyTop(drupal_render($page_array['page_top']));
    $page->setBodyBottom(drupal_render($page_array['page_bottom']));
    $page->setContent(drupal_render($page_array));
    // Collect cache tags for all the content in all the regions on the page.
    $page->setCacheTags($page_array['#cache']['tags']);
    $page->setStatusCode($status_code);

    return $page;
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
    $page_array['#page'] = $page;

    // HTML element attributes.
    $language_interface = $this->languageManager->getCurrentLanguage();
    $html_attributes = $page->getHtmlAttributes();
    $html_attributes['lang'] = $language_interface->id;
    $html_attributes['dir'] = $language_interface->direction ? 'rtl' : 'ltr';

    return $page;
  }

}
