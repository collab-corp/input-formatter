<?php

namespace CollabCorp\Formatter\Tests\Feature;

use Carbon\Carbon;
use CollabCorp\Formatter\Formatter;
use CollabCorp\Formatter\Tests\TestCase;
use CollabCorp\Formatter\Tests\TesterFormatClass;

class FormatterTest extends TestCase
{
    /** @test */
    public function itGetsTheResultWhenCastToAStringWhenValueIsNotArray()
    {
        $text = Formatter::create("hello world");

        $number = Formatter::create(1);

        $this->assertEquals("hello world", (string)$text);
        $this->assertEquals("1", (string)$number);
    }

    /** @test */
    public function itAppliesFormattersRecursivelyOnArrays()
    {
        $formatter = (new Formatter(['123something', ["foo123","bar456",'baz678']]))->onlyNumbers();
        $this->assertEquals(['123',['123','456', '678']], $formatter->get());
    }
    /** @test */
    public function itCanMethodChainAndApplyMultipleFormatterMethods()
    {
        $formatter = (new Formatter('&*^*#*1234567890%%%%%'))->rtrim('%')->onlyNumbers()->phone()->get();
        $this->assertEquals('(123)456-7890', $formatter);
    }
    /** @test */
    public function itCanMassConvertAndCanConvertWithPatterns()
    {
        $data = [
            'foo'=>'jksdalfs8(&$##',
            'something_foo'=>'jksdalfs8(&$##',
            'something_bar'=>'   something ',
            'baz_something'=>2,
            'same'=>'I wont change',
            'array'=>['faksd1', 'sladfjl2', 'sadjs3']
        ];

        $data = Formatter::convert($data,[
            '*foo*'=>'onlyNumbers',
            '*bar'=>'trim|suffix:yay',
            'baz*'=>'add:2',
            'array'=>'onlyNumbers'
        ]);

        $this->assertEquals($data['foo'], 8);
        $this->assertEquals($data['something_foo'], 8);
        $this->assertEquals($data['something_bar'], "somethingyay");
        $this->assertEquals($data['baz_something'], 4);
        $this->assertEquals($data['same'], 'I wont change');
        $this->assertEquals($data['array'], [1,2,3]);
    }
    /** @test */
    public function itCanBailProcessingIfValueIsEmpty()
    {
        $data = [
            'date'=>null
        ];

        $data = Formatter::convert($data,[
            'date'=>'bailIfEmpty|toCarbon',
        ]);

        $this->assertEquals($data['date'], null);

    }
    /** @test */
    public function itCanMassConvertUsingClosuresAndFormattableObjects()
    {
        $data = [
            'foo'=>'kalsdf23'
        ];

        $data = Formatter::convert($data,[
            'foo'=>['onlyNumbers','add:2', function($value){
                return $value + 2;
            }, TesterFormatClass::class], //class just add 4 to value
        ]);

        $this->assertEquals($data['foo'], 31);

    }
    /**
     * @test
     */
    public function explodeMethod()
    {
        $formatter = (new Formatter('foo|bar|baz'));
        $this->assertEquals(['foo', 'bar', 'baz'], $formatter->explode('|')->get());
    }
    /**
     * @test
     */
    public function toCarbonMethod()
    {
        $formatter = (new Formatter('now'))->toCarbon();
        $this->assertInstanceOf(Carbon::class, $formatter->get());
    }
    /**
     * @test
     */
    public function itCallsCarbonMethodsWhenValueIsCarbonInstance()
    {
        //add days and format dont exist on formatter so they go to carbon
        $formatter = (new Formatter('12/24/2018'))->toCarbon()->addDays(1)->format('m/d/Y');

        $this->assertEquals('12/25/2018', $formatter->get());
    }
    /**
     * @test
     */
    public function toBooleanMethod()
    {
        $formatter = (new Formatter('1'))->toBoolean();
        $this->assertEquals(true, $formatter->get());

        $formatter = (new Formatter('0'))->toBoolean();
        $this->assertEquals(false, $formatter->get());

        $formatter = (new Formatter('yes'))->toBoolean();
        $this->assertEquals(true, $formatter->get());

        $formatter = (new Formatter('no'))->toBoolean();
        $this->assertEquals(false, $formatter->get());

        $formatter = (new Formatter('any other value gets parsed by filter_var'))->toBoolean();
        $this->assertEquals(false, $formatter->get());
    }
    /**
     * @test
     */
    public function trimMethod()
    {
        $formatter = (new Formatter('foo bar   '))->trim();
        $this->assertEquals('foo bar', $formatter->get());
    }
    /**
     * @test
     */
    public function singleSpaceBetweenWordsMethod()
    {
        $formatter = (new Formatter('i am    a sentence   with lots of   spaces   in between  words  '))->singleSpaceBetweenWords();
        $this->assertEquals('i am a sentence with lots of spaces in between words', $formatter->get());
    }
    /**
     * @test
     */
    public function ltrimMethod()
    {
        $formatter = (new Formatter('$$$$foo bar'))->ltrim("$");
        $this->assertEquals('foo bar', $formatter->get());
    }
    /**
     * @test
     */
    public function rtrimMethod()
    {
        $formatter = (new Formatter('foo bar$$$$'))->rtrim("$");
        $this->assertEquals('foo bar', $formatter->get());
    }
    /**
     * @test
     */
    public function phoneMethod()
    {
        $formatter = (new Formatter('1234567890'))->phone();
        $this->assertEquals('(123)456-7890', $formatter->get());
    }
    /**
     * @test
     */
    public function onlyLettersMethod()
    {
        $formatter = (new Formatter('123456h789i0&*^#$($*#'))->onlyLetters();
        $this->assertEquals('hi', $formatter->get());
    }
    /**
     * @test
     */
    public function onlyNumbersMethod()
    {
        $formatter = (new Formatter('123456h789i0&*^#$($*#'))->onlyNumbers();
        $this->assertEquals('1234567890', $formatter->get());
    }
    /**
     * @test
     */
    public function suffixMethod()
    {
        $formatter = (new Formatter('foo'))->suffix('bar');
        $this->assertEquals('foobar', $formatter->get());
    }
    /**
     * @test
     */
    public function prefixMethod()
    {
        $formatter = (new Formatter('foo'))->prefix('bar');
        $this->assertEquals('barfoo', $formatter->get());
    }
    /**
     * @test
     */
    public function onlyAlphaNumericMethod()
    {
        $formatter = (new Formatter('something 4839&*&(*&*#'))->onlyAlphaNumeric();
        $this->assertEquals('something4839', $formatter->get());
        //param specifies if spaces should be allowed.
        $formatter = (new Formatter('something 4839&*&(*&*#'))->onlyAlphaNumeric(true);
        $this->assertEquals('something 4839', $formatter->get());
    }
    /**
     * @test
     */
    public function truncateMethod()
    {
        $formatter = (new Formatter('some words'))->truncate(2);

        $this->assertEquals("some wor", $formatter->get());
    }
    /**
     * @test
     */
    public function insertEveryMethod()
    {
        $formatter = (new Formatter('1234567890'))->insertEvery(4, ' ');

        $this->assertEquals("1234 5678 90", $formatter->get());
    }
    /**
     * @test
     */
    public function toUpperMethod()
    {
        $formatter = (new Formatter('hi there'))->toUpper();

        $this->assertEquals("HI THERE", $formatter->get());
    }
    /**
     * @test
     */
    public function toLowerMethod()
    {
        $formatter = (new Formatter('HI THERE'))->toLower();

        $this->assertEquals("hi there", $formatter->get());
    }
    /**
     * @test
     */
    public function decimalsMethod()
    {
        $formatter = (new Formatter('4'))->decimals(2);
        $this->assertEquals('4.00', $formatter->get());
        $this->assertEquals(strlen('4.00'), strlen($formatter->get()));
    }
    /**
     * @test
     */
    public function addMethod()
    {
        $formatter = (new Formatter('4'))->add(2);
        $this->assertEquals(6, $formatter->get());
    }
    /**
     * @test
     */
    public function subtractMethod()
    {
        $formatter = (new Formatter('4'))->subtract(2);
        $this->assertEquals(2, $formatter->get());
    }
    /**
     * @test
     */
    public function multipyMethod()
    {
        $formatter = (new Formatter('4'))->multiply(2);
        $this->assertEquals(8, $formatter->get());
    }
    /**
     * @test
     */
    public function divideMethod()
    {
        $formatter = (new Formatter('4'))->divide(2);
        $this->assertEquals(2, $formatter->get());
    }
    /**
     * @test
     */
    public function powerMethod()
    {
        $formatter = (new Formatter('4'))->power(2);
        $this->assertEquals(16, $formatter->get());
    }
    /**
     * @test
     */
    public function decimalPercentMethod()
    {
        $formatter = (new Formatter(10))->decimalPercent();
        $this->assertEquals(0.10, $formatter->get());
    }


}