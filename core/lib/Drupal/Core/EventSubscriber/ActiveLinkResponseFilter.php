<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// cspell:ignore noemptytag
/**
 * Subscribes to filter HTML responses, to set the 'is-active' class on links.
 *
 * Only for anonymous users; for authenticated users, the active-link asset
 * library is loaded.
 *
 * @see system_page_attachments()
 */
class ActiveLinkResponseFilter implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new ActiveLinkResponseFilter instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(AccountInterface $current_user, CurrentPathStack $current_path, PathMatcherInterface $path_matcher, LanguageManagerInterface $language_manager) {
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->pathMatcher = $path_matcher;
    $this->languageManager = $language_manager;
  }

  /**
   * Sets the 'is-active' class on links.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();

    // Only care about HTML responses.
    if (stripos($response->headers->get('Content-Type', ''), 'text/html') === FALSE) {
      return;
    }

    // For authenticated users, the 'is-active' class is set in JavaScript.
    // @see system_page_attachments()
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    // If content is FALSE, assume the response does not support the
    // setContent() method and skip it, for example,
    // \Symfony\Component\HttpFoundation\BinaryFileResponse.
    $content = $response->getContent();
    if ($content !== FALSE) {
      $response->setContent(static::setLinkActiveClass(
        $content,
        ltrim($this->currentPath->getPath(), '/'),
        $this->pathMatcher->isFrontPage(),
        $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)
          ->getId(),
        $event->getRequest()->query->all()
      ));
    }
  }

  /**
   * Sets the "is-active" class on relevant links.
   *
   * This is a PHP implementation of the drupal.active-link JavaScript library.
   *
   * @param string $html_markup
   *   The HTML markup to update.
   * @param string $current_path
   *   The system path of the currently active page.
   * @param bool $is_front
   *   Whether the current page is the front page (which implies the current
   *   path might also be <front>).
   * @param string $url_language
   *   The language code of the current URL.
   * @param array $query
   *   The query string for the current URL.
   *
   * @return string
   *   The updated HTML markup.
   *
   * @todo Once a future version of PHP supports parsing HTML5 properly
   *   (i.e. doesn't fail on
   *   https://www.drupal.org/comment/7938201#comment-7938201) then we can get
   *   rid of this manual parsing and use DOMDocument instead.
   */
  public static function setLinkActiveClass($html_markup, $current_path, $is_front, $url_language, array $query) {
    $search_key_current_path = 'data-drupal-link-system-path="' . $current_path . '"';
    $search_key_front = 'data-drupal-link-system-path="&lt;front&gt;"';

    // Receive the query in a standardized manner.
    ksort($query);

    $offset = 0;
    // There are two distinct conditions that can make a link be marked active:
    // 1. A link has the current path in its 'data-drupal-link-system-path'
    //    attribute.
    // 2. We are on the front page and a link has the special '<front>' value in
    //    its 'data-drupal-link-system-path' attribute.
    while (strpos($html_markup, $search_key_current_path, $offset) !== FALSE || ($is_front && strpos($html_markup, $search_key_front, $offset) !== FALSE)) {
      $pos_current_path = strpos($html_markup, $search_key_current_path, $offset);
      // Only look for links with the special '<front>' system path if we are
      // actually on the front page.
      $pos_front = $is_front ? strpos($html_markup, $search_key_front, $offset) : FALSE;

      // Determine which of the two values is the next match: the exact path, or
      // the <front> special case.
      $pos_match = NULL;
      if ($pos_front === FALSE) {
        $pos_match = $pos_current_path;
      }
      elseif ($pos_current_path === FALSE) {
        $pos_match = $pos_front;
      }
      elseif ($pos_current_path < $pos_front) {
        $pos_match = $pos_current_path;
      }
      else {
        $pos_match = $pos_front;
      }

      // Find beginning and ending of opening tag.
      $pos_tag_start = NULL;
      for ($i = $pos_match; $pos_tag_start === NULL && $i > 0; $i--) {
        if ($html_markup[$i] === '<') {
          $pos_tag_start = $i;
        }
      }
      $pos_tag_end = NULL;
      for ($i = $pos_match; $pos_tag_end === NULL && $i < strlen($html_markup); $i++) {
        if ($html_markup[$i] === '>') {
          $pos_tag_end = $i;
        }
      }

      // Get the HTML: this will be the opening part of a single tag, e.g.:
      // <a href="/" data-drupal-link-system-path="&lt;front&gt;">
      $tag = substr($html_markup, $pos_tag_start ?? 0, $pos_tag_end - $pos_tag_start + 1);

      // Parse it into a DOMDocument so we can reliably read and modify
      // attributes.
      $dom = new \DOMDocument();
      @$dom->loadHTML('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $tag . '</body></html>');
      $node = $dom->getElementsByTagName('body')->item(0)->firstChild;

      // Ensure we don't set the "active" class twice on the same element.
      $class = $node->getAttribute('class');
      $add_active = !in_array('is-active', explode(' ', $class));

      // The language of an active link is equal to the current language.
      if ($add_active && $url_language) {
        if ($node->hasAttribute('hreflang') && $node->getAttribute('hreflang') !== $url_language) {
          $add_active = FALSE;
        }
      }
      // The query parameters of an active link are equal to the current
      // parameters.
      if ($add_active) {
        if ($query) {
          if (!$node->hasAttribute('data-drupal-link-query') || $node->getAttribute('data-drupal-link-query') !== Json::encode($query)) {
            $add_active = FALSE;
          }
        }
        else {
          if ($node->hasAttribute('data-drupal-link-query')) {
            $add_active = FALSE;
          }
        }
      }

      // Only if the path, the language and the query match, we set the
      // "is-active" class.
      if ($add_active) {
        if (strlen($class) > 0) {
          $class .= ' ';
        }
        $class .= 'is-active';
        $node->setAttribute('class', $class);

        // Get the updated tag.
        $updated_tag = $dom->saveXML($node, LIBXML_NOEMPTYTAG);
        // saveXML() added a closing tag, remove it.
        $updated_tag = substr($updated_tag, 0, strrpos($updated_tag, '<'));

        $html_markup = str_replace($tag, $updated_tag, $html_markup);

        // Ensure we only search the remaining HTML.
        $offset = $pos_tag_end - strlen($tag) + strlen($updated_tag);
      }
      else {
        // Ensure we only search the remaining HTML.
        $offset = $pos_tag_end + 1;
      }
    }

    return $html_markup;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Should run after any other response subscriber that modifies the markup.
    $events[KernelEvents::RESPONSE][] = ['onResponse', -512];

    return $events;
  }

}
