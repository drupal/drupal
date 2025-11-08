<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Rules;

// cspell:ignore analyse testdox

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\TestCase;

/**
 * Validates method-level PHPUnit test metadata in test classes.
 *
 * @implements Rule<\PHPStan\Node\InClassMethodNode>
 *
 * @internal
 */
final class TestClassMethodMetadata implements Rule {

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
    private FileTypeMapper $fileTypeMapper,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return InClassMethodNode::class;
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

    $method = $node->getMethodReflection();

    // Resolve the method's PHPDoc.
    $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
      $scope->getFile(),
      $scope->isInClass() ? $scope->getClassReflection()->getName() : NULL,
      $scope->isInTrait() ? $scope->getTraitReflection()->getName() : NULL,
      $method->getName(),
      $method->getDocComment() ?? '',
    );

    $fails = [];

    // Test methods should no longer have PHPUnit metadata annotations.
    if ($resolvedPhpDoc) {
      foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpDocNode) {
        foreach ($phpDocNode->getTags() as $tag) {
          if (in_array($tag->name, $this->annotationTargets, TRUE)) {
            $fails[] = RuleErrorBuilder::message("Test method {$method->getName()} must not add annotation {$tag->name}.")
              ->identifier('testMethod.metadataForbidden')
              ->line($node->getStartLine())
              ->build();
          }
        }
      }
    }

    return $fails;
  }

}
