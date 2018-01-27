<?php

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Content Translation bundle info logic.
 *
 * @group content_translation
 */
class ContentTranslationEntityBundleInfoTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'language', 'a_content_translation_test', 'content_translation', 'entity_test'];

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->contentTranslationManager = $this->container->get('content_translation.manager');
    $this->bundleInfo = $this->container->get('entity_type.bundle.info');

    $this->installEntitySchema('entity_test_mul');
  }

  /**
   * Tests that modules can know whether bundles are translatable.
   */
  public function testHookInvocationOrder() {
    $this->contentTranslationManager->setEnabled('entity_test_mul', 'entity_test_mul', TRUE);
    $this->bundleInfo->clearCachedBundles();
    $this->bundleInfo->getAllBundleInfo();

    // Check that, although the "a_content_translation_test" hook implementation
    // by default would be invoked first, it still has access to the
    // "translatable" bundle info property.
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    $this->assertTrue($state->get('a_content_translation_test.translatable'));
  }

}
