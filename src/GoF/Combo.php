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

class Combo implements \ArrayAccess, \SeekableIterator, \Countable
{
    const COMBO_SINGLE          = 1;
    const COMBO_PAIR            = 2;
    const COMBO_TRIO            = 3;
    const COMBO_STRAIGHT        = 10;
    const COMBO_FLUSH           = 20;
    const COMBO_FULL_HOUSE      = 25;
    const COMBO_STRAIGHT_FLUSH  = 30;
    const COMBO_GANG            = 50;

    private $position;
    protected $cards;
    protected $type;

    public function __construct(\Erebot\Module\GoF\Card $card /* , ... */)
    {
        $cards = func_get_args();
        $values = array();
        $colors = array();
        foreach ($cards as $card) {
            if (!is_object($card) || !($card instanceof \Erebot\Module\GoF\Card)) {
                throw new \Erebot\Module\GoF\InvalidComboException("Not a card");
            }
            $value = $card->getValue();
            if (!isset($values[$value])) {
                $values[$value] = array();
            }
            $values[$value][] = $card;

            $color = $card->getColor();
            if (!isset($colors[$color])) {
                $colors[$color] = array();
            }
            $colors[$color][] = $card;
        }

        $nbCards = count($cards);
        if ($nbCards < 1 || $nbCards > 7) {
            throw new \Erebot\Module\GoF\InvalidComboException(
                "Bad cards count"
            );
        }

        $this->position = 0;

        // Sort cards by decreasing values and colors.
        usort($cards, array('\\Erebot\\Module\\GoF\\Card', 'compareCards'));
        $this->cards = array_reverse($cards);

        $nbValues = count($values);
        if ($nbValues == 1) {
            // Gangs.
            if ($nbCards >= 4) {
                $this->type = self::COMBO_GANG;
            } else {
                // self::COMBO_SINGLE, PAIR, TRIO map directly
                // to their number of cards, so it's easy.
                $this->type = $nbCards;
            }

            // This is it, we successfully identified the combo.
            return;
        }

        // Detect invalid combos.
        if ((!$nbValues != 1 && $nbCards <= 3) || $nbCards != 5) {
            throw new \Erebot\Module\GoF\InvalidComboException("WTF is that?");
        }

        foreach ($values as $val) {
            // At this point, it's impossible to have more than three times
            // the same value (which would be a gang but this was already
            // handled above). This prevents combos such as 4 X & 1 single Y.
            if (count($val) > 3) {
                throw new \Erebot\Module\GoF\InvalidComboException("WTF is that?");
            }
        }

        // Full houses.
        if ($nbValues == 2) {
            /* Since the cards have already been sorted,
             * there are only two possible arrangements:
             * - trio first, then pair,
             * - the other way around.
             * We must make sure the trio appears first. */

            // If the pair is first, swap the cards.
            if (count($values[$this->cards[0]->getValue()]) == 2) {
                $portion = array_splice($this->cards, 2);
                $this->cards = array_merge($portion, $this->cards);
            }
            $this->type = self::COMBO_FULL_HOUSE;
            return;
        }

        // Restricted cards for multi-cards combos.
        if (isset($values[\Erebot\Module\GoF\Card::VALUE_PHOENIX]) ||
            isset($values[\Erebot\Module\GoF\Card::VALUE_DRAGON])) {
            throw new \Erebot\Module\GoF\InvalidComboException("Restricted card");
        }

        // Other types.
        $this->type = 0;

        // For (straight) flushes, m1 can assume any color.
        // We hence remove it from the equation.
        unset($colors[\Erebot\Module\GoF\Card::COLOR_MULTI]);

        // Flush or straight flush.
        if (count($colors) == 1) {
            $this->type |= self::COMBO_FLUSH;
        }

        // Straight or straight flush.
        if ($nbValues == 5) {
            $previous = $this->cards[0]->getValue();
            for ($i = 1; $i < 5; $i++) {
                $value = $this->cards[$i]->getValue();
                if ($value != $previous - 1) {
                    $previous = null;
                    break;
                }
                $previous = $value;
            }
            if ($previous !== null) {
                $this->type |= self::COMBO_STRAIGHT;
            }
        }

        // Check whether we identified a valid type.
        if ($this->type) {
            return;
        }

        throw new \Erebot\Module\GoF\InvalidComboException("WTF is that?");
    }

    public function getType()
    {
        return $this->type;
    }

    public static function compareCombos(
        \Erebot\Module\GoF\Combo $comboA,
        \Erebot\Module\GoF\Combo $comboB
    ) {
        if ($comboA->type != $comboB->type) {
            $specials = array(
                self::COMBO_SINGLE,
                self::COMBO_PAIR,
                self::COMBO_TRIO,
            );
            if ((
                    in_array($comboA->type, $specials) &&
                    $comboB->type != self::COMBO_GANG
                ) || (
                    in_array($comboB->type, $specials) &&
                    $comboA->type != self::COMBO_GANG
                )) {
                throw new \Erebot\Module\GoF\NotComparableException();
            }
            return $comboA->type - $comboB->type;
        }

        $nbCardsA = count($comboA->cards);
        $nbCardsB = count($comboB->cards);
        if ($comboA->type == self::COMBO_GANG &&
            $nbCardsA != $nbCardsB) {
            return $nbCardsA - $nbCardsB;
        }

        assert($nbCardsA == $nbCardsB);
        for ($i = 0; $i < $nbCardsA; $i++) {
            $cmp = \Erebot\Module\GoF\Card::compareCards(
                $comboA->cards[$i],
                $comboB->cards[$i]
            );
            if ($cmp) {
                return $cmp;
            }
        }
        return 0;
    }

    // ArrayAccess interface.
    public function offsetExists($offset)
    {
        return isset($this->cards[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->cards[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception('Write-access forbidden');
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('Write-access forbidden');
    }

    // SeekableIterator interface.
    public function seek($position)
    {
        $this->position = $position;
    }

    public function current()
    {
        return $this->cards[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return ($this->position < count($this->cards));
    }

    // Countable interface.
    public function count()
    {
        return count($this->cards);
    }
}
