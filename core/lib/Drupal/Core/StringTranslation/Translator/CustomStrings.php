<?php

namespace Drupal\Core\StringTranslation\Translator;

use Drupal\Core\Site\Settings;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * String translator using overrides from variables.
 *
 * This is a high performance way to provide a handful of string replacements.
 * See settings.php for examples.
 */
class CustomStrings extends StaticTranslation {

  use DependencySerializationTrait;

  /**
   * The settings read only object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a CustomStrings object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings read only object.
   */
  public function __construct(Settings $settings) {
    parent::__construct();
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguage($langcode) {
    return $this->settings->get('locale_custom_strings_' . $langcode, array());
  }

}
