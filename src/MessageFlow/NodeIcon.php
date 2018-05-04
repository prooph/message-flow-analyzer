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

final class NodeIcon
{
    public const FA_SOLID = 'fas';
    public const FA_REGULAR = 'far';
    public const FA_BRAND = 'fab';
    public const LINK = 'link';

    public const TYPES = [
        self::FA_SOLID,
        self::FA_REGULAR,
        self::FA_BRAND,
        self::LINK,
    ];

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $icon;

    public static function faSolid(string $icon): self
    {
        return new self(self::FA_SOLID, $icon);
    }

    public static function faRegular(string  $icon): self
    {
        return new self(self::FA_REGULAR, $icon);
    }

    public static function faBrand(string $icon): self
    {
        return new self(self::FA_BRAND, $icon);
    }

    public static function link(string $link): self
    {
        return new self(self::LINK, $link);
    }

    public static function fromString(string $icon): self
    {
        [$type, $icon] = explode(' ', $icon);

        return new self($type, $icon);
    }

    private function __construct(string $type, string $icon)
    {
        if (! in_array($type, self::TYPES)) {
            throw new \InvalidArgumentException('Invalid icon type given. Should be one of ' . implode(', ', self::TYPES) . ". Got $type");
        }

        if ($icon === '') {
            throw new \InvalidArgumentException('Icon should not be an empty string');
        }

        $this->type = $type;
        $this->icon = $icon;
    }

    public function __toString()
    {
        return $this->type . ' ' . $this->icon;
    }
}
