<?php
// @codingStandardsIgnoreFile

namespace Drupal\Tests\Component\Annotation\Doctrine;

use Drupal\Component\Annotation\Doctrine\DocParser;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Annotation\Target;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithConstants;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\IntefaceWithConstants;
use Drupal\Tests\PhpunitCompatibilityTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Doctrine\DocParser
 *
 * This class is a near-copy of
 * Doctrine\Tests\Common\Annotations\DocParserTest, which is part of the
 * Doctrine project: <http://www.doctrine-project.org>.  It was copied from
 * version 1.2.7.
 *
 * The supporting test fixture classes in
 * core/tests/Drupal/Tests/Component/Annotation/Doctrine/Fixtures were also
 * copied from version 1.2.7.
 *
 * @group Annotation
 */
class DocParserTest extends TestCase
{
  use PhpunitCompatibilityTrait;
    public function testNestedArraysWithNestedAnnotation()
    {
        $parser = $this->createTestParser();

        // Nested arrays with nested annotations
        $result = $parser->parse('@Name(foo={1,2, {"key"=@Name}})');
        $annot = $result[0];

        $this->assertInstanceOf(Name::class, $annot);
        $this->assertNull($annot->value);
        $this->assertCount(3, $annot->foo);
        $this->assertEquals(1, $annot->foo[0]);
        $this->assertEquals(2, $annot->foo[1]);
        $this->assertIsArray($annot->foo[2]);

        $nestedArray = $annot->foo[2];
        $this->assertTrue(isset($nestedArray['key']));
        $this->assertInstanceOf(Name::class, $nestedArray['key']);
    }

    public function testBasicAnnotations()
    {
        $parser = $this->createTestParser();

        // Marker annotation
        $result = $parser->parse("@Name");
        $annot = $result[0];
        $this->assertInstanceOf(Name::class, $annot);
        $this->assertNull($annot->value);
        $this->assertNull($annot->foo);

        // Associative arrays
        $result = $parser->parse('@Name(foo={"key1" = "value1"})');
        $annot = $result[0];
        $this->assertNull($annot->value);
        $this->assertIsArray($annot->foo);
        $this->assertTrue(isset($annot->foo['key1']));

        // Numerical arrays
        $result = $parser->parse('@Name({2="foo", 4="bar"})');
        $annot = $result[0];
        $this->assertIsArray($annot->value);
        $this->assertEquals('foo', $annot->value[2]);
        $this->assertEquals('bar', $annot->value[4]);
        $this->assertFalse(isset($annot->value[0]));
        $this->assertFalse(isset($annot->value[1]));
        $this->assertFalse(isset($annot->value[3]));

        // Multiple values
        $result = $parser->parse('@Name(@Name, @Name)');
        $annot = $result[0];

        $this->assertInstanceOf(Name::class, $annot);
        $this->assertIsArray($annot->value);
        $this->assertInstanceOf(Name::class, $annot->value[0]);
        $this->assertInstanceOf(Name::class, $annot->value[1]);

        // Multiple types as values
        $result = $parser->parse('@Name(foo="Bar", @Name, {"key1"="value1", "key2"="value2"})');
        $annot = $result[0];

        $this->assertInstanceOf(Name::class, $annot);
        $this->assertIsArray($annot->value);
        $this->assertInstanceOf(Name::class, $annot->value[0]);
        $this->assertIsArray($annot->value[1]);
        $this->assertEquals('value1', $annot->value[1]['key1']);
        $this->assertEquals('value2', $annot->value[1]['key2']);

        // Complete docblock
        $docblock = <<<DOCBLOCK
/**
 * Some nifty class.
 *
 * @author Mr.X
 * @Name(foo="bar")
 */
DOCBLOCK;

        $result = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot = $result[0];
        $this->assertInstanceOf(Name::class, $annot);
        $this->assertEquals("bar", $annot->foo);
        $this->assertNull($annot->value);
   }

    public function testDefaultValueAnnotations()
    {
        $parser = $this->createTestParser();

        // Array as first value
        $result = $parser->parse('@Name({"key1"="value1"})');
        $annot = $result[0];

        $this->assertInstanceOf(Name::class, $annot);
        $this->assertIsArray($annot->value);
        $this->assertEquals('value1', $annot->value['key1']);

        // Array as first value and additional values
        $result = $parser->parse('@Name({"key1"="value1"}, foo="bar")');
        $annot = $result[0];

        $this->assertInstanceOf(Name::class, $annot);
        $this->assertIsArray($annot->value);
        $this->assertEquals('value1', $annot->value['key1']);
        $this->assertEquals('bar', $annot->foo);
    }

    public function testNamespacedAnnotations()
    {
        $parser = new DocParser;
        $parser->setIgnoreNotImportedAnnotations(true);

        $docblock = <<<DOCBLOCK
/**
 * Some nifty class.
 *
 * @package foo
 * @subpackage bar
 * @author Mr.X <mr@x.com>
 * @Drupal\Tests\Component\Annotation\Doctrine\Name(foo="bar")
 * @ignore
 */
DOCBLOCK;

        $result = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot = $result[0];
        $this->assertInstanceOf(Name::class, $annot);
        $this->assertEquals("bar", $annot->foo);
    }

