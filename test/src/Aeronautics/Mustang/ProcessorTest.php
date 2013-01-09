<?php

namespace Aeronautics\Mustang;

use \PHPUnit_Framework_TestCase;

class ProcessorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideRuleTestData
     */
    public function testRules($name)
    {
        $processor = new Processor(
            realpath(__DIR__.'/../../../data/cssl/'.$name), 
            array(__DIR__.'/../../../../style')
        );
        $expected = trim(file_get_contents(realpath(
            __DIR__.'/../../..//data/results/'.$name
        )));
        $result = trim((string) $processor->process());
        $this->assertEquals($expected, $result);
    }
    
    public function provideRuleTestData()
    {
        return array_map(
            function ($path) {
                return array(basename($path));
            }, 
            glob(realpath(__DIR__.'/../../../data/cssl') . '/*')
        );
    }
}
