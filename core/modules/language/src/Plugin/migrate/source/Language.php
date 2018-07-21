<?php

namespace Drupal\language\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * @MigrateSource(
 *   id = "language",
 *   source_module = "locale"
 * )
 */
class Language extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'language' => $this->t('The language code.'),
      'name' => $this->t('The English name of the language.'),
      'native' => $this->t('The native name of the language.'),
      'direction' => $this->t('The language direction. (0 = LTR, 1 = RTL)'),
      'enabled' => $this->t('Whether the language is enabled.'),
      'plurals' => $this->t('Number of plural indexes in this language.'),
      'formula' => $this->t('PHP formula to get plural indexes.'),
      'domain' => $this->t('Domain to use for this language.'),
      'prefix' => $this->t('Path prefix used for this language.'),
      'weight' => $this->t('The language weight when listed.'),
      'javascript' => $this->t('Location of the JavaScript translation file.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'language' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('languages')->fields('languages');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!empty($this->configuration['fetch_all'])) {
      // Get an array of all languages.
      $languages = $this->query()->execute()->fetchAll();
      $row->setSourceProperty('languages', $languages);
    }

    if (!empty($this->configuration['domain_negotiation'])) {
      // Check if domain negotiation is used to be able to fill in the default
      // language domain, which may be empty. In D6, domain negotiation is used
      // when the 'language_negotiation' variable is set to '3', and in D7, when
      // the 'locale_language_negotiation_url_part' variable is set to '1'.
      if ($this->variableGet('language_negotiation', 0) == 3 || $this->variableGet('locale_language_negotiation_url_part', 0) == 1) {
        $row->setSourceProperty('domain_negotiation_used', TRUE);
      }
    }

    return parent::prepareRow($row);
  }

}
