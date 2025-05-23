<?php

declare(strict_types=1);

namespace Drupal\image\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the Image module.
 */
class ImageRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ImageToolkitManager $imageToolkitManager,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $toolkit = $this->imageToolkitManager->getDefaultToolkit();
    if ($toolkit) {
      $plugin_definition = $toolkit->getPluginDefinition();
      $requirements = [
        'image.toolkit' => [
          'title' => $this->t('Image toolkit'),
          'value' => $toolkit->getPluginId(),
          'description' => $plugin_definition['title'],
        ],
      ];

      foreach ($toolkit->getRequirements() as $key => $requirement) {
        $namespaced_key = 'image.toolkit.' . $toolkit->getPluginId() . '.' . $key;
        $requirements[$namespaced_key] = $requirement;
      }
    }
    else {
      $requirements = [
        'image.toolkit' => [
          'title' => $this->t('Image toolkit'),
          'value' => $this->t('None'),
          'description' => $this->t("No image toolkit is configured on the site. Check PHP installed extensions or add a contributed toolkit that doesn't require a PHP extension. Make sure that at least one valid image toolkit is installed."),
          'severity' => RequirementSeverity::Error,
        ],
      ];
    }

    return $requirements;
  }

}
