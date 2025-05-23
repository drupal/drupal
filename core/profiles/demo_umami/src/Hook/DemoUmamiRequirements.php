<?php

declare(strict_types=1);

namespace Drupal\demo_umami\Hook;

use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the Demo: Umami Food Magazine (Experimental) profile.
 */
class DemoUmamiRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ProfileExtensionList $profileExtensionList,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $profile = \Drupal::installProfile();
    $info = $this->profileExtensionList->getExtensionInfo($profile);
    $requirements['experimental_profile_used'] = [
      'title' => $this->t('Experimental installation profile used'),
      'value' => $info['name'],
      'description' => $this->t('Experimental profiles are provided for testing purposes only. Use at your own risk. To start building a new site, reinstall Drupal and choose a non-experimental profile.'),
      'severity' => RequirementSeverity::Warning,
    ];
    return $requirements;
  }

}
