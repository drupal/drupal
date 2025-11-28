<?php

namespace Drupal\image\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

// cspell:ignore imagecache

/**
 * Defines the image cache actions migrate process plugin.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('d6_imagecache_actions')]
class ImageCacheActions extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $effects = [];

    foreach ($row->getSourceProperty('actions') as $action) {
      if (empty($action['action'])) {
        continue;
      }
      $id = preg_replace('/^imagecache/', 'image', $action['action']);

      if ($id === 'image_crop') {
        $action['data']['anchor'] = $action['data']['xoffset'] . '-' . $action['data']['yoffset'];

        if (!preg_match('/^[a-z]*\-[a-z]*/', $action['data']['anchor'])) {
          $migrate_executable->message->display(
            'The Drupal 8 image crop effect does not support numeric values for x and y offsets. Use keywords to set crop effect offsets instead.',
            'error'
          );
        }

        unset($action['data']['xoffset']);
        unset($action['data']['yoffset']);
      }

      $effects[] = [
        'id'     => $id,
        'weight' => $action['weight'],
        'data'   => $action['data'],
      ];
    }

    return $effects;
  }

}
