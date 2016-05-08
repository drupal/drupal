<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Discovery\YamlDiscovery as ComponentYamlDiscovery;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Allows YAML files to define plugin definitions.
 *
 * If the value of a key (like title) in the definition is translatable then
 * the addTranslatableProperty() method can be used to mark it as such and also
 * to add translation context. Then
 * \Drupal\Core\StringTranslation\TranslatableMarkup will be used to translate
 * the string and also to mark it safe. Only strings written in the YAML files
 * should be marked as safe, strings coming from dynamic plugin definitions
 * potentially containing user input should not.
 */
class YamlDiscovery implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * YAML file discovery and parsing handler.
   *
   * @var \Drupal\Component\Discovery\YamlDiscovery
   */
  protected $discovery;

  /**
   * Contains an array of translatable properties passed along to t().
   *
   * @see \Drupal\Core\Plugin\Discovery\YamlDiscovery::addTranslatableProperty()
   *
   * @var array
   */
  protected $translatableProperties = [];

  /**
   * Construct a YamlDiscovery object.
   *
   * @param string $name
   *   The file name suffix to use for discovery; for example, 'test' will
   *   become 'MODULE.test.yml'.
   * @param array $directories
   *   An array of directories to scan.
   */
  function __construct($name, array $directories) {
    $this->discovery = new ComponentYamlDiscovery($name, $directories);
  }

  /**
   * Set one of the YAML values as being translatable.
   *
   * @param string $value_key
   *   The key corresponding to the value in the YAML that contains a
   *   translatable string.
   * @param string $context_key
   *   (Optional) the translation context for the value specified by the
   *   $value_key.
   *
   * @return $this
   */
  public function addTranslatableProperty($value_key, $context_key = '') {
    $this->translatableProperties[$value_key] = $context_key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $plugins = $this->discovery->findAll();

    // Flatten definitions into what's expected from plugins.
    $definitions = array();
    foreach ($plugins as $provider => $list) {
      foreach ($list as $id => $definition) {
        // Add TranslatableMarkup.
        foreach ($this->translatableProperties as $property => $context_key) {
          if (isset($definition[$property])) {
            $options = [];
            // Move the t() context from the definition to the translation
            // wrapper.
            if ($context_key && isset($definition[$context_key])) {
              $options['context'] = $definition[$context_key];
              unset($definition[$context_key]);
            }
            $definition[$property] = new TranslatableMarkup($definition[$property], [], $options);
          }
        }
        // Add ID and provider.
        $definitions[$id] = $definition + array(
          'provider' => $provider,
          'id' => $id,
        );
      }
    }

    return $definitions;
  }

}
