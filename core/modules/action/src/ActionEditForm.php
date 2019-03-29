<?php

namespace Drupal\action;

use Drupal\action\Form\ActionEditForm as ActionEditFormCurrent;

@trigger_error('The ' . __NAMESPACE__ . '\ActionEditForm is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use ' . __NAMESPACE__ . '\Form\ActionEditForm. See https://www.drupal.org/node/3033540', E_USER_DEPRECATED);

/**
 * Provides a form for action edit forms.
 *
 * @internal
 *
 * @deprecated in Drupal 8.8.x and will be removed before Drupal 9.0.0. Use
 *   \Drupal\action\Form\ActionEditForm instead.
 *
 * @see https://www.drupal.org/node/3033540
 */
class ActionEditForm extends ActionEditFormCurrent {

}
