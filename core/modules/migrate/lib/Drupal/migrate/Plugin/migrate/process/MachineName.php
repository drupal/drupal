<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\MachineName.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;

/**
 * This plugin creates a machine name.
 *
 * The current value gets transliterated, non-alphanumeric characters removed
 * and replaced by an underscore and multiple underscores are collapsed into
 * one.
 *
 * @PluginId("machine_name")
 */
class MachineName extends PluginBase implements MigrateProcessInterface {

  /**
   * @var \Drupal\Core\Transliteration\PHPTransliteration
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    $new_value = $this->getTransliteration()->transliterate($value, Language::LANGCODE_DEFAULT, '_');
    $new_value = strtolower($new_value);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', $new_value);
    return preg_replace('/_+/', '_', $new_value);
  }

  /**
   * @return \Drupal\Core\Transliteration\PHPTransliteration
   */
  protected function getTransliteration() {
    if (!isset($this->transliteration)) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

}

