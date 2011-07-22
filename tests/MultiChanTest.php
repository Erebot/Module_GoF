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

require_once(
    dirname(__FILE__) .
    DIRECTORY_SEPARATOR . 'testenv' .
    DIRECTORY_SEPARATOR . 'bootstrap.php'
);

class   Erebot_Module_GoF_MultiChanText
extends ErebotModuleTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_module = new Erebot_Module_GoF(NULL);
        $this->_module->reload(
            $this->_connection,
            Erebot_Module_Base::RELOAD_MEMBERS
        );
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseInt')
            ->will($this->returnCallback(array($this, 'parseAny')));
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->returnCallback(array($this, 'parseAny')));

        $this->_registry = new Erebot_Module_TriggerRegistry(NULL);
        $this->_registry->reload(
            $this->_connection,
            Erebot_Module_Base::RELOAD_MEMBERS
        );

        $this->_tracker = new Erebot_Module_IrcTracker(NULL);
        $this->_tracker->reload(
            $this->_connection,
            Erebot_Module_Base::RELOAD_MEMBERS
        );

        $this->_tracker->handleJoin(
            $this->_eventHandler,
            new Erebot_Event_Join(
                $this->_connection,
                '#foo',
                'Clicky'
            )
        );
        $this->_tracker->handleJoin(
            $this->_eventHandler,
            new Erebot_Event_Join(
                $this->_connection,
                '#bar',
                'Clicky'
            )
        );

        $this->_connection
            ->expects($this->any())
            ->method('getModule')
            ->will($this->returnCallback(array($this, 'getModule')));
    }

    public function tearDown()
    {
        $this->_module->unload();
        parent::tearDown();
    }

    public function parseAny($module, $key, $default = NULL)
    {
        return $default;
    }

    public function getModule($name)
    {
        switch ($name) {
            case 'Erebot_Module_IrcTracker':
                return $this->_tracker;
            case 'Erebot_Module_TriggerRegistry':
                return $this->_registry;
            default:
                throw new Exception($name);
        }
    }

    public function testJoin()
    {
        // First channel.
        $this->_module->handleCreate(
            $this->_eventHandler,
            new Erebot_Event_ChanText(
                $this->_connection,
                '#foo',
                'Clicky',
                '!gof'
            )
        );

        $this->_module->handleJoin(
            $this->_eventHandler,
            new Erebot_Event_ChanText(
                $this->_connection,
                '#foo',
                'Clicky',
                'jo'
            )
        );

        // Second channel.
        $this->_module->handleCreate(
            $this->_eventHandler,
            new Erebot_Event_ChanText(
                $this->_connection,
                '#bar',
                'Clicky',
                '!gof'
            )
        );

        $this->_module->handleJoin(
            $this->_eventHandler,
            new Erebot_Event_ChanText(
                $this->_connection,
                '#bar',
                'Clicky',
                'jo'
            )
        );
    }
}

