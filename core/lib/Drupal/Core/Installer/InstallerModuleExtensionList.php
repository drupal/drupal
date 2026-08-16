<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Extension\ModuleExtensionList;

@trigger_error('Drupal\Core\Installer\InstallerModuleExtensionList is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use Drupal\Core\Extension\ModuleExtensionList instead. See https://www.drupal.org/node/3577846', E_USER_DEPRECATED);

/**
 * Backward-compatibility wrapper for the installer module extension list.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal\Core\Extension\ModuleExtensionList instead.
 *
 * @see https://www.drupal.org/node/3577846
 */
class InstallerModuleExtensionList extends ModuleExtensionList {
  use ExtensionListTrait;

}