    /**
     * @group debug
     */
    public function testTypicalMethodDocBlock()
    {
        $parser = $this->createTestParser();

        $docblock = <<<DOCBLOCK
/**
 * Some nifty method.
 *
 * @since 2.0
 * @Drupal\Tests\Component\Annotation\Doctrine\Name(foo="bar")
 * @param string \$foo This is foo.
 * @param mixed \$bar This is bar.
 * @return string Foo and bar.
 * @This is irrelevant
 * @Marker
 */
DOCBLOCK;

        $result = $parser->parse($docblock);
        $this->assertCount(2, $result);
        $this->assertTrue(isset($result[0]));
        $this->assertTrue(isset($result[1]));
        $annot = $result[0];
        $this->assertInstanceOf(Name::class, $annot);
        $this->assertEquals("bar", $annot->foo);
        $marker = $result[1];
        $this->assertInstanceOf(Marker::class, $marker);
    }


    public function testAnnotationWithoutConstructor()
    {
        $parser = $this->createTestParser();


        $docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructor("Some data")
 */
DOCBLOCK;

        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot      = $result[0];

        $this->assertNotNull($annot);
        $this->assertInstanceOf(SomeAnnotationClassNameWithoutConstructor::class, $annot);

        $this->assertNull($annot->name);
        $this->assertNotNull($annot->data);
        $this->assertEquals($annot->data, "Some data");




$docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructor(name="Some Name", data = "Some data")
 */
DOCBLOCK;


        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot      = $result[0];

        $this->assertNotNull($annot);
        $this->assertInstanceOf(SomeAnnotationClassNameWithoutConstructor::class, $annot);

        $this->assertEquals($annot->name, "Some Name");
        $this->assertEquals($annot->data, "Some data");




$docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructor(data = "Some data")
 */
DOCBLOCK;

        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot      = $result[0];

        $this->assertEquals($annot->data, "Some data");
        $this->assertNull($annot->name);


        $docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructor(name = "Some name")
 */
DOCBLOCK;

        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot      = $result[0];

        $this->assertEquals($annot->name, "Some name");
        $this->assertNull($annot->data);

        $docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructor("Some data")
 */
DOCBLOCK;

        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot      = $result[0];

        $this->assertEquals($annot->data, "Some data");
        $this->assertNull($annot->name);



        $docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructor("Some data",name = "Some name")
 */
DOCBLOCK;

        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot      = $result[0];

        $this->assertEquals($annot->name, "Some name");
        $this->assertEquals($annot->data, "Some data");


        $docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationWithConstructorWithoutParams(name = "Some name")
 */
DOCBLOCK;

        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $annot      = $result[0];

        $this->assertEquals($annot->name, "Some name");
        $this->assertEquals($annot->data, "Some data");

        $docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructorAndProperties()
 */
DOCBLOCK;

        $result     = $parser->parse($docblock);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(SomeAnnotationClassNameWithoutConstructorAndProperties::class, $result[0]);
    }

    public function testAnnotationTarget()
    {

        $parser = new DocParser;
        $parser->setImports(array(
            '__NAMESPACE__' => 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures',
        ));
        $class  = new \ReflectionClass('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithValidAnnotationTarget');


        $context    = 'class ' . $class->getName();
        $docComment = $class->getDocComment();

        $parser->setTarget(Target::TARGET_CLASS);
        $this->assertNotNull($parser->parse($docComment,$context));


        $property   = $class->getProperty('foo');
        $docComment = $property->getDocComment();
        $context    = 'property ' . $class->getName() . "::\$" . $property->getName();

        $parser->setTarget(Target::TARGET_PROPERTY);
        $this->assertNotNull($parser->parse($docComment,$context));



        $method     = $class->getMethod('someFunction');
        $docComment = $property->getDocComment();
        $context    = 'method ' . $class->getName() . '::' . $method->getName() . '()';

        $parser->setTarget(Target::TARGET_METHOD);
        $this->assertNotNull($parser->parse($docComment,$context));


        try {
            $class      = new \ReflectionClass('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithInvalidAnnotationTargetAtClass');
            $context    = 'class ' . $class->getName();
            $docComment = $class->getDocComment();

            $parser->setTarget(Target::TARGET_CLASS);
            $parser->parse($docComment, $context);

            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertNotNull($exc->getMessage());
        }


        try {

            $class      = new \ReflectionClass('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithInvalidAnnotationTargetAtMethod');
            $method     = $class->getMethod('functionName');
            $docComment = $method->getDocComment();
            $context    = 'method ' . $class->getName() . '::' . $method->getName() . '()';

            $parser->setTarget(Target::TARGET_METHOD);
            $parser->parse($docComment, $context);

            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertNotNull($exc->getMessage());
        }


        try {
            $class      = new \ReflectionClass('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithInvalidAnnotationTargetAtProperty');
            $property   = $class->getProperty('foo');
            $docComment = $property->getDocComment();
            $context    = 'property ' . $class->getName() . "::\$" . $property->getName();

            $parser->setTarget(Target::TARGET_PROPERTY);
            $parser->parse($docComment, $context);

            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertNotNull($exc->getMessage());
        }

    }

