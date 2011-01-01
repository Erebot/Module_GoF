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

class       Erebot_Module_GoF_Hand
implements  Countable
{
    protected $_cards;
    protected $_deck;
    protected $_player;

    public function __construct(
        Erebot_Module_GoF_Deck_Abstract    &$deck,
        Erebot_Module_GoF_Player           &$player
    )
    {
        $this->_deck    =&  $deck;
        $this->_player  =&  $player;
        $this->_cards   =   array();
        for ($i = 0; $i < 16; $i++)
            $this->_cards[] = $deck->draw();
        usort($this->_cards, array('Erebot_Module_GoF_Card', 'compareCards'));
        $this->_cards = array_reverse($this->_cards);
    }

    public function & getPlayer()
    {
        return $this->_player;
    }

    public function count()
    {
        return count($this->_cards);
    }

    public function addCard(Erebot_Module_GoF_Card &$card)
    {
        $this->_cards[] = $card;
        usort($this->_cards, array('Erebot_Module_GoF_Card', 'compareCards'));
        $this->_cards = array_reverse($this->_cards);
    }

    public function hasCard(Erebot_Module_GoF_Card &$card)
    {
        $label = $card->getLabel();
        foreach ($this->_cards as $key => &$c) {
            if ($c->getLabel() == $label)
                return TRUE;
        }
        return FALSE;
    }

    public function & removeCard(Erebot_Module_GoF_Card &$card)
    {
        $label = $card->getLabel();
        foreach ($this->_cards as $key => &$c) {
            if ($c->getLabel() == $label) {
                unset($this->_cards[$key]);
                return $card;
            }
        }
        unset($c);
        throw new Erebot_Module_GoF_NoSuchCardException();
    }

    public function discardCombo(Erebot_Module_GoF_Combo &$combo)
    {
        $removedCards = array();
        try {
            foreach ($combo as &$card) {
                $removedCards[] = $this->removeCard($card)->getLabel();
            }
            unset($card);
        }
        catch (Erebot_Module_GoF_NoSuchCardException $e) {
            // Restore the cards as they were before.
            $this->_cards = array_merge($this->_cards, $removedCards);
            usort($this->_cards, array('Erebot_Module_GoF_Card', 'compareCards'));
            $this->_cards = array_reverse($this->_cards);
            throw $e;
        }
        $this->_deck->discard($this->_player, $combo);
    }

    public function getScore()
    {
        $count      = count($this->_cards);
        $factors    = array(
                        16  => 5,
                        14  => 4,
                        11  => 3,
                        8   => 2,
                        1   => 1,
                    );
        foreach ($factors as $threshold => $factor)
            if ($count >= $threshold)
                return $count * $factor;
        return 0;
    }

    public function getBestCard()
    {
        $best = reset($this->_cards);
        if ($best === FALSE)
            return NULL;
        return Erebot_Module_GoF_Card::fromLabel($best);
    }
}

