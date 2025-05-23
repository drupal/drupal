<?php

declare(strict_types=1);

namespace Drupal\jsonapi\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Requirements for the JSON:API module.
 */
class JsonapiRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $potential_conflicts = [
      'content_translation',
      'config_translation',
      'language',
    ];
    $should_warn = array_reduce($potential_conflicts, function ($should_warn, $module_name) {
      return $should_warn ?: $this->moduleHandler->moduleExists($module_name);
    }, FALSE);
    if ($should_warn) {
      $requirements['jsonapi_multilingual_support'] = [
        'title' => $this->t('JSON:API multilingual support'),
        'value' => $this->t('Limited'),
        'severity' => RequirementSeverity::Info,
        'description' => $this->t('Some multilingual features currently do not work well with JSON:API. See the <a href=":jsonapi-docs">JSON:API multilingual support documentation</a> for more information on the current status of multilingual support.', [
          ':jsonapi-docs' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/translations',
        ]),
      ];
    }
    $requirements['jsonapi_revision_support'] = [
      'title' => $this->t('JSON:API revision support'),
      'value' => $this->t('Limited'),
      'severity' => RequirementSeverity::Info,
      'description' => $this->t('Revision support is currently read-only and only for the "Content" and "Media" entity types in JSON:API. See the <a href=":jsonapi-docs">JSON:API revision support documentation</a> for more information on the current status of revision support.', [
        ':jsonapi-docs' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/revisions',
      ]),
    ];
    $requirements['jsonapi_read_only_mode'] = [
      'title' => $this->t('JSON:API allowed operations'),
      'value' => $this->t('Read-only'),
      'severity' => RequirementSeverity::Info,
    ];
    if (!$this->configFactory->get('jsonapi.settings')->get('read_only')) {
      $requirements['jsonapi_read_only_mode']['value'] = $this->t('All (create, read, update, delete)');
      $requirements['jsonapi_read_only_mode']['description'] = $this->t('It is recommended to <a href=":configure-url">configure</a> JSON:API to only accept all operations if the site requires it. <a href=":docs">Learn more about securing your site with JSON:API.</a>', [
        ':docs' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/security-considerations',
        ':configure-url' => Url::fromRoute('jsonapi.settings')->toString(),
      ]);
    }
    return $requirements;
  }

}
