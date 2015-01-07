<?php
/**
 * Copyright (C) 2015 David Young
 * 
 * Tests the URL generator
 */
namespace RDev\HTTP\Routing\URL;
use RDev\HTTP\Requests;
use RDev\HTTP\Routing\Routes;
use RDev\HTTP\Routing\Compilers\Parsers;

class URLGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var URLGenerator The generator to use in tests */
    private $generator = null;

    /**
     * Sets up the tests
     */
    public function setUp()
    {
        $namedRoutes = [
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users",
                [
                    "controller" => "foo@bar",
                    "name" => "pathNoParameters"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users/{userId}",
                [
                    "controller" => "foo@bar",
                    "name" => "pathOneParameter"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users/{userId}/profile/{mode}",
                [
                    "controller" => "foo@bar",
                    "name" => "pathTwoParameters"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users{foo?}",
                [
                    "controller" => "foo@bar",
                    "name" => "pathOptionalVariable"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users/{userId}",
                [
                    "controller" => "foo@bar",
                    "variables" => ["userId" => "\d+"],
                    "name" => "pathVariableRegex"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users",
                [
                    "controller" => "foo@bar",
                    "host" => "example.com",
                    "name" => "hostNoParameters"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users",
                [
                    "controller" => "foo@bar",
                    "host" => "{subdomain}.example.com",
                    "name" => "hostOneParameter"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users",
                [
                    "controller" => "foo@bar",
                    "host" => "{subdomain1}.{subdomain2}.example.com",
                    "name" => "hostTwoParameters"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users",
                [
                    "controller" => "foo@bar",
                    "host" => "{subdomain?}example.com",
                    "name" => "hostOptionalVariable"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users/{userId}/profile/{mode}",
                [
                    "controller" => "foo@bar",
                    "host" => "{subdomain1}.{subdomain2}.example.com",
                    "name" => "hostAndPathMultipleParameters"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users{foo?}",
                [
                    "controller" => "foo@bar",
                    "host" => "{subdomain?}example.com",
                    "name" => "hostAndPathOptionalParameters"
                ]
            ),
            new Routes\Route(
                Requests\Request::METHOD_GET,
                "/users",
                [
                    "controller" => "foo@bar",
                    "host" => "foo.example.com",
                    "https" => true,
                    "name" => "secureHostNoParameters"
                ]
            )
        ];
        $routes = new Routes\Routes();

        foreach($namedRoutes as $name => $route)
        {
            $routes->add($route);
        }

        $this->generator = new URLGenerator($routes, new Parsers\Parser());
    }

    /**
     * Tests generating an HTTPs URL
     */
    public function testGeneratingHTTPSURL()
    {
        $this->assertEquals(
            "https://foo.example.com/users",
            $this->generator->createFromName("secureHostNoParameters")
        );
    }

    /**
     * Tests generating a route for a non-existent route
     */
    public function testGeneratingURLForNonExistentRoute()
    {
        $this->assertEmpty($this->generator->createFromName("foo"));
    }

    /**
     * Tests generating a URL with multiple host and path values
     */
    public function testGeneratingURLWithMultipleHostAndPathValues()
    {
        $this->assertEquals(
            "http://foo.bar.example.com/users/23/profile/edit",
            $this->generator->createFromName("hostAndPathMultipleParameters", ["foo", "bar", 23, "edit"])
        );
    }

    /**
     * Tests generating a URL with no values
     */
    public function testGeneratingURLWithNoValues()
    {
        $this->assertEquals("/users", $this->generator->createFromName("pathNoParameters"));
        $this->assertEquals("http://example.com/users", $this->generator->createFromName("hostNoParameters"));
    }

    /**
     * Tests generating a URL with one value
     */
    public function testGeneratingURLWithOneValue()
    {
        $this->assertEquals("/users/23", $this->generator->createFromName("pathOneParameter", [23]));
        $this->assertEquals("http://foo.example.com/users", $this->generator->createFromName("hostOneParameter", ["foo"]));
    }

    /**
     * Tests generating a URL with an optional host variable
     */
    public function testGeneratingURLWithOptionalHostVariable()
    {
        $this->assertEquals(
            "http://example.com/users",
            $this->generator->createFromName("hostOptionalVariable")
        );
    }

    /**
     * Tests generating a URL with an optional path variable
     */
    public function testGeneratingURLWithOptionalPathVariable()
    {
        $this->assertEquals(
            "/users",
            $this->generator->createFromName("pathOptionalVariable")
        );
    }

    /**
     * Tests generating a URL with optional variables in the path and host
     */
    public function testGeneratingURLWithOptionalVariablesInPathAndHost()
    {
        $this->assertEquals(
            "http://example.com/users",
            $this->generator->createFromName("hostAndPathOptionalParameters")
        );
    }

    /**
     * Tests generating a URL with two values
     */
    public function testGeneratingURLWithTwoValues()
    {
        $this->assertEquals("/users/23/profile/edit", $this->generator->createFromName("pathTwoParameters", [23, "edit"]));
        $this->assertEquals(
            "http://foo.bar.example.com/users",
            $this->generator->createFromName("hostTwoParameters", ["foo", "bar"])
        );
    }

    /**
     * Tests generating a URL with a variable value that does not satisfy the regex
     */
    public function testGeneratingURLWithVariableThatDoesNotSatisfyRegex()
    {
        $this->setExpectedException("RDev\\HTTP\\Routing\\URL\\URLException");
        $this->generator->createFromName("pathVariableRegex", "notANumber");
    }

    /**
     * Tests not filling all values in a host
     */
    public function testNotFillingAllHostValues()
    {
        $this->setExpectedException("RDev\\HTTP\\Routing\\URL\\URLException");
        $this->generator->createFromName("hostOneParameter");

    }

    /**
     * Tests not filling all values in a path
     */
    public function testNotFillingAllPathValues()
    {
        $this->setExpectedException("RDev\\HTTP\\Routing\\URL\\URLException");
        $this->generator->createFromName("pathOneParameter");
    }

    /**
     * Tests passing in a non array value
     */
    public function testPassingNonArrayValue()
    {
        $this->assertEquals("/users/23", $this->generator->createFromName("pathOneParameter", 23));
    }
}