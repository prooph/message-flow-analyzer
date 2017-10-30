<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Filter;

use Prooph\MessageFlowAnalyzer\Filter\ExcludeVendorDir;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;

class ExcludeVendorDirTest extends BaseTestCase
{
    /**
     * @var ExcludeVendorDir
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new ExcludeVendorDir();
    }

    /**
     * @test
     */
    public function it_excludes_vendor_dir()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $vendorDir = new \SplFileInfo($msgFlow->rootDir() . '/vendor');

        $this->assertTrue($vendorDir->isDir());

        $this->assertFalse($this->cut->accept($vendorDir, $msgFlow->rootDir()));
    }

    /**
     * @test
     */
    public function it_includes_files()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $registerUser = new \SplFileInfo($msgFlow->rootDir() . '/Model/User.php');

        $this->assertTrue($this->cut->accept($registerUser, $msgFlow->rootDir()));
    }

    /**
     * @test
     */
    public function it_includes_non_vendor_dir()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $modelDir = new \SplFileInfo($msgFlow->rootDir() . '/Model');

        $this->assertTrue($this->cut->accept($modelDir, $msgFlow->rootDir()));
    }
}
