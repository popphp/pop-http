<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Server\QualityValue;
use PHPUnit\Framework\TestCase;

class QualityValueTest extends TestCase
{

    public function testConstructorDefaultsQualityToOne()
    {
        $qv = new QualityValue('text/html');
        $this->assertEquals('text/html', $qv->getValue());
        $this->assertEquals(1.0, $qv->getQuality());
    }

    public function testConstructorWithExplicitQuality()
    {
        $qv = new QualityValue('text/html', 0.7);
        $this->assertEquals('text/html', $qv->getValue());
        $this->assertEquals(0.7, $qv->getQuality());
    }

    public function testParseListBasicValueWithQuality()
    {
        $entries = QualityValue::parseList('text/html;q=0.8');
        $this->assertCount(1, $entries);
        $this->assertEquals('text/html', $entries[0]->getValue());
        $this->assertEquals(0.8, $entries[0]->getQuality());
    }

    public function testParseListDefaultsQualityWhenMissing()
    {
        $entries = QualityValue::parseList('text/html');
        $this->assertCount(1, $entries);
        $this->assertEquals(1.0, $entries[0]->getQuality());
    }

    public function testParseListMalformedQualityDefaultsToOne()
    {
        $entries = QualityValue::parseList('text/html;q=not-a-number');
        $this->assertCount(1, $entries);
        $this->assertEquals('text/html', $entries[0]->getValue());
        $this->assertEquals(1.0, $entries[0]->getQuality());
    }

    public function testParseListOutOfRangeQualityDefaultsToOne()
    {
        $entries = QualityValue::parseList('text/html;q=2.5');
        $this->assertEquals(1.0, $entries[0]->getQuality());
    }

    public function testParseListSortedDescendingByQuality()
    {
        $entries = QualityValue::parseList('text/html;q=0.3, application/json;q=0.9, */*;q=0.5');
        $this->assertEquals('application/json', $entries[0]->getValue());
        $this->assertEquals('*/*', $entries[1]->getValue());
        $this->assertEquals('text/html', $entries[2]->getValue());
    }

    public function testParseListStableOrderAmongEqualQuality()
    {
        $entries = QualityValue::parseList('text/html;q=0.5, application/json;q=0.5, */*;q=0.5');
        $this->assertEquals('text/html', $entries[0]->getValue());
        $this->assertEquals('application/json', $entries[1]->getValue());
        $this->assertEquals('*/*', $entries[2]->getValue());
    }

    public function testParseListWhitespaceTolerance()
    {
        $entries = QualityValue::parseList(' text/html ; q=0.8 ,  application/json ; q=0.9 ');
        $this->assertEquals('application/json', $entries[0]->getValue());
        $this->assertEquals(0.9, $entries[0]->getQuality());
        $this->assertEquals('text/html', $entries[1]->getValue());
        $this->assertEquals(0.8, $entries[1]->getQuality());
    }

    public function testParseListIgnoresNonQParameters()
    {
        $entries = QualityValue::parseList('text/html;level=1;q=0.6');
        $this->assertCount(1, $entries);
        $this->assertEquals('text/html', $entries[0]->getValue());
        $this->assertEquals(0.6, $entries[0]->getQuality());
    }

    public function testParseListQParameterIsCaseInsensitive()
    {
        $entries = QualityValue::parseList('text/html;Q=0.4');
        $this->assertEquals(0.4, $entries[0]->getQuality());
    }

    public function testParseListEmptyStringReturnsEmptyArray()
    {
        $this->assertEquals([], QualityValue::parseList(''));
    }

}
