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
    // Load the cache form and embed it in a details element.
    $cache_clear = $this->formBuilder()->getForm(ClearCacheForm::class);
    $cache_clear['clear_cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear cache'),
      '#open' => TRUE,
      'clear' => $cache_clear['clear'],
    ];
    unset($cache_clear['clear']);
    return [
      'cache_clear' => $cache_clear,
      'performance' => $this->formBuilder()->getForm(PerformanceForm::class),
    ];
  }

}
