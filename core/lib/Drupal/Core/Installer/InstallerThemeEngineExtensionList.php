<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Extension\ThemeEngineExtensionList;

/**
 * Overrides the theme engine extension list to have a static cache.
 */
class InstallerThemeEngineExtensionList extends ThemeEngineExtensionList {
  use ExtensionListTrait;

}
