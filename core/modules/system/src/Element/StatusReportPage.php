<?php

namespace Drupal\system\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element\StatusReport;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

/**
 * Creates status report page element.
 *
 * @RenderElement("status_report_page")
 */
class StatusReportPage extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'status_report_page',
      '#pre_render' => [
        [$class, 'preRenderCounters'],
        [$class, 'preRenderGeneralInfo'],
        [$class, 'preRenderRequirements'],
      ],
    ];
  }

  /**
   * #pre_render callback to get general info out of requirements.
   */
  public static function preRenderGeneralInfo($element) {
    $element['#general_info'] = [
      '#theme' => 'status_report_general_info',
    ];
    // Loop through requirements and pull out items.
    foreach ($element['#requirements'] as $key => $requirement) {
      switch ($key) {
        case 'cron':
          foreach ($requirement['description'] as &$description_elements) {
            foreach ($description_elements as &$description_element) {
              if (isset($description_element['#url']) && $description_element['#url']->getRouteName() == 'system.run_cron') {
                $description_element['#attributes']['class'][] = 'button';
                $description_element['#attributes']['class'][] = 'button--small';
                $description_element['#attributes']['class'][] = 'button--primary';
                $description_element['#attributes']['class'][] = 'system-status-general-info__run-cron';
              }
            }
          }
          // Intentional fall-through.

        case 'drupal':
        case 'webserver':
        case 'database_system':
        case 'database_system_version':
        case 'php':
        case 'php_memory_limit':
          $element['#general_info']['#' . $key] = $requirement;
          if (isset($requirement['severity']) && $requirement['severity'] < REQUIREMENT_WARNING) {
            unset($element['#requirements'][$key]);
          }
          break;
      }
    }

    return $element;
  }

  /**
   * #pre_render callback to create counter elements.
   */
  public static function preRenderCounters($element) {
    // Count number of items with different severity for summary.
    $counters = [
      'error' => [
        'amount' => 0,
        'text' => t('Error'),
        'text_plural' => t('Errors'),
      ],
      'warning' => [
        'amount' => 0,
        'text' => t('Warning'),
        'text_plural' => t('Warnings'),
      ],
      'checked' => [
        'amount' => 0,
        'text' => t('Checked'),
        'text_plural' => t('Checked'),
      ],
    ];

    $severities = StatusReport::getSeverities();
    foreach ($element['#requirements'] as $key => &$requirement) {
      $severity = $severities[REQUIREMENT_INFO];
      if (isset($requirement['severity'])) {
        $severity = $severities[(int) $requirement['severity']];
      }
      elseif (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'install') {
        $severity = $severities[REQUIREMENT_OK];
      }

      if (isset($counters[$severity['status']])) {
        $counters[$severity['status']]['amount']++;
      }
    }

    foreach ($counters as $key => $counter) {
      if ($counter['amount'] === 0) {
        continue;
      }

      $text = new PluralTranslatableMarkup($counter['amount'], $counter['text'], $counter['text_plural']);

      $element['#counters'][$key] = [
        '#theme' => 'status_report_counter',
        '#amount' => $counter['amount'],
        '#text' => $text,
        '#severity' => $key,
      ];
    }

    return $element;
  }

  /**
   * #pre_render callback to create status report requirements.
   */
  public static function preRenderRequirements($element) {
    $element['#requirements'] = [
      '#type' => 'status_report',
      '#requirements' => $element['#requirements'],
    ];

    return $element;
  }

}
