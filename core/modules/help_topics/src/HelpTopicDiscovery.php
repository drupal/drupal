<?php

namespace Drupal\help_topics;

use Drupal\Component\Discovery\DiscoveryException;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\FileSystem\RegexDirectoryIterator;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Discovers help topic plugins from Twig files in help_topics directories.
 *
 * @see \Drupal\help_topics\HelpTopicTwig
 * @see \Drupal\help_topics\HelpTopicTwigLoader
 *
 * @internal
 *   Help Topic is currently experimental and should only be leveraged by
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
        list($file_name_provider,) = explode('.', $plugin_id, 2);
        // Only the Help Topics module can provider help for other extensions.
        // @todo https://www.drupal.org/project/drupal/issues/3025577 Remove
        //   help_topics special case once Help Topics is stable and core
        //   modules can provide their own help topics.
        if ($provider !== 'help_topics' && $provider !== $file_name_provider) {
          throw new DiscoveryException("$file should begin with '$provider.'");
        }
        $data = [
          // The plugin ID is derived from the filename. The extension
          // '.html.twig' is removed
          'id' => $plugin_id,
          'provider' => $file_name_provider,
          'class' => HelpTopicTwig::class,
          static::FILE_KEY => $file,
        ];

        // Get the rest of the plugin definition from meta tags contained in the
        // help topic Twig file.
        foreach (get_meta_tags($file) as $key => $value) {
          $key = substr($key, 11);
          switch ($key) {
            case 'related':
              $data[$key] = array_map('trim', explode(',', $value));
              break;
            case 'top_level':
              $data[$key] = TRUE;
              if ($value !== '') {
                throw new DiscoveryException("$file contains invalid meta tag with name='help_topic:top_level', the 'content' property should not exist");
              }
              break;
            case 'label':
              $data[$key] = new TranslatableMarkup($value);
              break;
            default:
              throw new DiscoveryException("$file contains invalid meta tag with name='$key'");
          }
        }
        if (!isset($data['label'])) {
          throw new DiscoveryException("$file does not contain the required meta tag with name='help_topic:label'");
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
