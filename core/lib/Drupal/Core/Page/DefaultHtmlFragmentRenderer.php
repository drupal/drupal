<?php

/**
 * @file
 * Contains \Drupal\Core\Page\DefaultHtmlFragmentRenderer
 */

namespace Drupal\Core\Page;

use Drupal\Core\Cache\CacheableInterface;
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
  public function render(HtmlFragmentInterface $fragment, $status_code = 200) {
    // Converts the given HTML fragment which represents the main content region
    // of the page into a render array.
    $page_content['main'] = array(
      '#markup' => $fragment->getContent(),
    );
    $page_content['#title'] = $fragment->getTitle();

    if ($fragment instanceof CacheableInterface) {
      $page_content['main']['#cache']['tags'] = $fragment->getCacheTags();
    }

    // Build the full page array by calling drupal_prepare_page(), which invokes
    // hook_page_build(). This adds the other regions to the page.
    $page_array = drupal_prepare_page($page_content);

    // Build the HtmlPage object.
    $page = new HtmlPage('', array(), $fragment->getTitle());
    $page = $this->preparePage($page, $page_array);
    $page->setBodyTop(drupal_render($page_array['page_top']));
    $page->setBodyBottom(drupal_render($page_array['page_bottom']));
    $page->setContent(drupal_render($page_array));
    $page->setStatusCode($status_code);

    if ($fragment instanceof CacheableInterface) {
      // Collect cache tags for all the content in all the regions on the page.
      $tags = $page_array['#cache']['tags'];
      // Tag every render cache item with the "rendered" cache tag. This allows us
      // to invalidate the entire render cache, regardless of the cache bin.
      $tags['rendered'] = TRUE;
      $page->setCacheTags($tags);
    }

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

    $this->setDefaultMetaTags($page);

    // @todo: collect feed links from #attached rather than a static once
    // http://drupal.org/node/2256365 is completed.
    foreach (drupal_get_feeds() as $feed) {
      // Force the URL to be absolute, for consistency with other <link> tags
      // output by Drupal.
      $link = new FeedLinkElement($feed['title'], url($feed['url'], array('absolute' => TRUE)));
      $page->addLinkElement($link);
    }

    return $page;
  }

  /**
   * Apply the default meta tags to the page object.
   *
   * @param \Drupal\Core\Page\HtmlPage $page
   *   The html page.
   */
  protected function setDefaultMetaTags(HtmlPage $page) {
    // Add default elements. Make sure the Content-Type comes first because the
    // IE browser may be vulnerable to XSS via encoding attacks from any content
    // that comes before this META tag, such as a TITLE tag.
    $page->addMetaElement(new MetaElement(NULL, array(
      'name' => 'charset',
      'charset' => 'utf-8',
    )));
    // Show Drupal and the major version number in the META GENERATOR tag.
    // Get the major version.
    list($version) = explode('.', \Drupal::VERSION, 2);
    $page->addMetaElement(new MetaElement('Drupal ' . $version . ' (http://drupal.org)', array(
      'name' => 'Generator',
    )));

    // Display the html.html.twig's default mobile metatags for responsive design.
    $page->addMetaElement(new MetaElement(NULL, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.0')));
  }

}