    public function getAnnotationVarTypeProviderValid()
    {
        //({attribute name}, {attribute value})
         return array(
            // mixed type
            array('mixed', '"String Value"'),
            array('mixed', 'true'),
            array('mixed', 'false'),
            array('mixed', '1'),
            array('mixed', '1.2'),
            array('mixed', '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll'),

            // boolean type
            array('boolean', 'true'),
            array('boolean', 'false'),

            // alias for internal type boolean
            array('bool', 'true'),
            array('bool', 'false'),

            // integer type
            array('integer', '0'),
            array('integer', '1'),
            array('integer', '123456789'),
            array('integer', '9223372036854775807'),

            // alias for internal type double
            array('float', '0.1'),
            array('float', '1.2'),
            array('float', '123.456'),

            // string type
            array('string', '"String Value"'),
            array('string', '"true"'),
            array('string', '"123"'),

              // array type
            array('array', '{@AnnotationExtendsAnnotationTargetAll}'),
            array('array', '{@AnnotationExtendsAnnotationTargetAll,@AnnotationExtendsAnnotationTargetAll}'),

            array('arrayOfIntegers', '1'),
            array('arrayOfIntegers', '{1}'),
            array('arrayOfIntegers', '{1,2,3,4}'),
            array('arrayOfAnnotations', '@AnnotationExtendsAnnotationTargetAll'),
            array('arrayOfAnnotations', '{@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll}'),
            array('arrayOfAnnotations', '{@AnnotationExtendsAnnotationTargetAll, @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll}'),

            // annotation instance
            array('annotation', '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll'),
            array('annotation', '@AnnotationExtendsAnnotationTargetAll'),
        );
    }

