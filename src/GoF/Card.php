<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Erebot\Module\GoF;

class Card
{
    protected $value;
    protected $color;

    const VALUE_1       = 1;
    const VALUE_2       = 2;
    const VALUE_3       = 3;
    const VALUE_4       = 4;
    const VALUE_5       = 5;
    const VALUE_6       = 6;
    const VALUE_7       = 7;
    const VALUE_8       = 8;
    const VALUE_9       = 9;
    const VALUE_10      = 10;
    const VALUE_PHOENIX = 11;
    const VALUE_DRAGON  = 12;

    const COLOR_GREEN   = 1;
    const COLOR_YELLOW  = 2;
    const COLOR_RED     = 3;
    const COLOR_MULTI   = 4;

    public function __construct($color, $value)
    {
        if (!is_int($color) || $color < self::COLOR_GREEN || $color > self::COLOR_MULTI) {
            throw new \Erebot\Module\GoF\InvalidCardException("Invalid color");
        }

        if (!is_int($value) || $value < self::VALUE_1 || $value > self::VALUE_DRAGON) {
            throw new \Erebot\Module\GoF\InvalidCardException("Invalid value");
        }

        // Allow only m1 as a multicolor card.
        if ($color == self::COLOR_MULTI && $value != self::VALUE_1) {
            throw new \Erebot\Module\GoF\InvalidCardException("Invalid multicolor");
        }

        // Allow only yp & gp as phoenixes.
        if ($value == self::VALUE_PHOENIX && $color != self::COLOR_GREEN && $color != self::COLOR_YELLOW) {
            throw new \Erebot\Module\GoF\InvalidCardException("Invalid phoenix");
        }

        // Allow only rd as a dragon.
        if ($value == self::VALUE_DRAGON && $color != self::COLOR_RED) {
            throw new \Erebot\Module\GoF\InvalidCardException("Invalid dragon");
        }

        $this->color = $color;
        $this->value = $value;

        $colors = array(
            self::COLOR_GREEN   => 'g',
            self::COLOR_YELLOW  => 'y',
            self::COLOR_RED     => 'r',
            self::COLOR_MULTI   => 'm',
        );

        $color = $colors[$this->color];
        $value = $this->value;
        switch ($value) {
            case self::VALUE_DRAGON:
                $value = 'd';
                break;
            case self::VALUE_PHOENIX:
                $value = 'p';
                break;
        }
        $this->label = $color.$value;
    }

    protected static function parseCard($card)
    {
        if (!is_string($card)) {
            throw new \Erebot\Module\GoF\InvalidCardException($card);
        }

        $colors = array(
            'g' => self::COLOR_GREEN,
            'y' => self::COLOR_YELLOW,
            'r' => self::COLOR_RED,
        );

        $card = strtolower($card);
        switch ($card) {
            case 'm1':
                return array(self::VALUE_1, self::COLOR_MULTI);
            case 'rd':
                return array(self::VALUE_DRAGON, self::COLOR_RED);
            case 'yp':
            case 'gp':
                return array(self::VALUE_PHOENIX, $colors[$card[0]]);
        }

        if (!in_array($card[0], array('g', 'y', 'r'))) {
            throw new \Erebot\Module\GoF\InvalidCardException($card);
        }

        if (!ctype_digit(substr($card, 1))) {
            throw new \Erebot\Module\GoF\InvalidCardException($card);
        }

        $value = (int) substr($card, 1);
        if ($value >= 1 && $value <= 10) {
            return array($value, $colors[$card[0]]);
        }

        throw new \Erebot\Module\GoF\InvalidCardException($card);
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->label;
    }

    public static function fromLabel($card)
    {
        list($value, $color) = self::parseCard($card);
        return new static($color, $value);
    }

    public static function compareCards(
        \Erebot\Module\GoF\Card $cardA,
        \Erebot\Module\GoF\Card $cardB
    ) {
        if ($cardA->value != $cardB->value) {
            return $cardA->value - $cardB->value;
        }
        return $cardA->color - $cardB->color;
    }
}
