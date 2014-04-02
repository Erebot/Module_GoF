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

class Player
{
    protected $token;
    protected $hand;
    protected $score;

    public function __construct(&$token)
    {
        $this->token  =&  $token;
        $this->score  =   0;
    }

    public function & getToken()
    {
        return $this->token;
    }

    public function setHand(\Erebot\Module\GoF\Hand $hand)
    {
        $this->hand = $hand;
    }

    public function & getHand()
    {
        return $this->hand;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function computeScore()
    {
        $this->score += $this->hand->getScore();
        return $this->score;
    }
}
