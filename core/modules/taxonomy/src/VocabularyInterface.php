<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyInterface.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Provides an interface defining a taxonomy vocabulary entity.
 */
interface VocabularyInterface extends ConfigEntityInterface, ThirdPartySettingsInterface {

}
