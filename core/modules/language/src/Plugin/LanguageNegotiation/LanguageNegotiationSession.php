<?php

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\language\LanguageSwitcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Identify language from a request/session parameter.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationSession::METHOD_ID,
  name: new TranslatableMarkup('Session'),
  weight: -6,
  description: new TranslatableMarkup("Language from a request/session parameter."),
  config_route_name: 'language.negotiation_session'
)]
class LanguageNegotiationSession extends LanguageNegotiationMethodBase implements OutboundPathProcessorInterface, LanguageSwitcherInterface, ContainerFactoryPluginInterface {

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-session';

  /**
   * Constructs a LanguageNegotiationSession object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    $config = $this->config->get('language.negotiation')->get('session');
    if (($param = $config['parameter']) && $request) {
      if ($request->query->has($param)) {
        return $request->query->get($param);
      }
      if ($request->getSession()->has($param)) {
        return $request->getSession()->get($param);
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function persist(LanguageInterface $language) {
    // We need to update the session parameter with the request value only if we
    // have an authenticated user.
    $langcode = $language->getId();
    if ($langcode && $this->languageManager) {
      $languages = $this->languageManager->getLanguages();
      if ($this->currentUser->isAuthenticated() && isset($languages[$langcode])) {
        $config = $this->config->get('language.negotiation')->get('session');
        $this->requestStack->getCurrentRequest()->getSession()->set($config['parameter'], $langcode);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
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
        if (!isset($options['query'][$this->queryParam])) {
          $options['query'][$this->queryParam] = $this->queryValue;
        }
        if ($bubbleable_metadata) {
          // Cached URLs that have been processed by this outbound path
          // processor must be:
          $bubbleable_metadata
            // - invalidated when the language negotiation config changes, since
            //   another query parameter may be used to determine the language.
            ->addCacheTags($this->config->get('language.negotiation')->getCacheTags())
            // - varied by the configured query parameter.
            ->addCacheContexts(['url.query_args:' . $this->queryParam]);
        }
      }
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url) {
    $links = [];
    $query = [];
    parse_str($request->getQueryString() ?? '', $query);
    $config = $this->config->get('language.negotiation')->get('session');
    $param = $config['parameter'];
    $language_query = $request->getSession()->has($param) ? $request->getSession()->get($param) : $this->languageManager->getCurrentLanguage($type)->getId();

    foreach ($this->languageManager->getNativeLanguages() as $language) {
      $langcode = $language->getId();
      $links[$langcode] = [
        // We need to clone the $url object to avoid using the same one for all
        // links. When the links are rendered, options are set on the $url
        // object, so if we use the same one, they would be set for all links.
        'url' => clone $url,
        'title' => $language->getName(),
        'attributes' => ['class' => ['language-link']],
        'query' => $query,
      ];
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
