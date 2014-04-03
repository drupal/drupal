<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class responsible for providing language support on language-unaware sites.
 */
class LanguageManager extends DependencySerialization implements LanguageManagerInterface {

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * An array of all the available languages keyed by language code.
   *
   * @var array
   */
  protected $languages;

  /**
   * The default language object.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $defaultLanguage;

  /**
   * Constructs the language manager.
   *
   * @param \Drupal\Core\Language\Language $default_language
   *   The default language.
   */
  public function __construct(LanguageDefault $default_language) {
    $this->defaultLanguage = $default_language;
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslation(TranslationInterface $translation) {
    $this->translation = $translation;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translation ? $this->translation->translate($string, $args, $options) : String::format($string, $args);
  }

  /**
   * {@inheritdoc}
   */
  public function init() {
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
    return array(Language::TYPE_INTERFACE, Language::TYPE_CONTENT, Language::TYPE_URL);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentLanguage($type = Language::TYPE_INTERFACE) {
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
  public function getLanguages($flags = Language::STATE_CONFIGURABLE) {
    // Initialize master language list.
    if (!isset($this->languages)) {
      // No language module, so use the default language only.
      $default = $this->getDefaultLanguage();
      $this->languages = array($default->id => $default);
      // Add the special languages, they will be filtered later if needed.
      $this->languages += $this->getDefaultLockedLanguages($default->weight);
    }

    // Filter the full list of languages based on the value of the $all flag. By
    // default we remove the locked languages, but the caller may request for
    // those languages to be added as well.
    $filtered_languages = array();

    // Add the site's default language if flagged as allowed value.
    if ($flags & Language::STATE_SITE_DEFAULT) {
      $default = isset($default) ? $default : $this->getDefaultLanguage();
      // Rename the default language.
      $default->name = $this->t("Site's default language (@lang_name)", array('@lang_name' => $default->name));
      $filtered_languages['site_default'] = $default;
    }

    foreach ($this->languages as $id => $language) {
      if (($language->locked && ($flags & Language::STATE_LOCKED)) || (!$language->locked && ($flags & Language::STATE_CONFIGURABLE))) {
        $filtered_languages[$id] = $language;
      }
    }

    return $filtered_languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage($langcode) {
    $languages = $this->getLanguages(Language::STATE_ALL);
    return isset($languages[$langcode]) ? $languages[$langcode] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageName($langcode) {
    if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
      return $this->t('None');
    }
    if ($language = $this->getLanguage($langcode)) {
      return $language->name;
    }
    if (empty($langcode)) {
      return $this->t('Unknown');
    }
    return $this->t('Unknown (@langcode)', array('@langcode' => $langcode));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultLockedLanguages($weight = 0) {
    $languages = array();

    $locked_language = array(
      'default' => FALSE,
      'locked' => TRUE,
    );
    $languages[Language::LANGCODE_NOT_SPECIFIED] = new Language(array(
      'id' => Language::LANGCODE_NOT_SPECIFIED,
      'name' => $this->t('Not specified'),
      'weight' => ++$weight,
    ) + $locked_language);

    $languages[Language::LANGCODE_NOT_APPLICABLE] = new Language(array(
      'id' => Language::LANGCODE_NOT_APPLICABLE,
      'name' => $this->t('Not applicable'),
      'weight' => ++$weight,
    ) + $locked_language);

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function isLanguageLocked($langcode) {
    $language = $this->getLanguage($langcode);
    return ($language ? $language->locked : FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackCandidates($langcode = NULL, array $context = array()) {
    return array(Language::LANGCODE_DEFAULT);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks($type, $path) {
    return array();
  }

  /**
   * Some common languages with their English and native names.
   *
   * Language codes are defined by the W3C language tags document for
   * interoperability. Language codes typically have a language and, optionally,
   * a script or regional variant name. See:
   * http://www.w3.org/International/articles/language-tags/ for more
   * information.
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

  /**
   * {@inheritdoc}
   *
   * This function is a noop since the configuration cannot be overridden by
   * language unless the Language module is enabled. That replaces the default
   * language manager with a configurable language manager.
   *
   * @see \Drupal\language\ConfigurableLanguageManager::setConfigOverrideLanguage()
   */
  public function setConfigOverrideLanguage(Language $language = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigOverrideLanguage() {
    return $this->getCurrentLanguage();
  }

}
