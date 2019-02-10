<?php

namespace Drupal\Core\Entity;

@trigger_error('The ' . __NAMESPACE__ . '\Entity is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\EntityBase. See https://www.drupal.org/node/3021808', E_USER_DEPRECATED);

/**
 * Defines a base entity class.
 *
 * @deprecated in Drupal 8.7.0 and will be removed in Drupal 9.0.0. Use
 *   \Drupal\Core\Entity\EntityBase instead.
 *
 * @see https://www.drupal.org/node/3021808
 */
abstract class Entity extends EntityBase {}
