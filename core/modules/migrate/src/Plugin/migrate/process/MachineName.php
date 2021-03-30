<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a machine name.
 *
 * The machine_name process plugin takes the source value and turns it into a
 * machine-readable name via the following four steps:
 * 1. Language decorations and accents are removed by transliterating the source
 *    value.
 * 2. The resulting value is made lowercase.
 * 3. Any special characters are replaced with an underscore. By default,
 *    anything that is not a number or a letter is replaced, but additional
 *    characters can be allowed or further restricted by using the
 *    replace_pattern configuration as described below.
 * 4. Any duplicate underscores either in the source value or as a result of
 *    replacing special characters are removed.
 *
 * Available configuration keys:
 *   - replace_pattern: (optional) A custom regular expression pattern to
 *     replace special characters with an underscore using preg_replace(). This
 *     can be used to allow additional characters in the machine name.
 *     Defaults to /[^a-z0-9_]+/
 *
 * Example:
 *
 * @code
 * process:
 *   bar:
 *     plugin: machine_name
 *     source: foo
 * @endcode
 *
 * If the value of foo in the source is 'áéí!' then the destination value of bar
 * will be 'aei_'.
 *
 * @code
 * process:
 *   bar:
 *     plugin: machine_name
 *     source: foo
 *     replace_pattern: '/[^a-z0-9_.]+/'
 * @endcode
 *
 * Here the replace pattern does not match the '.' character (as it is included
 * in the list of characters not to match) so if the value of foo in the source
 * is 'áéí!.jpg' then the destination value of bar will be 'aei_.jpg'.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "machine_name"
 * )
 */
class MachineName extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The regular expression pattern.
   *
   * @var string
   */
  protected $replacePattern;

  /**
   * Constructs a MachineName plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransliterationInterface $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transliteration = $transliteration;

    $this->replacePattern = $this->configuration['replace_pattern'] ?? '/[^a-z0-9_]+/';
    if (!is_string($this->replacePattern)) {
      throw new MigrateException('The replace pattern should be a string');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_value = $this->transliteration->transliterate($value, LanguageInterface::LANGCODE_DEFAULT, '_');
    $new_value = strtolower($new_value);
    $new_value = preg_replace($this->replacePattern, '_', $new_value);
    return preg_replace('/_+/', '_', $new_value);
  }

}
