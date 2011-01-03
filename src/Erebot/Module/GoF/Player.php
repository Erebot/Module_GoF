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

class Erebot_Module_GoF_Player
{
    protected $_token;
    protected $_hand;
    protected $_score;

    public function __construct(&$token)
    {
        $this->_token   =&  $token;
        $this->_score   =   0;
    }

    public function & getToken()
    {
        return $this->_token;
    }

    public function setHand(Erebot_Module_GoF_Hand $hand)
    {
        $this->_hand = $hand;
    }

    public function & getHand()
    {
        return $this->_hand;
    }

    public function getScore()
    {
        return $this->_score;
    }

    public function computeScore()
    {
        $this->_score += $this->_hand->getScore();
        return $this->_score;
    }
}

