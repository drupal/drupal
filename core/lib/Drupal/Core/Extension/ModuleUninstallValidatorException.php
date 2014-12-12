<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ModuleUninstallValidatorException.
 */

namespace Drupal\Core\Extension;

/**
 * Defines an exception thrown when uninstalling a module that did not validate.
 */
class ModuleUninstallValidatorException extends \InvalidArgumentException { }
