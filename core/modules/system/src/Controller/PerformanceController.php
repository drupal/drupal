<?php

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\Form\ClearCacheForm;
use Drupal\system\Form\PerformanceForm;

/**
 * Controller for performance admin.
 */
class PerformanceController extends ControllerBase {

  /**
   * Displays the system performance page.
   *
   * @return array
   *   A render array containing the cache-clear form and performance
   *   configuration form.
   */
  public function build(): array {
    return [
      'cache_clear' => $this->formBuilder()->getForm(ClearCacheForm::class),
      'performance' => $this->formBuilder()->getForm(PerformanceForm::class),
    ];
  }

}
