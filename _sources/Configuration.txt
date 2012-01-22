Configuration
=============

..  _`configuration options`:

Options
-------

This module provides several configuration options.

..  table:: Options for |project|

    +---------------+-----------+-----------+-------------------------------+
    | Name          | Type      | Default   | Description                   |
    |               |           | value     |                               |
    +===============+===========+===========+===============================+
    | |trigger_gof| | string    | "gof"     | The command to use to start   |
    |               |           |           | or end a Gang of Four game.   |
    +---------------+-----------+-----------+-------------------------------+
    | limit         | integer   | 100       | The game will stop after this |
    |               |           |           | limit is reached. The .       |
    |               |           |           | player(s) with the lowest     |
    |               |           |           | score win the game.           |
    +---------------+-----------+-----------+-------------------------------+
    | pause_delay   | integer   | 5         | How many seconds does the bot |
    |               |           |           | wait after a round ends       |
    |               |           |           | before it starts the next     |
    |               |           |           | round.                        |
    +---------------+-----------+-----------+-------------------------------+
    | start_delay   | integer   | 20        | How many seconds does the bot |
    |               |           |           | wait after enough players     |
    |               |           |           | have joined the game before   |
    |               |           |           | the game actually starts.     |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_ca|  | string    | "ca"      | The command to use to show    |
    |               |           |           | how many cards each player    |
    |               |           |           | has in his hand.              |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_cd|  | string    | "cd"      | The command to use to show    |
    |               |           |           | the last discarded combo.     |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_ch|  | string    | "ch"      | The command to use to choose  |
    |               |           |           | a card to give to the loser   |
    |               |           |           | of the previous round.        |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_jo|  | string    | "jo"      | The command to use to join a  |
    |               |           |           | game after it has been        |
    |               |           |           | created.                      |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_od|  | string    | "od"      | The command to use to show    |
    |               |           |           | playing order.                |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_pa|  | string    | "pa"      | The command to use to pass    |
    |               |           |           | a turn.                       |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_pl|  | string    | "pl"      | The command to use to play a  |
    |               |           |           | combination of cards. [#]_    |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_sc|  | string    | "sc"      | The command to use to display |
    |               |           |           | the current scores.           |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_ti|  | string    | "ti"      | The command to use to show    |
    |               |           |           | for how long a game has been  |
    |               |           |           | running.                      |
    +---------------+-----------+-----------+-------------------------------+
    | |trigger_tu|  | string    | "tu"      | The command to use to show    |
    |               |           |           | whose player's turn it is.    |
    +---------------+-----------+-----------+-------------------------------+

..  warning::
    All triggers should be written without any prefixes. Moreover, triggers
    should only contain alphanumeric characters.

..  [#] Valid combinations include:

    -   a single card, eg. ``g1``
    -   a pair, eg. ``g1y1``
    -   three of a kind, eg. ``g1y1r1``
    -   a straight, eg. ``m1r2y3g4r5``
    -   a flush, eg. ``g1g1g2g2g7``
    -   a full house, eg. ``g1y1r1g2g2``
    -   a straight flush, eg. ``g1g2g3g4g5``
    -   a gang, eg. ``g1g1y1y1`` for the lowest possible gang (a gang of four),
        up to ``g1g1y1y1r1r1m1`` for the highest gang (a gang of seven).

    See the official rules on `Days of Wonder's website`_ for more information
    on when you may play a given combination.


Example
-------

Here, we enable the Gang of Four module at the general configuration level.
Therefore, the game will be available on all networks/servers/channels.
Of course, you can use a more restrictive configuration file if it suits
your needs better.

..  parsed-code:: xml

    <?xml version="1.0"?>
    <configuration
      xmlns="http://localhost/Erebot/"
      version="0.20"
      language="fr-FR"
      timezone="Europe/Paris">

      <modules>
        <!-- Other modules ignored for clarity. -->

        <!--
          Configure the module:
          - the game will be started using the "!gangof4" command.
          - the game will start 2 minutes (120 seconds) after 3 players
            join it (to give time for a fourth player to join the game).
        -->
        <module name="|project|">
          <param name="trigger_create" value="gangof4" />
          <param name="start_delay"    value="120" />
        </module>
      </modules>
    </configuration>


..  |trigger_gof|   replace:: trigger_create
..  |trigger_ca|    replace:: trigger_show_cards
..  |trigger_cd|    replace:: trigger_show_discard
..  |trigger_ch|    replace:: trigger_choose
..  |trigger_jo|    replace:: trigger_join
..  |trigger_od|    replace:: trigger_show_order
..  |trigger_pa|    replace:: trigger_pass
..  |trigger_pl|    replace:: trigger_play
..  |trigger_sc|    replace:: trigger_show_scores
..  |trigger_ti|    replace:: trigger_show_time
..  |trigger_tu|    replace:: trigger_show_turn
..  _`Days of Wonder's website`:
    http://www.daysofwonder.com/gangoffour/en/content/rules/


.. vim: ts=4 et
