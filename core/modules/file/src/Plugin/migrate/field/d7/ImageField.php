<?php

namespace Drupal\file\Plugin\migrate\field\d7;

@trigger_error('ImageField is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.x. Use \Drupal\image\Plugin\migrate\field\d7\ImageField instead. See https://www.drupal.org/node/2936061.', E_USER_DEPRECATED);

use Drupal\image\Plugin\migrate\field\d7\ImageField as NonLegacyImageField;

/**
 * Field plugin for image fields.
 *
 * @deprecated in Drupal 8.5.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\image\Plugin\migrate\field\d7\ImageField instead.
 *
 * @see https://www.drupal.org/node/2936061
 */
class ImageField extends NonLegacyImageField {}
