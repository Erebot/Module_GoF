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

if (!defined('__DIR__')) {
  class __FILE_CLASS__ {
    function  __toString() {
      $X = debug_backtrace();
      return dirname($X[1]['file']);
    }
  }
  define('__DIR__', new __FILE_CLASS__);
} 

include_once(__DIR__.'/../src/exceptions.php');
include_once(__DIR__.'/../src/card.php');

class   GoFCardTest
extends PHPUnit_Framework_TestCase
{
    public function testRejectInvalidCards()
    {
        $cards = array(
            0,
            'r',
            'g0',
            'y0',
            'r0',
            '0',
            'rg',
            'x0',
            'xd',
            'xy',
            'gd',
            'yd',
            'rp',
            'm0',
            'm2',
            'm3',
            'm4',
            'm5',
            'm6',
            'm7',
            'm8',
            'm9',
            'mp',
            'md',
        );
        foreach ($cards as $card) {
            try {
                new GoFCard($card);
                $this->fail("Expected an EGoFInvalidCard exception.");
            }
            catch (EGoFInvalidCard $e) {
                // Okay.
            }
        }
    }

    public function validCardsProvider()
    {
        return array(
            // Red serie
            array('r1', GoFCard::COLOR_RED, GoFCard::VALUE_1),
            array('r10', GoFCard::COLOR_RED, GoFCard::VALUE_10),
            array('rd', GoFCard::COLOR_RED, GoFCard::VALUE_DRAGON),
            // Green serie
            array('g1', GoFCard::COLOR_GREEN, GoFCard::VALUE_1),
            array('g10', GoFCard::COLOR_GREEN, GoFCard::VALUE_10),
            array('gp', GoFCard::COLOR_GREEN, GoFCard::VALUE_PHOENIX),
            // Yellow serie
            array('y1', GoFCard::COLOR_YELLOW, GoFCard::VALUE_1),
            array('y10', GoFCard::COLOR_YELLOW, GoFCard::VALUE_10),
            array('yp', GoFCard::COLOR_YELLOW, GoFCard::VALUE_PHOENIX),
            // Multicolor serie
            array('m1', GoFCard::COLOR_MULTI, GoFCard::VALUE_1),
        );
    }

    /**
     * @dataProvider validCardsProvider
     */
    public function testAcceptValidCards($label, $color, $value)
    {
        $card = new GoFCard($label);
        $this->assertEquals($color, $card->getColor());
        $this->assertEquals($value, $card->getValue());
        $this->assertEquals($label, (string) $card);
    }
}

