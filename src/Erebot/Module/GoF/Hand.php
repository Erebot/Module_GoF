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

class Erebot_Module_GoF_Hand
{
    protected $_player;
    protected $_cards;

    public function __construct(&$player)
    {
        $this->_player  =&  $player;
        $this->_cards   =   array();
        $this->_deck    =&  $deck;
    }

    public function & getPlayer()
    {
        return $this->_player;
    }

    public function & getCards()
    {
        return $this->_cards;
    }

    public function getCardsCount()
    {
        return count($this->_cards);
    }

#    public function findCard($card)
#    {
#        if (!is_string())
#    }

    public function addCard(Erebot_Module_GoF_Card &$card)
    {
        if (in_array($card, $this->_cards, TRUE))
            throw new Erebot_Module_GoF_InvalidCardException();
        $this->_cards[] = $card;
    }

    public function & removeCard($card)
    {
        $key = array_search($card, $this->_cards, TRUE);
        if ($key === FALSE)
            throw new Erebot_Module_GoF_NoSuchCardException();
        unset($this->_cards[$key]);
        return $card;
    }

    public function discard(Erebot_Module_GoF_Combo &$combo)
    {
        if (!$this->hasCombination($combo))
            throw new Erebot_Module_GoF_NoSuchCardException();
        foreach ($combo as &$card)
            $this->removeCard($card);
    }

    public function hasCombination(Erebot_Module_GoF_Combo &$combo)
    {
#        foreach ($combo as &$card)
            
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
}

