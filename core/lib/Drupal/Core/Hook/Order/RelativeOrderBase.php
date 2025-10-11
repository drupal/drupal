<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Order;

use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\OrderOperation\BeforeOrAfter;
use Drupal\Core\Hook\OrderOperation\OrderOperation;

/**
 * Orders an implementation relative to other implementations.
 */
abstract readonly class RelativeOrderBase implements OrderInterface {

  /**
   * Constructor.
   *
   * @param list<string> $modules
   *   A list of modules the implementations should order against.
   * @param list<array{class-string, string}> $classesAndMethods
   *   A list of implementations to order against, as [$class, $method].
   */
  public function __construct(
    public array $modules = [],
    public array $classesAndMethods = [],
  ) {
    if (!$this->modules && !$this->classesAndMethods) {
      throw new \LogicException('Order must provide either modules or class-method pairs to order against.');
    }
  }

  /**
   * Specifies the ordering direction.
   *
   * @return bool
   *   TRUE, if the ordered implementation should be inserted after the
   *   implementations specified in the constructor.
   */
  abstract protected function isAfter(): bool;

  /**
   * {@inheritdoc}
   */
  public function getOperation(string $identifier): OrderOperation {
    return new BeforeOrAfter(
      $identifier,
      $this->modules,
      array_map(
        static function (array $class_and_method): string {
          if ($class_and_method[0] === ProceduralCall::class) {
            return $class_and_method[1];
          }
          else {
            return $class_and_method[0] . '::' . $class_and_method[1];
          }
        },
        $this->classesAndMethods,
      ),
      $this->isAfter(),
    );
  }

}
