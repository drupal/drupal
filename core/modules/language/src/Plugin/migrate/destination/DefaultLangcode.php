<?php

namespace Drupal\language\Plugin\migrate\destination;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\migrate\Row;

/**
 * Provides a destination plugin for the default langcode config.
 */
#[MigrateDestination('default_langcode')]
class DefaultLangcode extends Config {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $destination = $row->getDestination();
    $langcode = $destination['default_langcode'];

    // Check if the language exists.
    if (ConfigurableLanguage::load($langcode) === NULL) {
      throw new MigrateException("The language '$langcode' does not exist on this site.");
    }

    $this->config->set('default_langcode', $destination['default_langcode']);
    $this->config->save();
    return [$this->config->getName()];
  }

}
