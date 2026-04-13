<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides a process plugin for the user langcode.
 */
#[MigrateProcess('user_langcode')]
class UserLangcode extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    #[Autowire(service: 'language_manager')]
    protected LanguageManager $languageManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->configuration += ['fallback_to_site_default' => TRUE];

    // If the user's language is empty, then default to English and set the
    // user's preferred_langcode and preferred_admin_langcode to the default
    // language.
    if (empty($value)) {
      return $this->configuration['fallback_to_site_default']
        ? $this->languageManager->getDefaultLanguage()->getId()
        : 'en';
    }
    // If the user's language does not exist, then use the default language.
    elseif ($this->languageManager->getLanguage($value) === NULL) {
      return $this->languageManager->getDefaultLanguage()->getId();
    }

    // If the langcode is a valid one, then just return it.
    return $value;
  }

}
