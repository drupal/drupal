<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSession.
 */

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Url;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\language\LanguageSwitcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Identify language from a request/session parameter.
 *
 * @Plugin(
 *   id = Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSession::METHOD_ID,
 *   weight = -6,
 *   name = @Translation("Session"),
 *   description = @Translation("Language from a request/session parameter."),
 *   config_route_name = "language.negotiation_session"
 * )
 */
class LanguageNegotiationSession extends LanguageNegotiationMethodBase implements OutboundPathProcessorInterface, LanguageSwitcherInterface {

  /**
   * Flag used to determine whether query rewriting is active.
   *
   * @var bool
   */
  protected $queryRewrite;

  /**
   * The query parameter name to rewrite.
   *
   * @var string
   */
  protected $queryParam;

  /**
   * The query parameter value to be set.
   *
   * @var string
   */
  protected $queryValue;

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-session';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $config = $this->config->get('language.negotiation')->get('session');
    $param = $config['parameter'];
    $langcode = $request && $request->query->get($param) ? $request->query->get($param) : NULL;
    if (!$langcode && isset($_SESSION[$param])) {
      $langcode = $_SESSION[$param];
    }
    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function persist(LanguageInterface $language) {
    // We need to update the session parameter with the request value only if we
    // have an authenticated user.
    $langcode = $language->id;
    if ($langcode && $this->languageManager) {
      $languages = $this->languageManager->getLanguages();
      if ($this->currentUser->isAuthenticated() && isset($languages[$langcode])) {
        $config = $this->config->get('language.negotiation')->get('session');
        $_SESSION[$config['parameter']] = $langcode;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    if ($request) {
      // The following values are not supposed to change during a single page
      // request processing.
      if (!isset($this->queryRewrite)) {
        if ($this->currentUser->isAnonymous()) {
          $languages = $this->languageManager->getLanguages();
          $config = $this->config->get('language.negotiation')->get('session');
          $this->queryParam = $config['parameter'];
          $this->queryValue = $request->query->has($this->queryParam) ? $request->query->get($this->queryParam) : NULL;
          $this->queryRewrite = isset($languages[$this->queryValue]);
        }
        else {
          $this->queryRewrite = FALSE;
        }
      }

      // If the user is anonymous, the user language negotiation method is
      // enabled, and the corresponding option has been set, we must preserve
      // any explicit user language preference even with cookies disabled.
      if ($this->queryRewrite) {
        if (isset($options['query']) && is_string($options['query'])) {
          $query = array();
          parse_str($options['query'], $query);
          $options['query'] = $query;
        }
        if (!isset($options['query'][$this->queryParam])) {
          $options['query'][$this->queryParam] = $this->queryValue;
        }
      }
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url) {
    $links = array();
    $config = $this->config->get('language.negotiation')->get('session');
    $param = $config['parameter'];
    $language_query = isset($_SESSION[$param]) ? $_SESSION[$param] : $this->languageManager->getCurrentLanguage($type)->id;
    $query = array();
    parse_str($request->getQueryString(), $query);

    foreach ($this->languageManager->getNativeLanguages() as $language) {
      $langcode = $language->id;
      $links[$langcode] = array(
        'url' => $url,
        'title' => $language->getName(),
        'attributes' => array('class' => array('language-link')),
        'query' => $query,
      );
      if ($language_query != $langcode) {
        $links[$langcode]['query'][$param] = $langcode;
      }
      else {
        $links[$langcode]['attributes']['class'][] = 'session-active';
      }
    }

    return $links;
  }

}
