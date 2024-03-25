<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\Core\Config\Schema\TypeResolver
 * @group config
 */
class TypeResolverTest extends UnitTestCase {

  /**
   * @testWith ["[foo.%bar.qux]", "`foo.%bar.qux` is not a valid dynamic type expression. Dynamic type expressions must contain at least `%parent`, `%key`, or `%type`.`", {"foo": "foo"}]
   *           ["[%paren.field_type]", "`%paren.field_type` is not a valid dynamic type expression. Dynamic type expressions must contain at least `%parent`, `%key`, or `%type`."]
   *           ["[something.%type]", "`%type` can only used when immediately preceded by `%parent` in `something.%type`", {"something": "something"}]
   */
  public function testInvalidType(string $name, string $message, array $data = []): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage($message);
    TypeResolver::resolveDynamicTypeName($name, $data);
  }

}
