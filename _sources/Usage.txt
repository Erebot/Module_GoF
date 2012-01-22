Usage
=====

This section assumes default values are used for all triggers.
Please refer to :ref:`configuration options <configuration options>`
for more information on how to customize triggers.

Also, knowledge of the rules for the Gang of Four game is assumed.
The full rules for the game can be found (in multiple languages) on
`Days of Wonder's website`_.


Provided commands
-----------------

This module provides the following commands:

..  table:: Commands provided by |project|

    +---------------------------+-------------------------------------------+
    | Command                   | Description                               |
    +===========================+===========================================+
    | ``!gof``                  | Start a new Gang of Four game.            |
    +---------------------------+-------------------------------------------+
    | ``!gof cancel`` or        | Stop a currently running Gang of Four     |
    | ``!gof end`` or           | game. Can only be used by the person who  |
    | ``!gof off`` or           | started the game in the first place.      |
    | ``!gof stop``             |                                           |
    +---------------------------+-------------------------------------------+
    | ``ca``                    | Display the number of remaining cards in  |
    |                           | each player's hand.                       |
    +---------------------------+-------------------------------------------+
    | ``cd``                    | Display the last played (and thus         |
    |                           | discarded) card.                          |
    +---------------------------+-------------------------------------------+
    | :samp:`ch {card}`         | Choose a card to give to the loser of the |
    |                           | previous round. Can only be used at the   |
    |                           | end of a round by the winner of the       |
    |                           | previous round.                           |
    +---------------------------+-------------------------------------------+
    | ``jo``                    | Join a currently running Uno game.        |
    +---------------------------+-------------------------------------------+
    | ``od``                    | Display playing order.                    |
    +---------------------------+-------------------------------------------+
    | ``pa``                    | Pass instead of playing.                  |
    +---------------------------+-------------------------------------------+
    | :samp:`pl {combo}`        | Play the given *combo* of cards (see      |
    |                           | :ref:`mnemonics` for the syntax used).    |
    |                           | Eg. ``pl g1y1`` to play a pair of 1s,     |
    |                           | containing a "Green 1" and a "Yellow 1".  |
    +---------------------------+-------------------------------------------+
    | ``sc``                    | Display the score of each player involved |
    |                           | in the current game.                      |
    +---------------------------+-------------------------------------------+
    | ``ti``                    | Display information on how long the       |
    |                           | current game has been running for.        |
    +---------------------------+-------------------------------------------+
    | ``tu``                    | Display the name of the player whose turn |
    |                           | it is to play.                            |
    +---------------------------+-------------------------------------------+


..  _`mnemonics`:

Mnemonics for cards
-------------------

The general format used to refer to cards is the first letter of the card's
color (in english) followed by the card's figure.

The following colors are available:

-   **g**\ reen
-   **y**\ ellow
-   **r**\ ed
-   **m**\ ulti

The following figures are available:

-   Numbers from 1 to 10 (inclusive).
-   Phoenixes.
-   Dragon.

The following table lists a few examples of valid mnemnics with the full name
of the card they refer to:

..  table:: Valid mnemonics for cards

    +-----------+-----------------------+
    | Mnemonic  | Actual card           |
    +===========+=======================+
    | ``g1``    | "Green 1"             |
    +-----------+-----------------------+
    | ``m1``    | "Multicolored 1"      |
    +-----------+-----------------------+
    | ``r10``   | "Red 10"              |
    +-----------+-----------------------+
    | ``gp``    | "Green Phoenix"       |
    +-----------+-----------------------+
    | ``yp``    | "Yellow Phoenix"      |
    +-----------+-----------------------+
    | ``rd``    | "Red Dragon"          |
    +-----------+-----------------------+

Not all combinations of colors and figures are valid. In particular, there is
only one multicolored figure, one red dragon, a green and a yellow phoenix.


..  _`Days of Wonder's website`:
    http://www.daysofwonder.com/gangoffour/en/content/rules/

..  vim: ts=4 et
