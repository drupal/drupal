<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Drupal\Component\Utility\MapArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\StateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class responsible for initializing each language type.
 */
class LanguageManager {

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Key/Value Store to use for state.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state = NULL;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * An array of language objects keyed by language type.
   *
   * @var array
   */
  protected $languages;

  /**
   * Whether or not the language manager has been initialized.
   *
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * Whether already in the process of language initialization.
   *
   * @todo This is only needed due to the circular dependency between language
   *   and config. See http://drupal.org/node/1862202 for the plan to fix this.
   *
   * @var bool
   */
  protected $initializing = FALSE;

  /**
   * Constructs an LanguageManager object.
   *
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   (optional) The state keyvalue store. Defaults to NULL.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   (optional) The module handler service. Defaults to NULL.
   */
  public function __construct(StateInterface $state = NULL, ModuleHandlerInterface $module_handler = NULL) {
    $this->state = $state;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Initializes each language type to a language object.
   */
  public function init() {
    if ($this->initialized) {
      return;
    }
    if ($this->isMultilingual()) {
      foreach ($this->getLanguageTypes() as $type) {
        $this->getLanguage($type);
      }
    }
    $this->initialized = TRUE;
  }

  /**
   * Sets the $request property and resets all language types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
    $this->reset();
    $this->init();
  }

  /**
   * Returns a language object for the given type.
   *
   * @param string $type
   *   (optional) The language type, e.g. the interface or the content language.
   *   Defaults to \Drupal\Core\Language\Language::TYPE_INTERFACE.
   *
   * @return \Drupal\Core\Language\Language
   *   A language object for the given type.
   */
  public function getLanguage($type = Language::TYPE_INTERFACE) {
    if (isset($this->languages[$type])) {
      return $this->languages[$type];
    }

    if ($this->isMultilingual() && $this->request) {
      if (!$this->initializing) {
        $this->initializing = TRUE;
        // @todo Objectify the language system so that we don't have to load an
        //   include file and call out to procedural code. See
        //   http://drupal.org/node/1862202
        include_once DRUPAL_ROOT . '/core/includes/language.inc';
        $this->languages[$type] = language_types_initialize($type, $this->request);
        $this->initializing = FALSE;
      }
      else {
        // Config has called getLanguage() during initialization of a language
        // type. Simply return the default language without setting it on the
        // $this->languages property. See the TODO in the docblock for the
        // $initializing property.
        return $this->getLanguageDefault();
      }
    }
    else {
      $this->languages[$type] = $this->getLanguageDefault();
    }
    return $this->languages[$type];
  }

  /**
   * Resets the given language type or all types if none specified.
   *
   * @param string|null $type
   *   (optional) The language type to reset as a string, e.g.,
   *   Language::TYPE_INTERFACE, or NULL to reset all language types. Defaults
   *   to NULL.
   */
  public function reset($type = NULL) {
    if (!isset($type)) {
      $this->languages = array();
      $this->initialized = FALSE;
    }
    elseif (isset($this->languages[$type])) {
      unset($this->languages[$type]);
    }
  }

  /**
   * Returns whether or not the site has more than one language enabled.
   *
   * @return bool
   *   TRUE if more than one language is enabled, FALSE otherwise.
   */
  public function isMultilingual() {
    if (!isset($this->state)) {
      // No state service in install time.
      return FALSE;
    }
    return ($this->state->get('language_count') ?: 1) > 1;
  }

  /**
   * Returns the language fallback candidates for a given context.
   *
   * @param string $langcode
   *   (optional) The language of the current context. Defaults to NULL.
   * @param array $context
   *   (optional) An associative array of data that can be useful to determine
   *   the fallback sequence. The following keys are used in core:
   *   - langcode: The desired language.
   *   - operation: The name of the operation indicating the context where
   *     language fallback is being applied, e.g. 'entity_view'.
   *   - data: An arbitrary data structure that makes sense in the provided
   *     context, e.g. an entity.
   *
   * @return array
   *   An array of language codes sorted by priority: first values should be
   *   tried first.
   */
  public function getFallbackCandidates($langcode = NULL, array $context = array()) {
    if ($this->isMultilingual()) {
      // Get languages ordered by weight, add Language::LANGCODE_NOT_SPECIFIED at
      // the end.
      $candidates = array_keys(language_list());
      $candidates[] = Language::LANGCODE_NOT_SPECIFIED;
      $candidates = MapArray::copyValuesToKeys($candidates);

      // The first candidate should always be the desired language if specified.
      if (!empty($langcode)) {
        $candidates = array($langcode => $langcode) + $candidates;
      }

      // Let other modules hook in and add/change candidates.
      $type = 'language_fallback_candidates';
      $types = array();
      if (!empty($context['operation'])) {
        $types[] = $type . '_' .  $context['operation'];
      }
      $types[] = $type;
      $this->moduleHandler->alter($types, $candidates, $context);
    }
    else {
      $candidates = array(Language::LANGCODE_DEFAULT);
    }

    return $candidates;
  }

  /**
   * Returns an array of the available language types.
   *
   * @return array()
   *   An array of all language types.
   */
  protected function getLanguageTypes() {
    return language_types_get_all();
  }

  /**
   * Returns a language object representing the site's default language.
   *
   * @return \Drupal\Core\Language\Language
   *   A language object.
   */
  protected function getLanguageDefault() {
    $default_info = variable_get('language_default', array(
      'id' => 'en',
      'name' => 'English',
      'direction' => 0,
      'weight' => 0,
      'locked' => 0,
    ));
    $default_info['default'] = TRUE;
    return new Language($default_info);
  }

  /**
   * Some common languages with their English and native names.
   *
   * Language codes are defined by the W3C language tags document for
   * interoperability. Language codes typically have a language and optionally,
   * a script or regional variant name. See
   * http://www.w3.org/International/articles/language-tags/ for more information.
   *
   * This list is based on languages available from localize.drupal.org. See
   * http://localize.drupal.org/issues for information on how to add languages
   * there.
   *
   * The "Left-to-right marker" comments and the enclosed UTF-8 markers are to
   * make otherwise strange looking PHP syntax natural (to not be displayed in
   * right to left). See http://drupal.org/node/128866#comment-528929.
   *
   * @return array
   *   An array of language code to language name information.
   *   Language name information itself is an array of English and native names.
   */
  public static function getStandardLanguageList() {
    return array(
      'af' => array('Afrikaans', 'Afrikaans'),
      'am' => array('Amharic', 'አማርኛ'),
      'ar' => array('Arabic', /* Left-to-right marker "‭" */ 'العربية', Language::DIRECTION_RTL),
      'ast' => array('Asturian', 'Asturianu'),
      'az' => array('Azerbaijani', 'Azərbaycanca'),
      'be' => array('Belarusian', 'Беларуская'),
      'bg' => array('Bulgarian', 'Български'),
      'bn' => array('Bengali', 'বাংলা'),
      'bo' => array('Tibetan', 'བོད་སྐད་'),
      'bs' => array('Bosnian', 'Bosanski'),
      'ca' => array('Catalan', 'Català'),
      'cs' => array('Czech', 'Čeština'),
      'cy' => array('Welsh', 'Cymraeg'),
      'da' => array('Danish', 'Dansk'),
      'de' => array('German', 'Deutsch'),
      'dz' => array('Dzongkha', 'རྫོང་ཁ'),
      'el' => array('Greek', 'Ελληνικά'),
      'en' => array('English', 'English'),
      'eo' => array('Esperanto', 'Esperanto'),
      'es' => array('Spanish', 'Español'),
      'et' => array('Estonian', 'Eesti'),
      'eu' => array('Basque', 'Euskera'),
      'fa' => array('Persian, Farsi', /* Left-to-right marker "‭" */ 'فارسی', Language::DIRECTION_RTL),
      'fi' => array('Finnish', 'Suomi'),
      'fil' => array('Filipino', 'Filipino'),
      'fo' => array('Faeroese', 'Føroyskt'),
      'fr' => array('French', 'Français'),
      'fy' => array('Frisian, Western', 'Frysk'),
      'ga' => array('Irish', 'Gaeilge'),
      'gd' => array('Scots Gaelic', 'Gàidhlig'),
      'gl' => array('Galician', 'Galego'),
      'gsw-berne' => array('Swiss German', 'Schwyzerdütsch'),
      'gu' => array('Gujarati', 'ગુજરાતી'),
      'he' => array('Hebrew', /* Left-to-right marker "‭" */ 'עברית', Language::DIRECTION_RTL),
      'hi' => array('Hindi', 'हिन्दी'),
      'hr' => array('Croatian', 'Hrvatski'),
      'ht' => array('Haitian Creole', 'Kreyòl ayisyen'),
      'hu' => array('Hungarian', 'Magyar'),
      'hy' => array('Armenian', 'Հայերեն'),
      'id' => array('Indonesian', 'Bahasa Indonesia'),
      'is' => array('Icelandic', 'Íslenska'),
      'it' => array('Italian', 'Italiano'),
      'ja' => array('Japanese', '日本語'),
      'jv' => array('Javanese', 'Basa Java'),
      'ka' => array('Georgian', 'ქართული ენა'),
      'kk' => array('Kazakh', 'Қазақ'),
      'km' => array('Khmer', 'ភាសាខ្មែរ'),
      'kn' => array('Kannada', 'ಕನ್ನಡ'),
      'ko' => array('Korean', '한국어'),
      'ku' => array('Kurdish', 'Kurdî'),
      'ky' => array('Kyrgyz', 'Кыргызча'),
      'lo' => array('Lao', 'ພາສາລາວ'),
      'lt' => array('Lithuanian', 'Lietuvių'),
      'lv' => array('Latvian', 'Latviešu'),
      'mg' => array('Malagasy', 'Malagasy'),
      'mk' => array('Macedonian', 'Македонски'),
      'ml' => array('Malayalam', 'മലയാളം'),
      'mn' => array('Mongolian', 'монгол'),
      'mr' => array('Marathi', 'मराठी'),
      'ms' => array('Bahasa Malaysia', 'بهاس ملايو'),
      'my' => array('Burmese', 'ဗမာစကား'),
      'ne' => array('Nepali', 'नेपाली'),
      'nl' => array('Dutch', 'Nederlands'),
      'nb' => array('Norwegian Bokmål', 'Bokmål'),
      'nn' => array('Norwegian Nynorsk', 'Nynorsk'),
      'oc' => array('Occitan', 'Occitan'),
      'pa' => array('Punjabi', 'ਪੰਜਾਬੀ'),
      'pl' => array('Polish', 'Polski'),
      'pt-pt' => array('Portuguese, Portugal', 'Português, Portugal'),
      'pt-br' => array('Portuguese, Brazil', 'Português, Brasil'),
      'ro' => array('Romanian', 'Română'),
      'ru' => array('Russian', 'Русский'),
      'sco' => array('Scots', 'Scots'),
      'se' => array('Northern Sami', 'Sámi'),
      'si' => array('Sinhala', 'සිංහල'),
      'sk' => array('Slovak', 'Slovenčina'),
      'sl' => array('Slovenian', 'Slovenščina'),
      'sq' => array('Albanian', 'Shqip'),
      'sr' => array('Serbian', 'Српски'),
      'sv' => array('Swedish', 'Svenska'),
      'sw' => array('Swahili', 'Kiswahili'),
      'ta' => array('Tamil', 'தமிழ்'),
      'ta-lk' => array('Tamil, Sri Lanka', 'தமிழ், இலங்கை'),
      'te' => array('Telugu', 'తెలుగు'),
      'th' => array('Thai', 'ภาษาไทย'),
      'tr' => array('Turkish', 'Türkçe'),
      'tyv' => array('Tuvan', 'Тыва дыл'),
      'ug' => array('Uyghur', 'Уйғур'),
      'uk' => array('Ukrainian', 'Українська'),
      'ur' => array('Urdu', /* Left-to-right marker "‭" */ 'اردو', Language::DIRECTION_RTL),
      'vi' => array('Vietnamese', 'Tiếng Việt'),
      'xx-lolspeak' => array('Lolspeak', 'Lolspeak'),
      'zh-hans' => array('Chinese, Simplified', '简体中文'),
      'zh-hant' => array('Chinese, Traditional', '繁體中文'),
    );
  }

}
