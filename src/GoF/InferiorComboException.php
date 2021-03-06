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

/**
 * \brief
 *      Raised when trying to play a combination
 *      that is inferior to the currently leading
 *      one.
 */
class InferiorComboException extends \Erebot\Module\GoF\Exception
{
    /// Array of allowed (superior) combinations.
    protected $allowed;

    public function __construct($message = null, $code = 0, $allowed = null)
    {
        parent::__construct($message, $code);
        $this->allowed = $allowed;
    }

    /**
     * Returns a list of superior combinations
     * that may be played, in known.
     *
     * \retval list|null
     *      List of superior combinations,
     *      if known.
     */
    public function getAllowedCombo()
    {
        return $this->allowed;
    }
}
