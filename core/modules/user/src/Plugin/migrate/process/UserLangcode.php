<?php

namespace Drupal\user\Plugin\migrate\process;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a process plugin for the user langcode.
 *
 * @MigrateProcessPlugin(
 *   id = "user_langcode"
 * )
 */
class UserLangcode extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a UserLangcode object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definiiton.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManager $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($this->configuration['fallback_to_site_default'])) {
      $this->configuration['fallback_to_site_default'] = TRUE;
    }

    // If the user's language is empty, it means the locale module was not
    // installed, so the user's langcode should be English and the user's
    // preferred_langcode and preferred_admin_langcode should fallback to the
    // default language.
    if (empty($value)) {
      if ($this->configuration['fallback_to_site_default']) {
        return $this->languageManager->getDefaultLanguage()->getId();
      }
      else {
        return 'en';
      }
    }
    // If the user's language does not exist, use the default language.
    elseif ($this->languageManager->getLanguage($value) === NULL) {
      return $this->languageManager->getDefaultLanguage()->getId();
    }

    // If the langcode is a valid one, just return it.
    return $value;
  }

}
