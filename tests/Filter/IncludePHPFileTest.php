<?php

/**
 * This file is part of prooph/message-flow-analyzer.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Filter;

use Prooph\MessageFlowAnalyzer\Filter\IncludePHPFile;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;

class IncludePHPFileTest extends BaseTestCase
{
    /**
     * @var IncludePHPFile
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new IncludePHPFile();
    }

    /**
     * @test
     */
    public function it_includes_php_file()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $phpFile = new \SplFileInfo($msgFlow->rootDir() . '/Model/User.php');

        $this->assertTrue($this->cut->accept($phpFile, $msgFlow->rootDir()));
    }

    /**
     * @test
     */
    public function it_includes_directories()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $modelDir = new \SplFileInfo($msgFlow->rootDir() . '/Model');

        $this->assertTrue($this->cut->accept($modelDir, $msgFlow->rootDir()));
    }

    /**
     * @test
     */
    public function it_excludes_non_php_file()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $schemaFile = new \SplFileInfo($msgFlow->rootDir() . '/Model/User/Command/schema/RegisterUser.json');

        $this->assertFalse($this->cut->accept($schemaFile, $msgFlow->rootDir()));
    }
}
