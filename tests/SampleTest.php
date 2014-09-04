<?php
namespace Test;

use ngyuki\PhpUnitViaSshOnIde\Sample;

class SampleTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        assertEquals('Linux', php_uname('s'));
        assertEquals('cli', php_sapi_name());
        assertEquals(8, PHP_INT_SIZE);

        assertThat((new Sample())->add(1, 2), equalTo(3));
    }
}
