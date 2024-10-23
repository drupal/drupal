<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Ticket;

use Drupal\Component\Annotation\Doctrine\DocParser;
use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use PHPUnit\Framework\TestCase;

/**
 * This class is a near-copy of
 * \Doctrine\Tests\Common\Annotations\Ticket\DCOM58Test, which is part of the
 * Doctrine project: <http://www.doctrine-project.org>.  It was copied from
 * version 1.2.7.
 *
 * Run this test in a separate process as it includes code that might have side
 * effects.
 *
 * @group DCOM58
 * @runTestsInSeparateProcesses
 */
class DCOM58Test extends TestCase
{
    protected function setUp(): void
    {
        // Some class named Entity in the global namespace.
        include __DIR__ .'/DCOM58Entity.php';
    }

    public function testIssueGlobalNamespace(): void
    {
        $docblock   = "@Entity";
        $parser     = new DocParser();
        $parser->setImports(array(
            "__NAMESPACE__" =>"Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Mapping"
        ));

        $annots     = $parser->parse($docblock);

        $this->assertCount(1, $annots);
        $this->assertInstanceOf("Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Mapping\Entity", $annots[0]);
    }

    public function testIssueNamespaces(): void
    {
        $docblock   = "@Entity";
        $parser     = new DocParser();
        $parser->addNamespace("Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM");

        $annots     = $parser->parse($docblock);

        $this->assertCount(1, $annots);
        $this->assertInstanceOf("Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Entity", $annots[0]);
    }

    public function testIssueMultipleNamespaces(): void
    {
        $docblock   = "@Entity";
        $parser     = new DocParser();
        $parser->addNamespace("Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Mapping");
        $parser->addNamespace("Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM");

        $annots     = $parser->parse($docblock);

        $this->assertCount(1, $annots);
        $this->assertInstanceOf("Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Mapping\Entity", $annots[0]);
    }

    public function testIssueWithNamespacesOrImports(): void
    {
        $docblock   = "@Entity";
        $parser     = new DocParser();
        $annots     = $parser->parse($docblock);

        $this->assertCount(1, $annots);
        $this->assertInstanceOf("Entity", $annots[0]);
        $this->assertCount(1, $annots);
    }


    public function testIssueSimpleAnnotationReader(): void
    {
        $reader     = new SimpleAnnotationReader();
        $reader->addNamespace('Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Mapping');
        $annots     = $reader->getClassAnnotations(new \ReflectionClass(__NAMESPACE__."\MappedClass"));

        $this->assertCount(1, $annots);
        $this->assertInstanceOf("Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Mapping\Entity", $annots[0]);
    }

}

/**
 * @Entity
 */
class MappedClass
{

}


namespace Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM\Mapping;
/**
* @Annotation
*/
class Entity
{

}

namespace Drupal\Tests\Component\Annotation\Doctrine\Ticket\Doctrine\ORM;
/**
* @Annotation
*/
class Entity
{

}
