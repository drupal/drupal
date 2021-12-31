<?php

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
  protected $languages = [];

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
    return [LanguageInterface::TYPE_INTERFACE, LanguageInterface::TYPE_CONTENT, LanguageInterface::TYPE_URL];
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
    $this->definedLanguageTypesInfo = [
      LanguageInterface::TYPE_INTERFACE => [
        'name' => new TranslatableMarkup('Interface text'),
        'description' => new TranslatableMarkup('Order of language detection methods for interface text. If a translation of interface text is available in the detected language, it will be displayed.'),
        'locked' => TRUE,
      ],
      LanguageInterface::TYPE_CONTENT => [
        'name' => new TranslatableMarkup('Content'),
        'description' => new TranslatableMarkup('Order of language detection methods for content. If a version of content is available in the detected language, it will be displayed.'),
        'locked' => TRUE,
      ],
      LanguageInterface::TYPE_URL => [
        'locked' => TRUE,
      ],
    ];

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
      $languages = [$default->getId() => $default];
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
    return $languages[$langcode] ?? NULL;
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
    return new TranslatableMarkup('Unknown (@langcode)', ['@langcode' => $langcode]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultLockedLanguages($weight = 0) {
    $languages = [];

    $locked_language = [
      'default' => FALSE,
      'locked' => TRUE,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    // This is called very early while initializing the language system. Prevent
    // early t() calls by using the TranslatableMarkup.
    $languages[LanguageInterface::LANGCODE_NOT_SPECIFIED] = new Language([
      'id' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'name' => new TranslatableMarkup('Not specified'),
      'weight' => ++$weight,
    ] + $locked_language);

    $languages[LanguageInterface::LANGCODE_NOT_APPLICABLE] = new Language([
      'id' => LanguageInterface::LANGCODE_NOT_APPLICABLE,
      'name' => new TranslatableMarkup('Not applicable'),
      'weight' => ++$weight,
    ] + $locked_language);

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
  public function getFallbackCandidates(array $context = []) {
    return [LanguageInterface::LANGCODE_DEFAULT];
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks($type, Url $url) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStandardLanguageList() {
    // This list is based on languages available from localize.drupal.org. See
    // http://localize.drupal.org/issues for information on how to add languages
    // there.
    //
    // The "Left-to-right marker" comments and the enclosed UTF-8 markers are to
    // make otherwise strange looking PHP syntax natural (to not be displayed in
    // right to left). See https://www.drupal.org/node/128866#comment-528929.
    // cSpell:disable
    return [
      'af' => ['Afrikaans', 'Afrikaans'],
      'am' => ['Amharic', 'አማርኛ'],
      'ar' => ['Arabic', /* Left-to-right marker "‭" */ 'العربية', LanguageInterface::DIRECTION_RTL],
      'ast' => ['Asturian', 'Asturianu'],
      'az' => ['Azerbaijani', 'Azərbaycanca'],
      'be' => ['Belarusian', 'Беларуская'],
      'bg' => ['Bulgarian', 'Български'],
      'bn' => ['Bengali', 'বাংলা'],
      'bo' => ['Tibetan', 'བོད་སྐད་'],
      'bs' => ['Bosnian', 'Bosanski'],
      'ca' => ['Catalan', 'Català'],
      'cs' => ['Czech', 'Čeština'],
      'cy' => ['Welsh', 'Cymraeg'],
      'da' => ['Danish', 'Dansk'],
      'de' => ['German', 'Deutsch'],
      'dz' => ['Dzongkha', 'རྫོང་ཁ'],
      'el' => ['Greek', 'Ελληνικά'],
      'en' => ['English', 'English'],
      'en-x-simple' => ['Simple English', 'Simple English'],
      'eo' => ['Esperanto', 'Esperanto'],
      'es' => ['Spanish', 'Español'],
      'et' => ['Estonian', 'Eesti'],
      'eu' => ['Basque', 'Euskera'],
      'fa' => ['Persian, Farsi', /* Left-to-right marker "‭" */ 'فارسی', LanguageInterface::DIRECTION_RTL],
      'fi' => ['Finnish', 'Suomi'],
      'fil' => ['Filipino', 'Filipino'],
      'fo' => ['Faeroese', 'Føroyskt'],
      'fr' => ['French', 'Français'],
      'fy' => ['Frisian, Western', 'Frysk'],
      'ga' => ['Irish', 'Gaeilge'],
      'gd' => ['Scots Gaelic', 'Gàidhlig'],
      'gl' => ['Galician', 'Galego'],
      'gsw-berne' => ['Swiss German', 'Schwyzerdütsch'],
      'gu' => ['Gujarati', 'ગુજરાતી'],
      'he' => ['Hebrew', /* Left-to-right marker "‭" */ 'עברית', LanguageInterface::DIRECTION_RTL],
      'hi' => ['Hindi', 'हिन्दी'],
      'hr' => ['Croatian', 'Hrvatski'],
      'ht' => ['Haitian Creole', 'Kreyòl ayisyen'],
      'hu' => ['Hungarian', 'Magyar'],
      'hy' => ['Armenian', 'Հայերեն'],
      'id' => ['Indonesian', 'Bahasa Indonesia'],
      'is' => ['Icelandic', 'Íslenska'],
      'it' => ['Italian', 'Italiano'],
      'ja' => ['Japanese', '日本語'],
      'jv' => ['Javanese', 'Basa Java'],
      'ka' => ['Georgian', 'ქართული ენა'],
      'kk' => ['Kazakh', 'Қазақ'],
      'km' => ['Khmer', 'ភាសាខ្មែរ'],
      'kn' => ['Kannada', 'ಕನ್ನಡ'],
      'ko' => ['Korean', '한국어'],
      'ku' => ['Kurdish', 'Kurdî'],
      'ky' => ['Kyrgyz', 'Кыргызча'],
      'lo' => ['Lao', 'ພາສາລາວ'],
      'lt' => ['Lithuanian', 'Lietuvių'],
      'lv' => ['Latvian', 'Latviešu'],
      'mg' => ['Malagasy', 'Malagasy'],
      'mk' => ['Macedonian', 'Македонски'],
      'ml' => ['Malayalam', 'മലയാളം'],
      'mn' => ['Mongolian', 'монгол'],
      'mr' => ['Marathi', 'मराठी'],
      'ms' => ['Bahasa Malaysia', 'بهاس ملايو'],
      'my' => ['Burmese', 'ဗမာစကား'],
      'ne' => ['Nepali', 'नेपाली'],
      'nl' => ['Dutch', 'Nederlands'],
      'nb' => ['Norwegian Bokmål', 'Norsk, bokmål'],
      'nn' => ['Norwegian Nynorsk', 'Norsk, nynorsk'],
      'oc' => ['Occitan', 'Occitan'],
      'pa' => ['Punjabi', 'ਪੰਜਾਬੀ'],
      'pl' => ['Polish', 'Polski'],
      'pt-pt' => ['Portuguese, Portugal', 'Português, Portugal'],
      'pt-br' => ['Portuguese, Brazil', 'Português, Brasil'],
      'ro' => ['Romanian', 'Română'],
      'ru' => ['Russian', 'Русский'],
      'sco' => ['Scots', 'Scots'],
      'se' => ['Northern Sami', 'Sámi'],
      'si' => ['Sinhala', 'සිංහල'],
      'sk' => ['Slovak', 'Slovenčina'],
      'sl' => ['Slovenian', 'Slovenščina'],
      'sq' => ['Albanian', 'Shqip'],
      'sr' => ['Serbian', 'Српски'],
      'sv' => ['Swedish', 'Svenska'],
      'sw' => ['Swahili', 'Kiswahili'],
      'ta' => ['Tamil', 'தமிழ்'],
      'ta-lk' => ['Tamil, Sri Lanka', 'தமிழ், இலங்கை'],
      'te' => ['Telugu', 'తెలుగు'],
      'th' => ['Thai', 'ภาษาไทย'],
      'tr' => ['Turkish', 'Türkçe'],
      'tyv' => ['Tuvan', 'Тыва дыл'],
      'ug' => ['Uyghur', /* Left-to-right marker "‭" */ 'ئۇيغۇرچە', LanguageInterface::DIRECTION_RTL],
      'uk' => ['Ukrainian', 'Українська'],
      'ur' => ['Urdu', /* Left-to-right marker "‭" */ 'اردو', LanguageInterface::DIRECTION_RTL],
      'vi' => ['Vietnamese', 'Tiếng Việt'],
      'xx-lolspeak' => ['Lolspeak', 'Lolspeak'],
      'zh-hans' => ['Chinese, Simplified', '简体中文'],
      'zh-hant' => ['Chinese, Traditional', '繁體中文'],
    ];
    // cSpell:enable
  }

  /**
   * The 6 official languages used at the United Nations.
   *
   * This list is based on
   * http://www.un.org/en/sections/about-un/official-languages/index.html and it
   * uses the same format as getStandardLanguageList().
   *
   * @return array
   *   An array with language codes as keys, and English and native language
   *   names as values.
   */
  public static function getUnitedNationsLanguageList() {
    // cSpell:disable
    return [
      'ar' => ['Arabic', /* Left-to-right marker "‭" */ 'العربية', LanguageInterface::DIRECTION_RTL],
      'zh-hans' => ['Chinese, Simplified', '简体中文'],
      'en' => ['English', 'English'],
      'fr' => ['French', 'Français'],
      'ru' => ['Russian', 'Русский'],
      'es' => ['Spanish', 'Español'],
    ];
    // cSpell:enable
  }

  /**
   * Sets the configuration override language.
   *
   * This function is a noop since the configuration cannot be overridden by
   * language unless the Language module is enabled. That replaces the default
   * language manager with a configurable language manager.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language to override configuration with.
   *
   * @return $this
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
   *   Array with languages to be filtered.
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

    $filtered_languages = [];
    // Add the site's default language if requested.
    if ($flags & LanguageInterface::STATE_SITE_DEFAULT) {

      // Setup a language to have the defaults with data appropriate of the
      // default language only for runtime.
      $defaultLanguage = $this->getDefaultLanguage();
      $default = new Language(
        [
          'id' => $defaultLanguage->getId(),
          'name' => new TranslatableMarkup("Site's default language (@lang_name)",
            ['@lang_name' => $defaultLanguage->getName()]),
          'direction' => $defaultLanguage->getDirection(),
          'weight' => $defaultLanguage->getWeight(),
        ]
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
