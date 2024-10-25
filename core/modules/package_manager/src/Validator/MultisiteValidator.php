<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Checks that the current site is not part of a multisite.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class MultisiteValidator implements EventSubscriberInterface {

  use BaseRequirementValidatorTrait;
  use StringTranslationTrait;

  public function __construct(private readonly PathLocator $pathLocator) {
  }

  /**
   * Validates that the current site is not part of a multisite.
   */
  public function validate(PreOperationStageEvent $event): void {
    if ($this->isMultisite()) {
      $event->addError([
        $this->t('Drupal multisite is not supported by Package Manager.'),
      ]);
    }
  }

  /**
   * Detects if the current site is part of a multisite.
   *
   * @return bool
   *   TRUE if the current site is part of a multisite, otherwise FALSE.
   */
  private function isMultisite(): bool {
    $web_root = $this->pathLocator->getWebRoot();
    if ($web_root) {
      $web_root .= '/';
    }
    $sites_php_path = $this->pathLocator->getProjectRoot() . '/' . $web_root . 'sites/sites.php';

    if (!file_exists($sites_php_path)) {
      return FALSE;
    }

    // @see \Drupal\Core\DrupalKernel::findSitePath()
    $sites = [];
    include $sites_php_path;
    // @see example.sites.php
    return count(array_unique($sites)) > 1;
  }

}
