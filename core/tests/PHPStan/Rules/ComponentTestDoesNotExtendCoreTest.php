<?php

declare(strict_types=1);

// cspell:ignore analyse
namespace Drupal\PHPStan\Rules;

use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UnitTestCase;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Ensures that no component tests are extending a core test base class.
 *
 * @implements Rule<\PHPStan\Node\InClassNode>
 *
 * @internal
 */
final class ComponentTestDoesNotExtendCoreTest implements Rule {

  public function __construct(
    private ReflectionProvider $reflectionProvider,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return InClassNode::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {
    $class = $node->getClassReflection();

    if (!str_starts_with($class->getName(), 'Drupal\Tests\Component')) {
      return [];
    }

    $invalidParents = [
      $this->reflectionProvider->getClass(UnitTestCase::class),
      $this->reflectionProvider->getClass(BuildTestBase::class),
      $this->reflectionProvider->getClass(KernelTestBase::class),
      $this->reflectionProvider->getClass(BrowserTestBase::class),
    ];

    foreach ($invalidParents as $invalidParent) {
      if ($class->isSubclassOfClass($invalidParent)) {
        return [
          RuleErrorBuilder::message("Component tests should not extend {$invalidParent->getName()}.")
            ->line($node->getStartLine())
            ->build(),
        ];
      }
    }

    return [];
  }

}
