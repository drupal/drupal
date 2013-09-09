<?php

/**
 * @file
 * Contains \Drupal\Core\Utility\LinkGenerator.
 */

namespace Drupal\Core\Utility;

use Drupal\Component\Utility\String;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a class which generates a link with route names and parameters.
 */
class LinkGenerator implements LinkGeneratorInterface {

  /**
   * Stores some information about the current request, like the language.
   *
   * @var array
   */
  protected $active;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The module handler firing the route_link alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a LinkGenerator instance.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(UrlGeneratorInterface $url_generator, ModuleHandlerInterface $module_handler, LanguageManager $language_manager) {
    $this->urlGenerator = $url_generator;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * Sets the $request property.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   */
  public function setRequest(Request $request) {
    // Pre-calculate and store values based on the request that may be used
    // repeatedly in generate().
    $raw_variables = $request->attributes->get('_raw_variables');
    // $raw_variables is a ParameterBag object or NULL.
    $parameters = $raw_variables ? $raw_variables->all() : array();
    $this->active = array(
      'route_name' => $request->attributes->get(RouteObjectInterface::ROUTE_NAME),
      'language' => $this->languageManager->getLanguage(Language::TYPE_URL)->id,
      'parameters' => $parameters + (array) $request->query->all(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generate($text, $route_name, array $parameters = array(), array $options = array()) {
    // Start building a structured representation of our link to be altered later.
    $variables = array(
      // @todo Inject the service when drupal_render() is converted to one.
      'text' => is_array($text) ? drupal_render($text) : $text,
      'route_name' => $route_name,
      'parameters' => $parameters,
      'options' => $options,
    );

    // Merge in default options.
    $variables['options'] += array(
      'attributes' => array(),
      'query' => array(),
      'html' => FALSE,
      'language' => NULL,
    );
    // Add a hreflang attribute if we know the language of this link's url and
    // hreflang has not already been set.
    if (!empty($variables['options']['language']) && !isset($variables['options']['attributes']['hreflang'])) {
      $variables['options']['attributes']['hreflang'] = $variables['options']['language']->id;
    }

    // This is only needed for the active class. The generator also combines
    // the parameters and $options['query'] and adds parameters that are not
    // path slugs as query strings.
    $full_parameters = $parameters + (array) $variables['options']['query'];

    // Determine whether this link is "active", meaning that it has the same
    // URL path and query string as the current page. Note that this may be
    // removed from l() in https://drupal.org/node/1979468 and would be removed
    // or altered here also.
    $variables['url_is_active'] = $route_name == $this->active['route_name']
      // The language of an active link is equal to the current language.
      && (empty($variables['options']['language']) || $variables['options']['language']->id == $this->active['language'])
      && $full_parameters == $this->active['parameters'];

    // Add the "active" class if appropriate.
    if ($variables['url_is_active']) {
      $variables['options']['attributes']['class'][] = 'active';
    }

    // Remove all HTML and PHP tags from a tooltip, calling expensive strip_tags()
    // only when a quick strpos() gives suspicion tags are present.
    if (isset($variables['options']['attributes']['title']) && strpos($variables['options']['attributes']['title'], '<') !== FALSE) {
      $variables['options']['attributes']['title'] = strip_tags($variables['options']['attributes']['title']);
    }

    // Allow other modules to modify the structure of the link.
    $this->moduleHandler->alter('link', $variables);

    // Move attributes out of options. generateFromRoute(() doesn't need them.
    $attributes = new Attribute($variables['options']['attributes']);
    unset($variables['options']['attributes']);

    // The result of the url generator is a plain-text URL. Because we are using
    // it here in an HTML argument context, we need to encode it properly.
    $url = String::checkPlain($this->urlGenerator->generateFromRoute($variables['route_name'], $variables['parameters'], $variables['options']));

    // Sanitize the link text if necessary.
    $text = $variables['options']['html'] ? $variables['text'] : String::checkPlain($variables['text']);

    return '<a href="' . $url . '"' . $attributes . '>' . $text . '</a>';
  }

}
