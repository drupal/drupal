<?php

declare(strict_types = 1);

namespace Drupal\Core\Plugin\Plugin\Validation\Constraint;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Checks if a plugin exists and optionally implements a particular interface.
 */
#[Constraint(
  id: 'PluginExists',
  label: new TranslatableMarkup('Plugin exists', [], ['context' => 'Validation'])
)]
class PluginExistsConstraint extends SymfonyConstraint implements ContainerFactoryPluginInterface {

  /**
   * Constructs a PluginExistsConstraint object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager.
   * @param string $manager
   *   The ID of the plugin manager service.
   * @param string|null $interface
   *   Optional name of the interface that the plugin must implement.
   * @param bool|null $allowFallback
   *   Whether to consider fallback plugin IDs as valid.
   * @param string $unknownPluginMessage
   *   The error message if a plugin does not exist.
   * @param string $invalidInterfaceMessage
   *   The error message if a plugin does not implement the expected interface.
   * @param array|null $groups
   *   The groups that the constraint belongs to.
   * @param mixed|null $payload
   *   Domain-specific data attached to a constraint.
   */
  public function __construct(
    public readonly PluginManagerInterface $pluginManager,
    protected string $manager,
    public ?string $interface = NULL,
    public bool $allowFallback = FALSE,
    public string $unknownPluginMessage = "The '@plugin_id' plugin does not exist.",
    public string $invalidInterfaceMessage = "The '@plugin_id' plugin must implement or extend @interface.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct(NULL, $groups, $payload);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if (isset($configuration['value'])) {
      $configuration['manager'] ??= $configuration['value'];
      unset($configuration['value']);
      @trigger_error('Passing the "value" option in configuration to ' . __METHOD__ . ' is deprecated in drupal:11.4.0 and will not be supported in drupal:12.0.0. See https://www.drupal.org/node/3554746', E_USER_DEPRECATED);
    }
    $configuration['manager'] ??= NULL;
    $plugin_manager_id = $configuration['manager'];
    if ($plugin_manager_id === NULL) {
      throw new MissingOptionsException(sprintf('The option "manager" must be set for constraint "%s".', static::class), ['manager']);
    }
    return new static($container->get($plugin_manager_id), ...$configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'manager';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['manager'];
  }

}
