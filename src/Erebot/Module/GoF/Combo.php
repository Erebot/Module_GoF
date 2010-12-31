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

class       Erebot_Module_GoF_Combo
implements  ArrayAccess,
            Iterator
{
    const COMBO_SINGLE          = 1;
    const COMBO_PAIR            = 2;
    const COMBO_TRIO            = 3;
    const COMBO_STRAIGHT        = 10;
    const COMBO_FLUSH           = 20;
    const COMBO_FULL_HOUSE      = 25;
    const COMBO_STRAIGHT_FLUSH  = 30;
    const COMBO_GANG            = 50;

    private $_position;
    protected $_cards;
    protected $_type;

    public function __construct(Erebot_Module_GoF_Card $card /* , ... */)
    {
        $cards = func_get_args();
        $values = array();
        $colors = array();
        foreach ($cards as &$card) {
            if (!is_object($card) || !($card instanceof Erebot_Module_GoF_Card))
                throw new Erebot_Module_GoF_InvalidComboException("Not a card");
            $value = $card->getValue();
            if (!isset($values[$value]))
                $values[$value] = array();
            $values[$value][] = $card;

            $color = $card->getColor();
            if (!isset($colors[$color]))
                $colors[$color] = array();
            $colors[$color][] = $card;
        }
        unset($card);

        $nbCards = count($cards);
        if ($nbCards < 1 || $nbCards > 7)
            throw new Erebot_Module_GoF_InvalidComboException("Bad cards count");

        $this->_position = 0;

        // Sort cards by decreasing values and colors.
        usort($cards, array('Erebot_Module_GoF_Card', 'compareCards'));
        $this->_cards = array_reverse($cards);

        $nbValues = count($values);
        if ($nbValues == 1) {
            // Gangs.
            if ($nbCards >= 4)
                $this->_type = self::COMBO_GANG;
            // self::COMBO_SINGLE, PAIR, TRIO map directly
            // to their number of cards, so it's easy.
            else
                $this->_type = $nbCards;

            // This is it, we successfully identified the combo.
            return;
        }

        // Detect invalid combos.
        if ((!$nbValues != 1 && $nbCards <= 3) || $nbCards != 5)
            throw new Erebot_Module_GoF_InvalidComboException("WTF is that?");

        // Restricted cards for multi-cards combos.
        if (isset($values[Erebot_Module_GoF_Card::VALUE_PHOENIX]) ||
            isset($values[Erebot_Module_GoF_Card::VALUE_DRAGON]))
            throw new Erebot_Module_GoF_InvalidComboException("Restricted card");

        // Full houses.
        if ($nbValues == 2) {
            /* Since the cards have already been sorted,
             * there are only two possible arrangements:
             * - trio first, then pair,
             * - the other way around.
             * We must make sure the trio appears first. */

            // If the pair is first, swap the cards.
            if (count($values[$this->_cards[0]->getValue()]) == 2) {
                $portion = array_splice($this->_cards, 2);
                $this->_cards = array_merge($portion, $this->_cards);
            }
            $this->_type = self::COMBO_FULL_HOUSE;
            return;
        }

        // Other types.
        $nbColors = count($colors);
        $this->_type = 0;

        // Flush or straight flush.
        if ($nbColors == 1)
            $this->_type |= self::COMBO_FLUSH;

        // Straight or straight flush.
        if ($nbValues == 5) {
            $previous = $this->_cards[0]->getValue();
            for ($i = 1; $i < 5; $i++) {
                $value = $this->_cards[$i]->getValue();
                if ($value != $previous - 1) {
                    $previous = NULL;
                    break;
                }
                $previous = $value;
            }
            if ($previous !== NULL)
                $this->_type |= self::COMBO_STRAIGHT;
        }

        // Check whether we identified a valid type.
        if ($this->_type)
            return;

        throw new Erebot_Module_GoF_InvalidComboException("WTF is that?");
    }

    public function getType()
    {
        return $this->_type;
    }

    static public function compareCombos(
        Erebot_Module_GoF_Combo &$comboA,
        Erebot_Module_GoF_Combo &$comboB
    )
    {
        if ($comboA->_type != $comboB->_type) {
            $specials = array(
                self::COMBO_SINGLE,
                self::COMBO_PAIR,
                self::COMBO_TRIO,
            );
            if ((
                    in_array($comboA->_type, $specials) &&
                    $comboB->_type != self::COMBO_GANG
                ) || (
                    in_array($comboB->_type, $specials) &&
                    $comboA->_type != self::COMBO_GANG
                ))
                throw new Erebot_Module_GoF_NotComparableException();
            return $comboA->_type - $comboB->_type;
        }

        $nbCardsA = count($comboA->_cards);
        $nbCardsB = count($comboA->_cards);
        if ($comboA->_type == self::COMBO_GANG &&
            $nbCardsA != $nbCardsB)
            return $nbCardsA - $nbCardsB;

        assert($nbCardsA == $nbCardsB);
        for ($i = 0; $i < $nbCards; $i++) {
            $cmp = Erebot_Module_GoF_Card::compareCards(
                $comboA->_cards[$i],
                $comboB->_cards[$i]
            );
            if ($cmp)
                return $cmp;
        }
        return 0;
    }

    // ArrayAccess interface.
    public function offsetExists($offset)
    {
        return isset($this->_cards[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_cards[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new Exception('Write-access forbidden');
    }

    public function offsetUnset($offset)
    {
        throw new Exception('Write-access forbidden');
    }

    // Iterator interface.
    public function current()
    {
        return $this->_cards[$this->_position];
    }

    public function key()
    {
        return $this->_position;
    }

    public function next()
    {
        $this->_position++;
    }

    public function rewind()
    {
        $this->_position = 0;
    }

    public function valid()
    {
        return ($this->_position < count($this->_cards));
    }
}

