<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginCollection;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManagerInterface;

/**
 * Configuration entity.
 *
 * Allow to override and scope design tokens.
 *
 * @internal
 *   This API is experimental.
 */
#[ConfigEntityType(
  id: 'design_token',
  label: new TranslatableMarkup('Design token'),
  entity_keys: [
    'id' => 'id',
    'label' => 'id',
  ],
  admin_permission: 'administer design tokens',
  constraints: [
    'ImmutableProperties' => [
      'properties' => [
        'id',
        'path',
        'type',
      ],
    ],
  ],
  config_export: [
    'id',
    'path',
    'type',
    'scopes',
  ],
)]
class DesignToken extends ConfigEntityBase implements DesignTokenInterface, EntityWithPluginCollectionInterface {

  /**
   * The design token ID.
   */
  protected ?string $id;

  /**
   * The design token path.
   */
  protected string $path;

  /**
   * The design token type.
   */
  protected string $type;

  /**
   * The design token scopes.
   */
  protected array $scopes;

  /**
   * Holds the collection of scopes.
   */
  private DesignTokenValuePluginCollection $scopesCollection;

  /**
   * {@inheritdoc}
   */
  public function getCssVariableName(): string {
    $name = $this->path;
    $name = \strtr($name, [
      '_' => '-',
      ' ' => '-',
      '.' => '-',
    ]);
    return "--{$name}";
  }

  /**
   * {@inheritdoc}
   */
  public function getScopes(): DesignTokenValuePluginCollection {
    if (!isset($this->scopesCollection)) {
      /** @var \Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManagerInterface $manager */
      $manager = \Drupal::service(DesignTokenValuePluginManagerInterface::class);
      $this->scopesCollection = new DesignTokenValuePluginCollection(
        $manager,
        $this->type,
        $this->scopes,
      );
    }
    return $this->scopesCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['scopes' => $this->getScopes()];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Ensure scope does not contain a dot.
    foreach ($this->scopes as $scope => $value) {
      if (\strpos($scope, '.') !== FALSE) {
        $this->scopes[static::getConfigScopeName($scope)] = $value;
        unset($this->scopes[$scope]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function toCss(array $tokens): string {
    // Regroup values by scope.
    $scopes = [];
    foreach ($tokens as $token) {
      foreach ($token->getScopes() as $scope => $value) {
        $scope = static::getCssScopeName($scope);
        if (!isset($scopes[$scope])) {
          $scopes[$scope] = [];
        }

        $scopes[$scope][$token->getCssVariableName()] = $value;
      }
    }

    return static::getCssVariablesInlineCss($scopes);
  }

  /**
   * Prepares a scope for CSS usage.
   *
   * Transform DOT_CONVERSION_CHARACTER into dot.
   *
   * @param string $scope
   *   The scope to convert.
   *
   * @return string
   *   The converted scope.
   */
  public static function getCssScopeName(string $scope): string {
    return \str_replace(self::DOT_CONVERSION_CHARACTER, '.', $scope);
  }

  /**
   * Prepares a scope for config storage.
   *
   * Transform dot into DOT_CONVERSION_CHARACTER.
   *
   * @param string $scope
   *   The scope to convert.
   *
   * @return string
   *   The converted scope.
   */
  public static function getConfigScopeName(string $scope): string {
    return \str_replace('.', self::DOT_CONVERSION_CHARACTER, $scope);
  }

  /**
   * Prepares inline CSS from scope grouped CSS variables.
   *
   * @param array $scopes
   *   The prepared scopes.
   *
   * @return string
   *   The inline CSS.
   */
  protected static function getCssVariablesInlineCss(array $scopes): string {
    $css = '';
    foreach ($scopes as $scope => $variables) {
      if (!\is_array($variables) || empty($variables)) {
        continue;
      }

      $scope_variables = [];
      foreach ($variables as $variableName => $variableValue) {
        $scope_variables[] = "{$variableName}:{$variableValue->toCss()};";
      }
      $css .= "{$scope}{" . \implode('', $scope_variables) . '}';
    }
    return $css;
  }

}
