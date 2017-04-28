<?php

namespace Stecman\Component\Symfony\Console\BashCompletion\Tests;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Stecman\Component\Symfony\Console\BashCompletion\EnvironmentCompletionContext;

class CompletionContextTest extends \PHPUnit_Framework_TestCase
{

    public function testWordBreakSplit()
    {
        $context = new CompletionContext();
        $context->setCommandLine('console  config:application --direction="west" --with-bruce --repeat 3');

        // Cursor at the end of the first word
        $context->setCharIndex(7);
        $words = $context->getWords();

        $this->assertEquals(array(
            'console',
            'config:application',
            '--direction',
            'west',
            '--with-bruce',
            '--repeat',
            '3'
        ), $words);
    }

    public function testCursorPosition()
    {
        $context = new CompletionContext();
        $context->setCommandLine('make horse --legs 4 --colour black ');

        // Cursor at the start of the line
        $context->setCharIndex(0);
        $this->assertEquals(0, $context->getWordIndex());

        // Cursor at the end of the line
        $context->setCharIndex(34);
        $this->assertEquals(5, $context->getWordIndex());
        $this->assertEquals('black', $context->getCurrentWord());

        // Cursor after space at the end of the string
        $context->setCharIndex(35);
        $this->assertEquals(6, $context->getWordIndex());
        $this->assertEquals('', $context->getCurrentWord());

        // Cursor in the middle of 'horse'
        $context->setCharIndex(8);
        $this->assertEquals(1, $context->getWordIndex());
        $this->assertEquals('hor', $context->getCurrentWord());

        // Cursor at the end of '--legs'
        $context->setCharIndex(17);
        $this->assertEquals(2, $context->getWordIndex());
        $this->assertEquals('--legs', $context->getCurrentWord());
    }

    public function testWordBreakingWithSmallInputs()
    {
        $context = new CompletionContext();

        // Cursor at the end of a word and not in the following space has no effect
        $context->setCommandLine('cmd a');
        $context->setCharIndex(5);
        $this->assertEquals(array('cmd', 'a'), $context->getWords());
        $this->assertEquals(1, $context->getWordIndex());
        $this->assertEquals('a', $context->getCurrentWord());

        // As above, but in the middle of the command line string
        $context->setCommandLine('cmd a');
        $context->setCharIndex(3);
        $this->assertEquals(array('cmd', 'a'), $context->getWords());
        $this->assertEquals(0, $context->getWordIndex());
        $this->assertEquals('cmd', $context->getCurrentWord());

        // Cursor at the end of the command line with a space appends an empty word
        $context->setCommandLine('cmd   a ');
        $context->setCharIndex(8);
        $this->assertEquals(array('cmd', 'a', ''), $context->getWords());
        $this->assertEquals(2, $context->getWordIndex());
        $this->assertEquals('', $context->getCurrentWord());

        // Cursor in break space before a word appends an empty word in that position
        $context->setCommandLine('cmd a');
        $context->setCharIndex(4);
        $this->assertEquals(array('cmd', '',  'a',), $context->getWords());
        $this->assertEquals(1, $context->getWordIndex());
        $this->assertEquals('', $context->getCurrentWord());
    }

    public function testConfigureFromEnvironment()
    {
        putenv("CMDLINE_CONTENTS=beam up li");
        putenv('CMDLINE_CURSOR_INDEX=10');

        $context = new EnvironmentCompletionContext();

        $this->assertEquals(
            array(
                'beam',
                'up',
                'li'
            ),
            $context->getWords()
        );

        $this->assertEquals('li', $context->getCurrentWord());
    }
}
