<?php

/**
 * This file is part of the prooph/message-flow-analyzer.
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

namespace Prooph\MessageFlowAnalyzer\Helper\PhpParser;

use PhpParser\Node;
use PhpParser\NodeTraverser;

final class MessageScanningNodeTraverser
{
    /**
     * @var MessageScanner
     */
    private $messageScanner;

    /**
     * @var NodeTraverser
     */
    private $nodeTraverser;

    public function __construct(NodeTraverser $nodeTraverser, MessageScanner $messageScanner)
    {
        $this->messageScanner = $messageScanner;
        $this->nodeTraverser = $nodeTraverser;
        $this->nodeTraverser->addVisitor($this->messageScanner);
    }

    /**
     * Traverses an array of nodes using the registered visitors.
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[] Traversed array of nodes
     */
    public function traverse(array $nodes): array
    {
        return $this->nodeTraverser->traverse($nodes);
    }

    public function messageScanner(): MessageScanner
    {
        return $this->messageScanner;
    }
}
