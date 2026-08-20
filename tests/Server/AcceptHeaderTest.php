<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Server\AcceptHeader;
use PHPUnit\Framework\TestCase;

class AcceptHeaderTest extends TestCase
{

    public function testExactMatch()
    {
        $accept = new AcceptHeader('text/html');
        $this->assertEquals(1.0, $accept->matches('text/html'));
        $this->assertEquals(0.0, $accept->matches('application/json'));
    }

    public function testSubtypeWildcardMatch()
    {
        $accept = new AcceptHeader('text/*');
        $this->assertEquals(1.0, $accept->matches('text/html'));
        $this->assertEquals(1.0, $accept->matches('text/plain'));
        $this->assertEquals(0.0, $accept->matches('application/json'));
    }

    public function testFullWildcardMatch()
    {
        $accept = new AcceptHeader('*/*');
        $this->assertEquals(1.0, $accept->matches('text/html'));
        $this->assertEquals(1.0, $accept->matches('application/json'));
    }

    public function testMissingHeaderTreatedAsFullWildcard()
    {
        $accept = new AcceptHeader();
        $this->assertEquals(1.0, $accept->matches('text/html'));
        $this->assertTrue($accept->accepts('application/xml'));
    }

    public function testEmptyStringHeaderTreatedAsFullWildcard()
    {
        $accept = new AcceptHeader('');
        $this->assertEquals(1.0, $accept->matches('text/html'));
    }

    public function testQZeroExcludesSpecificTypeEvenWithWildcardPresent()
    {
        $accept = new AcceptHeader('text/html;q=0, */*');
        $this->assertEquals(0.0, $accept->matches('text/html'));
        $this->assertTrue($accept->accepts('application/json'));
        $this->assertFalse($accept->accepts('text/html'));
    }

    public function testSpecificityPrecedenceRegardlessOfHeaderOrder()
    {
        // */* listed first with a higher raw quality than the exact match - exact match must still win
        $accept = new AcceptHeader('*/*;q=0.9, text/html;q=0.5');
        $this->assertEquals(0.5, $accept->matches('text/html'));
    }

    public function testDuplicateEntriesAtSameSpecificityResolveToMaxQuality()
    {
        $accept = new AcceptHeader('text/html;q=0.4, text/html;q=0.9');
        $this->assertEquals(0.9, $accept->matches('text/html'));
    }

    public function testAcceptsWithSingleType()
    {
        $accept = new AcceptHeader('application/json');
        $this->assertTrue($accept->accepts('application/json'));
        $this->assertFalse($accept->accepts('text/html'));
    }

    public function testAcceptsWithArrayOfTypesReturnsTrueIfAnyMatch()
    {
        $accept = new AcceptHeader('application/json');
        $this->assertTrue($accept->accepts(['text/html', 'application/json']));
        $this->assertFalse($accept->accepts(['text/html', 'text/xml']));
    }

    public function testGetPreferredTypeSelectsHighestScoring()
    {
        $accept = new AcceptHeader('text/html;q=0.5, application/json;q=0.9');
        $this->assertEquals('application/json', $accept->getPreferredType(['text/html', 'application/json']));
    }

    public function testGetPreferredTypeTiesBrokenByAvailableOrder()
    {
        $accept = new AcceptHeader('*/*');
        $this->assertEquals('text/html', $accept->getPreferredType(['text/html', 'application/json']));
        $this->assertEquals('application/json', $accept->getPreferredType(['application/json', 'text/html']));
    }

    public function testGetPreferredTypeReturnsNullWhenNothingMatches()
    {
        $accept = new AcceptHeader('application/json');
        $this->assertNull($accept->getPreferredType(['text/html', 'text/xml']));
    }

}
