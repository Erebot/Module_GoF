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

class   Erebot_Module_GoF
extends Erebot_Module_Base
{
    protected $_chans;
    protected $_creator;

    const COLOR_RED                 = '00,04';
    const COLOR_GREEN               = '00,03';
    const COLOR_YELLOW              = '01,08';

    public function _reload($flags)
    {
        if ($flags & self::RELOAD_MEMBERS) {
            $this->_chans    = array();
        }

        if ($flags & self::RELOAD_HANDLERS) {
            $registry   = $this->_connection->getModule(
                'Erebot_Module_TriggerRegistry'
            );
            $matchAny  = Erebot_Utils::getVStatic($registry, 'MATCH_ANY');

            if (!($flags & self::RELOAD_INIT)) {
                $this->_connection->removeEventHandler(
                    $this->_creator['handlerCreate']
                );
                $this->_connection->removeEventHandler(
                    $this->_creator['handlerStop']
                );
                $registry->freeTriggers($this->_creator['trigger'], $matchAny);
            }

            $triggerCreate               =
                $this->parseString('trigger_create', 'gof');
            $this->_creator['trigger']   =
                $registry->registerTriggers($triggerCreate, $matchAny);
            if ($this->_creator['trigger'] === NULL) {
                $translator = $this->getTranslator(FALSE);
                throw new Exception($translator->gettext(
                    'Could not register Gang of Four creation trigger'
                ));
            }

            $this->_creator['handlerCreate'] = new Erebot_EventHandler(
                new Erebot_Callable(array($this, 'handleCreate')),
                new Erebot_Event_Match_All(
                    new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                    new Erebot_Event_Match_TextStatic($triggerCreate, TRUE)
                )
            );

            $this->_creator['handlerStop'] = new Erebot_EventHandler(
                new Erebot_Callable(array($this, 'handleStop')),
                new Erebot_Event_Match_All(
                    new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                    new Erebot_Event_Match_TextWildcard($triggerCreate.' &', TRUE)
                )
            );

            $this->_connection->addEventHandler($this->_creator['handlerCreate']);
            $this->_connection->addEventHandler($this->_creator['handlerStop']);
        }
    }

    protected function _unload()
    {
        foreach ($this->_chans as $entry) {
            if (isset($entry['timer']))
                $this->removeTimer($entry['timer']);
        }
    }

    protected function getLogo()
    {
        return  Erebot_Styling::CODE_BOLD.
                Erebot_Styling::CODE_COLOR.'03Gang '.
                Erebot_Styling::CODE_COLOR.'08of '.
                Erebot_Styling::CODE_COLOR.'04Four'.
                Erebot_Styling::CODE_COLOR.
                Erebot_Styling::CODE_BOLD;
    }

    protected function sendMessage(
        $targets,
        $message,
        $type = self::MSG_TYPE_PRIVMSG
    )
    {
        if (!is_array($targets) && $this->_connection->isChannel($targets))
            $message = $this->getLogo().': '.$message;
        return parent::sendMessage($targets, $message, $type);
    }

    protected function getColoredCard($color, $text)
    {
        $text       = ' '.$text.' ';
        $colorCodes =   array(
                            'r' => self::COLOR_RED,
                            'g' => self::COLOR_GREEN,
                            'y' => self::COLOR_YELLOW,
                        );

        if (!isset($colorCodes[$color]))
            throw new Exception('Unknown color!');

        return  Erebot_Styling::CODE_COLOR.$colorCodes[$color].
                Erebot_Styling::CODE_BOLD.$text.
                Erebot_Styling::CODE_BOLD.
                Erebot_Styling::CODE_COLOR;
    }

    protected function cleanup($chan)
    {
        $registry   = $this->_connection->getModule(
            'Erebot_Module_TriggerRegistry'
        );

        $infos      =&  $this->_chans[$chan];
        foreach ($infos['handlers'] as $handler)
            $this->_connection->removeEventHandler($handler);

        $registry->freeTriggers($infos['triggers_token'], $chan);

        if ($infos['timer'] !== NULL)
            $this->_connection->removeTimer($infos['timer']);

        unset($infos);
        unset($this->_chans[$chan]);
    }

    protected function getCardText($card)
    {
        if ($card instanceof Erebot_Module_GoF_Card)
            $card = $card->getLabel();

        $colors = array(
            'r' => 'Red',
            'g' => 'Green',
            'y' => 'Yellow',
        );

        $texts = array(
            'd' => 'Dragon',
            'p' => 'Phoenix',
        );

        if ($card == 'm1')
            return
                Erebot_Styling::CODE_BOLD.
                Erebot_Styling::CODE_COLOR.
                self::COLOR_GREEN.' Mu'.
                Erebot_Styling::CODE_COLOR.
                self::COLOR_YELLOW.'lti'.
                Erebot_Styling::CODE_COLOR.
                self::COLOR_RED.' 1 '.
                Erebot_Styling::CODE_COLOR.
                Erebot_Styling::CODE_BOLD;

        if (isset($texts[$card[1]]))
            return $this->getColoredCard(
                $card[0],
                $colors[$card[0]].' '.$texts[$card[1]]
            );

        return $this->getColoredCard(
            $card[0],
            $colors[$card[0]].' '.substr($card, 1)
        );
    }

    public function handleCreate(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $nick       = $event->getSource();
        $chan       = $event->getChan();
        $translator = $this->getTranslator($chan);

        if (isset($this->_chans[$chan])) {
            $creator = (string) $this->_chans[$chan]['game']->getCreator();
            $msg = $translator->gettext(
                'A game managed by <b><var name="admin"/></b> '.
                'is already in progress here. '.
                'Say "<b><var name="trigger"/></b>" to join it.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('admin',   $creator);
            $tpl->assign('trigger', $this->_chans[$chan]['triggers']['join']);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $limit = $this->parseInt('limit', 100);
        if ($limit < 10) {
            $msg = $translator->gettext(
                'Bad limit in the configuration file (<var name="limit"/>). '.
                'The game cannot start.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('limit',   $limit);
            $tpl->assign('logo',    $this->getLogo());
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $registry   =   $this->_connection->getModule(
            'Erebot_Module_TriggerRegistry'
        );
        $triggers   =   array(
            'choose'       => $this->parseString('trigger_choose',       'ch'),
            'join'         => $this->parseString('trigger_join',         'jo'),
            'pass'         => $this->parseString('trigger_pass',         'pa'),
            'play'         => $this->parseString('trigger_play',         'pl'),
            'show_cards'   => $this->parseString('trigger_show_cards',   'ca'),
            'show_discard' => $this->parseString('trigger_show_discard', 'cd'),
            'show_order'   => $this->parseString('trigger_show_order',   'od'),
            'show_scores'  => $this->parseString('trigger_show_scores',  'sc'),
            'show_time'    => $this->parseString('trigger_show_time',    'ti'),
            'show_turn'    => $this->parseString('trigger_show_turn',    'tu'),
        );

        $token  = $registry->registerTriggers($triggers, $chan);
        if ($token === NULL) {
            $msg = $translator->gettext(
                'Unable to register triggers for the game!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo', $this->getLogo());
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $this->_chans[$chan] = array();
        $infos =& $this->_chans[$chan];
        $infos['triggers'] =& $triggers;

        $infos['handlers']['choose']        =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleChoose')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextWildcard($infos['triggers']['choose'].' *', NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['join']          =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleJoin')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['join'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['pass']          =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handlePass')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['pass'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['play']          =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handlePlay')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextWildcard($infos['triggers']['play'].' *', NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['show_cards']    =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleShowCardsCount')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['show_cards'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['show_discard']  =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleShowDiscard')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['show_discard'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['show_order']    =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleShowOrder')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['show_order'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['show_scores']   =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleShowScores')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['show_scores'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['show_time']     =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleShowTime')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['show_time'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        $infos['handlers']['show_turn'] =   new Erebot_EventHandler(
            new Erebot_Callable(array($this, 'handleShowTurn')),
            new Erebot_Event_Match_All(
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_ChanText'),
                new Erebot_Event_Match_TextStatic($infos['triggers']['show_turn'], NULL),
                new Erebot_Event_Match_Chan($chan)
            )
        );

        foreach ($infos['handlers'] as $handler)
            $this->_connection->addEventHandler($handler);

        $tracker    = $this->_connection->getModule('Erebot_Module_IrcTracker');
        $deck       = new Erebot_Module_GoF_Deck_Official();
        $creator    = $tracker->startTracking($nick);

        $infos['triggers_token']    =   $token;
        $infos['triggers']          =&  $triggers;
        $infos['game']              =   new Erebot_Module_GoF_Game($creator, $deck);
        $infos['timer']             =   NULL;
        $infos['limit']             =   $limit;

        $msg = $translator->gettext(
            'Ok! A new game has been created in <var name="chan"/>. '.
            'It will not stop until someone gets '.
            'at least <b><var name="limit"/></b> points. '.
            'Say "<b><var name="trigger"/></b>" to join it.'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',    $this->getLogo());
        $tpl->assign('chan',    $chan);
        $tpl->assign('limit',   $limit);
        $tpl->assign('trigger', $infos['triggers']['join']);
        $this->sendMessage($chan, $tpl->render());
        $event->preventDefault(TRUE);
    }

    public function handleStop(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $nick       = $event->getSource();
        $chan       = $event->getChan();
        $translator = $this->getTranslator($chan);
        $end        = in_array(
            $event->getText()->getTokens(1),
            array('end', 'off', 'stop', 'cancel')
        );

        if (!$end) return;

        if (!isset($this->_chans[$chan])) {
            $msg = $translator->gettext(
                'No game has been started in <var name="chan"/> yet! '.
                'Nothing to stop.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('chan',    $chan);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $creator = (string) $this->_chans[$chan]['game']->getCreator();
        if (!$this->_connection->irccmp($creator, $nick)) {
            $msg = $translator->gettext(
                '<b><var name="admin"/></b> stopped the game!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('admin',   $nick);
            $this->sendMessage($chan, $tpl->render());
            $this->cleanup($chan);
            return $event->preventDefault(TRUE);
        }

        $msg = $translator->gettext(
            'Well tried <var name="nick"/>! '.
            'But only <b><var name="admin"/></b> can stop this game!'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',    $this->getLogo());
        $tpl->assign('nick',    $nick);
        $tpl->assign('admin',   $creator);
        $this->sendMessage($chan, $tpl->render());
        return $event->preventDefault(TRUE);
    }

    protected function compareCards($a, $b)
    {
        $levels = array();
        for ($i = 1; $i <= 10; $i++)
            $levels[] = "$i";
        $levels[] = 'p';
        $levels[] = 'd';
        $levels = array_flip($levels);

        $lvlA = $levels[substr($a, 1)];
        $lvlB = $levels[substr($b, 1)];
        if ($lvlA != $lvlB)
            return $lvlA - $lvlB;

        $colors = array('g', 'y', 'r', 'm');
        $colors = array_flip($colors);
        return $colors[$a[0]] - $colors[$b[0]];
    }

    public function startGame(Erebot_Interface_Timer $timer, $chan)
    {
        if (!isset($this->_chans[$chan])) return;
        $infos =& $this->_chans[$chan];

        $translator     = $this->getTranslator($chan);
        $infos['timer'] = NULL;
        $lastLoser      = $infos['game']->getLastLoser();

        // First round, actually start the game.
        if ($lastLoser === NULL)
            $infos['game']->start();
        $starter        = $infos['game']->getCurrentPlayer();

        if ($lastLoser !== NULL) {
            $bestCard   = $lastLoser->getHand()->getBestCard();
            $playtime   = $translator->formatDuration(
                $infos['game']->getElapsedTime()
            );

            $msg = $translator->gettext(
                'This is round #<b><var name="round"/></b>, starting '.
                'after <var name="playtime"/>. '.
                '<b><var name="last_winner"/></b>, you must now choose '.
                'a card to give to <b><var name="last_loser"/></b>. '.
                'You will receive <var name="card"/>. Please choose '.
                'with: "<b><var name="trigger"/></b> &lt;card&gt;".'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',        $this->getLogo());
            $tpl->assign('round',       $infos['game']->getNbRounds());
            $tpl->assign('playtime',    $playtime);
            $tpl->assign('last_winner', (string) $starter->getToken());
            $tpl->assign('last_loser',  (string) $lastLoser->getToken());
            $tpl->assign('card',        $this->getCardText($bestCard));
            $tpl->assign('trigger',     $infos['triggers']['choose']);
            $this->sendMessage($chan, $tpl->render());
        }
        else {
            $msg = $translator->gettext(
                'The game starts now. '.
                '<b><var name="starter"/></b>, you must start this game. '.
                'If you have the <var name="m1"/>, you MUST play it (alone '.
                'or in a combination).'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('starter', (string) $starter->getToken());
            $tpl->assign('m1',      $this->getCardText('m1'));
            $this->sendMessage($chan, $tpl->render());
        }

        foreach ($infos['game']->getPlayers() as $player) {
            if ($player == $lastLoser) continue;
            $this->_sendCards($chan, $player);
        }
    }

    public function handleChoose(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan   = $event->getChan();
        $nick   = $event->getSource();
        if (!isset($this->_chans[$chan])) return;

        $infos      =&  $this->_chans[$chan];
        $current    =   $infos['game']->getCurrentPlayer();
        if ($current === FALSE || $this->_connection->irccasecmp(
            $nick, (string) $current->getToken()))
            return;

        $translator = $this->getTranslator($chan);
        try {
            $card = Erebot_Module_GoF_Card::fromLabel(
                strtolower($event->getText()->getTokens(1))
            );
        }
        catch (Erebot_Module_GoF_InvalidCardException $e) {
            $msg = $translator->gettext(
                'Hmm? What card was that '.
                '<b><var name="nick"/></b>?'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo', $this->getLogo());
            $tpl->assign('nick', $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $loser = $infos['game']->getLastLoser();

        try {
            $best = $infos['game']->chooseCard($card);
        }
        catch (Erebot_Module_GoF_InternalErrorException $e) {
            $msg = $translator->gettext(
                'Uh <b><var name="nick"/></b>? '.
                'No cards need to be exchanged for now...'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo', $this->getLogo());
            $tpl->assign('nick', $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }
        catch( Erebot_Module_GoF_NoSuchCardException $e) {
            $msg = $translator->gettext(
                'Sorry <b><var name="nick"/></b>, '.
                'you do not have that card! (<var name="card"/>)'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo', $this->getLogo());
            $tpl->assign('nick', $nick);
            $tpl->assign('card', $card);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $msg = $translator->gettext(
            'Exchanges: '.
            '<b><var name="winner"/></b> receives a <var name="received"/> '.
            'and gives a <var name="given"/> to <b><var name="loser"/></b>. '.
            '<b><var name="winner"/></b>, you may now start this round.'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',        $this->getLogo());
        $tpl->assign('winner',      $nick);
        $tpl->assign('loser',       (string) $loser->getToken());
        $tpl->assign('received',    $this->getCardText($best));
        $tpl->assign('given',       $this->getCardText($card));
        $this->sendMessage($chan, $tpl->render());

        $this->_sendCards($chan, $current);
        $this->_sendCards($chan, $loser);
        $event->preventDefault(TRUE);
    }

    public function handleJoin(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $tracker    = $this->_connection->getModule(
            'Erebot_Module_IrcTracker'
        );
        $nick       = $event->getSource();
        $chan       = $event->getChan();

        if (!isset($this->_chans[$chan])) return;
        $infos      =&  $this->_chans[$chan];
        $translator =   $this->getTranslator($chan);

        foreach ($infos['game']->getPlayers() as $player) {
            if (!$this->_connection->irccasecmp(
                $nick, (string) $player->getToken())) {
                $msg = $translator->gettext(
                    'You are already part of that game '.
                    '<b><var name="nick"/></b>!'
                );
                $tpl = new Erebot_Styling($msg, $translator);
                $tpl->assign('logo', $this->getLogo());
                $tpl->assign('nick', $nick);
                $this->sendMessage($chan, $tpl->render());
                return $event->preventDefault(TRUE);
            }
        }

        try {
            $token = $tracker->startTracking($nick);
            $player = $infos['game']->join($token);
        }
        catch (Erebot_Module_GoF_EnoughPlayersException $e) {
            $msg = $translator->gettext(
                'This game may only be played by 3-4 players '.
                'and you cannot join it once it started.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo', $this->getLogo());
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $msg = $translator->gettext(
            '<b><var name="nick"/></b> joins the game.'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo', $this->getLogo());
        $tpl->assign('nick', $nick);
        $this->sendMessage($chan, $tpl->render());

        // If we have enough players, start the game.
        if (count($infos['game']) >= 3) {
            $startDelay = $this->parseInt('start_delay', 20);
            if ($startDelay < 0)
                $startDelay = 20;

            $infos['timer'] = new Erebot_Timer(
                new Erebot_Callable(array($this, 'startGame')),
                $startDelay,
                FALSE,
                array($chan)
            );
            $this->addTimer($infos['timer']);

            $msg = $translator->gettext(
                'The game will start in <var name="delay"/> seconds.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('delay', $startDelay);
            $this->sendMessage($chan, $tpl->render());
        }
        $event->preventDefault(TRUE);
    }

    public function handlePass(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan   = $event->getChan();
        $nick   = $event->getSource();
        if (!isset($this->_chans[$chan])) return;

        $translator = $this->getTranslator($chan);
        $infos      =&  $this->_chans[$chan];
        $current    =   $infos['game']->getCurrentPlayer();
        if ($current === FALSE || $this->_connection->irccasecmp(
            $nick, (string) $current->getToken()))
            return;

        try {
            $infos['game']->pass();
        }
        catch (Erebot_Module_GoF_WaitingForCardException $e) {
            $msg = $translator->gettext(
                'Proceed with the card exchange first. '.
                'Use <b><var name="trigger"/> &lt;card&gt;</b> to select '.
                'the &lt;card&gt; to give away.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('trigger', $infos['triggers']['choose']);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }
        catch (Erebot_Module_GoF_InternalErrorException $e) {
            $msg = $translator->gettext(
                'Now is not the time to pass '.
                '<b><var name="nick"/></b>!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $msg = $translator->gettext(
            '<b><var name="nick"/></b> passes turn.'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',    $this->getLogo());
        $tpl->assign('nick',    $nick);
        $this->sendMessage($chan, $tpl->render());
        $this->_showTurn($chan, NULL);
        $event->preventDefault(TRUE);
    }

    public function handlePlay(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan   = $event->getChan();
        $nick   = $event->getSource();
        $move   = strtolower(
            str_replace(' ', '', $event->getText()->getTokens(1))
        );
        if (!isset($this->_chans[$chan])) return;

        $translator = $this->getTranslator($chan);
        $infos      =&  $this->_chans[$chan];
        $current    =   $infos['game']->getCurrentPlayer();
        if ($current === FALSE || $this->_connection->irccasecmp(
            $nick, (string) $current->getToken()))
            return;

        if (!preg_match('/^(?:[gyr][0-9]+|m1|gp|yp|rd)+$/', $move)) {
            $msg = $translator->gettext(
                'Hmm? What move was that '.
                '<b><var name="nick"/></b>?'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        // Build the corresponding cards.
        preg_match_all('/(?:[gyr][0-9]+|m1|gp|yp|rd)/', $move, $matches);
        try {
            $cards = array_map(
                array('Erebot_Module_GoF_Card', 'fromLabel'),
                $matches[0]
            );
        }
        catch (Erebot_Module_GoF_InvalidCardException $e) {
            $msg = $translator->gettext(
                'Hmm? What card was that '.
                '<b><var name="nick"/></b>?'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        // Build a combo from those cards.
        $reflector = new ReflectionClass('Erebot_Module_GoF_Combo');
        try {
            $combo = $reflector->newInstanceArgs($cards);
        }
        catch (Erebot_Module_GoF_InvalidComboException $e) {
            $msg = $translator->gettext(
                'Hmm? What move was that '.
                '<b><var name="nick"/></b>?'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        // Try to play that combo.
        try {
            $endOfRound = $infos['game']->play($combo);
        }
        catch (Erebot_Module_GoF_InternalErrorException $e) {
            $msg = $translator->gettext(
                'Now is not the time to play '.
                '<b><var name="nick"/></b>!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }
        catch (Erebot_Module_GoF_WaitingForCardException $e) {
            $msg = $translator->gettext(
                'Proceed with the card exchange first. '.
                'Use <b><var name="trigger"/> &lt;card&gt;</b> to select '.
                'the &lt;card&gt; to give away.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('trigger', $infos['triggers']['choose']);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }
        catch (Erebot_Module_GoF_InferiorComboException $e) {
            $msg = $translator->gettext(
                'Sorry <b><var name="nick"/></b>, '.
                'but this is not enough to take the leadership!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }
        catch (Erebot_Module_GoF_StartWithMulti1Exception $e) {
            $msg = $translator->gettext(
                'This is the first round and '.
                'you have the <var name="m1"/> <b><var name="nick"/></b>. '.
                'You <b>must</b> play it alone or in a combination!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $tpl->assign('m1',      $this->getCardText('m1'));
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }
        catch (Erebot_Module_GoF_NoSuchCardException $e) {
            $msg = $translator->gettext(
                'You do not have the cards required '.
                'for that move <b><var name="nick"/></b>!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }
        catch (Erebot_Module_GoF_NotComparableException $e) {
            $msg = $translator->gettext(
                '<b><var name="nick"/></b>, '.
                'you cannot play <b><var name="type"/></b> right now!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',            $this->getLogo());
            $tpl->assign('nick',            $nick);
            $tpl->assign('type',            $this->_qualify($chan, $combo));
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $cards = array();
        foreach ($combo as $card)
            $cards[] = $this->getCardText($card);

        if (count($current->getHand()) == 1)
            $msg = $translator->gettext(
                '<b><var name="nick"/></b> plays <var name="type"/>: '.
                '<for from="cards" item="card" separator=" "><var '.
                'name="card"/></for> - This is <b><var name="nick"/></b>\'s '.
                'last card!'
            );
        else
            $msg = $translator->gettext(
                '<b><var name="nick"/></b> plays <var name="type"/>: '.
                '<for from="cards" item="card" separator=" "><var '.
                'name="card"/></for>'
            );

        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',    $this->getLogo());
        $tpl->assign('nick',    $nick);
        $tpl->assign('type',    $this->_qualify($chan, $combo));
        $tpl->assign('cards',   $cards);
        $this->sendMessage($chan, $tpl->render());

        // We have a winner.
        if ($endOfRound !== FALSE) {
            $end = ($endOfRound >= $infos['limit']);

            $pauseDelay = $this->parseInt('pause_delay', 5);
            if ($pauseDelay < 0)
                $pauseDelay = 5;

            if (!$end)
                $msg = $translator->gettext(
                    '<b><var name="nick"/></b> wins this round! '.
                    'The next round will start in <var name="delay"/> seconds.'
                );
            else
                $msg = $translator->gettext(
                    '<b><var name="nick"/></b> wins this round!'
                );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $tpl->assign('delay',   $pauseDelay);
            $this->sendMessage($chan, $tpl->render());

            $this->_showScores($chan);

            if ($end) {
                $msg = $translator->gettext(
                    'The game was won by '.
                    '<for from="winners" item="winner">'.
                    '<b><var name="winner"/></b></for> '.
                    'with only <b><var name="score"/></b> '.
                    'points after <var name="playtime"/> '.
                    'and <var name="rounds"/> rounds.'
                );
                $scores = array();
                foreach ($infos['game']->getPlayers() as $player) {
                    $scores[(string) $player->getToken()] = $player->getScore();
                }
                $minScore   = min($scores);
                $winners    = array_keys($scores, $minScore);
                $playtime   = $translator->formatDuration(
                    $infos['game']->getElapsedTime()
                );
                $tpl = new Erebot_Styling($msg, $translator);
                $tpl->assign('logo',        $this->getLogo());
                $tpl->assign('score',       $minScore);
                $tpl->assign('winners',     $winners);
                $tpl->assign('playtime',    $playtime);
                // Subtract 1: the bot was ready for a new round,
                // but there will not be one.
                $tpl->assign('rounds',      $infos['game']->getNbRounds() - 1);
                $this->sendMessage($chan, $tpl->render());
                $this->cleanup($chan);
            }
            else {
                $infos['timer'] = new Erebot_Timer(
                    new Erebot_Callable(array($this, 'startGame')),
                    $pauseDelay,
                    FALSE,
                    array($chan)
                );
                $this->addTimer($infos['timer']);
            }
        } // endOfRound (or game)
        else $this->_showTurn($chan, NULL);

        $event->preventDefault(TRUE);
    }

    protected function _qualify($chan, Erebot_Module_GoF_Combo &$combo)
    {
        $translator     = $this->getTranslator($chan);
        // lazy-gettext so that only 1 lookup is actually done
        // through the real gettext method.
        $gettext = create_function('$a', 'return $a;');
        $typeNames = array(
            Erebot_Module_GoF_Combo::COMBO_SINGLE =>
                $gettext('a single'),
            Erebot_Module_GoF_Combo::COMBO_PAIR =>
                $gettext('a pair'),
            Erebot_Module_GoF_Combo::COMBO_TRIO =>
                $gettext('three of a kind'),
            Erebot_Module_GoF_Combo::COMBO_STRAIGHT =>
                $gettext('a straight'),
            Erebot_Module_GoF_Combo::COMBO_FLUSH =>
                $gettext('a flush'),
            Erebot_Module_GoF_Combo::COMBO_FULL_HOUSE =>
                $gettext('a full house'),
            Erebot_Module_GoF_Combo::COMBO_STRAIGHT_FLUSH =>
                $gettext('a straight flush'),
            Erebot_Module_GoF_Combo::COMBO_GANG =>
                $gettext('a gang'),
        );
        return $translator->gettext($typeNames[$combo->getType()]);
    }

    public function handleShowCardsCount(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan   = $event->getChan();
        $nick   = $event->getSource();
        if (!isset($this->_chans[$chan])) return;
        $infos =& $this->_chans[$chan];
        $translator = $this->getTranslator($chan);

        if (!$this->_checkStarted($chan))
            return $event->preventDefault(TRUE);

        $hands = array();
        foreach ($infos['game']->getPlayers() as $player) {
            $playerNick = (string) $player->getToken();
            $hands[$playerNick] = count($player->getHand());
            if (!$this->_connection->irccasecmp($playerNick, $nick))
                $this->_sendCards($chan, $player);
        }

        $msg = $translator->gettext(
            'Hands: '.
            '<for from="hands" key="nick" item="nb_cards"><b><var '.
            'name="nick"/></b>: <var name="nb_cards"/></for>.'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',    $this->getLogo());
        $tpl->assign('hands',   $hands);
        $this->sendMessage($chan, $tpl->render());
        $event->preventDefault(TRUE);
    }

    public function handleShowDiscard(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan   = $event->getChan();
        $nick   = $event->getSource();

        if (!isset($this->_chans[$chan])) return;
        $infos =& $this->_chans[$chan];
        $translator     = $this->getTranslator($chan);
        $lastDiscard    = $infos['game']->getDeck()->getLastDiscard();

        if ($lastDiscard === NULL) {
            $msg = $translator->gettext(
                'No card has been played in this round yet.'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo', $this->getLogo());
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $discardNick = (string) $lastDiscard['player']->getToken();
        if (!$this->_connection->irccasecmp($nick, $discardNick)) {
            $msg = $translator->gettext(
                '<b><var name="nick"/></b> has got the upper hand '.
                'and may now start a new combination'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $nick);
            $this->sendMessage($chan, $tpl->render());
            return $event->preventDefault(TRUE);
        }

        $cards = array();
        foreach ($lastDiscard['combo'] as $card)
            $cards[] = $this->getCardText($card);

        $msg = $translator->gettext(
            'Current discard: <for from="cards" '.
            'item="card" separator=" "><var name="card"/></for> (<var '.
            'name="type"/> played by <b><var name="player"/></b>)'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('cards',   $cards);
        $tpl->assign('type',    $this->_qualify($chan, $lastDiscard['combo']));
        $tpl->assign('player',  $discardNick);
        $this->sendMessage($chan, $tpl->render());
        $event->preventDefault(TRUE);
    }

    public function handleShowOrder(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan = $event->getChan();
        if (!isset($this->_chans[$chan])) return;
        $infos =& $this->_chans[$chan];

        $translator = $this->getTranslator($chan);
        if (!$this->_checkStarted($chan))
            return $event->preventDefault(TRUE);

        $nicks = array();
        foreach ($infos['game']->getPlayers() as $player)
            $nicks[] = (string) $player->getToken();

        $msg = $translator->gettext(
            'Playing turn: '.
            '<for from="nicks" item="nick"><b><var name="nick"/></b></for>.'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',    $this->getLogo());
        $tpl->assign('nicks',   $nicks);
        $this->sendMessage($chan, $tpl->render());
        $event->preventDefault(TRUE);
    }

    protected function _showScores($chan)
    {
        if (!isset($this->_chans[$chan])) return;
        $infos =& $this->_chans[$chan];
        $translator = $this->getTranslator($chan);

        $msg = $translator->gettext(
            'Scores: '.
            '<for from="scores" key="nick" item="score"><b><var '.
            'name="nick"/></b>: <var name="score"/></for>.'
        );
        $scores = array();
        foreach ($infos['game']->getPlayers() as $player)
            $scores[(string) $player->getToken()] = $player->getScore();

        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',    $this->getLogo());
        $tpl->assign('scores',  $scores);
        $this->sendMessage($chan, $tpl->render());
    }

    public function handleShowScores(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan = $event->getChan();
        if (!isset($this->_chans[$chan])) return;
        $this->_showScores($chan);
        $event->preventDefault(TRUE);
    }

    public function handleShowTime(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan = $event->getChan();
        if (!isset($this->_chans[$chan])) return;
        $translator = $this->getTranslator($chan);

        if (!$this->_checkStarted($chan))
            return $event->preventDefault(TRUE);

        $playtime = $this->_chans[$chan]['game']->getElapsedTime();
        $msg = $translator->gettext(
            'This game has been running for <var name="playtime"/>.'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',        $this->getLogo());
        $tpl->assign('playtime',    $translator->formatDuration($playtime));
        $this->sendMessage($chan, $tpl->render());
        $event->preventDefault(TRUE);
    }

    protected function _showTurn($chan, $from)
    {
        $infos          =&  $this->_chans[$chan];
        $current        =   $infos['game']->getCurrentPlayer();
        $currentNick    =   (string) $current->getToken();
        $translator     =   $this->getTranslator($chan);

        if (!$this->_checkStarted($chan)) return;

        if ($from !== NULL && !$this->_connection->irccasecmp(
            $from, $currentNick)) {
            $msg = $translator->gettext(
                '<b><var name="nick"/></b>: '.
                'it\'s your turn sleepyhead!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo', $this->getLogo());
            $tpl->assign('nick', $from);
            $this->sendMessage($chan, $tpl->render());
        }
        else {
            $lastDiscard = $infos['game']->getDeck()->getLastDiscard();
            if ($lastDiscard !== NULL && $lastDiscard['player'] === $current)
                $msg = $translator->gettext(
                    'No player dared to raise the heat! '.
                    'It\'s <b><var name="nick"/></b>\'s turn '.
                    'to play a new combination.'
                );
            else
                $msg = $translator->gettext(
                    'It\'s <b><var name="nick"/></b>\'s turn.'
                );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',    $this->getLogo());
            $tpl->assign('nick',    $currentNick);
            $this->sendMessage($chan, $tpl->render());
        }

        $players    = $infos['game']->getPlayers();
        reset($players);
        $nextPlayer = next($players);

        if (count($nextPlayer->getHand()) == 1) {
            $msg = $translator->gettext(
                '<b><var name="nick"/></b>, since there is only 1 card left '.
                'in <b><var name="next_player"/></b>\'s hand, you MUST play '.
                'your best card or a combination on this turn!'
            );
            $tpl = new Erebot_Styling($msg, $translator);
            $tpl->assign('logo',        $this->getLogo());
            $tpl->assign('nick',        $currentNick);
            $tpl->assign('next_player', (string) $nextPlayer->getToken());
            $this->sendMessage($chan, $tpl->render());
        }

        if ($from === NULL || !$this->_connection->irccasecmp(
            $from, $currentNick))
            $this->_sendCards($chan, $current);
    }

    public function handleShowTurn(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $chan = $event->getChan();
        if (!isset($this->_chans[$chan])) return;
        $this->_showTurn($chan, $event->getSource());
        $event->preventDefault(TRUE);
    }

    protected function _sendCards($chan, Erebot_Module_GoF_Player &$player)
    {
        $translator = $this->getTranslator($chan);
        $cards = array_map(
            array($this, 'getCardText'),
            $player->getHand()->getCards()
        );
        $msg = $translator->gettext(
            'Your cards: <for from="cards" item="card" '.
            'separator=" "><var name="card"/></for>'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('cards', $cards);
        $this->sendMessage((string) $player->getToken(), $tpl->render());
    }

    protected function _checkStarted($chan)
    {
        $infos          =&  $this->_chans[$chan];
        $translator     =   $this->getTranslator($chan);

        if ($infos['game']->getNbRounds())
            return TRUE;

        $msg = $translator->gettext(
            'The game has not yet started!'
        );
        $tpl = new Erebot_Styling($msg, $translator);
        $tpl->assign('logo',        $this->getLogo());
        $this->sendMessage($chan, $tpl->render());
        return FALSE;
    }
}

