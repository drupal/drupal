<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * Collects input values for recipes from the command line.
 *
 * @internal
 *   This API is experimental.
 */
final class ConsoleInputCollector implements InputCollectorInterface {

  /**
   * The name of the command-line option for passing input values.
   *
   * @var string
   */
  public const INPUT_OPTION = 'input';

  public function __construct(
    private readonly InputInterface $input,
    private readonly StyleInterface $io,
  ) {}

  /**
   * Configures a console command to support the `--input` option.
   *
   * This should be called by a command's configure() method.
   *
   * @param \Symfony\Component\Console\Command\Command $command
   *   The command being configured.
   */
  public static function configureCommand(Command $command): void {
    $command->addOption(
      static::INPUT_OPTION,
      'i',
      InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
      sprintf('An input value to pass to the recipe or one of its dependencies, in the form `--%s=RECIPE_NAME.INPUT_NAME=VALUE`.', static::INPUT_OPTION),
      [],
    );
  }

  /**
   * Returns the `--input` options passed to the command.
   *
   * @return string[]
   *   The values from the `--input` options passed to the command, keyed by
   *   fully qualified name (i.e., prefixed with the name of their defining
   *   recipe).
   */
  private function getInputFromOptions(): array {
    $options = [];
    try {
      foreach ($this->input->getOption(static::INPUT_OPTION) ?? [] as $option) {
        [$key, $value] = explode('=', $option, 2);
        $options[$key] = $value;
      }
    }
    catch (InvalidArgumentException) {
      // The option is undefined; there's nothing we need to do.
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function collectValue(string $name, DataDefinitionInterface $definition, mixed $default_value): mixed {
    $option_values = $this->getInputFromOptions();

    // If the value was passed as a `--input` option, return that.
    if (array_key_exists($name, $option_values)) {
      return $option_values[$name];
    }

    /** @var array{method: string, arguments?: array<mixed>}|null $settings */
    $settings = $definition->getSetting('prompt');
    // If there's no information on how to prompt the user, there's nothing else
    // for us to do; return the default value.
    if (empty($settings)) {
      return $default_value;
    }

    $method = $settings['method'];
    $arguments = $settings['arguments'] ?? [];

    // Most of the input-collecting methods of StyleInterface have a `default`
    // parameter.
    $arguments += [
      'default' => $default_value,
    ];
    // We don't support using Symfony Console's inline validation; instead,
    // input definitions should define constraints.
    unset($arguments['validator']);

    return $this->io->$method(...$arguments);
  }

}
