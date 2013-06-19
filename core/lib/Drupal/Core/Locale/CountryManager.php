<?php

/**
 * @file
 * Definition of \Drupal\Core\Locale\CountryManager.
 */

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

  /*
  * Constructor.
  *
  * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
  */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get an array of all ISO 3166-1 alpha-2 country code => country name pairs.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  public static function getStandardList() {
    $countries = array(
      'AD' => t('Andorra'),
      'AE' => t('United Arab Emirates'),
      'AF' => t('Afghanistan'),
      'AG' => t('Antigua and Barbuda'),
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
      'BA' => t('Bosnia and Herzegovina'),
      'BB' => t('Barbados'),
      'BD' => t('Bangladesh'),
      'BE' => t('Belgium'),
      'BF' => t('Burkina Faso'),
      'BG' => t('Bulgaria'),
      'BH' => t('Bahrain'),
      'BI' => t('Burundi'),
      'BJ' => t('Benin'),
      'BL' => t('Saint Barthélemy'),
      'BM' => t('Bermuda'),
      'BN' => t('Brunei Darussalam'),
      'BO' => t('Bolivia, Plurinational State of'),
      'BQ' => t('Bonaire, Sint Eustatius and Saba'),
      'BR' => t('Brazil'),
      'BS' => t('Bahamas'),
      'BT' => t('Bhutan'),
      'BV' => t('Bouvet Island'),
      'BW' => t('Botswana'),
      'BY' => t('Belarus'),
      'BZ' => t('Belize'),
      'CA' => t('Canada'),
      'CC' => t('Cocos (Keeling) Islands'),
      'CD' => t('Congo, The Democratic Republic of the'),
      'CF' => t('Central African Republic'),
      'CG' => t('Congo'),
      'CH' => t('Switzerland'),
      'CI' => t("Côte d'Ivoire"),
      'CK' => t('Cook Islands'),
      'CL' => t('Chile'),
      'CM' => t('Cameroon'),
      'CN' => t('China'),
      'CO' => t('Colombia'),
      'CR' => t('Costa Rica'),
      'CU' => t('Cuba'),
      'CV' => t('Cape Verde'),
      'CW' => t('Curaçao'),
      'CX' => t('Christmas Island'),
      'CY' => t('Cyprus'),
      'CZ' => t('Czech Republic'),
      'DE' => t('Germany'),
      'DJ' => t('Djibouti'),
      'DK' => t('Denmark'),
      'DM' => t('Dominica'),
      'DO' => t('Dominican Republic'),
      'DZ' => t('Algeria'),
      'EC' => t('Ecuador'),
      'EE' => t('Estonia'),
      'EG' => t('Egypt'),
      'EH' => t('Western Sahara'),
      'ER' => t('Eritrea'),
      'ES' => t('Spain'),
      'ET' => t('Ethiopia'),
      'FI' => t('Finland'),
      'FJ' => t('Fiji'),
      'FK' => t('Falkland Islands (Malvinas)'),
      'FM' => t('Micronesia, Federated States of'),
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
      'GS' => t('South Georgia and the South Sandwich Islands'),
      'GT' => t('Guatemala'),
      'GU' => t('Guam'),
      'GW' => t('Guinea-Bissau'),
      'GY' => t('Guyana'),
      'HK' => t('Hong Kong'),
      'HM' => t('Heard Island and McDonald Islands'),
      'HN' => t('Honduras'),
      'HR' => t('Croatia'),
      'HT' => t('Haiti'),
      'HU' => t('Hungary'),
      'ID' => t('Indonesia'),
      'IE' => t('Ireland'),
      'IL' => t('Israel'),
      'IM' => t('Isle of Man'),
      'IN' => t('India'),
      'IO' => t('British Indian Ocean Territory'),
      'IQ' => t('Iraq'),
      'IR' => t('Iran, Islamic Republic of'),
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
      'KN' => t('Saint Kitts and Nevis'),
      'KP' => t("Korea, Democratic People's Republic of"),
      'KR' => t('Korea, Republic of'),
      'KW' => t('Kuwait'),
      'KY' => t('Cayman Islands'),
      'KZ' => t('Kazakhstan'),
      'LA' => t("Lao People's Democratic Republic"),
      'LB' => t('Lebanon'),
      'LC' => t('Saint Lucia'),
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
      'MD' => t('Moldova, Republic of'),
      'ME' => t('Montenegro'),
      'MF' => t('Saint Martin (French part)'),
      'MG' => t('Madagascar'),
      'MH' => t('Marshall Islands'),
      'MK' => t('Macedonia, Republic of'),
      'ML' => t('Mali'),
      'MM' => t('Myanmar'),
      'MN' => t('Mongolia'),
      'MO' => t('Macao'),
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
      'PM' => t('Saint Pierre and Miquelon'),
      'PN' => t('Pitcairn'),
      'PR' => t('Puerto Rico'),
      'PS' => t('Palestine, State of'),
      'PT' => t('Portugal'),
      'PW' => t('Palau'),
      'PY' => t('Paraguay'),
      'QA' => t('Qatar'),
      'RE' => t('Réunion'),
      'RO' => t('Romania'),
      'RS' => t('Serbia'),
      'RU' => t('Russian Federation'),
      'RW' => t('Rwanda'),
      'SA' => t('Saudi Arabia'),
      'SB' => t('Solomon Islands'),
      'SC' => t('Seychelles'),
      'SD' => t('Sudan'),
      'SE' => t('Sweden'),
      'SG' => t('Singapore'),
      'SH' => t('Saint Helena, Ascension and Tristan da Cunha'),
      'SI' => t('Slovenia'),
      'SJ' => t('Svalbard and Jan Mayen'),
      'SK' => t('Slovakia'),
      'SL' => t('Sierra Leone'),
      'SM' => t('San Marino'),
      'SN' => t('Senegal'),
      'SO' => t('Somalia'),
      'SR' => t('Suriname'),
      'SS' => t('South Sudan'),
      'ST' => t('Sao Tome and Principe'),
      'SV' => t('El Salvador'),
      'SX' => t('Sint Maarten (Dutch part)'),
      'SY' => t('Syrian Arab Republic'),
      'SZ' => t('Swaziland'),
      'TC' => t('Turks and Caicos Islands'),
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
      'TT' => t('Trinidad and Tobago'),
      'TV' => t('Tuvalu'),
      'TW' => t('Taiwan, Province of China'),
      'TZ' => t('Tanzania, United Republic of'),
      'UA' => t('Ukraine'),
      'UG' => t('Uganda'),
      'UM' => t('United States Minor Outlying Islands'),
      'US' => t('United States'),
      'UY' => t('Uruguay'),
      'UZ' => t('Uzbekistan'),
      'VA' => t('Holy See (Vatican City State)'),
      'VC' => t('Saint Vincent and the Grenadines'),
      'VE' => t('Venezuela, Bolivarian Republic of'),
      'VG' => t('Virgin Islands, British'),
      'VI' => t('Virgin Islands, U.S.'),
      'VN' => t('Viet Nam'),
      'VU' => t('Vanuatu'),
      'WF' => t('Wallis and Futuna'),
      'WS' => t('Samoa'),
      'YE' => t('Yemen'),
      'YT' => t('Mayotte'),
      'ZA' => t('South Africa'),
      'ZM' => t('Zambia'),
      'ZW' => t('Zimbabwe'),
    );

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
