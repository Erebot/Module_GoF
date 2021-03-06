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

class   ComboTest
extends \PHPUnit\Framework\TestCase
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

                $combo = new \Erebot\Module\GoF\Combo(
                    \Erebot\Module\GoF\Card::fromLabel($colorA.$value),
                    \Erebot\Module\GoF\Card::fromLabel($colorB.$value),
                    \Erebot\Module\GoF\Card::fromLabel($colorC.$value)
                );
                $this->assertEquals(
                    \Erebot\Module\GoF\Combo::COMBO_TRIO,
                    $combo->getType(),
                    print_r(array(
                        $colorA.$value,
                        $colorB.$value,
                        $colorC.$value
                    ), TRUE)
                );

                if (!($id % 3)) {
                    $combo = new \Erebot\Module\GoF\Combo(
                        \Erebot\Module\GoF\Card::fromLabel($colorA.$value),
                        \Erebot\Module\GoF\Card::fromLabel($colorB.$value)
                    );
                    $this->assertEquals(
                        \Erebot\Module\GoF\Combo::COMBO_PAIR,
                        $combo->getType(),
                        print_r(array(
                            $colorA.$value,
                            $colorB.$value
                        ), TRUE)
                    );
                }

                if (!($id % 9)) {
                    $combo = new \Erebot\Module\GoF\Combo(
                        \Erebot\Module\GoF\Card::fromLabel($colorA.$value)
                    );
                    $this->assertEquals(
                        \Erebot\Module\GoF\Combo::COMBO_SINGLE,
                        $combo->getType(),
                        print_r(array($colorA.$value), TRUE)
                    );
                }
            }
        }

        // Special singles.
        foreach (array('m1', 'gp', 'yp', 'rd') as $label) {
            $combo = new \Erebot\Module\GoF\Combo(
                \Erebot\Module\GoF\Card::fromLabel($label)
            );
            $this->assertEquals(
                \Erebot\Module\GoF\Combo::COMBO_SINGLE,
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
            $combo = new \Erebot\Module\GoF\Combo(
                \Erebot\Module\GoF\Card::fromLabel($pair[0]),
                \Erebot\Module\GoF\Card::fromLabel($pair[1])
            );
            $this->assertEquals(
                \Erebot\Module\GoF\Combo::COMBO_PAIR,
                $combo->getType(),
                print_r(array($pair[0], $pair[1]), TRUE)
            );
        }

        // Special trios (those with m1 in them).
        foreach ($colors as $colorA)
            foreach ($colors as $colorB) {
                $combo = new \Erebot\Module\GoF\Combo(
                    \Erebot\Module\GoF\Card::fromLabel('m1'),
                    \Erebot\Module\GoF\Card::fromLabel($colorA.'1'),
                    \Erebot\Module\GoF\Card::fromLabel($colorB.'1')
                );
                $this->assertEquals(
                    \Erebot\Module\GoF\Combo::COMBO_TRIO,
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

                $combo = new \Erebot\Module\GoF\Combo(
                    \Erebot\Module\GoF\Card::fromLabel($colorA.($base + 0)),
                    \Erebot\Module\GoF\Card::fromLabel($colorB.($base + 1)),
                    \Erebot\Module\GoF\Card::fromLabel($colorC.($base + 2)),
                    \Erebot\Module\GoF\Card::fromLabel($colorD.($base + 3)),
                    \Erebot\Module\GoF\Card::fromLabel($colorE.($base + 4))
                );
                // If all five cards are of the same color,
                // this is a straight flush.
                // Otherwise, it's an ordinary straight.
                $type = (
                        ($colorA == 'm' || $colorA == $colorB) &&
                        $colorB == $colorC &&
                        $colorC == $colorD &&
                        $colorD == $colorE
                    )
                    ? \Erebot\Module\GoF\Combo::COMBO_STRAIGHT_FLUSH
                    : \Erebot\Module\GoF\Combo::COMBO_STRAIGHT;
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

    public function testFlushesWithMultiColored1()
    {
        foreach (array('r', 'y', 'g') as $color) {
            $combo = new \Erebot\Module\GoF\Combo(
                \Erebot\Module\GoF\Card::fromLabel('m1'),
                \Erebot\Module\GoF\Card::fromLabel($color.'3'),
                \Erebot\Module\GoF\Card::fromLabel($color.'5'),
                \Erebot\Module\GoF\Card::fromLabel($color.'7'),
                \Erebot\Module\GoF\Card::fromLabel($color.'9')
            );
            $this->assertEquals(
                \Erebot\Module\GoF\Combo::COMBO_FLUSH,
                $combo->getType(),
                print_r(array(
                    'm1',
                    $color.'3',
                    $color.'5',
                    $color.'7',
                    $color.'9'
                ), TRUE)
            );
        }
    }

    public function testFullHouse()
    {
        $combo = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('y2')
        );
        $this->assertEquals(
            \Erebot\Module\GoF\Combo::COMBO_FULL_HOUSE,
            $combo->getType()
        );

        // Force a swap.
        $combo = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('y1'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g2')
        );
        $this->assertEquals(
            \Erebot\Module\GoF\Combo::COMBO_FULL_HOUSE,
            $combo->getType()
        );

        // Using the pair of phoenixes.
        $combo = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('y1'),
            \Erebot\Module\GoF\Card::fromLabel('gp'),
            \Erebot\Module\GoF\Card::fromLabel('yp')
        );
        $this->assertEquals(
            \Erebot\Module\GoF\Combo::COMBO_FULL_HOUSE,
            $combo->getType()
        );
    }

    public function testInterfaces()
    {
        $combo = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g5'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g4'),
            \Erebot\Module\GoF\Card::fromLabel('g3'),
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        // Failsafe test.
        $this->assertEquals(
            \Erebot\Module\GoF\Combo::COMBO_STRAIGHT_FLUSH,
            $combo->getType()
        );
        $expected = array('g5', 'g4', 'g3', 'g2', 'g1');

        // Countable interface.
        $this->assertEquals(5, count($combo));

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
        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        // The two combos are the same.
        $this->assertEquals(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g2')
        );
        // $comboA < $comboB (inferior value).
        $this->assertLessThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('y1')
        );
        // $comboA < $comboB (inferior color).
        $this->assertLessThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('r2')
        );
        // $comboA > $comboB.
        $this->assertGreaterThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('r2')
        );
        // $comboA = $comboB.
        $this->assertEquals(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('y1'),
            \Erebot\Module\GoF\Card::fromLabel('y1')
        );
        // $comboA < $comboB (gang of four).
        $this->assertLessThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('y10'),
            \Erebot\Module\GoF\Card::fromLabel('y10'),
            \Erebot\Module\GoF\Card::fromLabel('r10'),
            \Erebot\Module\GoF\Card::fromLabel('r10')
        );
        // $comboA > $comboB (higher gang of four).
        $this->assertGreaterThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g9'),
            \Erebot\Module\GoF\Card::fromLabel('g9'),
            \Erebot\Module\GoF\Card::fromLabel('y9'),
            \Erebot\Module\GoF\Card::fromLabel('y9'),
            \Erebot\Module\GoF\Card::fromLabel('r9')
        );
        // $comboA < $comboB (gang of five).
        $this->assertLessThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('y2'),
            \Erebot\Module\GoF\Card::fromLabel('y2'),
            \Erebot\Module\GoF\Card::fromLabel('r2'),
            \Erebot\Module\GoF\Card::fromLabel('r2')
        );
        // $comboA > $comboB (gang of six).
        $this->assertGreaterThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );

        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('y1'),
            \Erebot\Module\GoF\Card::fromLabel('y1'),
            \Erebot\Module\GoF\Card::fromLabel('r1'),
            \Erebot\Module\GoF\Card::fromLabel('r1'),
            \Erebot\Module\GoF\Card::fromLabel('m1')
        );
        // $comboA < $comboB (gang of seven).
        $this->assertLessThan(
            0, \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB)
        );
    }

    /**
     * @expectedException \Erebot\Module\GoF\NotComparableException
     */
    public function testNotComparable()
    {
        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g2')
        );
        \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException \Erebot\Module\GoF\NotComparableException
     */
    public function testNotComparable2()
    {
        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('y2')
        );
        \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException \Erebot\Module\GoF\NotComparableException
     */
    public function testNotComparable3()
    {
        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('y2')
        );
        \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException \Erebot\Module\GoF\NotComparableException
     */
    public function testNotComparable4()
    {
        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g3'),
            \Erebot\Module\GoF\Card::fromLabel('g4'),
            \Erebot\Module\GoF\Card::fromLabel('g5')
        );
        \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB);
    }

    /**
     * @expectedException \Erebot\Module\GoF\NotComparableException
     */
    public function testNotComparable5()
    {
        $comboA = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g1')
        );
        $comboB = new \Erebot\Module\GoF\Combo(
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('g2'),
            \Erebot\Module\GoF\Card::fromLabel('y3'),
            \Erebot\Module\GoF\Card::fromLabel('g3'),
            \Erebot\Module\GoF\Card::fromLabel('g3')
        );
        \Erebot\Module\GoF\Combo::compareCombos($comboA, $comboB);
    }
}

