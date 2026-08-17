<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Extension\ThemeEngineExtensionList;

@trigger_error('Drupal\Core\Installer\InstallerThemeEngineExtensionList is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use Drupal\Core\Extension\ThemeEngineExtensionList instead. See https://www.drupal.org/node/3577846', E_USER_DEPRECATED);

/**
 * Backward-compatibility wrapper for the installer theme engine extension list.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal\Core\Extension\ThemeEngineExtensionList instead.
 *
 * @see https://www.drupal.org/node/3577846
 */
class InstallerThemeEngineExtensionList extends ThemeEngineExtensionList {
  use ExtensionListTrait;

}
