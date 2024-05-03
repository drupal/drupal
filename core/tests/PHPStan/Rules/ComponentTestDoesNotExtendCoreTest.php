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
      UnitTestCase::class,
      BuildTestBase::class,
      KernelTestBase::class,
      BrowserTestBase::class,
    ];

    foreach ($invalidParents as $invalidParent) {
      if ($class->isSubclassOf($invalidParent)) {
        return [
          RuleErrorBuilder::message("Component tests should not extend {$invalidParent}.")
            ->line($node->getStartLine())
            ->build(),
        ];
      }
    }

    return [];
  }

}
