<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Drupal\Core\Config\Schema\TypeResolver.
 */
#[CoversClass(TypeResolver::class)]
#[Group('config')]
class TypeResolverTest extends UnitTestCase {

  /**
   * Tests invalid type.
   */
  // phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
  #[TestWith(["[foo.%bar.qux]", "`foo.%bar.qux` is not a valid dynamic type expression. Dynamic type expressions must contain at least `%parent`, `%key`, or `%type`.`", ["foo" => "foo"]])]
  #[TestWith(["[%paren.field_type]", "`%paren.field_type` is not a valid dynamic type expression. Dynamic type expressions must contain at least `%parent`, `%key`, or `%type`."])]
  #[TestWith(["[something.%type]", "`%type` can only used when immediately preceded by `%parent` in `something.%type`", ["something" => "something"]])]
  // phpcs:enable
  public function testInvalidType(string $name, string $message, array $data = []): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage($message);
    TypeResolver::resolveDynamicTypeName($name, $data);
  }

}
