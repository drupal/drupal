<?php

namespace Drupal\action;

use Drupal\action\Form\ActionFormBase as ActionFormBaseCurrent;

@trigger_error('The ' . __NAMESPACE__ . '\ActionFormBase is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use ' . __NAMESPACE__ . '\Form\ActionFormBase. See https://www.drupal.org/node/3033540', E_USER_DEPRECATED);

/**
 * Provides a base form for action forms.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\action\Form\ActionFormBase instead.
 *
 * @see https://www.drupal.org/node/3033540
 */
abstract class ActionFormBase extends ActionFormBaseCurrent {

}
