<?php

namespace Drupal\tour;

@trigger_error('The ' . __NAMESPACE__ . '\TourTipPluginInterface is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Implement ' . __NAMESPACE__ . '\TipPluginInterface instead. See https://www.drupal.org/node/3340701.', E_USER_DEPRECATED);

/**
 * Defines an interface for tour items.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginBase
 * @see \Drupal\tour\TipPluginManager
 * @see plugin_api
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Implements
 *   TipPluginInterface instead.
 *
 * @see https://www.drupal.org/node/3340701
 */
interface TourTipPluginInterface extends TipPluginInterface {}