class   Erebot_Module_GoF_InvalidCombosTest
extends \PHPUnit\Framework\TestCase
{
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

            // Gang + random card
            array('g1', 'g1', 'y1', 'y1', 'r6'),

            // The dragon cannot be used with other cards.
            array('r1', 'r3', 'r5', 'r7', 'rd'),    // Tentative Flush

            // The phoenixes cannot be used in a straight+flush.
            array('g7', 'y8', 'r9', 'g10', 'gp'),   // Tentative Straight
            array('g7', 'y8', 'r9', 'g10', 'yp'),   // Tentative Straight
            array('g1', 'g3', 'g5', 'g7', 'gp'),    // Tentative Flush
            array('y1', 'y3', 'y5', 'y7', 'yp'),    // Tentative Flush
            array('g7', 'g8', 'g9', 'g10', 'gp'),   // Tentative Straight flush
            array('y7', 'y8', 'y9', 'y10', 'yp'),   // Tentative Straight flush

        );
    }

    /**
     * @dataProvider invalidCombos
     * @expectedException \Erebot\Module\GoF\InvalidComboException
     */
    public function testInvalidCombos()
    {
        $args = func_get_args();
        $reflector = new ReflectionClass('\\Erebot\\Module\\GoF\\Combo');
        $cards = array_map(array('\\Erebot\\Module\\GoF\\Card', 'fromLabel'), $args);
        $reflector->newInstanceArgs($cards);
    }
}

