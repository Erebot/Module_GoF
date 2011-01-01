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

class       Erebot_Module_GoF_Game
implements  Countable
{
    /// 
    protected $_deck;
    protected $_order;
    protected $_players;
    protected $_startTime;
    protected $_creator;
    protected $_leader;
    protected $_lastLoser;
    protected $_nbRounds;

    const DIR_COUNTERCLOCKWISE      = 1;
    const DIR_CLOCKWISE             = 0;

    public function __construct(
                                            $creator,
        Erebot_Module_GoF_Deck_Abstract    &$deck
    )
    {
        $this->_creator     =&  $creator;
        $this->_deck        =   $deck;
        $this->_players     =   array();
        $this->_startTime   =   NULL;
        $this->_lastLoser   =   NULL;
        $this->_nbRounds    =   0;
    }

    public function & join($token)
    {
        $nbPlayers = count($this->_players);
        if ($nbPlayers >= 4)
            throw new Erebot_Module_GoF_EnoughPlayersException();

        $player             = new Erebot_Module_GoF_Player($token);
        $this->_players[]   = $player;
        $hand               = new Erebot_Module_GoF_Hand($this->_deck, $player);
        $player->setHand($hand);
        return $player;
    }

    public function start()
    {
        if ($nbPlayers < 3)
            throw new Erebot_Module_GoF_InternalErrorException();

        $this->_startTime = time();
        shuffle($this->_players);
        $this->nbRounds++;
        $multiOne = new Erebot_Module_GoF_Card::fromLabel('m1');
        foreach ($this->_players as &$player) {
            if ($player->getHand()->hasCard($multiOne)) {
                while (reset($this->_players) !== $player)
                    $this->_shiftPlayer();
                return $player;
            }
        }
        unset($player);
        return reset($this->_players);
    }

    public function play(Erebot_Module_GoF_Combo &$combo)
    {
        if (!$this->_nbRounds)
            throw new Erebot_Module_GoF_InternalErrorException();

        if ($this->_lastLoser !== NULL)
            throw new Erebot_Module_GoF_WaitingForCardException();

        // Check that the new combo is indeed
        // superior to the previous one.
        $lastDiscard = $this->_deck->getLastDiscard();
        if ($lastDiscard !== NULL &&
            Erebot_Module_GoF_Combo::compareCombos($combo, $lastDiscard['combo']) <= 0)
            throw new Erebot_Module_GoF_InferiorComboException();

        $current = $this->getCurrentPlayer();
        $currentHand =& $current->getHand();

        // If the first player in the first round has
        // the multi-colored one, he or she MUST play it.
        if ($this->nbRounds == 1) {
            $multiOne = new Erebot_Module_GoF_Card::fromLabel('m1');
            if ($currentHand->hasCard($multiOne)) {
                $playedMultiOne = FALSE;
                foreach ($combo as &$card) {
                    if ($card->getLabel() == 'm1') {
                        $playedMultiOne = TRUE;
                        break;
                    }
                }
                if (!$playedMultiOne)
                    throw new Erebot_Module_GoF_StartWithMulti1Exception();
            }
        }

        $currentHand->discardCombo($combo);
        $this->_shiftPlayer();

        if (!count($currentHand)) {
            $maxScore   = 0;
            $nbCards    = array();
            foreach ($this->_players as $index => &$player) {
                $nbCards[$index] = count($player->getHand());
                $maxScore = max($player->computeScore(), $maxScore);
            }
            unset($player);

            $losers = array_keys($nbCards, max($nbCards));
            if (count($losers) > 1) {
                $losers = array_combine($losers, $losers);
                $getTotalScore = create_function(
                    '&$v,$k,$p',
                    'return $p[$k]->getScore();'
                );
                array_walk($losers, $getTotalScore, $this->_players);
                $losers = array_keys($losers, max($losers));

                if (count($losers) > 1) {
                    if ($this->getDirection() == self::DIR_COUNTERCLOCKWISE)
                        $losers = array(min($losers));
                    else
                        $losers = array(max($losers));
                }
            }

            // Get ready for the next round.
            // Reverse direction, increment rounds counter, etc.
            assert(count($losers) == 1);
            $this->_lastLoser = $this->_players[reset($losers)];
            $this->_players = array_reverse($this->_players);
            $this->_nbRounds++;
            $this->_deck->shuffle();
            foreach ($this->_players as &$player) {
                $hand = new Erebot_Module_GoF_Hand($this->_deck, $player);
                $player->setHand($hand);
            }
            unset($player);
            return $maxScore;
        }
        return FALSE;
    }

    public function pass()
    {
        if (!$this->_nbRounds)
            throw new Erebot_Module_GoF_InternalErrorException();

        if ($this->_lastLoser !== NULL)
            throw new Erebot_Module_GoF_WaitingForCardException();
        $this->_shiftPlayer();
    }

    protected function _shiftPlayer()
    {
        $last = array_shift($this->_players);
        $this->_players[] =& $last;
    }

    public function & chooseCard(Erebot_Module_GoF_Card &$card)
    {
        if ($this->_lastLoser === NULL)
            throw new Erebot_Module_GoF_InternalErrorException();

        $loserHand  =&  $this->_lastLoser->getHand();
        $best       =   $loserHand->removeCard($loserHand->getBestCard());
        $winner     =   $this->getCurrentPlayer();
        $winnerHand =&  $winner->getHand();
        $chosen     =   $winnerHand->removeCard($card);
        $winnerHand->addCard($best);
        $loserHand->addCard($chosen);

        $this->_lastLoser = NULL;
        return $best;
    }

    public function getCurrentPlayer()
    {
        return reset($this->_players);
    }

    public function getLastLoser()
    {
        return $this->_lastLoser;
    }

    public function getLeadingPlayer()
    {
        $discard = $this->_deck->getLastDiscard();
        if ($discard === NULL)
            return NULL;
        return $discard['player'];
    }

    public function getCreator()
    {
        return $this->_creator;
    }

    public function getElapsedTime()
    {
        if ($this->_startTime === NULL)
            return NULL;

        return time() - $this->_startTime;
    }

    public function count()
    {
        return count($this->_players);
    }

    public function getPlayers()
    {
        return $this->_players;
    }

    public function getNbRounds()
    {
        return $this->_nbRounds;
    }

    public function getDirection()
    {
        return ($this->_nbRounds & 1);
    }

    public function & getDeck()
    {
        return $this->_deck;
    }
}

# vim: et ts=4 sts=4 sw=4
