<?php

namespace Drupal\language\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * @MigrateSource(
 *   id = "language",
 *   source_provider = "locale"
 * )
 */
class Language extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'language' => array(
        'type' => 'string',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('languages')->fields('languages');
  }

}
