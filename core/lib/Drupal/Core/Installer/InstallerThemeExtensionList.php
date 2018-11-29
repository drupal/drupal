<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Extension\ThemeExtensionList;

/**
 * Overrides the theme extension list to have a static cache.
 */
class InstallerThemeExtensionList extends ThemeExtensionList {
  use ExtensionListTrait;

}
