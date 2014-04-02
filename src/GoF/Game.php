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

class Game implements \Countable
{
    protected $deck;
    protected $order;
    protected $players;
    protected $startTime;
    protected $creator;
    protected $leader;
    protected $lastLoser;
    protected $nbRounds;

    const DIR_COUNTERCLOCKWISE      = 1;
    const DIR_CLOCKWISE             = 0;

    public function __construct(
        $creator,
        \Erebot\Module\GoF\DeckInterface &$deck
    ) {
        $this->creator    =&  $creator;
        $this->deck       =   $deck;
        $this->players    =   array();
        $this->startTime  =   null;
        $this->lastLoser  =   null;
        $this->nbRounds   =   0;
    }

    public function & join($token)
    {
        if (count($this->players) >= 4 || $this->nbRounds) {
            throw new \Erebot\Module\GoF\EnoughPlayersException();
        }

        $player             = new \Erebot\Module\GoF\Player($token);
        $this->players[]   = $player;
        $player->setHand(new \Erebot\Module\GoF\Hand($this->deck, $player));
        return $player;
    }

    public function start()
    {
        if (count($this->players) < 3 || $this->nbRounds) {
            throw new \Erebot\Module\GoF\InternalErrorException();
        }

        $this->startTime = time();
        $this->shuffle();
        $this->nbRounds++;
        $multiOne = \Erebot\Module\GoF\Card::fromLabel('m1');
        foreach ($this->players as &$player) {
            if ($player->getHand()->hasCard($multiOne)) {
                while ($this->getCurrentPlayer() !== $player) {
                    $this->shiftPlayer();
                }
                return $player;
            }
        }
        unset($player);
        return $this->getCurrentPlayer();
    }

    protected function shuffle()
    {
        shuffle($this->players);
    }

    public function play(\Erebot\Module\GoF\Combo $combo)
    {
        if (!$this->nbRounds) {
            throw new \Erebot\Module\GoF\InternalErrorException();
        }

        if ($this->lastLoser !== null) {
            throw new \Erebot\Module\GoF\WaitingForCardException();
        }

        // Check that the new combo is indeed
        // superior to the previous one.
        $current = $this->getCurrentPlayer();
        $lastDiscard = $this->deck->getLastDiscard();
        if ($lastDiscard !== null &&
            $lastDiscard['player'] !== $current &&
            \Erebot\Module\GoF\Combo::compareCombos(
                $combo,
                $lastDiscard['combo']
            ) <= 0) {
            throw new \Erebot\Module\GoF\InferiorComboException();
        }

        $currentHand =& $current->getHand();

        // If the first player in the first round has
        // the multi-colored one, he or she MUST play it.
        if ($this->nbRounds == 1) {
            $multiOne = \Erebot\Module\GoF\Card::fromLabel('m1');
            if ($currentHand->hasCard($multiOne)) {
                $playedMultiOne = false;
                foreach ($combo as $card) {
                    if ($card->getLabel() == 'm1') {
                        $playedMultiOne = true;
                        break;
                    }
                }
                if (!$playedMultiOne) {
                    throw new \Erebot\Module\GoF\StartWithMulti1Exception();
                }
            }
        }

        $currentHand->discardCombo($combo);
        $this->shiftPlayer();

        if (!count($currentHand)) {
            $maxScore   = 0;
            $nbCards    = array();
            foreach ($this->players as $index => &$player) {
                $nbCards[$index] = count($player->getHand());
                $maxScore = max($player->computeScore(), $maxScore);
            }
            unset($player);

            $losers = array_keys($nbCards, max($nbCards));
            if (count($losers) > 1) {
                $losers = array_combine($losers, $losers);
                array_walk(
                    $losers,
                    array('self', 'getScore'),
                    $this->players
                );
                $losers = array_keys($losers, max($losers));

                if (count($losers) > 1) {
                    if ($this->getDirection() == self::DIR_COUNTERCLOCKWISE) {
                        $losers = array(min($losers));
                    } else {
                        $losers = array(max($losers));
                    }
                }
            }

            // Get ready for the next round.
            // Reverse direction, increment rounds counter, etc.
            assert(count($losers) == 1);
            $this->lastLoser = $this->players[reset($losers)];
            $this->players = array_reverse($this->players);
            $this->nbRounds++;
            $this->deck->shuffle();
            foreach ($this->players as $player) {
                $player->setHand(new \Erebot\Module\GoF\Hand($this->deck, $player));
            }
            return $maxScore;
        }
        return false;
    }

    protected static function getScore(&$v, $k, $p)
    {
        return $p[$k]->getScore();
    }

    public function pass()
    {
        // Game not started yet.
        if (!$this->nbRounds) {
            throw new \Erebot\Module\GoF\InternalErrorException();
        }

        // The card exchange has not been done yet.
        if ($this->lastLoser !== null) {
            throw new \Erebot\Module\GoF\WaitingForCardException();
        }

        // No combo has been played yet.
        // The current player MUST play one.
        if ($this->deck->getLastDiscard() === null) {
            throw new \Erebot\Module\GoF\InternalErrorException();
        }

        $this->shiftPlayer();
    }

    protected function shiftPlayer()
    {
        $last = array_shift($this->players);
        $this->players[] =& $last;
    }

    public function chooseCard(\Erebot\Module\GoF\Card $card)
    {
        if ($this->lastLoser === null) {
            throw new \Erebot\Module\GoF\InternalErrorException();
        }

        $winner     =   $this->getCurrentPlayer();
        $winnerHand =&  $winner->getHand();
        $chosen     =   $winnerHand->removeCard($card);

        $loserHand  =&  $this->lastLoser->getHand();
        $best       =   $loserHand->getBestCard();
        $loserHand->removeCard($best);
        $winnerHand->addCard($best);
        $loserHand->addCard($chosen);

        $this->lastLoser = null;
        return $best;
    }

    public function getCurrentPlayer()
    {
        return reset($this->players);
    }

    public function getLastLoser()
    {
        return $this->lastLoser;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getElapsedTime()
    {
        if ($this->startTime === null) {
            return null;
        }

        return time() - $this->startTime;
    }

    public function count()
    {
        return count($this->players);
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function getNbRounds()
    {
        return $this->nbRounds;
    }

    public function getDirection()
    {
        return ($this->nbRounds & 1);
    }

    public function & getDeck()
    {
        return $this->deck;
    }
}