    public function getAnnotationVarTypeProviderInvalid()
    {
         //({attribute name}, {type declared type}, {attribute value} , {given type or class})
         return array(
            // boolean type
            array('boolean','boolean','1','integer'),
            array('boolean','boolean','1.2','double'),
            array('boolean','boolean','"str"','string'),
            array('boolean','boolean','{1,2,3}','array'),
            array('boolean','boolean','@Name', 'an instance of Drupal\Tests\Component\Annotation\Doctrine\Name'),

            // alias for internal type boolean
            array('bool','bool', '1','integer'),
            array('bool','bool', '1.2','double'),
            array('bool','bool', '"str"','string'),
            array('bool','bool', '{"str"}','array'),

            // integer type
            array('integer','integer', 'true','boolean'),
            array('integer','integer', 'false','boolean'),
            array('integer','integer', '1.2','double'),
            array('integer','integer', '"str"','string'),
            array('integer','integer', '{"str"}','array'),
            array('integer','integer', '{1,2,3,4}','array'),

            // alias for internal type double
            array('float','float', 'true','boolean'),
            array('float','float', 'false','boolean'),
            array('float','float', '123','integer'),
            array('float','float', '"str"','string'),
            array('float','float', '{"str"}','array'),
            array('float','float', '{12.34}','array'),
            array('float','float', '{1,2,3}','array'),

            // string type
            array('string','string', 'true','boolean'),
            array('string','string', 'false','boolean'),
            array('string','string', '12','integer'),
            array('string','string', '1.2','double'),
            array('string','string', '{"str"}','array'),
            array('string','string', '{1,2,3,4}','array'),

             // annotation instance
            array('annotation','Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', 'true','boolean'),
            array('annotation','Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', 'false','boolean'),
            array('annotation','Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '12','integer'),
            array('annotation','Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '1.2','double'),
            array('annotation','Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '{"str"}','array'),
            array('annotation','Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '{1,2,3,4}','array'),
            array('annotation','Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '@Name','an instance of Drupal\Tests\Component\Annotation\Doctrine\Name'),
        );
    }

    public function getAnnotationVarTypeArrayProviderInvalid()
    {
         //({attribute name}, {type declared type}, {attribute value} , {given type or class})
         return array(
            array('arrayOfIntegers', 'integer', 'true', 'boolean'),
            array('arrayOfIntegers', 'integer', 'false', 'boolean'),
            array('arrayOfIntegers', 'integer', '{true,true}', 'boolean'),
            array('arrayOfIntegers', 'integer', '{1,true}', 'boolean'),
            array('arrayOfIntegers', 'integer', '{1,2,1.2}', 'double'),
            array('arrayOfIntegers', 'integer', '{1,2,"str"}', 'string'),

            array('arrayOfStrings', 'string', 'true', 'boolean'),
            array('arrayOfStrings', 'string', 'false', 'boolean'),
            array('arrayOfStrings', 'string', '{true,true}', 'boolean'),
            array('arrayOfStrings', 'string', '{"foo",true}', 'boolean'),
            array('arrayOfStrings', 'string', '{"foo","bar",1.2}', 'double'),
            array('arrayOfStrings', 'string', '1', 'integer'),

            array('arrayOfAnnotations', 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', 'true', 'boolean'),
            array('arrayOfAnnotations', 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', 'false', 'boolean'),
            array('arrayOfAnnotations', 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '{@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll,true}', 'boolean'),
            array('arrayOfAnnotations', 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '{@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll,true}', 'boolean'),
            array('arrayOfAnnotations', 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '{@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll,1.2}', 'double'),
            array('arrayOfAnnotations', 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll', '{@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll,@AnnotationExtendsAnnotationTargetAll,"str"}', 'string'),
        );
    }

    /**
     * @dataProvider getAnnotationVarTypeProviderValid
     */
    public function testAnnotationWithVarType($attribute, $value)
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::$invalidProperty.';
        $docblock   = sprintf('@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithVarType(%s = %s)',$attribute, $value);
        $parser->setTarget(Target::TARGET_PROPERTY);

        $result = $parser->parse($docblock, $context);

        $this->assertTrue(sizeof($result) === 1);
        $this->assertInstanceOf('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithVarType', $result[0]);
        $this->assertNotNull($result[0]->$attribute);
    }

    /**
     * @dataProvider getAnnotationVarTypeProviderInvalid
     */
    public function testAnnotationWithVarTypeError($attribute,$type,$value,$given)
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $docblock   = sprintf('@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithVarType(%s = %s)',$attribute, $value);
        $parser->setTarget(Target::TARGET_PROPERTY);

        try {
            $parser->parse($docblock, $context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains("[Type Error] Attribute \"$attribute\" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithVarType declared on property SomeClassName::invalidProperty. expects a(n) $type, but got $given.", $exc->getMessage());
        }
    }


    /**
     * @dataProvider getAnnotationVarTypeArrayProviderInvalid
     */
    public function testAnnotationWithVarTypeArrayError($attribute,$type,$value,$given)
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $docblock   = sprintf('@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithVarType(%s = %s)',$attribute, $value);
        $parser->setTarget(Target::TARGET_PROPERTY);

        try {
            $parser->parse($docblock, $context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains("[Type Error] Attribute \"$attribute\" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithVarType declared on property SomeClassName::invalidProperty. expects either a(n) $type, or an array of {$type}s, but got $given.", $exc->getMessage());
        }
    }

    /**
     * @dataProvider getAnnotationVarTypeProviderValid
     */
    public function testAnnotationWithAttributes($attribute, $value)
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::$invalidProperty.';
        $docblock   = sprintf('@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithAttributes(%s = %s)',$attribute, $value);
        $parser->setTarget(Target::TARGET_PROPERTY);

        $result = $parser->parse($docblock, $context);

        $this->assertTrue(sizeof($result) === 1);
        $this->assertInstanceOf('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithAttributes', $result[0]);
        $getter = "get".ucfirst($attribute);
        $this->assertNotNull($result[0]->$getter());
    }

   /**
     * @dataProvider getAnnotationVarTypeProviderInvalid
     */
    public function testAnnotationWithAttributesError($attribute,$type,$value,$given)
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $docblock   = sprintf('@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithAttributes(%s = %s)',$attribute, $value);
        $parser->setTarget(Target::TARGET_PROPERTY);

        try {
            $parser->parse($docblock, $context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains("[Type Error] Attribute \"$attribute\" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithAttributes declared on property SomeClassName::invalidProperty. expects a(n) $type, but got $given.", $exc->getMessage());
        }
    }


   /**
     * @dataProvider getAnnotationVarTypeArrayProviderInvalid
     */
    public function testAnnotationWithAttributesWithVarTypeArrayError($attribute,$type,$value,$given)
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $docblock   = sprintf('@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithAttributes(%s = %s)',$attribute, $value);
        $parser->setTarget(Target::TARGET_PROPERTY);

        try {
            $parser->parse($docblock, $context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains("[Type Error] Attribute \"$attribute\" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithAttributes declared on property SomeClassName::invalidProperty. expects either a(n) $type, or an array of {$type}s, but got $given.", $exc->getMessage());
        }
    }

    public function testAnnotationWithRequiredAttributes()
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $parser->setTarget(Target::TARGET_PROPERTY);


        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributes("Some Value", annot = @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation)';
        $result     = $parser->parse($docblock);

        $this->assertTrue(sizeof($result) === 1);
        $this->assertInstanceOf('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributes', $result[0]);
        $this->assertEquals("Some Value",$result[0]->getValue());
        $this->assertInstanceOf('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation', $result[0]->getAnnot());


        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributes("Some Value")';
        try {
            $result = $parser->parse($docblock,$context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains('Attribute "annot" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributes declared on property SomeClassName::invalidProperty. expects a(n) Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation. This value should not be null.', $exc->getMessage());
        }

        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributes(annot = @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation)';
        try {
            $result = $parser->parse($docblock,$context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains('Attribute "value" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributes declared on property SomeClassName::invalidProperty. expects a(n) string. This value should not be null.', $exc->getMessage());
        }

    }

    public function testAnnotationWithRequiredAttributesWithoutContructor()
    {
        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $parser->setTarget(Target::TARGET_PROPERTY);


        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributesWithoutContructor("Some Value", annot = @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation)';
        $result     = $parser->parse($docblock);

        $this->assertTrue(sizeof($result) === 1);
        $this->assertInstanceOf('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributesWithoutContructor', $result[0]);
        $this->assertEquals("Some Value", $result[0]->value);
        $this->assertInstanceOf('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation', $result[0]->annot);


        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributesWithoutContructor("Some Value")';
        try {
            $result = $parser->parse($docblock,$context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains('Attribute "annot" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributesWithoutContructor declared on property SomeClassName::invalidProperty. expects a(n) Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation. This value should not be null.', $exc->getMessage());
        }

        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributesWithoutContructor(annot = @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation)';
        try {
            $result = $parser->parse($docblock,$context);
            $this->fail();
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            $this->assertContains('Attribute "value" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithRequiredAttributesWithoutContructor declared on property SomeClassName::invalidProperty. expects a(n) string. This value should not be null.', $exc->getMessage());
        }

    }

    public function testAnnotationEnumeratorException()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('Attribute "value" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationEnum declared on property SomeClassName::invalidProperty. accept only [ONE, TWO, THREE], but got FOUR.');

        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationEnum("FOUR")';

        $parser->setIgnoreNotImportedAnnotations(false);
        $parser->setTarget(Target::TARGET_PROPERTY);
        $parser->parse($docblock, $context);
    }

    public function testAnnotationEnumeratorLiteralException()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('Attribute "value" of @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationEnumLiteral declared on property SomeClassName::invalidProperty. accept only [AnnotationEnumLiteral::ONE, AnnotationEnumLiteral::TWO, AnnotationEnumLiteral::THREE], but got 4.');

        $parser     = $this->createTestParser();
        $context    = 'property SomeClassName::invalidProperty.';
        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationEnumLiteral(4)';

        $parser->setIgnoreNotImportedAnnotations(false);
        $parser->setTarget(Target::TARGET_PROPERTY);
        $parser->parse($docblock, $context);
    }

    public function testAnnotationEnumInvalidTypeDeclarationException()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('@Enum supports only scalar values "array" given.');

        $parser     = $this->createTestParser();
        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationEnumInvalid("foo")';

        $parser->setIgnoreNotImportedAnnotations(false);
        $parser->parse($docblock);
    }

    public function testAnnotationEnumInvalidLiteralDeclarationException()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Undefined enumerator value "3" for literal "AnnotationEnumLiteral::THREE".');

        $parser     = $this->createTestParser();
        $docblock   = '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationEnumLiteralInvalid("foo")';

        $parser->setIgnoreNotImportedAnnotations(false);
        $parser->parse($docblock);
    }

    public function getConstantsProvider()
    {
        $provider[] = array(
            '@AnnotationWithConstants(PHP_EOL)',
            PHP_EOL
        );
        $provider[] = array(
            '@AnnotationWithConstants(AnnotationWithConstants::INTEGER)',
            AnnotationWithConstants::INTEGER
        );
        $provider[] = array(
            '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants(AnnotationWithConstants::STRING)',
            AnnotationWithConstants::STRING
        );
        $provider[] = array(
            '@AnnotationWithConstants(Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants::FLOAT)',
            AnnotationWithConstants::FLOAT
        );
        $provider[] = array(
            '@AnnotationWithConstants(ClassWithConstants::SOME_VALUE)',
            ClassWithConstants::SOME_VALUE
        );
        $provider[] = array(
            '@AnnotationWithConstants(ClassWithConstants::OTHER_KEY_)',
            ClassWithConstants::OTHER_KEY_
        );
        $provider[] = array(
            '@AnnotationWithConstants(ClassWithConstants::OTHER_KEY_2)',
            ClassWithConstants::OTHER_KEY_2
        );
        $provider[] = array(
            '@AnnotationWithConstants(Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithConstants::SOME_VALUE)',
            ClassWithConstants::SOME_VALUE
        );
        $provider[] = array(
            '@AnnotationWithConstants(IntefaceWithConstants::SOME_VALUE)',
            IntefaceWithConstants::SOME_VALUE
        );
        $provider[] = array(
            '@AnnotationWithConstants(\Drupal\Tests\Component\Annotation\Doctrine\Fixtures\IntefaceWithConstants::SOME_VALUE)',
            IntefaceWithConstants::SOME_VALUE
        );
        $provider[] = array(
            '@AnnotationWithConstants({AnnotationWithConstants::STRING, AnnotationWithConstants::INTEGER, AnnotationWithConstants::FLOAT})',
            array(AnnotationWithConstants::STRING, AnnotationWithConstants::INTEGER, AnnotationWithConstants::FLOAT)
        );
        $provider[] = array(
            '@AnnotationWithConstants({
                AnnotationWithConstants::STRING = AnnotationWithConstants::INTEGER
             })',
            array(AnnotationWithConstants::STRING => AnnotationWithConstants::INTEGER)
        );
        $provider[] = array(
            '@AnnotationWithConstants({
                Drupal\Tests\Component\Annotation\Doctrine\Fixtures\IntefaceWithConstants::SOME_KEY = AnnotationWithConstants::INTEGER
             })',
            array(IntefaceWithConstants::SOME_KEY => AnnotationWithConstants::INTEGER)
        );
        $provider[] = array(
            '@AnnotationWithConstants({
                \Drupal\Tests\Component\Annotation\Doctrine\Fixtures\IntefaceWithConstants::SOME_KEY = AnnotationWithConstants::INTEGER
             })',
            array(IntefaceWithConstants::SOME_KEY => AnnotationWithConstants::INTEGER)
        );
        $provider[] = array(
            '@AnnotationWithConstants({
                AnnotationWithConstants::STRING = AnnotationWithConstants::INTEGER,
                ClassWithConstants::SOME_KEY = ClassWithConstants::SOME_VALUE,
                Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithConstants::SOME_KEY = IntefaceWithConstants::SOME_VALUE
             })',
            array(
                AnnotationWithConstants::STRING => AnnotationWithConstants::INTEGER,
                ClassWithConstants::SOME_KEY    => ClassWithConstants::SOME_VALUE,
                ClassWithConstants::SOME_KEY    => IntefaceWithConstants::SOME_VALUE
            )
        );
        $provider[] = array(
            '@AnnotationWithConstants(AnnotationWithConstants::class)',
            'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants'
        );
        $provider[] = array(
            '@AnnotationWithConstants({AnnotationWithConstants::class = AnnotationWithConstants::class})',
            array('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants' => 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants')
        );
        $provider[] = array(
            '@AnnotationWithConstants(Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants::class)',
            'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants'
        );
        $provider[] = array(
            '@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants(Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants::class)',
            'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants'
        );
        return $provider;
    }

    /**
     * @dataProvider getConstantsProvider
     */
    public function testSupportClassConstants($docblock, $expected)
    {
        $parser = $this->createTestParser();
        $parser->setImports(array(
            'classwithconstants'        => 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\ClassWithConstants',
            'intefacewithconstants'     => 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\IntefaceWithConstants',
            'annotationwithconstants'   => 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants'
        ));

        $result = $parser->parse($docblock);
        $this->assertInstanceOf('\Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithConstants', $annotation = $result[0]);
        $this->assertEquals($expected, $annotation->value);
    }

    public function testWithoutConstructorWhenIsNotDefaultValue()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('The annotation @SomeAnnotationClassNameWithoutConstructorAndProperties declared on  does not accept any values, but got {"value":"Foo"}.');

        $parser     = $this->createTestParser();
        $docblock   = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructorAndProperties("Foo")
 */
