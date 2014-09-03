<?php
namespace Ore;

class SampleTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $this->assertEquals('Linux', php_uname('s'));
        $this->assertEquals('cli', php_sapi_name());
        $this->assertEquals(8, PHP_INT_SIZE);
    }
}
