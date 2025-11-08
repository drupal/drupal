<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Rules;

// cspell:ignore analyse testdox

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Rules class-level PHPUnit test metadata in test classes.
 *
 * @implements Rule<\PHPStan\Node\InClassNode>
 *
 * @internal
 */
final class TestClassClassMetadata implements Rule {

  /**
   * PHPUnit metadata annotations.
   *
   * @var list<string>
   */
  private array $annotationTargets = [
    '@after',
    '@afterClass',
    '@author',
    '@backupGlobals',
    '@backupStaticAttributes',
    '@before',
    '@beforeClass',
    '@covers',
    '@coversClass',
    '@coversDefaultClass',
    '@coversNothing',
    '@dataProvider',
    '@depends',
    '@doesNotPerformAssertions',
    '@group',
    '@large',
    '@legacy-covers',
    '@medium',
    '@postCondition',
    '@preCondition',
    '@preserveGlobalState',
    '@requires',
    '@runInSeparateProcess',
    '@runTestsInSeparateProcesses',
    '@small',
    '@test',
    '@testdox',
    '@testWith',
    '@ticket',
    '@uses',
  ];

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

    // We only process PHPUnit test classes here.
    if (!$class->isSubclassOfClass($this->reflectionProvider->getClass(TestCase::class))) {
      return [];
    }

    $fails = [];

    if ($class->isAbstract()) {
      // Abstract test classes (i.e. base test classes) should not have any
      // metadata on the class definition, neither attributes nor annotations.
      foreach ($class->getAttributes() as $attribute) {
        if (str_starts_with($attribute->getName(), 'PHPUnit\\Framework\\Attributes\\')) {
          $fails[] = RuleErrorBuilder::message("Abstract test class {$class->getName()} must not add attribute {$attribute->getName()}.")
            ->identifier('abstractTestClass.metadataForbidden')
            ->line($node->getStartLine())
            ->build();
        }
      }

      $resolvedPhpDoc = $class->getResolvedPhpDoc();
      if ($resolvedPhpDoc) {
        foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpDocNode) {
          foreach ($phpDocNode->getTags() as $tag) {
            if (in_array($tag->name, $this->annotationTargets, TRUE)) {
              $fails[] = RuleErrorBuilder::message("Abstract test class {$class->getName()} must not add annotation {$tag->name}.")
                ->identifier('abstractTestClass.metadataForbidden')
                ->line($node->getStartLine())
                ->build();
            }
          }
        }
      }
    }
    else {
      // Concrete test classes should no longer have PHPUnit metadata
      // annotations.
      $resolvedPhpDoc = $class->getResolvedPhpDoc();
      if ($resolvedPhpDoc) {
        foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpDocNode) {
          foreach ($phpDocNode->getTags() as $tag) {
            if (in_array($tag->name, $this->annotationTargets, TRUE)) {
              $fails[] = RuleErrorBuilder::message("Test class {$class->getName()} must not add annotation {$tag->name}.")
                ->identifier('testClass.metadataForbidden')
                ->line($node->getStartLine())
                ->build();
            }
          }
        }
      }
    }

    return $fails;
  }

}
