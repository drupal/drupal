<?php

/**
 * @file
 * Contains \Drupal\language\LanguageNegotiatorInterface
 */

namespace Drupal\language;

use Drupal\Core\Session\AccountInterface;

/**
 * Common interface for language negotiation services.
 *
 * The language negotiation API is based on two major concepts:
 * - Language types: types of translatable data (the types of data that a user
 *   can view or request).
 * - Language negotiation methods: responsible for determining which language to
 *   use to present a particular piece of data to the user.
 * Both language types and language negotiation methods are customizable.
 *
 * Drupal defines three built-in language types:
 * - Interface language: The page's main language, used to present translated
 *   user interface elements such as titles, labels, help text, and messages.
 * - Content language: The language used to present content that is available
 *   in more than one language.
 * - URL language: The language associated with URLs. When generating a URL,
 *   this value will be used for URL's as a default if no explicit preference is
 *   provided.
 * Modules can define additional language types through
 * hook_language_types_info(), and alter existing language type definitions
 * through hook_language_types_info_alter().
 *
 * Language types may be configurable or fixed. The language negotiation
 * methods associated with a configurable language type can be explicitly
 * set through the user interface. A fixed language type has predetermined
 * (module-defined) language negotiation settings and, thus, does not appear in
 * the configuration page. Here is a code snippet that makes the content
 * language (which by default inherits the interface language's values)
 * configurable:
 * @code
 * function mymodule_language_types_info_alter(&$language_types) {
 *   unset($language_types[LanguageInterface::TYPE_CONTENT]['fixed']);
 * }
 * @endcode
 *
 * The locked configuration property prevents one language type from being
 * switched from customized to not customized, and vice versa.
 * @see \Drupal\language\LanguageNegotiator::updateConfiguration()
 *
 * Every language type can have a different set of language negotiation methods
 * assigned to it. Different language types often share the same language
 * negotiation settings, but they can have independent settings if needed. If
 * two language types are configured the same way, their language switcher
 * configuration will be functionally identical and the same settings will act
 * on both language types.
 *
 * Drupal defines the following built-in language negotiation methods:
 * - URL: Determine the language from the URL (path prefix or domain).
 * - Session: Determine the language from a request/session parameter.
 * - User: Follow the user's language preference.
 * - User admin language: Identify admin language from the user preferences.
 * - Browser: Determine the language from the browser's language settings.
 * - Selected language: Use the default site language.
 * Language negotiation methods are simple plugin classes that implement a
 * particular logic to return a language code. For instance, the URL method
 * searches for a valid path prefix or domain name in the current request URL.
 * If a language negotiation method does not return a valid language code, the
 * next method associated to the language type (based on method weight) is
 * invoked.
 *
 * Modules can define additional language negotiation methods by simply provide
 * the related plugins, and alter existing methods through
 * hook_language_negotiation_info_alter(). Here is an example snippet that lets
 * path prefixes be ignored for administrative paths:
 * @code
 * function mymodule_language_negotiation_info_alter(&$negotiation_info) {
 *   // Replace the original plugin with our own implementation.
 *   $method_id = \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl::METHOD_ID;
 *   $negotiation_info[$method_id]['class'] = 'Drupal\my_module\Plugin\LanguageNegotiation\MyLanguageNegotiationUrl';
 * }
 *
 * class MyLanguageNegotiationUrl extends LanguageNegotiationUrl {
 *   public function getCurrentLanguage(Request $request = NULL) {
 *     if ($request) {
 *       // Use the original URL language negotiation method to get a valid
 *       // language code.
 *       $langcode = parent::getCurrentLanguage($request);
 *
 *       // If we are on an administrative path, override with the default
 *       language.
 *       if ($request->query->has('q') && strtok($request->query->get('q'), '/') == 'admin') {
 *         return $this->languageManager->getDefaultLanguage()->getId();
 *       }
 *       return $langcode;
 *     }
 *   }
 * }
 * ?>
 * @endcode
 *
 * For more information, see
 * @link https://www.drupal.org/node/1497272 Language Negotiation API @endlink
 */
interface LanguageNegotiatorInterface {

  /**
   * The language negotiation method id for the language negotiator itself.
   */
  const METHOD_ID = 'language-default';

  /**
   * Resets the negotiated languages and the method instances.
   */
  public function reset();

  /**
   * Sets the current active user and resets all language types.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   */
  public function setCurrentUser(AccountInterface $current_user);

  /**
   * Initializes the specified language type.
   *
   * @param string $type
   *   The language type to be initialized.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   Returns an array containing a single language keyed by the language
   *   negotiation method ID used to determine the language of the specified
   *   type. If negotiation is not possible the default language is returned.
   */
  public function initializeType($type);

  /**
   * Returns the language negotiation methods enabled for a language type.
   *
   * @param string $type
   *   (optional) The language type. If no type is specified all the method
   *   definitions are returned.
   *
   * @return array
   *   An array of language negotiation method definitions keyed by method id.
   */
  public function getNegotiationMethods($type = NULL);

  /**
   * Returns an instance of the specified language negotiation method.
   *
   * @param string $method_id
   *   The method identifier.
   *
   * @return \Drupal\language\LanguageNegotiationMethodInterface
   */
  public function getNegotiationMethodInstance($method_id);

  /**
   * Returns the ID of the language type's primary language negotiation method.
   *
   * @param $type
   *   The language type.
   *
   * @return
   *   The identifier of the primary language negotiation method for the given
   *   language type, or the default method if none exists.
   */
  public function getPrimaryNegotiationMethod($type);

  /**
   * Checks whether a language negotiation method is enabled for a language type.
   *
   * @param $method_id
   *   The language negotiation method ID.
   * @param $type
   *   (optional) The language type. If none is passed, all the configurable
   *   language types will be inspected.
   *
   * @return
   *   TRUE if the method is enabled for at least one of the given language
   *   types, or FALSE otherwise.
   */
  public function isNegotiationMethodEnabled($method_id, $type = NULL);

  /**
   * Saves a list of language negotiation methods for a language type.
   *
   * @param string $type
   *   The language type.
   * @param array $enabled_methods
   *   An array of language negotiation method weights keyed by method ID.
   */
  function saveConfiguration($type, $enabled_methods);

  /**
   * Resave the configuration to purge missing negotiation methods.
   */
  function purgeConfiguration();

  /**
   * Updates the configuration based on the given language types.
   *
   * Stores the list of the language types along with information about their
   * configurable state. Stores the default settings if the language type is
   * not configurable.
   *
   * @param array $types
   *   An array of configurable language types.
   */
  function updateConfiguration(array $types);

}