DOCBLOCK;


        $parser->setTarget(Target::TARGET_CLASS);
        $parser->parse($docblock);
    }

    public function testWithoutConstructorWhenHasNoProperties()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('The annotation @SomeAnnotationClassNameWithoutConstructorAndProperties declared on  does not accept any values, but got {"value":"Foo"}.');

        $parser     = $this->createTestParser();
        $docblock   = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructorAndProperties(value = "Foo")
 */
DOCBLOCK;

        $parser->setTarget(Target::TARGET_CLASS);
        $parser->parse($docblock);
    }

    public function testAnnotationTargetSyntaxError()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('Expected namespace separator or identifier, got \')\' at position 24 in class @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithTargetSyntaxError.');

        $parser     = $this->createTestParser();
        $context    = 'class ' . 'SomeClassName';
        $docblock   = <<<DOCBLOCK
/**
 * @Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationWithTargetSyntaxError()
 */
DOCBLOCK;

        $parser->setTarget(Target::TARGET_CLASS);
        $parser->parse($docblock,$context);
    }

    public function testAnnotationWithInvalidTargetDeclarationError()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid Target "Foo". Available targets: [ALL, CLASS, METHOD, PROPERTY, ANNOTATION]');

        $parser     = $this->createTestParser();
        $context    = 'class ' . 'SomeClassName';
        $docblock   = <<<DOCBLOCK
