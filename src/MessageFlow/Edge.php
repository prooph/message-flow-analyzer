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

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

class Edge
{
    /**
     * @var string
     */
    private $sourceNodeId;

    /**
     * @var string
     */
    private $targetNodeId;

    public function __construct(string $sourceNodeId, string $targetNodeId)
    {
        $this->sourceNodeId = $sourceNodeId;
        $this->targetNodeId = $targetNodeId;
    }

    public function id(): string
    {
        return $this->sourceNodeId . '_' . $this->targetNodeId;
    }

    /**
     * @return string
     */
    public function sourceNodeId(): string
    {
        return $this->sourceNodeId;
    }

    /**
     * @return string
     */
    public function targetNodeId(): string
    {
        return $this->targetNodeId;
    }

    public function toArray(): array
    {
        return [
            'data' => [
                'id' => $this->id(),
                'source' => $this->sourceNodeId,
                'target' => $this->targetNodeId,
            ],
        ];
    }
}
