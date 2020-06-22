<?php


namespace App\Tests;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MessageControllerTest extends WebTestCase
{
    public function testPingRequestFailsWithInvalidXmlBody()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<ping_requestxxx>
  <header>
    <type>ping_request</type>
    <sender>thisissender</sender>
    <recipient>thisisrecipient</recipient>
    <reference>somerefcode</reference>
    <timestamp>2020-06-22T12:43:40+00:00</timestamp>
  </header>
  <body>
    <echo>echo me</echo>
  </body>
</ping_request>'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('nack', $crawler->getNode(0)->nodeName);
    }

    public function testPingRequestFailsIfReferenceCodeIsBiggerThan48Chars()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<ping_request>
  <header>
    <type>ping_request</type>
    <sender>thisissender</sender>
    <recipient>thisisrecipient</recipient>
    <reference>somerefcodesomerefcodesomerefcodesomerefcodesomerefcodesomerefcodesomerefcode</reference>
    <timestamp>2020-06-22T12:43:40+00:00</timestamp>
  </header>
  <body></body>
</ping_request>'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('nack', $crawler->getNode(0)->nodeName);
    }

    public function testPingRequestValidPingRequestBody()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<ping_request>
  <header>
    <type>ping_request</type>
    <sender>thisissender</sender>
    <recipient>thisisrecipient</recipient>
    <reference>somerefcode</reference>
    <timestamp>2020-06-22T12:43:40+00:00</timestamp>
  </header>
  <body>
    <echo>echo me</echo>
  </body>
</ping_request>'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('ping_response', $crawler->getNode(0)->nodeName);
        $this->assertEquals('echo me', $crawler->getNode(0)->lastChild->firstChild->nodeValue);
    }

    public function testPingRequestSuccessWhenOptionalEchoElementRemoved()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<ping_request>
  <header>
    <type>ping_request</type>
    <sender>thisissender</sender>
    <recipient>thisisrecipient</recipient>
    <reference>somerefcode</reference>
    <timestamp>2020-06-22T12:43:40+00:00</timestamp>
  </header>
  <body></body>
</ping_request>'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('ping_response', $crawler->getNode(0)->nodeName);
    }

    public function testReverseRequestWillFailIfNoStringElementSent()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<reverse_request>
  <header>
    <type>reverse_request</type>
    <sender>me</sender>
    <recipient>her</recipient>
    <reference>refcode</reference>
    <timestamp>2020-06-22T12:43:40</timestamp>
  </header>
  <body>
  </body>
</reverse_request>'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('nack', $crawler->getNode(0)->nodeName);
    }

    public function testReverseRequestFailsWithInvalidXmlBody()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<reverse_requestxxx>
  <header>
    <type>reverse_request</type>
    <sender>me</sender>
    <recipient>her</recipient>
    <reference>refcode</reference>
    <timestamp>2020-06-22T12:43:40</timestamp>
  </header>
  <body>
    <string>reverseme</string>
  </body>
</reverse_request>'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('nack', $crawler->getNode(0)->nodeName);
    }

    public function testReverseRequestFailsWithEmptyString()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<reverse_request>
  <header>
    <type>reverse_request</type>
    <sender>me</sender>
    <recipient>her</recipient>
    <reference>refcode</reference>
    <timestamp>2020-06-22T12:43:40</timestamp>
  </header>
  <body>
    <string></string>
  </body>
</reverse_request>'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('reverse_response', $crawler->getNode(0)->nodeName);
    }

    public function testReverseRequestSuccessWithValidBody()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/message',
            [], [], [],
            '<?xml version="1.0" encoding="utf-8"?>
<reverse_request>
  <header>
    <type>reverse_request</type>
    <sender>me</sender>
    <recipient>her</recipient>
    <reference>refcode</reference>
    <timestamp>2020-06-22T12:43:40</timestamp>
  </header>
  <body>
    <string>reverseme</string>
  </body>
</reverse_request>'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('reverse_response', $crawler->getNode(0)->nodeName);
        $this->assertEquals('emesrever', $crawler->getNode(0)->lastChild->lastChild->nodeValue);
    }
}