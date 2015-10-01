<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Class responsible for providing language support on language-unaware sites.
 */
class LanguageManager implements LanguageManagerInterface {
  use DependencySerializationTrait;

  /**
   * A static cache of translated language lists.
   *
   * Array of arrays to cache the result of self::getLanguages() keyed by the
   * language the list is translated to (first level) and the flags provided to
   * the method (second level).
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   *
   * @see \Drupal\Core\Language\LanguageManager::getLanguages()
   */
  protected $languages = array();

  /**
   * The default language object.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $defaultLanguage;

  /**
   * Constructs the language manager.
   *
   * @param \Drupal\Core\Language\LanguageDefault $default_language
   *   The default language.
   */
  public function __construct(LanguageDefault $default_language) {
    $this->defaultLanguage = $default_language;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultilingual() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageTypes() {
    return array(LanguageInterface::TYPE_INTERFACE, LanguageInterface::TYPE_CONTENT, LanguageInterface::TYPE_URL);
  }

  /**
   * Returns information about all defined language types.
   *
   * Defines the three core language types:
   * - Interface language is the only configurable language type in core. It is
   *   used by t() as the default language if none is specified.
   * - Content language is by default non-configurable and inherits the
   *   interface language negotiated value. It is used by the Field API to
   *   determine the display language for fields if no explicit value is
   *   specified.
   * - URL language is by default non-configurable and is determined through the
   *   URL language negotiation method or the URL fallback language negotiation
   *   method if no language can be detected. It is used by l() as the default
   *   language if none is specified.
   *
   * @return array
   *   An associative array of language type information arrays keyed by
   *   language type machine name, in the format of
   *   hook_language_types_info().
   */
  public function getDefinedLanguageTypesInfo() {
    $this->definedLanguageTypesInfo = array(
      LanguageInterface::TYPE_INTERFACE => array(
        'name' => new TranslatableMarkup('Interface text'),
        'description' => new TranslatableMarkup('Order of language detection methods for interface text. If a translation of interface text is available in the detected language, it will be displayed.'),
        'locked' => TRUE,
      ),
      LanguageInterface::TYPE_CONTENT => array(
        'name' => new TranslatableMarkup('Content'),
        'description' => new TranslatableMarkup('Order of language detection methods for content. If a version of content is available in the detected language, it will be displayed.'),
        'locked' => TRUE,
      ),
      LanguageInterface::TYPE_URL => array(
        'locked' => TRUE,
      ),
    );

    return $this->definedLanguageTypesInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentLanguage($type = LanguageInterface::TYPE_INTERFACE) {
    return $this->getDefaultLanguage();
  }

  /**
   * {@inheritdoc}
   */
  public function reset($type = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultLanguage() {
    return $this->defaultLanguage->get();
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguages($flags = LanguageInterface::STATE_CONFIGURABLE) {
    $static_cache_id = $this->getCurrentLanguage()->getId();
    if (!isset($this->languages[$static_cache_id][$flags])) {
      // If this language manager is used, there are no configured languages.
      // The default language and locked languages comprise the full language
      // list.
      $default = $this->getDefaultLanguage();
      $languages = array($default->getId() => $default);
      $languages += $this->getDefaultLockedLanguages($default->getWeight());

      // Filter the full list of languages based on the value of $flags.
      $this->languages[$static_cache_id][$flags] = $this->filterLanguages($languages, $flags);
    }
    return $this->languages[$static_cache_id][$flags];
  }

  /**
   * {@inheritdoc}
   */
  public function getNativeLanguages() {
    // In a language unaware site we don't have translated languages.
    return $this->getLanguages();
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage($langcode) {
    $languages = $this->getLanguages(LanguageInterface::STATE_ALL);
    return isset($languages[$langcode]) ? $languages[$langcode] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageName($langcode) {
    if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      return new TranslatableMarkup('None');
    }
    if ($language = $this->getLanguage($langcode)) {
      return $language->getName();
    }
    if (empty($langcode)) {
      return new TranslatableMarkup('Unknown');
    }
    return new TranslatableMarkup('Unknown (@langcode)', array('@langcode' => $langcode));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultLockedLanguages($weight = 0) {
    $languages = array();

    $locked_language = array(
      'default' => FALSE,
      'locked' => TRUE,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    // This is called very early while initializing the language system. Prevent
    // early t() calls by using the TranslatableMarkup.
    $languages[LanguageInterface::LANGCODE_NOT_SPECIFIED] = new Language(array(
      'id' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'name' => new TranslatableMarkup('Not specified'),
      'weight' => ++$weight,
    ) + $locked_language);

    $languages[LanguageInterface::LANGCODE_NOT_APPLICABLE] = new Language(array(
      'id' => LanguageInterface::LANGCODE_NOT_APPLICABLE,
      'name' => new TranslatableMarkup('Not applicable'),
      'weight' => ++$weight,
    ) + $locked_language);

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function isLanguageLocked($langcode) {
    $language = $this->getLanguage($langcode);
    return ($language ? $language->isLocked() : FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackCandidates(array $context = array()) {
    return array(LanguageInterface::LANGCODE_DEFAULT);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks($type, Url $url) {
    return array();
  }

  /**
   * @inheritdoc
   */
  public static function getStandardLanguageList() {
    // This list is based on languages available from localize.drupal.org. See
    // http://localize.drupal.org/issues for information on how to add languages
    // there.
    //
    // The "Left-to-right marker" comments and the enclosed UTF-8 markers are to
    // make otherwise strange looking PHP syntax natural (to not be displayed in
    // right to left). See https://www.drupal.org/node/128866#comment-528929.
    return array(
      'af' => array('Afrikaans', 'Afrikaans'),
      'am' => array('Amharic', 'አማርኛ'),
      'ar' => array('Arabic', /* Left-to-right marker "‭" */ 'العربية', LanguageInterface::DIRECTION_RTL),
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
      'fa' => array('Persian, Farsi', /* Left-to-right marker "‭" */ 'فارسی', LanguageInterface::DIRECTION_RTL),
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
      'he' => array('Hebrew', /* Left-to-right marker "‭" */ 'עברית', LanguageInterface::DIRECTION_RTL),
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
      'nb' => array('Norwegian Bokmål', 'Norsk, bokmål'),
      'nn' => array('Norwegian Nynorsk', 'Norsk, nynorsk'),
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
      'ur' => array('Urdu', /* Left-to-right marker "‭" */ 'اردو', LanguageInterface::DIRECTION_RTL),
      'vi' => array('Vietnamese', 'Tiếng Việt'),
      'xx-lolspeak' => array('Lolspeak', 'Lolspeak'),
      'zh-hans' => array('Chinese, Simplified', '简体中文'),
      'zh-hant' => array('Chinese, Traditional', '繁體中文'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * This function is a noop since the configuration cannot be overridden by
   * language unless the Language module is enabled. That replaces the default
   * language manager with a configurable language manager.
   *
   * @see \Drupal\language\ConfigurableLanguageManager::setConfigOverrideLanguage()
   */
  public function setConfigOverrideLanguage(LanguageInterface $language = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigOverrideLanguage() {
    return $this->getCurrentLanguage();
  }

  /**
   * Filters the full list of languages based on the value of the flag.
   *
   * The locked languages are removed by default.
   *
   * @param \Drupal\Core\Language\LanguageInterface[] $languages
   *    Array with languages to be filtered.
   * @param int $flags
   *   (optional) Specifies the state of the languages that have to be returned.
   *   It can be: LanguageInterface::STATE_CONFIGURABLE,
   *   LanguageInterface::STATE_LOCKED, or LanguageInterface::STATE_ALL.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   An associative array of languages, keyed by the language code.
   */
  protected function filterLanguages(array $languages, $flags = LanguageInterface::STATE_CONFIGURABLE) {
    // STATE_ALL means we don't actually filter, so skip the rest of the method.
    if ($flags == LanguageInterface::STATE_ALL) {
      return $languages;
    }

    $filtered_languages = array();
    // Add the site's default language if requested.
    if ($flags & LanguageInterface::STATE_SITE_DEFAULT) {

      // Setup a language to have the defaults with data appropriate of the
      // default language only for runtime.
      $defaultLanguage = $this->getDefaultLanguage();
      $default = new Language(
        array(
          'id' => $defaultLanguage->getId(),
          'name' => new TranslatableMarkup("Site's default language (@lang_name)",
            array('@lang_name' => $defaultLanguage->getName())),
          'direction' => $defaultLanguage->getDirection(),
          'weight' => $defaultLanguage->getWeight(),
        )
      );
      $filtered_languages[LanguageInterface::LANGCODE_SITE_DEFAULT] = $default;
    }

    foreach ($languages as $id => $language) {
      if (($language->isLocked() && ($flags & LanguageInterface::STATE_LOCKED)) || (!$language->isLocked() && ($flags & LanguageInterface::STATE_CONFIGURABLE))) {
        $filtered_languages[$id] = $language;
      }
    }

    return $filtered_languages;
  }

}
