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

class       Erebot_Module_GoF_Deck_Official
implements  Erebot_Module_GoF_Deck_Abstract
{
    protected $_cards;
    protected $_discarded;

    public function __construct()
    {
        // Shuffle takes care of recreating the deck.
        $this->shuffle();
    }

    public function draw()
    {
        if (!count($this->_cards))
            throw new Erebot_Module_GoF_InternalErrorException();
        return Erebot_Module_GoF_Card::fromLabel(array_shift($this->_cards));
    }

    public function discard(
        Erebot_Module_GoF_Player   $player,
        Erebot_Module_GoF_Combo    $combo
    )
    {
        $this->_discarded = array(
            'player'    => $player,
            'combo'     => $combo,
        );
    }

    public function shuffle()
    {
        $this->_discarded   = NULL;
        $this->_cards       = array();
        $colors             = str_split('gyr');

        // Add colored cards.
        foreach ($colors as $color) {
            for ($i = 0; $i < 2; $i++) {
                for ($j = 1; $j <= 10; $j++)
                    $this->_cards[] = $color.$j;
            }
        }

        // Add special cards.
        $this->_cards[] = 'm1'; // Multi-colored 1
        $this->_cards[] = 'gp'; // Green phoenix
        $this->_cards[] = 'yp'; // Yellow phoenix
        $this->_cards[] = 'rd'; // Red dragon

        shuffle($this->_cards);
    }

    public function getLastDiscard()
    {
        return $this->_discarded;
    }
}

