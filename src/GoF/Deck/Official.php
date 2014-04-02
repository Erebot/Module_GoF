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

namespace Erebot\Module\GoF\Deck;

class Official implements \Erebot\Module\GoF\DeckInterface
{
    protected $cards;
    protected $discarded;

    public function __construct()
    {
        // Shuffling the deck actually recreates it.
        $this->shuffle();
    }

    public function draw()
    {
        if (!count($this->cards)) {
            throw new \Erebot\Module\GoF\InternalErrorException();
        }
        return \Erebot\Module\GoF\Card::fromLabel(array_shift($this->cards));
    }

    public function discard(
        \Erebot\Module\GoF\Player   $player,
        \Erebot\Module\GoF\Combo    $combo
    ) {
        $this->discarded = array(
            'player'    => $player,
            'combo'     => $combo,
        );
    }

    public function shuffle()
    {
        $this->discarded    = null;
        $this->cards        = array();
        $colors             = str_split('gyr');

        // Add colored cards.
        foreach ($colors as $color) {
            for ($i = 0; $i < 2; $i++) {
                for ($j = 1; $j <= 10; $j++) {
                    $this->cards[] = $color.$j;
                }
            }
        }

        // Add special cards.
        $this->cards[] = 'm1'; // Multi-colored 1
        $this->cards[] = 'gp'; // Green phoenix
        $this->cards[] = 'yp'; // Yellow phoenix
        $this->cards[] = 'rd'; // Red dragon

        shuffle($this->cards);
    }

    public function getLastDiscard()
    {
        return $this->discarded;
    }
}
