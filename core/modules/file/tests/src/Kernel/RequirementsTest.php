<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests the file requirements.
 *
 * @group file
 */
class RequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * Tests the file upload requirements.
   */
  public function testUploadRequirements(): void {
    if (\extension_loaded('uploadprogress')) {
      $this->markTestSkipped('We are testing only when the uploadprogress extension is not loaded.');
    }

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    $moduleHandler = $this->container->get('module_handler');
    $moduleHandler->loadInclude('file', 'install');

    // Test unspecified server software.
    $this->setServerSoftware(NULL);
    $requirements = \file_requirements('runtime');
    $this->assertNotEmpty($requirements);

    $this->assertEquals('Upload progress', (string) $requirements['file_progress']['title']);
    $this->assertEquals('Not enabled', (string) $requirements['file_progress']['value']);
    $this->assertEquals('Your server is not capable of displaying file upload progress. File upload progress requires an Apache server running PHP with mod_php or Nginx with PHP-FPM.', (string) $requirements['file_progress']['description']);

    // Test Apache + mod_php.
    $this->setServerSoftware('Apache mod_php');
    $requirements = \file_requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertEquals('Not enabled', (string) $requirements['file_progress']['value']);
    $this->assertEquals('Your server is capable of displaying file upload progress, but does not have the required libraries. It is recommended to install the <a href="http://pecl.php.net/package/uploadprogress">PECL uploadprogress library</a>.', (string) $requirements['file_progress']['description']);

    // Test Apache + mod_fastcgi.
    $this->setServerSoftware('Apache mod_fastcgi');
    $requirements = \file_requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertEquals('Not enabled', (string) $requirements['file_progress']['value']);
    $this->assertEquals('Your server is not capable of displaying file upload progress. File upload progress requires PHP be run with mod_php or PHP-FPM and not as FastCGI.', (string) $requirements['file_progress']['description']);

    // Test Nginx.
    $this->setServerSoftware('Nginx');
    $requirements = \file_requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertEquals('Not enabled', (string) $requirements['file_progress']['value']);
    $this->assertEquals('Your server is capable of displaying file upload progress, but does not have the required libraries. It is recommended to install the <a href="http://pecl.php.net/package/uploadprogress">PECL uploadprogress library</a>.', (string) $requirements['file_progress']['description']);

  }

  /**
   * Sets the server software attribute in the request.
   */
  private function setServerSoftware(?string $software): void {
    $request = new Request();
    $request->setSession(new Session(new MockArraySessionStorage()));
    if (is_string($software)) {
      $request->server->set('SERVER_SOFTWARE', $software);
    }
    $requestStack = new RequestStack();
    $requestStack->push($request);
    $this->container->set('request_stack', $requestStack);
  }

}
