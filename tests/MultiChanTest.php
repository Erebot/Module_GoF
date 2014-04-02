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

abstract class  RegistryStub
extends         \Erebot\Module\Base
{
    public function registerTriggers($trigger, $chan)
    {
        return "token";
    }
}

abstract class  TrackerStub
extends         \Erebot\Module\Base
{
    public function startTracking($identity)
    {
        return 42;
    }
}

class   GoFStub
extends \Erebot\Module\GoF
{
    protected function getLogo()
    {
        return "GoF";
    }
}

class   MultiChanText
extends Erebot_Testenv_Module_TestCase
{
    public function setUp()
    {
        $this->_module = new GoFStub(NULL);
        parent::setUp();
        $this->_module->reloadModule($this->_connection, 0);

        $this->_serverConfig
            ->expects($this->any())
            ->method('parseInt')
            ->will($this->returnCallback(array($this, 'parseAny')));
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->returnCallback(array($this, 'parseAny')));

        $this->_registry = $this->getMockForAbstractClass(
            'RegistryStub',
            array(), '', FALSE, FALSE
        );

        $this->_tracker = $this->getMockForAbstractClass(
            'TrackerStub',
            array(), '', FALSE, FALSE
        );
    }

    public function tearDown()
    {
        $this->_module->unloadModule();
        parent::tearDown();
    }

    protected function _mockMessage($chan, $text)
    {
        $event = $this->getMock(
            '\\Erebot\\Interfaces\\Event\\ChanText',
            array(), array(), '', FALSE, FALSE
        );
        $event
            ->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->_connection));
        $event
            ->expects($this->any())
            ->method('getSource')
            ->will($this->returnValue('Clicky'));
        $event
            ->expects($this->any())
            ->method('getChan')
            ->will($this->returnValue($chan));
        $event
            ->expects($this->any())
            ->method('getText')
            ->will($this->returnValue($text));
        return $event;
    }

    public function parseAny($module, $key, $default = NULL)
    {
        return $default;
    }

    public function _getModule($name, $chan = null, $autoload = true)
    {
        switch ($name) {
            case '\\Erebot\\Module\\IrcTracker':
                return $this->_tracker;
            case '\\Erebot\\Module\\TriggerRegistry':
                return $this->_registry;
            default:
                throw new Exception($name);
        }
    }

    public function testJoin()
    {
        $this->_module->setFactory(
            '!EventHandler',
            get_class($this->_eventHandler)
        );

        // First channel.
        $this->_module->handleCreate(
            $this->_eventHandler,
            $this->_mockMessage('#foo', '!gof')
        );

        $this->_module->handleJoin(
            $this->_eventHandler,
            $this->_mockMessage('#foo', 'jo')
        );

        // Second channel.
        $this->_module->handleCreate(
            $this->_eventHandler,
            $this->_mockMessage('#bar', '!gof')
        );

        $this->_module->handleJoin(
            $this->_eventHandler,
            $this->_mockMessage('#foo', 'jo')
        );
    }
}
