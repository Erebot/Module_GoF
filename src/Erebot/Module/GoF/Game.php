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

class Erebot_Module_GoF_Game
{
    protected $_deck;
    protected $_order;
    protected $_players;
    protected $_startTime;
    protected $_creator;
    protected $_leader;

    const DIR_COUNTERCLOCKWISE      = FALSE;
    const DIR_CLOCKWISE             = TRUE;

    public function __construct(
                                            $creator,
        Erebot_Module_GoF_Deck_Abstract    &$deck
    )
    {
        $this->_creator     =&  $creator;
        $this->_deck        =   $deck;
        $this->_players     =   array();
        $this->_startTime   =   NULL;
        $this->_leader      =   NULL;
    }

    public function __destruct()
    {
        
    }

    public function & join($token)
    {
        $nbPlayers = count($this->_players);
        if ($nbPlayers >= 4)
            throw new Erebot_Module_GoF_EnoughPlayersException();

        $this->_players[]   = new Erebot_Module_GoF_Hand($token, $this->_deck);
        $player             = end($this->_players);
        if (count($this->_players) == 3) {
            $this->_startTime = time();
            shuffle($this->_players);
        }
        return $player;
    }

    public function play($combo)
    {
        
    }

    public function pass()
    {
        
    }

    public function chooseCard($card)
    {
        
    }

    public function getCurrentPlayer()
    {
        return reset($this->_players);
    }

    public function & getLeadingPlayer()
    {
        return $this->_leader;
    }

    public function & getCreator()
    {
        return $this->_creator;
    }

    public function getElapsedTime()
    {
        if ($this->_startTime === NULL)
            return NULL;

        return time() - $this->_startTime;
    }

    public function getLastPlayedCombo()
    {
        return $this->_deck->getLastDiscardedCombo();
    }

    public function & getPlayers()
    {
        return $this->_players;
    }
}

# vim: et ts=4 sts=4 sw=4
