<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Creates status report page element.
 */
#[RenderElement('status_report')]
class StatusReport extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'status_report_grouped',
      '#priorities' => [
        'error',
        'warning',
        'checked',
        'ok',
      ],
      '#pre_render' => [
        [static::class, 'preRenderGroupRequirements'],
      ],
    ];
  }

  /**
   * Render API callback: Groups requirements.
   *
   * This function is assigned as a #pre_render callback.
   */
  public static function preRenderGroupRequirements($element) {
    $grouped_requirements = [];
    RequirementSeverity::convertLegacyIntSeveritiesToEnums($element['#requirements'], __METHOD__);
    /** @var array{title: \Drupal\Core\StringTranslation\TranslatableMarkup, value: mixed, description: \Drupal\Core\StringTranslation\TranslatableMarkup, severity: \Drupal\Core\Extension\Requirement\RequirementSeverity} $requirement */
    foreach ($element['#requirements'] as $key => $requirement) {
      $severity = RequirementSeverity::Info;
      if (isset($requirement['severity'])) {
        $severity = $requirement['severity'] === RequirementSeverity::OK ? RequirementSeverity::Info : $requirement['severity'];
      }
      elseif (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'install') {
        $severity = RequirementSeverity::OK;
      }

      $grouped_requirements[$severity->status()]['title'] = $severity->title();
      $grouped_requirements[$severity->status()]['type'] = $severity->status();
      $grouped_requirements[$severity->status()]['items'][$key] = $requirement;
    }

    // Order the grouped requirements by a set order.
    $order = array_flip($element['#priorities']);
    uksort($grouped_requirements, function ($a, $b) use ($order) {
      return $order[$a] <=> $order[$b];
    });

    $element['#grouped_requirements'] = $grouped_requirements;

    return $element;
  }

  /**
   * Gets the severities.
   *
   * @return array
   *   An associative array of the requirements severities. The keys are the
   *   requirement constants defined in install.inc.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3410939
   */
  public static function getSeverities() {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:11.2.0 and is removed from in drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3410939', \E_USER_DEPRECATED);
    return [
      RequirementSeverity::Info->value => [
        'title' => t('Checked', [], ['context' => 'Examined']),
        'status' => 'checked',
      ],
      RequirementSeverity::OK->value => [
        'title' => t('OK'),
        'status' => 'ok',
      ],
      RequirementSeverity::Warning->value => [
        'title' => t('Warnings found'),
        'status' => 'warning',
      ],
      RequirementSeverity::Error->value => [
        'title' => t('Errors found'),
        'status' => 'error',
      ],
    ];
  }

}
