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

class   FakeDeck
extends Erebot_Module_GoF_Deck_Official
{
    public function shuffle()
    {
        $this->_discarded   = NULL;
        $this->_cards       = array();

        for ($i = 0; $i < 4; $i++)
            $this->_cards[] = 'm1';
        for (; $i < 8; $i++)
            $this->_cards[] = 'g1';
        for (; $i < 16; $i++)
            $this->_cards[] = 'r10';
        for (; $i < 32; $i++)
            $this->_cards[] = 'g2';
        for (; $i < 64; $i++)
            $this->_cards[] = 'g3';
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
        $deck = new FakeDeck();
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
}

