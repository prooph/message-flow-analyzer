<?php

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Filter;

use Prooph\MessageFlowAnalyzer\Filter\ExcludeHiddenFileInfo;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;

class ExcludeHiddenFileInfoTest extends BaseTestCase
{
    /**
     * @var ExcludeHiddenFileInfo
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new ExcludeHiddenFileInfo();
    }

    /**
     * @test
     */
    public function it_excludes_hidden_files()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $hiddenFile = new \SplFileInfo($msgFlow->rootDir() . '/.php_cs');

        $this->assertFalse($this->cut->accept($hiddenFile, $msgFlow->rootDir()));
    }

    /**
     * @test
     */
    public function it_excludes_hidden_directories()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $hiddenDir = new \SplFileInfo($msgFlow->rootDir() . '/.cache');

        $this->assertFalse($this->cut->accept($hiddenDir, $msgFlow->rootDir()));
    }

    /**
     * @test
     * @dataProvider provideValidFileInfo
     */
    public function it_includes_normal_files_and_dirs(\SplFileInfo $info, MessageFlow $msgFlow)
    {
        $this->assertTrue($this->cut->accept($info, $msgFlow->rootDir()));
    }

    public function provideValidFileInfo(): array
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        return [
            [
                new \SplFileInfo($msgFlow->rootDir() . '/Model'),
                $msgFlow,
            ],
            [
                new \SplFileInfo($msgFlow->rootDir() . '/Model/User.php'),
                $msgFlow,
            ],
        ];
    }
}
