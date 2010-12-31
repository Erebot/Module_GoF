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

    public function invalidCombos()
    {
        return array(
            // Too many cards
            array('g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7'),

            // Still too much
            array('g1', 'g2', 'g3', 'g4', 'g5', 'g6'),

            // Double-pair
            array('g1', 'g1', 'g2', 'g2'),

            // A pair and a single
            array('g1', 'g1', 'g2'),

            // Two singles
            array('g1', 'g2'),

            // Three singles
            array('g1', 'g2', 'g3'),

            // Four singles
            array('g1', 'g2', 'g3', 'g4'),

            // Some random WTF
            array('g1', 'g2', 'g3', 'g5', 'r6'),
        );
    }

    /**
     * @dataProvider invalidCombos
     * @expectedException Erebot_Module_GoF_InvalidComboException
     */
    public function testInvalidCombos()
    {
        $args = func_get_args();
        $reflector = new ReflectionClass('Erebot_Module_GoF_Combo');
        $cards = array_map(array('Erebot_Module_GoF_Card', 'fromLabel'), $args);
        $reflector->newInstanceArgs($cards);
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

    public function testInterfaces()
    {
        $combo = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g5'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g4'),
            Erebot_Module_GoF_Card::fromLabel('g3'),
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        // Failsafe test.
        $this->assertEquals(
            Erebot_Module_GoF_Combo::COMBO_STRAIGHT_FLUSH,
            $combo->getType()
        );
        $expected = array('g5', 'g4', 'g3', 'g2', 'g1');

        // Iterator interface.
        foreach ($combo as $key => $value) {
            $this->assertTrue(isset($expected[$key]));
            $this->assertEquals($expected[$key], $value->getLabel());
        }

        // ArrayAccess interface.
        for ($i = 0; $i < 5; $i++) {
            // offsetExists
            $this->assertTrue(isset($combo[$i]));
            // offsetGet
            $this->assertEquals($expected[$i], $combo[$i]->getLabel());

            try {
                // offsetSet
                $combo[$i] = 42;
            }
            catch (Exception $e) {
            }

            try {
                // offsetUnset
                unset($combo[$i]);
            }
            catch (Exception $e) {
            }
        }
    }

    public function testComparisonForSinglesAndGangs()
    {
        // This compares singles and gangs.
        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        // The two combos are the same.
        $this->assertEquals(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g2')
        );
        // $comboA < $comboB (inferior value).
        $this->assertLessThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('y1')
        );
        // $comboA < $comboB (inferior color).
        $this->assertLessThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('r2')
        );
        // $comboA > $comboB.
        $this->assertGreaterThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('r2')
        );
        // $comboA = $comboB.
        $this->assertEquals(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('y1'),
            Erebot_Module_GoF_Card::fromLabel('y1')
        );
        // $comboA < $comboB (gang of four).
        $this->assertLessThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('y10'),
            Erebot_Module_GoF_Card::fromLabel('y10'),
            Erebot_Module_GoF_Card::fromLabel('r10'),
            Erebot_Module_GoF_Card::fromLabel('r10')
        );
        // $comboA > $comboB (higher gang of four).
        $this->assertGreaterThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g9'),
            Erebot_Module_GoF_Card::fromLabel('g9'),
            Erebot_Module_GoF_Card::fromLabel('y9'),
            Erebot_Module_GoF_Card::fromLabel('y9'),
            Erebot_Module_GoF_Card::fromLabel('r9')
        );
        // $comboA < $comboB (gang of five).
        $this->assertLessThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('y2'),
            Erebot_Module_GoF_Card::fromLabel('y2'),
            Erebot_Module_GoF_Card::fromLabel('r2'),
            Erebot_Module_GoF_Card::fromLabel('r2')
        );
        // $comboA > $comboB (gang of six).
        $this->assertGreaterThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('y1'),
            Erebot_Module_GoF_Card::fromLabel('y1'),
            Erebot_Module_GoF_Card::fromLabel('r1'),
            Erebot_Module_GoF_Card::fromLabel('r1'),
            Erebot_Module_GoF_Card::fromLabel('m1')
        );
        // $comboA < $comboB (gang of seven).
        $this->assertLessThan(
            0, Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB)
        );
    }

    /**
     * @expectedException Erebot_Module_GoF_NotComparableException
     */
    public function testNotComparable()
    {
        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2')
        );
        Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException Erebot_Module_GoF_NotComparableException
     */
    public function testNotComparable2()
    {
        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('y2')
        );
        Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException Erebot_Module_GoF_NotComparableException
     */
    public function testNotComparable3()
    {
        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('y2')
        );
        Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException Erebot_Module_GoF_NotComparableException
     */
    public function testNotComparable4()
    {
        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g3'),
            Erebot_Module_GoF_Card::fromLabel('g4'),
            Erebot_Module_GoF_Card::fromLabel('g5')
        );
        Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException Erebot_Module_GoF_NotComparableException
     */
    public function testNotComparable5()
    {
        $comboA = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g1')
        );
        $comboB = new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('g2'),
            Erebot_Module_GoF_Card::fromLabel('y3'),
            Erebot_Module_GoF_Card::fromLabel('g3'),
            Erebot_Module_GoF_Card::fromLabel('g3')
        );
        Erebot_Module_GoF_Combo::compareCombos($comboA, $comboB);
    }
}

