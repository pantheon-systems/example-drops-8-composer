<?php

namespace spec\Drupal\Driver\Exception;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Drupal\Driver\DriverInterface;

class UnsupportedDriverActionExceptionSpec extends ObjectBehavior
{
    function let(DriverInterface $driver)
    {
        $this->beConstructedWith('Unsupported action in %s driver!', $driver);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\Driver\Exception\UnsupportedDriverActionException');
    }

    function it_should_get_the_driver()
    {
        $this->getDriver()->shouldBeAnInstanceOf('Drupal\Driver\DriverInterface');
    }
}
