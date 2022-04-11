<?php

namespace Drupal\action\Plugin\migrate\source;

@trigger_error('The ' . __NAMESPACE__ . '\Action is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use \Drupal\system\Plugin\migrate\source\Action instead. See https://www.drupal.org/node/3110401', E_USER_DEPRECATED);

use Drupal\system\Plugin\migrate\source\Action as SystemAction;

/**
 * Drupal 6/7 action source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\system\Plugin\migrate\source\Action instead.
 *
 * @see https://www.drupal.org/node/3110401
 */
class Action extends SystemAction {

}
