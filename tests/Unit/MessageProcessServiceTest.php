<?php


namespace App\Tests\Unit;


use App\Exception\ElementNotFoundException;
use App\Exception\FileNotFoundException;
use App\Exception\ParseException;
use App\Exception\SchemeValidationException;
use App\Service\MessageProcessService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MessageProcessServiceTest extends KernelTestCase
{
    private ?MessageProcessService $messageProcessService;

    protected function setUp()
    {
        self::bootKernel();

        $this->messageProcessService = self::$container->get('App\Service\MessageProcessService');
    }

    public function testParseFailsWithMalformedXml()
    {
        $this->expectException(ParseException::class);

        $this->messageProcessService->parse('<?xml version="1.0" encoding="utf-8"?>
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
    </ping_request>');
    }

    public function testParseSuccessWithGoodXml()
    {
        $decoded = $this->messageProcessService->parse('<?xml version="1.0" encoding="utf-8"?>
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
    </ping_request>');

        $this->assertInstanceOf(\DOMDocument::class, $decoded);
    }

    public function testExtractTypeFailsWithoutTypeElementInXml()
    {
        $this->expectException(ElementNotFoundException::class);

        $this->messageProcessService->extractType('<?xml version="1.0" encoding="utf-8"?>
    <ping_request>
      <header>
        <sender>thisissender</sender>
        <recipient>thisisrecipient</recipient>
        <reference>somerefcode</reference>
        <timestamp>2020-06-22T12:43:40+00:00</timestamp>
      </header>
      <body>
        <echo>echo me</echo>
      </body>
    </ping_request>');
    }

    public function testExtractTypeFailsWithoutHeaderElementInXml()
    {
        $this->expectException(ElementNotFoundException::class);

        $this->messageProcessService->extractType('<?xml version="1.0" encoding="utf-8"?>
    <ping_request>
      <body>
        <echo>echo me</echo>
      </body>
    </ping_request>');
    }

    public function testGetXsdByTypeFailsWithNonExistentType()
    {
        $this->expectException(FileNotFoundException::class);

        $this->messageProcessService->getXsdByType('nofile');
    }

    public function testGetXsdByTypeSuccessesWithExistentType()
    {
        $content = $this->messageProcessService->getXsdByType('nack');

        $this->assertNotEmpty($content);
    }

    public function testSuccessGetXsdByXml()
    {
        $xml = <<<'NOW'
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
            </ping_request>
        NOW;

        $xsd = <<<HERE
        <?xml version="1.0" encoding="UTF-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">
          <xs:element name="ping_request" type="PingRequestType"/>
          <xs:complexType name="PingRequestType">
            <xs:sequence>
              <xs:element name="header" type="HeaderType"/>
              <xs:element name="body" type="PingRequestTypeBodyType"/>
            </xs:sequence>
          </xs:complexType>
          <xs:complexType name="HeaderType">
            <xs:sequence>
              <xs:element name="type" type="xs:string"/>
              <xs:element name="sender" type="xs:string"/>
              <xs:element name="recipient" type="xs:string"/>
              <xs:element name="reference" minOccurs="0">
                <xs:simpleType>
                  <xs:restriction base="xs:string">
                    <xs:maxLength value="48"/>
                  </xs:restriction>
                </xs:simpleType>
              </xs:element>
              <xs:element name="timestamp" type="xs:dateTime" minOccurs="0"/>
            </xs:sequence>
          </xs:complexType>
          <xs:complexType name="PingRequestTypeBodyType">
            <xs:sequence>
              <xs:element name="echo" type="xs:string" minOccurs="0"/>
            </xs:sequence>
          </xs:complexType>
        </xs:schema>
        HERE;

        $xsdFound = $this->messageProcessService->getXsdByXml($xml);

        $this->assertXmlStringEqualsXmlString($xsd, $xsdFound);
    }

    public function testFailGetXsdByXmlElementNotFoundException()
    {
        $this->expectException(ElementNotFoundException::class);

        $xml = <<<'NOW'
            <ping_request>
              <header>
                <sender>thisissender</sender>
                <recipient>thisisrecipient</recipient>
                <reference>somerefcode</reference>
                <timestamp>2020-06-22T12:43:40+00:00</timestamp>
              </header>
              <body>
                <echo>echo me</echo>
              </body>
            </ping_request>
        NOW;

        $this->messageProcessService->getXsdByXml($xml);
    }

    public function testFailGetXsdByXmlFileNotFoundException()
    {
        $this->expectException(FileNotFoundException::class);

        $xml = <<<'NOW'
            <ping_request>
              <header>
                <type>ping_requestxxx</type>
                <sender>thisissender</sender>
                <recipient>thisisrecipient</recipient>
                <reference>somerefcode</reference>
                <timestamp>2020-06-22T12:43:40+00:00</timestamp>
              </header>
              <body>
                <echo>echo me</echo>
              </body>
            </ping_request>
        NOW;

        $this->messageProcessService->getXsdByXml($xml);
    }

    public function testSuccessValidateWithScheme()
    {
        $exceptionThrown = false;

        $xml = <<<'NOW'
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
            </ping_request>
        NOW;

        try {
            $this->messageProcessService->validateWithSchema($xml);
        } catch (ElementNotFoundException | FileNotFoundException | SchemeValidationException $e) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown);
    }

    public function testFailValidateWithSchemeElementNotFoundException()
    {
        $this->expectException(ElementNotFoundException::class);

        $xml = <<<'NOW'
            <ping_request>
              <header>
                <sender>thisissender</sender>
                <recipient>thisisrecipient</recipient>
                <reference>somerefcode</reference>
                <timestamp>2020-06-22T12:43:40+00:00</timestamp>
              </header>
              <body>
                <echo>echo me</echo>
              </body>
            </ping_request>
        NOW;

        $this->messageProcessService->validateWithSchema($xml);
    }

    public function testFailValidateWithSchemeFileNotFoundException()
    {
        $this->expectException(FileNotFoundException::class);

        $xml = <<<'NOW'
            <ping_request>
              <header>
                <type>ping_requestxxx</type>
                <sender>thisissender</sender>
                <recipient>thisisrecipient</recipient>
                <reference>somerefcode</reference>
                <timestamp>2020-06-22T12:43:40+00:00</timestamp>
              </header>
              <body>
                <echo>echo me</echo>
              </body>
            </ping_request>
        NOW;

        $this->messageProcessService->validateWithSchema($xml);
    }

    public function testFailValidateWithSchemeSchemeValidationException()
    {
        $this->expectException(SchemeValidationException::class);

        $xml = <<<'NOW'
            <ping_request>
              <header>
                <type>ping_request</type>
                <recipient>thisisrecipient</recipient>
                <reference>somerefcode</reference>
                <timestamp>2020-06-22T12:43:40+00:00</timestamp>
              </header>
              <body>
                <echo>echo me</echo>
              </body>
            </ping_request>
        NOW;

        $this->messageProcessService->validateWithSchema($xml);
    }
}