<?php
namespace Composer\Installers\Test;

use Composer\Installers\MayaInstaller;
use Composer\Package\Package;
use Composer\Composer;
use PHPUnit\Framework\TestCase as BaseTestCase;

class MayaInstallerTest extends BaseTestCase
{
    /**
     * @var MayaInstaller
     */
    private $installer;

    public function setUp()
    {
        $this->installer = new MayaInstaller(
            new Package('NyanCat', '4.2', '4.2'),
            new Composer()
        );
    }

    /**
     * @dataProvider packageNameInflectionProvider
     */
    public function testInflectPackageVars($type, $name, $expected)
    {
        $this->assertEquals(
            array('name' => $expected, 'type' => $type),
            $this->installer->inflectPackageVars(array('name' => $name, 'type' => $type))
        );
    }

    public function packageNameInflectionProvider()
    {
        return array(
            // Should keep module name StudlyCase
            array(
                'maya-module',
                'user-profile',
                'UserProfile'
            ),
            array(
                'maya-module',
                'maya-module',
                'Maya'
            ),
            array(
                'maya-module',
                'blog',
                'Blog'
            ),
            // tests that exactly one '-module' is cut off
            array(
                'maya-module',
                'some-module-module',
                'SomeModule',
            ),
        );
    }
}
