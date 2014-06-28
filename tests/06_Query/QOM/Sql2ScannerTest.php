<?php
namespace PHPCR\Tests\Query\QOM;

require_once(__DIR__ . '/../../../inc/BaseCase.php');

use PHPCR\Util\QOM\Sql2Scanner;

/**
 * Test for PHPCR\Util\QOM\Sql2Scanner
 */
class Sql2ScannerTest extends \PHPCR\Test\BaseCase
{
    protected $sql2;
    protected $tokens;

    public function setUp()
    {
        parent::setUp();

        $this->sql2 = '
            SELECT * FROM
                [nt:file]
            INNER JOIN
                [nt:folder] ON ISSAMENODE(sel1, sel2, [/home])';
        $this->tokens = array(
            'SELECT', '*', 'FROM','[nt:file]', 'INNER', 'JOIN', '[nt:folder]',
            'ON', 'ISSAMENODE', '(', 'sel1', ',', 'sel2', ',', '[/home]', ')');
    }

    public function testConstructor()
    {
        $scanner = new Sql2Scanner($this->sql2);
        $this->assertAttributeEquals($this->sql2, 'sql2', $scanner);
        $this->assertAttributeEquals($this->tokens, 'tokens', $scanner);
    }

    public function testLookupAndFetch()
    {
        $scanner = new Sql2Scanner($this->sql2);
        foreach ($this->tokens as $token) {
            $this->assertEquals($token, $scanner->lookupNextToken());
            $this->assertEquals($token, $scanner->fetchNextToken());
        }

        $this->assertEquals('', $scanner->lookupNextToken());
        $this->assertEquals('', $scanner->fetchNextToken());
    }

    private function executeSingleParserTest($input, $expectedTokens, $testEOF = true) {
        $scanner = new Sql2Scanner($input);

        foreach($expectedTokens as $expected) {
            $token = $scanner->fetchNextToken();
            $this->assertEquals($expected, $token);
        }

        if($testEOF) {
            $this->assertEquals('', $scanner->fetchNextToken());
        }
    }

    private function executeParserTests($testData, $testEOF = true) {
        foreach($testData as $test) {
            list($input, $expectedTokens) = $test;
            $this->executeSingleParserTest($input, $expectedTokens, $testEOF);
        }
    }

    public function testStringLiteral() {
        $testdata = array(
            array('  "hello world"   ', array('"hello world"')),
            array('  \'hello world\'  ', array('\'hello world\'')),
            array('  "escaped \" double-quote"   ', array('"escaped " double-quote"')),
            array('   \'escaped \\\' quote\'  ', array('\'escaped \' quote\'')),
        );

        $this->executeParserTests($testdata);
    }

    /**
     * @expectedException InvalidQueryException
     */
    public function testInvalidStringLiteral() {
        $scanner = new Sql2Scanner("\"test test exception ");
    }

    public function testNumberLiteral() {
        $testdata = array(
            array(' 1 ', array('1')),
            array(' 3.14 ', array('3.14')),
            array(' 0.314e1 ', array('0.314e1')),
        );

        $this->executeParserTests($testdata);
    }
}
