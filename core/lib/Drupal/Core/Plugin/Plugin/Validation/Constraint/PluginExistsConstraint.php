<?php

declare(strict_types = 1);

namespace Drupal\Core\Plugin\Plugin\Validation\Constraint;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Checks if a plugin exists and optionally implements a particular interface.
 *
 * @Constraint(
 *   id = "PluginExists",
 *   label = @Translation("Plugin exists", context = "Validation"),
 * )
 */
class PluginExistsConstraint extends Constraint implements ContainerFactoryPluginInterface {

  /**
   * The error message if a plugin does not exist.
   *
   * @var string
   */
  public string $unknownPluginMessage = "The '@plugin_id' plugin does not exist.";

  /**
   * The error message if a plugin does not implement the expected interface.
   *
   * @var string
   */
  public string $invalidInterfaceMessage = "The '@plugin_id' plugin must implement or extend @interface.";

  /**
   * The ID of the plugin manager service.
   *
   * @var string
   */
  protected string $manager;

  /**
   * Optional name of the interface that the plugin must implement.
   *
   * @var string|null
   */
  public ?string $interface = NULL;

  /**
   * Constructs a PluginExistsConstraint.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager associated with the constraint.
   * @param mixed|null $options
   *   The options (as associative array) or the value for the default option
   *   (any other type).
   * @param array|null $groups
   *   An array of validation groups.
   * @param mixed|null $payload
   *   Domain-specific data attached to a constraint.
   */
  public function __construct(public readonly PluginManagerInterface $pluginManager, mixed $options = NULL, array $groups = NULL, mixed $payload = NULL) {
    parent::__construct($options, $groups, $payload);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin_manager_id = $configuration['manager'] ?? $configuration['value'] ?? NULL;
    if ($plugin_manager_id === NULL) {
      throw new MissingOptionsException(sprintf('The option "manager" must be set for constraint "%s".', static::class), ['manager']);
    }
    return new static($container->get($plugin_manager_id), $configuration);
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
