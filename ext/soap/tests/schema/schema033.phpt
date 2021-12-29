--TEST--
SOAP XML Schema 33: Nested complex types
--EXTENSIONS--
soap
xml
--FILE--
<?php
include "test_schema.inc";
$schema = <<<EOF
    <complexType name="testType2">
        <sequence>
            <element name="int" type="int"/>
        </sequence>
    </complexType>
    <complexType name="testType">
        <sequence>
            <element name="int" type="int"/>
            <element name="nest" type="tns:testType2"/>
        </sequence>
    </complexType>
EOF;
test_schema($schema,'type="tns:testType"',(object)array("int"=>123.5,"nest"=>array("int"=>123.5)));
echo "ok";
?>
--EXPECTF--
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://test-uri/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:test><testParam xsi:type="ns1:testType"><int xsi:type="xsd:int">123</int><nest xsi:type="ns1:testType2"><int xsi:type="xsd:int">123</int></nest></testParam></ns1:test></SOAP-ENV:Body></SOAP-ENV:Envelope>
object(stdClass)#%d (2) {
  ["int"]=>
  int(123)
  ["nest"]=>
  object(stdClass)#%d (1) {
    ["int"]=>
    int(123)
  }
}
ok
