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

class   FakeDeck
extends Erebot_Module_GoF_Deck_Official
{
    protected $_fixedCards;

    public function __construct($cards)
    {
        $this->setCards($cards);
        parent::__construct();
    }

    public function setCards($cards)
    {
        if (!is_array($cards) || count($cards) != 64)
            throw new Exception('Invalid deck');
        // Try to convert the given string to a valid card.
        foreach ($cards as $card)
            Erebot_Module_GoF_Card::fromLabel($card);
        $this->_fixedCards = array_values($cards);
    }

    public function shuffle()
    {
        $this->_discarded   = NULL;
        $this->_cards       = $this->_fixedCards;
    }
}

class   FakeGame
extends Erebot_Module_GoF_Game
{
    protected function _shuffle()
    {
        // Don't shuffle.
    }
}

class   Erebot_Module_GoF_InGameTest
extends PHPUnit_Framework_TestCase
{
    public function testDifferentHandsAfterVictory()
    {
        $deck = new FakeDeck(array_merge(
            array_fill(0, 4, 'm1'),
            array_fill(4, 4, 'g1'),
            array_fill(8, 8, 'r10'),
            array_fill(16, 16, 'g2'),
            array_fill(32, 32, 'g3')
        ));
        $game = new FakeGame('creator', $deck);
        $foo = $game->join('foo');
        $bar = $game->join('bar');
        $baz = $game->join('baz');
        $starter = $game->start();
        $this->assertSame($foo, $starter);

        $m1 = Erebot_Module_GoF_Card::fromLabel('m1');
        $comboFoo1 = new Erebot_Module_GoF_Combo($m1, $m1, $m1, $m1);

        $g1 = Erebot_Module_GoF_Card::fromLabel('g1');
        $comboFoo2 = new Erebot_Module_GoF_Combo($g1, $g1, $g1, $g1);

        $r10 = Erebot_Module_GoF_Card::fromLabel('r10');
        $comboFoo3 = new Erebot_Module_GoF_Combo($r10, $r10, $r10, $r10);

        $g2 = Erebot_Module_GoF_Card::fromLabel('g2');
        $comboBar = new Erebot_Module_GoF_Combo($g2, $g2, $g2, $g2);

        $score = FALSE;
        for ($i = 0; $i < 2; $i++) {
            $player = $game->getCurrentPlayer();
            $this->assertEquals(
                $foo, $player,
                $player->getToken()." instead of foo"
            );
            $game->play($i == 0 ? $comboFoo1 : $comboFoo2);

            $player = $game->getCurrentPlayer();
            $this->assertEquals(
                $bar, $player,
                $player->getToken()." instead of bar"
            );
            $game->play($comboBar);

            $player = $game->getCurrentPlayer();
            $this->assertEquals(
                $baz, $player,
                $player->getToken()." instead of baz"
            );
            $game->pass();

            $player = $game->getCurrentPlayer();
            $this->assertEquals(
                $foo, $player,
                $player->getToken()." instead of foo"
            );
            $score = $game->play($comboFoo3);

            if ($i == 1)
                break;

            $player = $game->getCurrentPlayer();
            $this->assertEquals(
                $bar, $player,
                $player->getToken()." instead of bar"
            );
            $game->pass();

            $player = $game->getCurrentPlayer();
            $this->assertEquals(
                $baz, $player,
                $player->getToken()." instead of baz"
            );
            $game->pass();
        }
        $this->assertEquals(80, $score);
        $player = $game->getCurrentPlayer();
        $this->assertEquals(
            $foo, $player,
            $player->getToken()." instead of foo"
        );

        foreach ($foo->getHand()->getCards() as $card)
            if (!in_array($card->getLabel(), array('g1', 'm1', 'r10')))
                $this->fail('Unexpected card!');

        foreach ($bar->getHand()->getCards() as $card)
            $this->assertEquals('g3', $card->getLabel());

        foreach ($baz->getHand()->getCards() as $card)
            $this->assertEquals('g2', $card->getLabel());
    }

    /**
     * @expectedException   Erebot_Module_GoF_InferiorComboException
     */
    public function testIncorrectLeader()
    {
        $deck = new FakeDeck(array_merge(
            explode(' ', str_replace('  ', ' ', 'm1  g1  r1  y3  g3  r8  g8 r10 g10  r5  r5  y9  y2  g4  g7  r7')), // c
            explode(' ', str_replace('  ', ' ', 'r4  y4  y4  r3  r3  g3  r6  y6  r9  g9  rd  r1  g8  y2  g5  y7')), // t
            explode(' ', str_replace('  ', ' ', 'y5  y5  g5  r6  y6  g6  g6  r2  g2  r7  g7  yp  gp  y3  g4 g10')), // s
            explode(' ', str_replace('  ', ' ', 'r8  y8  y8  r9  y9  g9  y1  y1  g1 r10 y10 y10  r4  y7  g2  r2')), // g
            array()
        ));

        $game = new FakeGame('c', $deck);
        $playerC = $game->join('c');
        $playerT = $game->join('t');
        $playerS = $game->join('s');
        $playerG = $game->join('g');
        $starter = $game->start();

        // Prepare the cards for round #2.
        $deck->setCards(array_merge(
            explode(' ', str_replace('  ', ' ', 'y2 g4 y4 y5 r5 g6 y7 r7 g8 r8 r8 g9 y9 g10 gp rd')),   // s
            explode(' ', str_replace('  ', ' ', 'y1 r1 g2 g2 y3 r3 r3 r5 y6 r6 g7 y9 r9 y10 r10 yp')),  // t
            explode(' ', str_replace('  ', ' ', 'y1 m1 y2 r2 r2 g3 g4 y4 r4 r4 y5 g7 y7 y8 r9 g10')),   // c
            explode(' ', str_replace('  ', ' ', 'g1 g1 r1 g3 y3 g5 g5 g6 y6 r6 r7 g8 y8 g9 y10 r10')),  // g
            array()
        ));

        // Round #1.
        try {
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('m1'),
                Erebot_Module_GoF_Card::fromLabel('g1'),
                Erebot_Module_GoF_Card::fromLabel('r1')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r4'),
                Erebot_Module_GoF_Card::fromLabel('y4'),
                Erebot_Module_GoF_Card::fromLabel('y4')
            ));
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r8'),
                Erebot_Module_GoF_Card::fromLabel('y8'),
                Erebot_Module_GoF_Card::fromLabel('y8')
            ));
            $game->pass();
            $game->pass();
            $game->pass();

            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r9'),
                Erebot_Module_GoF_Card::fromLabel('y9'),
                Erebot_Module_GoF_Card::fromLabel('g9')
            ));
            $game->pass();
            $game->pass();
            $game->pass();

            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('y1'),
                Erebot_Module_GoF_Card::fromLabel('y1'),
                Erebot_Module_GoF_Card::fromLabel('g1')
            ));
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r3'),
                Erebot_Module_GoF_Card::fromLabel('r3'),
                Erebot_Module_GoF_Card::fromLabel('g3')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('y5'),
                Erebot_Module_GoF_Card::fromLabel('y5'),
                Erebot_Module_GoF_Card::fromLabel('g5')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r10'),
                Erebot_Module_GoF_Card::fromLabel('y10'),
                Erebot_Module_GoF_Card::fromLabel('y10')
            ));
            $game->pass();
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r6'),
                Erebot_Module_GoF_Card::fromLabel('y6'),
                Erebot_Module_GoF_Card::fromLabel('g6'),
                Erebot_Module_GoF_Card::fromLabel('g6')
            ));
            $game->pass();
            $game->pass();
            $game->pass();

            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r2'),
                Erebot_Module_GoF_Card::fromLabel('g2')
            ));
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('y3'),
                Erebot_Module_GoF_Card::fromLabel('g3')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r6'),
                Erebot_Module_GoF_Card::fromLabel('y6')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r7'),
                Erebot_Module_GoF_Card::fromLabel('g7')
            ));
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r8'),
                Erebot_Module_GoF_Card::fromLabel('g8')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r9'),
                Erebot_Module_GoF_Card::fromLabel('g9')
            ));
            $game->pass();
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r10'),
                Erebot_Module_GoF_Card::fromLabel('g10')
            ));
            $game->pass();
            $game->pass();
            $game->pass();

            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r5'),
                Erebot_Module_GoF_Card::fromLabel('r5')
            ));
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('yp'),
                Erebot_Module_GoF_Card::fromLabel('gp')
            ));
            $game->pass();
            $game->pass();
            $game->pass();

            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('y3')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r4')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('y9')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('rd')
            ));
            $game->pass();
            $game->pass();
            $game->pass();

            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r1')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('g4')
            ));
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('y7')
            ));
            $game->pass();
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('g8')
            ));
            $end = $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('g10')
            ));
            $this->assertEquals(4, $end);
        }
        catch (Erebot_Module_GoF_InferiorComboException $e) {
            $this->fail("Early exception");
        }

        $game->chooseCard(Erebot_Module_GoF_Card::fromLabel('y2'));

        // Round #2.
        try {
            $this->assertEquals($playerS, $game->getCurrentPlayer());
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('y4'),
                Erebot_Module_GoF_Card::fromLabel('g4')
            ));

            $this->assertEquals($playerT, $game->getCurrentPlayer());
            $game->play(new Erebot_Module_GoF_Combo(
                Erebot_Module_GoF_Card::fromLabel('r6'),
                Erebot_Module_GoF_Card::fromLabel('y6')
            ));

            $this->assertEquals($playerC, $game->getCurrentPlayer());
            $game->pass();
        }
        catch (Erebot_Module_GoF_InferiorComboException $e) {
            $this->fail("Early exception");
        }

        $this->assertEquals($playerG, $game->getCurrentPlayer());
        $game->play(new Erebot_Module_GoF_Combo(
            Erebot_Module_GoF_Card::fromLabel('y3'),
            Erebot_Module_GoF_Card::fromLabel('g3')
        ));
    }
}