/**
 * @AnnotationWithInvalidTargetDeclaration()
 */
DOCBLOCK;

        $parser->setTarget(Target::TARGET_CLASS);
        $parser->parse($docblock,$context);
    }

    public function testAnnotationWithTargetEmptyError()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('@Target expects either a string value, or an array of strings, "NULL" given.');

        $parser     = $this->createTestParser();
        $context    = 'class ' . 'SomeClassName';
        $docblock   = <<<DOCBLOCK
/**
 * @AnnotationWithTargetEmpty()
 */
DOCBLOCK;

        $parser->setTarget(Target::TARGET_CLASS);
        $parser->parse($docblock,$context);
    }

    /**
     * @group DDC-575
     */
    public function testRegressionDDC575()
    {
        $parser = $this->createTestParser();

        $docblock = <<<DOCBLOCK
/**
 * @Name
 *
 * Will trigger error.
 */
DOCBLOCK;

        $result = $parser->parse($docblock);

        $this->assertInstanceOf("Drupal\Tests\Component\Annotation\Doctrine\Name", $result[0]);

        $docblock = <<<DOCBLOCK
/**
 * @Name
 * @Marker
 *
 * Will trigger error.
 */
DOCBLOCK;

        $result = $parser->parse($docblock);

        $this->assertInstanceOf("Drupal\Tests\Component\Annotation\Doctrine\Name", $result[0]);
    }

    /**
     * @group DDC-77
     */
    public function testAnnotationWithoutClassIsIgnoredWithoutWarning()
    {
        $parser = new DocParser();
        $parser->setIgnoreNotImportedAnnotations(true);
        $result = $parser->parse("@param");

        $this->assertCount(0, $result);
    }

    /**
     * @group DCOM-168
     */
    public function testNotAnAnnotationClassIsIgnoredWithoutWarning()
    {
        $parser = new DocParser();
        $parser->setIgnoreNotImportedAnnotations(true);
        $parser->setIgnoredAnnotationNames(array('PHPUnit_Framework_TestCase' => true));
        $result = $parser->parse('@PHPUnit_Framework_TestCase');

        $this->assertCount(0, $result);
    }

    public function testAnnotationDontAcceptSingleQuotes()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('Expected PlainValue, got \'\'\' at position 10.');

        $parser = $this->createTestParser();
        $parser->parse("@Name(foo='bar')");
    }

    /**
     * @group DCOM-41
     */
    public function testAnnotationDoesNotThrowExceptionWhenAtSignIsNotFollowedByIdentifier()
    {
        $parser = new DocParser();
        $result = $parser->parse("'@'");

        $this->assertCount(0, $result);
    }

    /**
     * @group DCOM-41
     */
    public function testAnnotationThrowsExceptionWhenAtSignIsNotFollowedByIdentifierInNestedAnnotation()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');

        $parser = new DocParser();
        $parser->parse("@Drupal\Tests\Component\Annotation\Doctrine\Name(@')");
    }

    /**
     * @group DCOM-56
     */
    public function testAutoloadAnnotation()
    {
        $this->assertFalse(class_exists('Drupal\Tests\Component\Annotation\Doctrine\Fixture\Annotation\Autoload', false), 'Pre-condition: Drupal\Tests\Component\Annotation\Doctrine\Fixture\Annotation\Autoload not allowed to be loaded.');

        $parser = new DocParser();

        AnnotationRegistry::registerAutoloadNamespace('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation', __DIR__ . '/../../../../');

        $parser->setImports(array(
            'autoload' => 'Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation\Autoload',
        ));
        $annotations = $parser->parse('@Autoload');

        $this->assertCount(1, $annotations);
        $this->assertInstanceOf('Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation\Autoload', $annotations[0]);
    }

    public function createTestParser()
    {
        $parser = new DocParser();
        $parser->setIgnoreNotImportedAnnotations(true);
        $parser->setImports(array(
            'name' => 'Drupal\Tests\Component\Annotation\Doctrine\Name',
            '__NAMESPACE__' => 'Drupal\Tests\Component\Annotation\Doctrine',
        ));

        return $parser;
    }

    /**
     * @group DDC-78
     */
    public function testSyntaxErrorWithContextDescription()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('Expected PlainValue, got \'\'\' at position 10 in class \Drupal\Tests\Component\Annotation\Doctrine\Name');

        $parser = $this->createTestParser();
        $parser->parse("@Name(foo='bar')", "class \Drupal\Tests\Component\Annotation\Doctrine\Name");
    }

    /**
     * @group DDC-183
     */
    public function   testSyntaxErrorWithUnknownCharacters()
    {
        $docblock = <<<DOCBLOCK
/**
 * @test at.
 */
class A {
}
DOCBLOCK;

        //$lexer = new \Doctrine\Common\Annotations\Lexer();
        //$lexer->setInput(trim($docblock, '/ *'));
        //var_dump($lexer);

        try {
            $parser = $this->createTestParser();
            $result = $parser->parse($docblock);
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @group DCOM-14
     */
    public function testIgnorePHPDocThrowTag()
    {
        $docblock = <<<DOCBLOCK
/**
 * @throws \RuntimeException
 */
class A {
}
DOCBLOCK;

        try {
            $parser = $this->createTestParser();
            $result = $parser->parse($docblock);
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @group DCOM-38
     */
    public function testCastInt()
    {
        $parser = $this->createTestParser();

        $result = $parser->parse("@Name(foo=1234)");
        $annot = $result[0];
        $this->assertInternalType('int', $annot->foo);
    }

    /**
     * @group DCOM-38
     */
    public function testCastNegativeInt()
    {
        $parser = $this->createTestParser();

        $result = $parser->parse("@Name(foo=-1234)");
        $annot = $result[0];
        $this->assertInternalType('int', $annot->foo);
    }

    /**
     * @group DCOM-38
     */
    public function testCastFloat()
    {
        $parser = $this->createTestParser();

        $result = $parser->parse("@Name(foo=1234.345)");
        $annot = $result[0];
        $this->assertInternalType('float', $annot->foo);
    }

    /**
     * @group DCOM-38
     */
    public function testCastNegativeFloat()
    {
        $parser = $this->createTestParser();

        $result = $parser->parse("@Name(foo=-1234.345)");
        $annot = $result[0];
        $this->assertInternalType('float', $annot->foo);

        $result = $parser->parse("@Marker(-1234.345)");
        $annot = $result[0];
        $this->assertInternalType('float', $annot->value);
    }

    public function testReservedKeywordsInAnnotations()
    {
        if (PHP_VERSION_ID >= 70000) {
            $this->markTestSkipped('This test requires PHP 5.6 or lower.');
        }
        require 'ReservedKeywordsClasses.php';

        $parser = $this->createTestParser();

        $result = $parser->parse('@Drupal\Tests\Component\Annotation\Doctrine\True');
        $this->assertInstanceOf(True::class, $result[0]);
        $result = $parser->parse('@Drupal\Tests\Component\Annotation\Doctrine\False');
        $this->assertInstanceOf(False::class, $result[0]);
        $result = $parser->parse('@Drupal\Tests\Component\Annotation\Doctrine\Null');
        $this->assertInstanceOf(Null::class, $result[0]);

        $result = $parser->parse('@True');
        $this->assertInstanceOf(True::class, $result[0]);
        $result = $parser->parse('@False');
        $this->assertInstanceOf(False::class, $result[0]);
        $result = $parser->parse('@Null');
        $this->assertInstanceOf(Null::class, $result[0]);
    }

    public function testSetValuesException()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('[Creation Error] The annotation @SomeAnnotationClassNameWithoutConstructor declared on some class does not have a property named "invalidaProperty". Available properties: data, name');

        $docblock = <<<DOCBLOCK
/**
 * @SomeAnnotationClassNameWithoutConstructor(invalidaProperty = "Some val")
 */
DOCBLOCK;

        $this->createTestParser()->parse($docblock, 'some class');
    }

    public function testInvalidIdentifierInAnnotation()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('[Syntax Error] Expected Doctrine\Common\Annotations\DocLexer::T_IDENTIFIER or Doctrine\Common\Annotations\DocLexer::T_TRUE or Doctrine\Common\Annotations\DocLexer::T_FALSE or Doctrine\Common\Annotations\DocLexer::T_NULL, got \'3.42\' at position 5.');

        $parser = $this->createTestParser();
        $parser->parse('@Foo\3.42');
    }

    public function testTrailingCommaIsAllowed()
    {
        $parser = $this->createTestParser();

        $annots = $parser->parse('@Name({
            "Foo",
            "Bar",
        })');
        $this->assertCount(1, $annots);
        $this->assertEquals(array('Foo', 'Bar'), $annots[0]->value);
    }

    public function testDefaultAnnotationValueIsNotOverwritten()
    {
        $parser = $this->createTestParser();

        $annots = $parser->parse('@Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation\AnnotWithDefaultValue');
        $this->assertCount(1, $annots);
        $this->assertEquals('bar', $annots[0]->foo);
    }

    public function testArrayWithColon()
    {
        $parser = $this->createTestParser();

        $annots = $parser->parse('@Name({"foo": "bar"})');
        $this->assertCount(1, $annots);
        $this->assertEquals(array('foo' => 'bar'), $annots[0]->value);
    }

    public function testInvalidContantName()
    {
        $this->expectException('\Doctrine\Common\Annotations\AnnotationException');
        $this->expectExceptionMessage('[Semantical Error] Couldn\'t find constant foo.');

        $parser = $this->createTestParser();
        $parser->parse('@Name(foo: "bar")');
    }

    /**
     * Tests parsing empty arrays.
     */
    public function testEmptyArray()
    {
        $parser = $this->createTestParser();

        $annots = $parser->parse('@Name({"foo": {}})');
        $this->assertCount(1, $annots);
        $this->assertEquals(array('foo' => array()), $annots[0]->value);
    }

    public function testKeyHasNumber()
    {
        $parser = $this->createTestParser();
        $annots = $parser->parse('@SettingsAnnotation(foo="test", bar2="test")');

        $this->assertCount(1, $annots);
        $this->assertEquals(array('foo' => 'test', 'bar2' => 'test'), $annots[0]->settings);
    }

    /**
     * @group 44
     */
    public function testSupportsEscapedQuotedValues()
    {
        $result = $this->createTestParser()->parse('@Drupal\Tests\Component\Annotation\Doctrine\Name(foo="""bar""")');

        $this->assertCount(1, $result);

        $this->assertInstanceOf(Name::class, $result[0]);
        $this->assertEquals('"bar"', $result[0]->foo);
    }
}

/** @Annotation */
class SettingsAnnotation
{
    public $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }
}

/** @Annotation */
class SomeAnnotationClassNameWithoutConstructor
{
    public $data;
    public $name;
}

/** @Annotation */
class SomeAnnotationWithConstructorWithoutParams
{
    function __construct()
    {
        $this->data = "Some data";
    }
    public $data;
    public $name;
}

/** @Annotation */
class SomeAnnotationClassNameWithoutConstructorAndProperties{}

/**
 * @Annotation
 * @Target("Foo")
 */
class AnnotationWithInvalidTargetDeclaration{}

/**
 * @Annotation
 * @Target
 */
class AnnotationWithTargetEmpty{}

/** @Annotation */
class AnnotationExtendsAnnotationTargetAll extends \Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll
{
}

/** @Annotation */
class Name extends \Doctrine\Common\Annotations\Annotation {
    public $foo;
}

/** @Annotation */
class Marker {
    public $value;
}

namespace Drupal\Tests\Component\Annotation\Doctrine\FooBar;

/** @Annotation */
class Name extends \Doctrine\Common\Annotations\Annotation {
}
