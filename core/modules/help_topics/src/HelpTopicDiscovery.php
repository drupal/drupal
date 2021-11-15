<?php

namespace Drupal\help_topics;

use Drupal\Component\Discovery\DiscoveryException;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\FileSystem\RegexDirectoryIterator;
use Drupal\Component\FrontMatter\FrontMatter;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Discovers help topic plugins from Twig files in help_topics directories.
 *
 * @see \Drupal\help_topics\HelpTopicTwig
 * @see \Drupal\help_topics\HelpTopicTwigLoader
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicDiscovery implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * Defines the key in the discovered data where the file path is stored.
   */
  const FILE_KEY = '_discovered_file_path';

  /**
   * An array of directories to scan, keyed by the provider.
   *
   * The value can either be a string or an array of strings. The string values
   * should be the path of a directory to scan.
   *
   * @var array
   */
  protected $directories = [];

  /**
   * Constructs a HelpTopicDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider. The value can
   *   either be a string or an array of strings. The string values should be
   *   the path of a directory to scan.
   */
  public function __construct(array $directories) {
    $this->directories = $directories;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $plugins = $this->findAll();

    // Flatten definitions into what's expected from plugins.
    $definitions = [];
    foreach ($plugins as $list) {
      foreach ($list as $id => $definition) {
        $definitions[$id] = $definition;
      }
    }

    return $definitions;
  }

  /**
   * Returns an array of discoverable items.
   *
   * @return array
   *   An array of discovered data keyed by provider.
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   *   Exception thrown if there is a problem during discovery.
   */
  public function findAll() {
    $all = [];

    $files = $this->findFiles();

    $file_cache = FileCacheFactory::get('help_topic_discovery:help_topics');

    // Try to load from the file cache first.
    foreach ($file_cache->getMultiple(array_keys($files)) as $file => $data) {
      $all[$files[$file]][$data['id']] = $data;
      unset($files[$file]);
    }

    // If there are files left that were not returned from the cache, load and
    // parse them now. This list was flipped above and is keyed by filename.
    if ($files) {
      foreach ($files as $file => $provider) {
        $plugin_id = substr(basename($file), 0, -10);
        // The plugin ID begins with provider.
        [$file_name_provider] = explode('.', $plugin_id, 2);
        // Only the Help Topics module can provide help for other extensions.
        // @todo https://www.drupal.org/project/drupal/issues/3072312 Remove
        //   help_topics special case once Help Topics is stable and core
        //   modules can provide their own help topics.
        if ($provider !== 'help_topics' && $provider !== $file_name_provider) {
          throw new DiscoveryException("$file file name should begin with '$provider'");
        }
        $data = [
          // The plugin ID is derived from the filename. The extension
          // '.html.twig' is removed.
          'id' => $plugin_id,
          'provider' => $file_name_provider,
          'class' => HelpTopicTwig::class,
          static::FILE_KEY => $file,
        ];

        // Get the rest of the plugin definition from front matter contained in
        // the help topic Twig file.
        try {
          $front_matter = FrontMatter::create(file_get_contents($file), Yaml::class)->getData();
        }
        catch (InvalidDataTypeException $e) {
          throw new DiscoveryException(sprintf('Malformed YAML in help topic "%s": %s.', $file, $e->getMessage()));
        }
        foreach ($front_matter as $key => $value) {
          switch ($key) {
            case 'related':
              if (!is_array($value)) {
                throw new DiscoveryException("$file contains invalid value for 'related' key, the value must be an array of strings");
              }
              $data[$key] = $value;
              break;

            case 'top_level':
              if (!is_bool($value)) {
                throw new DiscoveryException("$file contains invalid value for 'top_level' key, the value must be a Boolean");
              }
              $data[$key] = $value;
              break;

            case 'label':
              $data[$key] = new TranslatableMarkup($value);
              break;

            default:
              throw new DiscoveryException("$file contains invalid key='$key'");
          }
        }
        if (!isset($data['label'])) {
          throw new DiscoveryException("$file does not contain the required key with name='label'");
        }

        $all[$provider][$data['id']] = $data;
        $file_cache->set($file, $data);
      }
    }

    return $all;
  }

  /**
   * Returns an array of providers keyed by file path.
   *
   * @return array
   *   An array of providers keyed by file path.
   */
  protected function findFiles() {
    $file_list = [];
    foreach ($this->directories as $provider => $directories) {
      $directories = (array) $directories;
      foreach ($directories as $directory) {
        if (is_dir($directory)) {
          /** @var \SplFileInfo $fileInfo */
          $iterator = new RegexDirectoryIterator($directory, '/\.html\.twig$/i');
          foreach ($iterator as $fileInfo) {
            $file_list[$fileInfo->getPathname()] = $provider;
          }
        }
      }
    }
    return $file_list;
  }

}
