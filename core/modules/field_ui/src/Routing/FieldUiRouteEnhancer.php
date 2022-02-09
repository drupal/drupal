<?php

namespace Drupal\field_ui\Routing;

@trigger_error('The ' . __NAMESPACE__ . '\EntityBundleRouteEnhancer is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Instead, use \Drupal\Core\Entity\Enhancer\EntityBundleRouteEnhancer. See https://www.drupal.org/node/3245017', E_USER_DEPRECATED);

use Drupal\Core\Entity\Enhancer\EntityBundleRouteEnhancer;

/**
 * Enhances Field UI routes by adding proper information about the bundle name.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0.
 * Use \Drupal\Core\Entity\Enhancer\EntityBundleRouteEnhancer.
 *
 * @see https://www.drupal.org/node/3245017
 */
class FieldUiRouteEnhancer extends EntityBundleRouteEnhancer {}
