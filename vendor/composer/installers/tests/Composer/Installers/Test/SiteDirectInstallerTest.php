<?php

namespace Composer\Installers\Test;

use Composer\Composer;
use Composer\Installers\SiteDirectInstaller;
use Composer\Package\Package;

class SiteDirectInstallerTest extends TestCase
{
    /** @var SiteDirectInstaller $installer */
    protected $installer;

    /** @var Package */
    private $package;

    public function SetUp()
    {
        $this->package = new Package('sitedirect/some_name', '1.0.9', '1.0');
        $this->installer = new SiteDirectInstaller(
            $this->package,
            new Composer()
        );

    }

    /**
     * @dataProvider dataProvider
     */
    public function testInflectPackageVars($data, $expected)
    {
        $result = $this->installer->inflectPackageVars($data);
        $this->assertEquals($result, $expected);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testInstallPath($data, $expected)
    {
        $result = $this->installer->inflectPackageVars($data);
        $path = $this->createPackage($data);

        // use $result to get the proper capitalization for the vendor path
        $expectedPath = "modules/{$result['vendor']}/{$result['name']}/";
        $notExpectedPath = "modules/{$data['vendor']}/{$data['name']}/";
        $this->assertEquals($expectedPath, $path);
        $this->assertNotEquals($notExpectedPath, $path);
    }

    /**
     * @param $data
     * @return string
     */
    private function createPackage($data)
    {
        $fullName = "{$data['vendor']}/{$data['name']}";

        $package = new Package($fullName, '1.0', '1.0');
        $package->setType('sitedirect-module');
        $installer = new SiteDirectInstaller($package, new Composer());

        $path = $installer->getInstallPath($package, 'sitedirect');
        return $path;
    }

    public function dataProvider()
    {
        return array(
            array(
                'data' => array(
                    'name' => 'kernel',
                    'vendor' => 'sitedirect',
                    'type' => 'sitedirect-module',
                ),
                'expected' => array(
                    'name' => 'Kernel',
                    'vendor' => 'SiteDirect',
                    'type' => 'sitedirect-module',
                )
            ),
            array(
                'data' => array(
                    'name' => 'that_guy',
                    'vendor' => 'whatGuy',
                    'type' => 'sitedirect-module',
                ),
                'expected' => array(
                    'name' => 'ThatGuy',
                    'vendor' => 'whatGuy',
                    'type' => 'sitedirect-module',
                )
            ),
            array(
                'data' => array(
                    'name' => 'checkout',
                    'vendor' => 'someVendor',
                    'type' => 'sitedirect-plugin',
                ),
                'expected' => array(
                    'name' => 'Checkout',
                    'vendor' => 'someVendor',
                    'type' => 'sitedirect-plugin',
                )
            ),
            array(
                'data' => array(
                    'name' => 'checkout',
                    'vendor' => 'siteDirect',
                    'type' => 'sitedirect-plugin',
                ),
                'expected' => array(
                    'name' => 'Checkout',
                    'vendor' => 'SiteDirect',
                    'type' => 'sitedirect-plugin',
                )
            ),
        );
    }
}
