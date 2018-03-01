<?php

namespace Drupal\file\Plugin\migrate\cckfield\d7;

@trigger_error('ImageField is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\image\Plugin\migrate\field\d7\ImageField instead. See https://www.drupal.org/node/2936061.', E_USER_DEPRECATED);

use Drupal\image\Plugin\migrate\cckfield\d7\ImageField as LegacyImageField;

/**
 * CCK plugin for image fields.
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\image\Plugin\migrate\field\d7\ImageField instead.
 *
 * @see https://www.drupal.org/node/2936061
 */
class ImageField extends LegacyImageField {}
