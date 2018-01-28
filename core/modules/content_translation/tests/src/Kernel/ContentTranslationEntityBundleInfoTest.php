<?php

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Content Translation bundle info logic.
 *
 * @group content_translation
 */
class ContentTranslationEntityBundleInfoTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'language', 'content_translation_test', 'content_translation', 'entity_test'];

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

    ConfigurableLanguage::createFromLangcode('it')->save();
  }

  /**
   * Tests that modules can know whether bundles are translatable.
   */
  public function testHookInvocationOrder() {
    $this->contentTranslationManager->setEnabled('entity_test_mul', 'entity_test_mul', TRUE);
    $this->bundleInfo->clearCachedBundles();
    $this->bundleInfo->getAllBundleInfo();

    // Verify that the test module comes first in the module list, which would
    // normally make its hook implementation to be invoked first.
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $module_list = $module_handler->getModuleList();
    $expected_modules = [
      'content_translation_test',
      'content_translation',
    ];
    $actual_modules = array_keys(array_intersect_key($module_list, array_flip($expected_modules)));
    $this->assertEquals($expected_modules, $actual_modules);

    // Check that the "content_translation_test" hook implementation has access
    // to the "translatable" bundle info property.
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    $this->assertTrue($state->get('content_translation_test.translatable'));
  }

  /**
   * Tests that field synchronization is skipped for disabled bundles.
   */
  public function testFieldSynchronizationWithDisabledBundle() {
    $entity = EntityTestMul::create();
    $entity->save();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
    $translation = $entity->addTranslation('it');
    $translation->save();

    $this->assertTrue($entity->isTranslatable());
  }

}
