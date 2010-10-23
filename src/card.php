<?php

class GoFCard
{
    protected $_value;
    protected $_color;

    const VALUE_1       = 1;
    const VALUE_2       = 2;
    const VALUE_3       = 3;
    const VALUE_4       = 4;
    const VALUE_5       = 5;
    const VALUE_6       = 6;
    const VALUE_7       = 7;
    const VALUE_8       = 8;
    const VALUE_9       = 9;
    const VALUE_10      = 10;
    const VALUE_PHOENIX = 11;
    const VALUE_DRAGON  = 12;

    const COLOR_GREEN   = 1;
    const COLOR_YELLOW  = 2;
    const COLOR_RED     = 3;
    const COLOR_MULTI   = 4;

    public function __construct($card)
    {
        list($this->_value, $this->_color) = $this->_parseCard($card);
        $this->_label = $this->getLabel();
    }

    protected function _parseCard($card)
    {
        if (!is_string($card))
            throw new EGoFInvalidCard($card);

        $colors = array(
            'g' => self::COLOR_GREEN,
            'y' => self::COLOR_YELLOW,
            'r' => self::COLOR_RED,
        );

        $card = strtolower($card);
        switch ($card) {
            case 'm1':
                return array(self::VALUE_1, self::COLOR_MULTI);
            case 'rd':
                return array(self::VALUE_DRAGON, self::COLOR_RED);
            case 'yp':
            case 'gp':
                return array(self::VALUE_PHOENIX, $colors[$card[0]]);
        }

        if (!in_array($card[0], array('g', 'y', 'r')))
            throw new EGoFInvalidCard($card);

        if (!ctype_digit(substr($card, 1)))
            throw new EGoFInvalidCard($card);

        $value = (int) substr($card, 1);
        if ($value >= 1 && $value <= 10)
            return array($value, $colors[$card[0]]);

        throw new EGoFInvalidCard($card);
    }

    public function getLabel()
    {
        $colors = array(
            self::COLOR_GREEN   => 'g',
            self::COLOR_YELLOW  => 'y',
            self::COLOR_RED     => 'r',
            self::COLOR_MULTI   => 'm',
        );

        $color = $colors[$this->_color];
        $value = $this->_value;
        switch ($value) {
            case self::VALUE_DRAGON:
                $value = 'd';
                break;
            case self::VALUE_PHOENIX:
                $value = 'p';
                break;
        }
        return $color.$value;
    }

    public function getColor()
    {
        return $this->_color;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function __toString()
    {
        return $this->_label;
    }

    static public function compareCard(GoFCard &$card1, GofCard &$card2)
    {
        if ($card1->_value != $card2->_value)
            return $card1->_value - $card2->_value;
        return $card1->_color - $card2->_color;
    }
}

