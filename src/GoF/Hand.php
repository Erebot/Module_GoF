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

namespace Erebot\Module\GoF;

class Hand implements \Countable
{
    protected $cards;
    protected $deck;
    protected $player;

    public function __construct(
        \Erebot\Module\GoF\DeckInterface   $deck,
        \Erebot\Module\GoF\Player          $player
    ) {
        $this->deck    = $deck;
        $this->player  = $player;
        $this->cards   = array();
        for ($i = 0; $i < 16; $i++) {
            $this->cards[] = $deck->draw();
        }
        usort($this->cards, array('\\Erebot\\Module\\GoF\\Card', 'compareCards'));
        $this->cards = array_reverse($this->cards);
    }

    public function & getPlayer()
    {
        return $this->player;
    }

    public function count()
    {
        return count($this->cards);
    }

    public function getCards()
    {
        return array_reverse($this->cards);
    }

    public function addCard(\Erebot\Module\GoF\Card $card)
    {
        $this->cards[] = $card;
        usort($this->cards, array('\\Erebot\\Module\\GoF\\Card', 'compareCards'));
        $this->cards = array_reverse($this->cards);
    }

    public function hasCard(\Erebot\Module\GoF\Card $card)
    {
        $label = $card->getLabel();
        foreach ($this->cards as $key => &$c) {
            if ($c->getLabel() == $label) {
                return true;
            }
        }
        unset($c);
        return false;
    }

    public function & removeCard(\Erebot\Module\GoF\Card $card)
    {
        $label = $card->getLabel();
        foreach ($this->cards as $key => &$c) {
            if ($c->getLabel() == $label) {
                unset($this->cards[$key]);
                return $card;
            }
        }
        unset($c);
        throw new \Erebot\Module\GoF\NoSuchCardException();
    }

    public function discardCombo(\Erebot\Module\GoF\Combo $combo)
    {
        $removedCards = array();
        try {
            foreach ($combo as $card) {
                $removedCards[] = $this->removeCard($card);
            }
        } catch (\Erebot\Module\GoF\NoSuchCardException $e) {
            // Restore the cards as they were before.
            $this->cards = array_merge($this->cards, $removedCards);
            usort(
                $this->cards,
                array('\\Erebot\\Module\\GoF\\Card', 'compareCards')
            );
            $this->cards = array_reverse($this->cards);
            throw $e;
        }
        $this->deck->discard($this->player, $combo);
    }

    public function getScore()
    {
        $count      = count($this->cards);
        $factors    = array(
                        16  => 5,
                        14  => 4,
                        11  => 3,
                        8   => 2,
                        1   => 1,
                    );
        foreach ($factors as $threshold => $factor) {
            if ($count >= $threshold) {
                return $count * $factor;
            }
        }
        return 0;
    }

    public function getBestCard()
    {
        $best = reset($this->cards);
        if ($best === false) {
            return null;
        }
        return $best;
    }
}
