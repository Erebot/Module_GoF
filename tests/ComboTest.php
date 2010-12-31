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

require_once(
    dirname(__FILE__) .
    DIRECTORY_SEPARATOR . 'testenv' .
    DIRECTORY_SEPARATOR . 'bootstrap.php'
);

class   Erebot_Module_GoF_ComboTest
extends PHPUnit_Framework_TestCase
{
    public function testAllSinglesDuosTrios()
    {
        // Tests all singles, duos & trios except for a few special cards
        // (m1, gp, yp, rd) which are specially dealt with below.
        $colors = array('g', 'y', 'r');
        for ($value = 1; $value <= 10; $value++) {
            for ($id = 0; $id < 27; $id++) {
                $colorA = $colors[(int) ($id / 9)];
                $colorB = $colors[(int) (($id % 9) / 3)];
                $colorC = $colors[$id % 3];

                $combo = new Erebot_Module_GoF_Combo(
                    Erebot_Module_GoF_Card::fromLabel($colorA.$value),
                    Erebot_Module_GoF_Card::fromLabel($colorB.$value),
                    Erebot_Module_GoF_Card::fromLabel($colorC.$value)
                );
                $this->assertEquals(
                    Erebot_Module_GoF_Combo::COMBO_TRIO,
                    $combo->getType(),
                    print_r(array(
                        $colorA.$value,
                        $colorB.$value,
                        $colorC.$value
                    ), TRUE)
                );

                if (!($id % 3)) {
                    $combo = new Erebot_Module_GoF_Combo(
                        Erebot_Module_GoF_Card::fromLabel($colorA.$value),
                        Erebot_Module_GoF_Card::fromLabel($colorB.$value)
                    );
                    $this->assertEquals(
                        Erebot_Module_GoF_Combo::COMBO_PAIR,
                        $combo->getType(),
                        print_r(array(
                            $colorA.$value,
                            $colorB.$value
                        ), TRUE)
                    );
                }

                if (!($id % 9)) {
                    $combo = new Erebot_Module_GoF_Combo(
                        Erebot_Module_GoF_Card::fromLabel($colorA.$value)
                    );
                    $this->assertEquals(
                        Erebot_Module_GoF_Combo::COMBO_SINGLE,
                        $combo->getType(),
                        print_r(array($colorA.$value), TRUE)
                    );
                }
            }
        }

        // Special singles.
        foreach (array('m1', 'gp', 'yp', 'rd') as $label) {
            $combo = new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel($label)
            );
            $this->assertEquals(
                Erebot_Module_GoF_Combo::COMBO_SINGLE,
                $combo->getType(),
                print_r(array($label), TRUE)
            );
        }

        // Special pairs (those with m1 in them and the phoenix pair).
        $pairs = array(
            array('gp', 'yp'),
            array('m1', 'g1'),
            array('m1', 'y1'),
            array('m1', 'r1'),
        );
        foreach ($pairs as $pair) {
            $combo = new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel($pair[0]),
                Erebot_Module_GoF_Card::fromLabel($pair[1])
            );
            $this->assertEquals(
                Erebot_Module_GoF_Combo::COMBO_PAIR,
                $combo->getType(),
                print_r(array($pair[0], $pair[1]), TRUE)
            );
        }

        // Special trios (those with m1 in them).
        foreach ($colors as $colorA)
            foreach ($colors as $colorB) {
                $combo = new Erebot_Module_GoF_Combo(
                    Erebot_Module_GoF_Card::fromLabel('m1'),
                    Erebot_Module_GoF_Card::fromLabel($colorA.'1'),
                    Erebot_Module_GoF_Card::fromLabel($colorB.'1')
                );
                $this->assertEquals(
                    Erebot_Module_GoF_Combo::COMBO_TRIO,
                    $combo->getType(),
                    print_r(array('m1', $colorA.'1', $colorB.'1'), TRUE)
                );
            }
    }

    public function testAllStraights()
    {
        // This tests all valid straights, including straight flushes.
        $colors = array('g', 'y', 'r', 'm');
        for ($base = 1; $base <= 6; $base++) {
            $max = 3 * 3 * 3 * 3 * ($base == 1 ? 4 : 3);
            for ($id = 0; $id < $max; $id++) {
                $colorA = $colors[(int) ($id        / 81)];
                $colorB = $colors[(int) (($id % 81) / 27)];
                $colorC = $colors[(int) (($id % 27) /  9)];
                $colorD = $colors[(int) (($id %  9) /  3)];
                $colorE = $colors[$id % 3];

                $combo = new Erebot_Module_GoF_Combo(
                    Erebot_Module_GoF_Card::fromLabel($colorA.($base + 0)),
                    Erebot_Module_GoF_Card::fromLabel($colorB.($base + 1)),
                    Erebot_Module_GoF_Card::fromLabel($colorC.($base + 2)),
                    Erebot_Module_GoF_Card::fromLabel($colorD.($base + 3)),
                    Erebot_Module_GoF_Card::fromLabel($colorE.($base + 4))
                );
                // If all five cards are of the same color,
                // this is a straight flush.
                // Otherwise, it's an ordinary straight.
                $type = (
                        $colorA == $colorB &&
                        $colorB == $colorC &&
                        $colorC == $colorD &&
                        $colorD == $colorE)
                    ? Erebot_Module_GoF_Combo::COMBO_STRAIGHT_FLUSH
                    : Erebot_Module_GoF_Combo::COMBO_STRAIGHT;
                $this->assertEquals(
                    $type,
                    $combo->getType(),
                    print_r(array(
                        $colorA.($base + 0),
                        $colorB.($base + 1),
                        $colorC.($base + 2),
                        $colorD.($base + 3),
                        $colorE.($base + 4)
                    ), TRUE)." with type ".$type
                );
            }
        }
    }

    /**
     * @expectedException Erebot_Module_GoF_InvalidComboException
     */
    public function testDoublePair()
    {
        new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2')
        );
    }

    /**
     * @expectedException Erebot_Module_GoF_InvalidComboException
     */
    public function testAPairAndASingle()
    {
        new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g2')
        );
    }

    /**
     * @expectedException Erebot_Module_GoF_InvalidComboException
     */
    public function testTwoSingles()
    {
        new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g2')
        );
    }

    /**
     * @expectedException Erebot_Module_GoF_InvalidComboException
     */
    public function testThreeSingles()
    {
        new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g3')
        );
    }

    /**
     * @expectedException Erebot_Module_GoF_InvalidComboException
     */
    public function testFourSingles()
    {
        new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g3'),
            Erebot_Module_GoF_Card::fromLabel('g4')
        );
    }

    public function testFullHouse()
    {
        $combo = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('y2')
        );
        $this->assertEquals(
            Erebot_Module_GoF_Combo::COMBO_FULL_HOUSE,
            $combo->getType()
        );

        // Force a swap.
        $combo = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('y1'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2')
        );
        $this->assertEquals(
            Erebot_Module_GoF_Combo::COMBO_FULL_HOUSE,
            $combo->getType()
        );
    }
}

