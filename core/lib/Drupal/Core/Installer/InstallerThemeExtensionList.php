<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Extension\ThemeExtensionList;

@trigger_error('Drupal\Core\Installer\InstallerThemeExtensionList is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use Drupal\Core\Extension\ThemeExtensionList instead. See https://www.drupal.org/node/3577846', E_USER_DEPRECATED);

/**
 * Backward-compatibility wrapper for the installer theme extension list.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal\Core\Extension\ThemeExtensionList instead.
 *
 * @see https://www.drupal.org/node/3577846
 */
class InstallerThemeExtensionList extends ThemeExtensionList {
  use ExtensionListTrait;

}
