<?php

namespace Drupal\Core\Locale;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Component\Transliteration\TransliterationInterface;

/**
 * Provides list of countries.
 */
class CountryManager implements CountryManagerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * An array of country code, country name pairs keyed by language code.
   *
   * @var array
   */
  protected $countries = [];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Component\Transliteration\TransliterationInterface|null $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Language\LanguageManagerInterface|null $language_manager
   *   The language manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TransliterationInterface $transliteration = NULL, LanguageManagerInterface $language_manager = NULL) {
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->transliteration = $transliteration;
    if (!$transliteration) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $transliteration argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3280207', E_USER_DEPRECATED);
      $this->transliteration = \Drupal::service('transliteration');
    }
    if (!$language_manager) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $language_manager argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3280207', E_USER_DEPRECATED);
      $this->languageManager = \Drupal::service('language_manager');
    }
  }

  /**
   * Get an array of all two-letter country code => country name pairs.
   *
   * $countries is updated by core/scripts/update-countries.sh.
   *
   * @param string|null $language_id
   *   (optional) The language code for translation.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  public static function getStandardList($language_id = NULL) {
    // Set up translation options with the specified language.
    $translation_options = ($language_id === NULL) ? [] : ['langcode' => $language_id];

    // cSpell:disable
    $countries = [
      'AC' => t('Ascension Island', [], $translation_options),
      'AD' => t('Andorra', [], $translation_options),
      'AE' => t('United Arab Emirates', [], $translation_options),
      'AF' => t('Afghanistan', [], $translation_options),
      'AG' => t('Antigua & Barbuda', [], $translation_options),
      'AI' => t('Anguilla', [], $translation_options),
      'AL' => t('Albania', [], $translation_options),
      'AM' => t('Armenia', [], $translation_options),
      'AN' => t('Netherlands Antilles', [], $translation_options),
      'AO' => t('Angola', [], $translation_options),
      'AQ' => t('Antarctica', [], $translation_options),
      'AR' => t('Argentina', [], $translation_options),
      'AS' => t('American Samoa', [], $translation_options),
      'AT' => t('Austria', [], $translation_options),
      'AU' => t('Australia', [], $translation_options),
      'AW' => t('Aruba', [], $translation_options),
      'AX' => t('Åland Islands', [], $translation_options),
      'AZ' => t('Azerbaijan', [], $translation_options),
      'BA' => t('Bosnia & Herzegovina', [], $translation_options),
      'BB' => t('Barbados', [], $translation_options),
      'BD' => t('Bangladesh', [], $translation_options),
      'BE' => t('Belgium', [], $translation_options),
      'BF' => t('Burkina Faso', [], $translation_options),
      'BG' => t('Bulgaria', [], $translation_options),
      'BH' => t('Bahrain', [], $translation_options),
      'BI' => t('Burundi', [], $translation_options),
      'BJ' => t('Benin', [], $translation_options),
      'BL' => t('St. Barthélemy', [], $translation_options),
      'BM' => t('Bermuda', [], $translation_options),
      'BN' => t('Brunei', [], $translation_options),
      'BO' => t('Bolivia', [], $translation_options),
      'BQ' => t('Caribbean Netherlands', [], $translation_options),
      'BR' => t('Brazil', [], $translation_options),
      'BS' => t('Bahamas', [], $translation_options),
      'BT' => t('Bhutan', [], $translation_options),
      'BV' => t('Bouvet Island', [], $translation_options),
      'BW' => t('Botswana', [], $translation_options),
      'BY' => t('Belarus', [], $translation_options),
      'BZ' => t('Belize', [], $translation_options),
      'CA' => t('Canada', [], $translation_options),
      'CC' => t('Cocos (Keeling) Islands', [], $translation_options),
      'CD' => t('Congo - Kinshasa', [], $translation_options),
      'CF' => t('Central African Republic', [], $translation_options),
      'CG' => t('Congo - Brazzaville', [], $translation_options),
      'CH' => t('Switzerland', [], $translation_options),
      'CI' => t('Côte d’Ivoire', [], $translation_options),
      'CK' => t('Cook Islands', [], $translation_options),
      'CL' => t('Chile', [], $translation_options),
      'CM' => t('Cameroon', [], $translation_options),
      'CN' => t('China', [], $translation_options),
      'CO' => t('Colombia', [], $translation_options),
      'CP' => t('Clipperton Island', [], $translation_options),
      'CR' => t('Costa Rica', [], $translation_options),
      'CU' => t('Cuba', [], $translation_options),
      'CV' => t('Cape Verde', [], $translation_options),
      'CW' => t('Curaçao', [], $translation_options),
      'CX' => t('Christmas Island', [], $translation_options),
      'CY' => t('Cyprus', [], $translation_options),
      'CZ' => t('Czechia', [], $translation_options),
      'DE' => t('Germany', [], $translation_options),
      'DG' => t('Diego Garcia', [], $translation_options),
      'DJ' => t('Djibouti', [], $translation_options),
      'DK' => t('Denmark', [], $translation_options),
      'DM' => t('Dominica', [], $translation_options),
      'DO' => t('Dominican Republic', [], $translation_options),
      'DZ' => t('Algeria', [], $translation_options),
      'EA' => t('Ceuta & Melilla', [], $translation_options),
      'EC' => t('Ecuador', [], $translation_options),
      'EE' => t('Estonia', [], $translation_options),
      'EG' => t('Egypt', [], $translation_options),
      'EH' => t('Western Sahara', [], $translation_options),
      'ER' => t('Eritrea', [], $translation_options),
      'ES' => t('Spain', [], $translation_options),
      'ET' => t('Ethiopia', [], $translation_options),
      'FI' => t('Finland', [], $translation_options),
      'FJ' => t('Fiji', [], $translation_options),
      'FK' => t('Falkland Islands', [], $translation_options),
      'FM' => t('Micronesia', [], $translation_options),
      'FO' => t('Faroe Islands', [], $translation_options),
      'FR' => t('France', [], $translation_options),
      'GA' => t('Gabon', [], $translation_options),
      'GB' => t('United Kingdom', [], $translation_options),
      'GD' => t('Grenada', [], $translation_options),
      'GE' => t('Georgia', [], $translation_options),
      'GF' => t('French Guiana', [], $translation_options),
      'GG' => t('Guernsey', [], $translation_options),
      'GH' => t('Ghana', [], $translation_options),
      'GI' => t('Gibraltar', [], $translation_options),
      'GL' => t('Greenland', [], $translation_options),
      'GM' => t('Gambia', [], $translation_options),
      'GN' => t('Guinea', [], $translation_options),
      'GP' => t('Guadeloupe', [], $translation_options),
      'GQ' => t('Equatorial Guinea', [], $translation_options),
      'GR' => t('Greece', [], $translation_options),
      'GS' => t('South Georgia & South Sandwich Islands', [], $translation_options),
      'GT' => t('Guatemala', [], $translation_options),
      'GU' => t('Guam', [], $translation_options),
      'GW' => t('Guinea-Bissau', [], $translation_options),
      'GY' => t('Guyana', [], $translation_options),
      'HK' => t('Hong Kong SAR China', [], $translation_options),
      'HM' => t('Heard & McDonald Islands', [], $translation_options),
      'HN' => t('Honduras', [], $translation_options),
      'HR' => t('Croatia', [], $translation_options),
      'HT' => t('Haiti', [], $translation_options),
      'HU' => t('Hungary', [], $translation_options),
      'IC' => t('Canary Islands', [], $translation_options),
      'ID' => t('Indonesia', [], $translation_options),
      'IE' => t('Ireland', [], $translation_options),
      'IL' => t('Israel', [], $translation_options),
      'IM' => t('Isle of Man', [], $translation_options),
      'IN' => t('India', [], $translation_options),
      'IO' => t('British Indian Ocean Territory', [], $translation_options),
      'IQ' => t('Iraq', [], $translation_options),
      'IR' => t('Iran', [], $translation_options),
      'IS' => t('Iceland', [], $translation_options),
      'IT' => t('Italy', [], $translation_options),
      'JE' => t('Jersey', [], $translation_options),
      'JM' => t('Jamaica', [], $translation_options),
      'JO' => t('Jordan', [], $translation_options),
      'JP' => t('Japan', [], $translation_options),
      'KE' => t('Kenya', [], $translation_options),
      'KG' => t('Kyrgyzstan', [], $translation_options),
      'KH' => t('Cambodia', [], $translation_options),
      'KI' => t('Kiribati', [], $translation_options),
      'KM' => t('Comoros', [], $translation_options),
      'KN' => t('St. Kitts & Nevis', [], $translation_options),
      'KP' => t('North Korea', [], $translation_options),
      'KR' => t('South Korea', [], $translation_options),
      'KW' => t('Kuwait', [], $translation_options),
      'KY' => t('Cayman Islands', [], $translation_options),
      'KZ' => t('Kazakhstan', [], $translation_options),
      'LA' => t('Laos', [], $translation_options),
      'LB' => t('Lebanon', [], $translation_options),
      'LC' => t('St. Lucia', [], $translation_options),
      'LI' => t('Liechtenstein', [], $translation_options),
      'LK' => t('Sri Lanka', [], $translation_options),
      'LR' => t('Liberia', [], $translation_options),
      'LS' => t('Lesotho', [], $translation_options),
      'LT' => t('Lithuania', [], $translation_options),
      'LU' => t('Luxembourg', [], $translation_options),
      'LV' => t('Latvia', [], $translation_options),
      'LY' => t('Libya', [], $translation_options),
      'MA' => t('Morocco', [], $translation_options),
      'MC' => t('Monaco', [], $translation_options),
      'MD' => t('Moldova', [], $translation_options),
      'ME' => t('Montenegro', [], $translation_options),
      'MF' => t('St. Martin', [], $translation_options),
      'MG' => t('Madagascar', [], $translation_options),
      'MH' => t('Marshall Islands', [], $translation_options),
      'MK' => t('North Macedonia', [], $translation_options),
      'ML' => t('Mali', [], $translation_options),
      'MM' => t('Myanmar (Burma)', [], $translation_options),
      'MN' => t('Mongolia', [], $translation_options),
      'MO' => t('Macao SAR China', [], $translation_options),
      'MP' => t('Northern Mariana Islands', [], $translation_options),
      'MQ' => t('Martinique', [], $translation_options),
      'MR' => t('Mauritania', [], $translation_options),
      'MS' => t('Montserrat', [], $translation_options),
      'MT' => t('Malta', [], $translation_options),
      'MU' => t('Mauritius', [], $translation_options),
      'MV' => t('Maldives', [], $translation_options),
      'MW' => t('Malawi', [], $translation_options),
      'MX' => t('Mexico', [], $translation_options),
      'MY' => t('Malaysia', [], $translation_options),
      'MZ' => t('Mozambique', [], $translation_options),
      'NA' => t('Namibia', [], $translation_options),
      'NC' => t('New Caledonia', [], $translation_options),
      'NE' => t('Niger', [], $translation_options),
      'NF' => t('Norfolk Island', [], $translation_options),
      'NG' => t('Nigeria', [], $translation_options),
      'NI' => t('Nicaragua', [], $translation_options),
      'NL' => t('Netherlands', [], $translation_options),
      'NO' => t('Norway', [], $translation_options),
      'NP' => t('Nepal', [], $translation_options),
      'NR' => t('Nauru', [], $translation_options),
      'NU' => t('Niue', [], $translation_options),
      'NZ' => t('New Zealand', [], $translation_options),
      'OM' => t('Oman', [], $translation_options),
      'PA' => t('Panama', [], $translation_options),
      'PE' => t('Peru', [], $translation_options),
      'PF' => t('French Polynesia', [], $translation_options),
      'PG' => t('Papua New Guinea', [], $translation_options),
      'PH' => t('Philippines', [], $translation_options),
      'PK' => t('Pakistan', [], $translation_options),
      'PL' => t('Poland', [], $translation_options),
      'PM' => t('St. Pierre & Miquelon', [], $translation_options),
      'PN' => t('Pitcairn Islands', [], $translation_options),
      'PR' => t('Puerto Rico', [], $translation_options),
      'PS' => t('Palestinian Territories', [], $translation_options),
      'PT' => t('Portugal', [], $translation_options),
      'PW' => t('Palau', [], $translation_options),
      'PY' => t('Paraguay', [], $translation_options),
      'QA' => t('Qatar', [], $translation_options),
      'QO' => t('Outlying Oceania', [], $translation_options),
      'RE' => t('Réunion', [], $translation_options),
      'RO' => t('Romania', [], $translation_options),
      'RS' => t('Serbia', [], $translation_options),
      'RU' => t('Russia', [], $translation_options),
      'RW' => t('Rwanda', [], $translation_options),
      'SA' => t('Saudi Arabia', [], $translation_options),
      'SB' => t('Solomon Islands', [], $translation_options),
      'SC' => t('Seychelles', [], $translation_options),
      'SD' => t('Sudan', [], $translation_options),
      'SE' => t('Sweden', [], $translation_options),
      'SG' => t('Singapore', [], $translation_options),
      'SH' => t('St. Helena', [], $translation_options),
      'SI' => t('Slovenia', [], $translation_options),
      'SJ' => t('Svalbard & Jan Mayen', [], $translation_options),
      'SK' => t('Slovakia', [], $translation_options),
      'SL' => t('Sierra Leone', [], $translation_options),
      'SM' => t('San Marino', [], $translation_options),
      'SN' => t('Senegal', [], $translation_options),
      'SO' => t('Somalia', [], $translation_options),
      'SR' => t('Suriname', [], $translation_options),
      'SS' => t('South Sudan', [], $translation_options),
      'ST' => t('São Tomé & Príncipe', [], $translation_options),
      'SV' => t('El Salvador', [], $translation_options),
      'SX' => t('Sint Maarten', [], $translation_options),
      'SY' => t('Syria', [], $translation_options),
      'SZ' => t('Eswatini', [], $translation_options),
      'TA' => t('Tristan da Cunha', [], $translation_options),
      'TC' => t('Turks & Caicos Islands', [], $translation_options),
      'TD' => t('Chad', [], $translation_options),
      'TF' => t('French Southern Territories', [], $translation_options),
      'TG' => t('Togo', [], $translation_options),
      'TH' => t('Thailand', [], $translation_options),
      'TJ' => t('Tajikistan', [], $translation_options),
      'TK' => t('Tokelau', [], $translation_options),
      'TL' => t('Timor-Leste', [], $translation_options),
      'TM' => t('Turkmenistan', [], $translation_options),
      'TN' => t('Tunisia', [], $translation_options),
      'TO' => t('Tonga', [], $translation_options),
      'TR' => t('Turkey', [], $translation_options),
      'TT' => t('Trinidad & Tobago', [], $translation_options),
      'TV' => t('Tuvalu', [], $translation_options),
      'TW' => t('Taiwan', [], $translation_options),
      'TZ' => t('Tanzania', [], $translation_options),
      'UA' => t('Ukraine', [], $translation_options),
      'UG' => t('Uganda', [], $translation_options),
      'UM' => t('U.S. Outlying Islands', [], $translation_options),
      'US' => t('United States', [], $translation_options),
      'UY' => t('Uruguay', [], $translation_options),
      'UZ' => t('Uzbekistan', [], $translation_options),
      'VA' => t('Vatican City', [], $translation_options),
      'VC' => t('St. Vincent & Grenadines', [], $translation_options),
      'VE' => t('Venezuela', [], $translation_options),
      'VG' => t('British Virgin Islands', [], $translation_options),
      'VI' => t('U.S. Virgin Islands', [], $translation_options),
      'VN' => t('Vietnam', [], $translation_options),
      'VU' => t('Vanuatu', [], $translation_options),
      'WF' => t('Wallis & Futuna', [], $translation_options),
      'WS' => t('Samoa', [], $translation_options),
      'XK' => t('Kosovo', [], $translation_options),
      'YE' => t('Yemen', [], $translation_options),
      'YT' => t('Mayotte', [], $translation_options),
      'ZA' => t('South Africa', [], $translation_options),
      'ZM' => t('Zambia', [], $translation_options),
      'ZW' => t('Zimbabwe', [], $translation_options),
    ];
    // cSpell:enable

    // Sort the list.
    natcasesort($countries);

    return $countries;
  }

  /**
   * Get an array of country code => country name pairs, altered by alter hooks.
   *
   * @param string $language_type
   *   (optional) The language type; for example, the interface or the content
   *   language. Defaults to
   *   \Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE.
   *
   * @return array
   *   An array of country code => country name pairs.
   *
   * @see \Drupal\Core\Locale\CountryManager::getStandardList()
   */
  public function getList($language_type = LanguageInterface::TYPE_INTERFACE) {
    // Populate the country list if it is not already populated.
    $language_id = $this->languageManager
      ->getCurrentLanguage($language_type)->getId();
    if (!isset($this->countries[$language_id])) {
      $this->countries[$language_id] = static::getStandardList($language_id);

      // Sort the list.
      uasort($this->countries[$language_id], function ($a, $b) use ($language_id) {
        $a = $this->transliteration->transliterate($a, $language_id);
        $b = $this->transliteration->transliterate($b, $language_id);
        return $a <=> $b;
      });
      $this->moduleHandler->alter('countries', $this->countries);
    }

    return $this->countries[$language_id];
  }

}
