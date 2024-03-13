<?php

namespace Drupal\layout_builder;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Defines an interface for an object that stores layout sections for defaults.
 */
interface DefaultsSectionStorageInterface extends SectionStorageInterface, ThirdPartySettingsInterface, LayoutBuilderEnabledInterface, LayoutBuilderOverridableInterface {}
