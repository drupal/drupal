<?php

namespace Drupal\Core\Locale;

use Drupal\Core\Extension\ModuleHandlerInterface;

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
   * An array of country code => country name pairs.
   */
  protected $countries;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get an array of all two-letter country code => country name pairs.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  public static function getStandardList() {
    // cSpell:disable
    $countries = [
      'AC' => t('Ascension Island'),
      'AD' => t('Andorra'),
      'AE' => t('United Arab Emirates'),
      'AF' => t('Afghanistan'),
      'AG' => t('Antigua & Barbuda'),
      'AI' => t('Anguilla'),
      'AL' => t('Albania'),
      'AM' => t('Armenia'),
      'AN' => t('Netherlands Antilles'),
      'AO' => t('Angola'),
      'AQ' => t('Antarctica'),
      'AR' => t('Argentina'),
      'AS' => t('American Samoa'),
      'AT' => t('Austria'),
      'AU' => t('Australia'),
      'AW' => t('Aruba'),
      'AX' => t('Åland Islands'),
      'AZ' => t('Azerbaijan'),
      'BA' => t('Bosnia & Herzegovina'),
      'BB' => t('Barbados'),
      'BD' => t('Bangladesh'),
      'BE' => t('Belgium'),
      'BF' => t('Burkina Faso'),
      'BG' => t('Bulgaria'),
      'BH' => t('Bahrain'),
      'BI' => t('Burundi'),
      'BJ' => t('Benin'),
      'BL' => t('St. Barthélemy'),
      'BM' => t('Bermuda'),
      'BN' => t('Brunei'),
      'BO' => t('Bolivia'),
      'BQ' => t('Caribbean Netherlands'),
      'BR' => t('Brazil'),
      'BS' => t('Bahamas'),
      'BT' => t('Bhutan'),
      'BV' => t('Bouvet Island'),
      'BW' => t('Botswana'),
      'BY' => t('Belarus'),
      'BZ' => t('Belize'),
      'CA' => t('Canada'),
      'CC' => t('Cocos (Keeling) Islands'),
      'CD' => t('Congo - Kinshasa'),
      'CF' => t('Central African Republic'),
      'CG' => t('Congo - Brazzaville'),
      'CH' => t('Switzerland'),
      'CI' => t('Côte d’Ivoire'),
      'CK' => t('Cook Islands'),
      'CL' => t('Chile'),
      'CM' => t('Cameroon'),
      'CN' => t('China'),
      'CO' => t('Colombia'),
      'CP' => t('Clipperton Island'),
      'CR' => t('Costa Rica'),
      'CU' => t('Cuba'),
      'CV' => t('Cape Verde'),
      'CW' => t('Curaçao'),
      'CX' => t('Christmas Island'),
      'CY' => t('Cyprus'),
      'CZ' => t('Czechia'),
      'DE' => t('Germany'),
      'DG' => t('Diego Garcia'),
      'DJ' => t('Djibouti'),
      'DK' => t('Denmark'),
      'DM' => t('Dominica'),
      'DO' => t('Dominican Republic'),
      'DZ' => t('Algeria'),
      'EA' => t('Ceuta & Melilla'),
      'EC' => t('Ecuador'),
      'EE' => t('Estonia'),
      'EG' => t('Egypt'),
      'EH' => t('Western Sahara'),
      'ER' => t('Eritrea'),
      'ES' => t('Spain'),
      'ET' => t('Ethiopia'),
      'FI' => t('Finland'),
      'FJ' => t('Fiji'),
      'FK' => t('Falkland Islands'),
      'FM' => t('Micronesia'),
      'FO' => t('Faroe Islands'),
      'FR' => t('France'),
      'GA' => t('Gabon'),
      'GB' => t('United Kingdom'),
      'GD' => t('Grenada'),
      'GE' => t('Georgia'),
      'GF' => t('French Guiana'),
      'GG' => t('Guernsey'),
      'GH' => t('Ghana'),
      'GI' => t('Gibraltar'),
      'GL' => t('Greenland'),
      'GM' => t('Gambia'),
      'GN' => t('Guinea'),
      'GP' => t('Guadeloupe'),
      'GQ' => t('Equatorial Guinea'),
      'GR' => t('Greece'),
      'GS' => t('South Georgia & South Sandwich Islands'),
      'GT' => t('Guatemala'),
      'GU' => t('Guam'),
      'GW' => t('Guinea-Bissau'),
      'GY' => t('Guyana'),
      'HK' => t('Hong Kong SAR China'),
      'HM' => t('Heard & McDonald Islands'),
      'HN' => t('Honduras'),
      'HR' => t('Croatia'),
      'HT' => t('Haiti'),
      'HU' => t('Hungary'),
      'IC' => t('Canary Islands'),
      'ID' => t('Indonesia'),
      'IE' => t('Ireland'),
      'IL' => t('Israel'),
      'IM' => t('Isle of Man'),
      'IN' => t('India'),
      'IO' => t('British Indian Ocean Territory'),
      'IQ' => t('Iraq'),
      'IR' => t('Iran'),
      'IS' => t('Iceland'),
      'IT' => t('Italy'),
      'JE' => t('Jersey'),
      'JM' => t('Jamaica'),
      'JO' => t('Jordan'),
      'JP' => t('Japan'),
      'KE' => t('Kenya'),
      'KG' => t('Kyrgyzstan'),
      'KH' => t('Cambodia'),
      'KI' => t('Kiribati'),
      'KM' => t('Comoros'),
      'KN' => t('St. Kitts & Nevis'),
      'KP' => t('North Korea'),
      'KR' => t('South Korea'),
      'KW' => t('Kuwait'),
      'KY' => t('Cayman Islands'),
      'KZ' => t('Kazakhstan'),
      'LA' => t('Laos'),
      'LB' => t('Lebanon'),
      'LC' => t('St. Lucia'),
      'LI' => t('Liechtenstein'),
      'LK' => t('Sri Lanka'),
      'LR' => t('Liberia'),
      'LS' => t('Lesotho'),
      'LT' => t('Lithuania'),
      'LU' => t('Luxembourg'),
      'LV' => t('Latvia'),
      'LY' => t('Libya'),
      'MA' => t('Morocco'),
      'MC' => t('Monaco'),
      'MD' => t('Moldova'),
      'ME' => t('Montenegro'),
      'MF' => t('St. Martin'),
      'MG' => t('Madagascar'),
      'MH' => t('Marshall Islands'),
      'MK' => t('North Macedonia'),
      'ML' => t('Mali'),
      'MM' => t('Myanmar (Burma)'),
      'MN' => t('Mongolia'),
      'MO' => t('Macao SAR China'),
      'MP' => t('Northern Mariana Islands'),
      'MQ' => t('Martinique'),
      'MR' => t('Mauritania'),
      'MS' => t('Montserrat'),
      'MT' => t('Malta'),
      'MU' => t('Mauritius'),
      'MV' => t('Maldives'),
      'MW' => t('Malawi'),
      'MX' => t('Mexico'),
      'MY' => t('Malaysia'),
      'MZ' => t('Mozambique'),
      'NA' => t('Namibia'),
      'NC' => t('New Caledonia'),
      'NE' => t('Niger'),
      'NF' => t('Norfolk Island'),
      'NG' => t('Nigeria'),
      'NI' => t('Nicaragua'),
      'NL' => t('Netherlands'),
      'NO' => t('Norway'),
      'NP' => t('Nepal'),
      'NR' => t('Nauru'),
      'NU' => t('Niue'),
      'NZ' => t('New Zealand'),
      'OM' => t('Oman'),
      'PA' => t('Panama'),
      'PE' => t('Peru'),
      'PF' => t('French Polynesia'),
      'PG' => t('Papua New Guinea'),
      'PH' => t('Philippines'),
      'PK' => t('Pakistan'),
      'PL' => t('Poland'),
      'PM' => t('St. Pierre & Miquelon'),
      'PN' => t('Pitcairn Islands'),
      'PR' => t('Puerto Rico'),
      'PS' => t('Palestinian Territories'),
      'PT' => t('Portugal'),
      'PW' => t('Palau'),
      'PY' => t('Paraguay'),
      'QA' => t('Qatar'),
      'QO' => t('Outlying Oceania'),
      'RE' => t('Réunion'),
      'RO' => t('Romania'),
      'RS' => t('Serbia'),
      'RU' => t('Russia'),
      'RW' => t('Rwanda'),
      'SA' => t('Saudi Arabia'),
      'SB' => t('Solomon Islands'),
      'SC' => t('Seychelles'),
      'SD' => t('Sudan'),
      'SE' => t('Sweden'),
      'SG' => t('Singapore'),
      'SH' => t('St. Helena'),
      'SI' => t('Slovenia'),
      'SJ' => t('Svalbard & Jan Mayen'),
      'SK' => t('Slovakia'),
      'SL' => t('Sierra Leone'),
      'SM' => t('San Marino'),
      'SN' => t('Senegal'),
      'SO' => t('Somalia'),
      'SR' => t('Suriname'),
      'SS' => t('South Sudan'),
      'ST' => t('São Tomé & Príncipe'),
      'SV' => t('El Salvador'),
      'SX' => t('Sint Maarten'),
      'SY' => t('Syria'),
      'SZ' => t('Eswatini'),
      'TA' => t('Tristan da Cunha'),
      'TC' => t('Turks & Caicos Islands'),
      'TD' => t('Chad'),
      'TF' => t('French Southern Territories'),
      'TG' => t('Togo'),
      'TH' => t('Thailand'),
      'TJ' => t('Tajikistan'),
      'TK' => t('Tokelau'),
      'TL' => t('Timor-Leste'),
      'TM' => t('Turkmenistan'),
      'TN' => t('Tunisia'),
      'TO' => t('Tonga'),
      'TR' => t('Turkey'),
      'TT' => t('Trinidad & Tobago'),
      'TV' => t('Tuvalu'),
      'TW' => t('Taiwan'),
      'TZ' => t('Tanzania'),
      'UA' => t('Ukraine'),
      'UG' => t('Uganda'),
      'UM' => t('U.S. Outlying Islands'),
      'US' => t('United States'),
      'UY' => t('Uruguay'),
      'UZ' => t('Uzbekistan'),
      'VA' => t('Vatican City'),
      'VC' => t('St. Vincent & Grenadines'),
      'VE' => t('Venezuela'),
      'VG' => t('British Virgin Islands'),
      'VI' => t('U.S. Virgin Islands'),
      'VN' => t('Vietnam'),
      'VU' => t('Vanuatu'),
      'WF' => t('Wallis & Futuna'),
      'WS' => t('Samoa'),
      'XK' => t('Kosovo'),
      'YE' => t('Yemen'),
      'YT' => t('Mayotte'),
      'ZA' => t('South Africa'),
      'ZM' => t('Zambia'),
      'ZW' => t('Zimbabwe'),
    ];
    // cSpell:enable

    // Sort the list.
    natcasesort($countries);

    return $countries;
  }

  /**
   * Get an array of country code => country name pairs, altered by alter hooks.
   *
   * @return array
   *   An array of country code => country name pairs.
   *
   * @see \Drupal\Core\Locale\CountryManager::getStandardList()
   */
  public function getList() {
    // Populate the country list if it is not already populated.
    if (!isset($this->countries)) {
      $this->countries = static::getStandardList();
      $this->moduleHandler->alter('countries', $this->countries);
    }

    return $this->countries;
  }

}
