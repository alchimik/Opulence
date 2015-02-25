<?php
/**
 * Copyright (C) 2015 David Young
 * 
 * Tests the table formatter
 */
namespace RDev\Console\Responses\Formatters;

class TableTest extends \PHPUnit_Framework_TestCase
{
    /** @var Table The formatter to use in tests */
    private $table = null;

    /**
     * Sets up the tests
     */
    public function setUp()
    {
        $this->table = new Table(new Padding());
    }

    /**
     * Tests formatting an empty table
     */
    public function testFormattingEmptyTable()
    {
        $this->assertEmpty($this->table->format([]));
    }

    /**
     * Tests formatting a table with a single header and column
     */
    public function testFormattingSingleHeaderAndColumn()
    {
        $headers = ["foo"];
        $rows = [["a"]];
        $expected =
            "+-----+" . PHP_EOL .
            "| foo |" . PHP_EOL .
            "+-----+" . PHP_EOL .
            "| a   |" . PHP_EOL .
            "+-----+";
        $this->assertEquals($expected, $this->table->format($rows, $headers));
    }

    /**
     * Tests formatting a table with a single row
     */
    public function testFormattingSingleRow()
    {
        $rows = [["a", "bb", "ccc"]];
        $expected =
            "+---+----+-----+" . PHP_EOL .
            "| a | bb | ccc |" . PHP_EOL .
            "+---+----+-----+";
        $this->assertEquals($expected, $this->table->format($rows));
    }

    /**
     * Tests formatting a table with a single row and column
     */
    public function testFormattingSingleRowAndColumn()
    {
        $rows = [["a"]];
        $expected =
            "+---+" . PHP_EOL .
            "| a |" . PHP_EOL .
            "+---+";
        $this->assertEquals($expected, $this->table->format($rows));
    }

    /**
     * Tests formatting a table with all custom characters
     */
    public function testFormattingTableWithCustomCharacters()
    {
        $headers = ["foo", "bar"];
        $rows = [
            ["a"],
            ["aa", "bb"],
            ["aaa", "bbb", "ccc"]
        ];
        $this->table->setPadAfter(false);
        $this->table->setCellPaddingString("_");
        $this->table->setEOLChar("<br>");
        $this->table->setVerticalBorderChar("I");
        $this->table->setHorizontalBorderChar("=");
        $this->table->setIntersectionChar("*");
        $expected =
            "*=====*=====*=====*<br>".
            "I_foo_I_bar_I_   _I<br>" .
            "*=====*=====*=====*<br>" .
            "I_  a_I_   _I_   _I<br>" .
            "I_ aa_I_ bb_I_   _I<br>" .
            "I_aaa_I_bbb_I_ccc_I<br>" .
            "*=====*=====*=====*";
        $this->assertEquals($expected, $this->table->format($rows, $headers));
    }

    /**
     * Tests formatting a table with headers but without rows
     */
    public function testFormattingTableWithHeadersButWithoutRows()
    {
        $this->assertEmpty($this->table->format([], ["foo", "bar"]));
    }

    /**
     * Tests formatting a table with more headers than row columns
     */
    public function testFormattingTableWithMoreHeadersThanRowColumns()
    {
        $headers = ["foo", "bar", "baz", "blah"];
        $rows = [
            ["a"],
            ["aa", "bb"],
            ["aaa", "bbb", "ccc"]
        ];
        $expected =
            "+-----+-----+-----+------+" . PHP_EOL .
            "| foo | bar | baz | blah |" . PHP_EOL .
            "+-----+-----+-----+------+" . PHP_EOL .
            "| a   |     |     |      |" . PHP_EOL .
            "| aa  | bb  |     |      |" . PHP_EOL .
            "| aaa | bbb | ccc |      |" . PHP_EOL .
            "+-----+-----+-----+------+";
        $this->assertEquals($expected, $this->table->format($rows, $headers));
    }

    /**
     * Tests formatting a table with more row columns than headers
     */
    public function testFormattingTableWithMoreRowColumnsThanHeaders()
    {
        $headers = ["foo", "bar"];
        $rows = [
            ["a"],
            ["aa", "bb"],
            ["aaa", "bbb", "ccc"]
        ];
        $expected =
            "+-----+-----+-----+" . PHP_EOL .
            "| foo | bar |     |" . PHP_EOL .
            "+-----+-----+-----+" . PHP_EOL .
            "| a   |     |     |" . PHP_EOL .
            "| aa  | bb  |     |" . PHP_EOL .
            "| aaa | bbb | ccc |" . PHP_EOL .
            "+-----+-----+-----+";
        $this->assertEquals($expected, $this->table->format($rows, $headers));
    }

    /**
     * Tests formatting a table without headers
     */
    public function testFormattingTableWithoutHeaders()
    {
        $rows = [
            ["a"],
            ["aa", "bb"],
            ["aaa", "bbb", "ccc"]
        ];
        $expected =
            "+-----+-----+-----+" . PHP_EOL .
            "| a   |     |     |" . PHP_EOL .
            "| aa  | bb  |     |" . PHP_EOL .
            "| aaa | bbb | ccc |" . PHP_EOL .
            "+-----+-----+-----+";
        $this->assertEquals($expected, $this->table->format($rows));
    }

    /**
     * Tests setting the rows to non-array values
     */
    public function testSettingRowsWithNonArrayValues()
    {$expected =
        "+-----+" . PHP_EOL .
        "| foo |" . PHP_EOL .
        "| bar |" . PHP_EOL .
        "+-----+";
        $this->assertEquals($expected, $this->table->format(["foo", "bar"]));
    }
}