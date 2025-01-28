<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Http;

use Drupal\Core\Http\LinkRelationType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests link relationships in Drupal.
 *
 * @group HTTP
 */
class LinkRelationsTest extends KernelTestBase {

  /**
   * Tests the Link Relations returned from the Link Relation Type Manager.
   */
  public function testAvailableLinkRelationships(): void {
    /** @var \Drupal\Core\Http\LinkRelationTypeManager $link_relation_type_manager */
    $link_relation_type_manager = $this->container->get('plugin.manager.link_relation_type');

    // A link relation type of the "registered" kind.
    /** @var \Drupal\Core\Http\LinkRelationTypeInterface $canonical */
    $canonical = $link_relation_type_manager->createInstance('canonical');
    $this->assertInstanceOf(LinkRelationType::class, $canonical);
    $this->assertTrue($canonical->isRegistered());
    $this->assertFalse($canonical->isExtension());
    $this->assertSame('canonical', $canonical->getRegisteredName());
    $this->assertNull($canonical->getExtensionUri());
    $this->assertEquals('[RFC6596]', $canonical->getReference());
    $this->assertEquals('Designates the preferred version of a resource (the IRI and its contents).', $canonical->getDescription());
    $this->assertEquals('', $canonical->getNotes());

    // A link relation type of the "extension" kind.
    /** @var \Drupal\Core\Http\LinkRelationTypeInterface $canonical */
    $add_form = $link_relation_type_manager->createInstance('add-form');
    $this->assertInstanceOf(LinkRelationType::class, $add_form);
    $this->assertFalse($add_form->isRegistered());
    $this->assertTrue($add_form->isExtension());
    $this->assertNull($add_form->getRegisteredName());
    $this->assertSame('https://drupal.org/link-relations/add-form', $add_form->getExtensionUri());
    $this->assertEquals('', $add_form->getReference());
    $this->assertEquals('A form where a resource of this type can be created.', $add_form->getDescription());
    $this->assertEquals('', $add_form->getNotes());

    // Test a couple of examples.
    $this->assertContains('about', array_keys($link_relation_type_manager->getDefinitions()));
    $this->assertContains('original', array_keys($link_relation_type_manager->getDefinitions()));
    $this->assertContains('type', array_keys($link_relation_type_manager->getDefinitions()));
  }

}
