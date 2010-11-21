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

// If the given combo is inferior to the currently
// leading one. The $allowed parameter may contain
// an array of valid combos which could be played.
class   Erebot_Module_GoF_InferiorComboException
extends Erebot_Module_GoF_Exception
{
    protected $allowed;

    public function __construct($message = NULL, $code = 0, $allowed = NULL)
    {
        parent::__construct($message, $code, $previous);
        $this->allowed = $allowed;
    }

    public function getAllowedCombo()
    {
        return $this->allowed;
    }
}

