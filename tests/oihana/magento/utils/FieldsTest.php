<?php

namespace tests\oihana\magento\utils;

use InvalidArgumentException;
use oihana\magento\utils\Fields;
use PHPUnit\Framework\TestCase;

final class FieldsTest extends TestCase
{
    public function testConstructWithString(): void
    {
        $f = new Fields('items[sku,name],total_count');
        $this->assertSame('items[sku,name],total_count', (string)$f);
        $this->assertSame(['__raw' => 'items[sku,name],total_count'], $f->fields);
    }

    public function testConstructWithArray(): void
    {
        $f = new Fields
        ([
            'items' => ['sku','name','custom_attributes' => ['attr','value']],
            'total_count'
        ]);

        $expectedString = 'items[sku,name,custom_attributes[attr,value]],total_count';
        $this->assertSame($expectedString, (string)$f);
    }

    public function testConstructWithNull(): void
    {
        $f = new Fields(null);
        $this->assertSame('', (string)$f);
        $this->assertSame([], $f->fields);
    }

    public function testSetString(): void
    {
        $f = new Fields([]);
        $f->fields = 'items[sku,name]';
        $this->assertSame('items[sku,name]', (string)$f);
    }

    public function testSetArray(): void
    {
        $f = new Fields([]);
        $f->fields = ['items' => ['sku','name']];
        $this->assertSame('items[sku,name]', (string)$f);
    }

    public function testNestedMultipleLevels(): void
    {
        $f = new Fields
        ([
            'items' =>
            [
                'sku',
                'extension_attributes' =>
                [
                    'stock_item' => ['qty','is_in_stock']
                ]
            ]
        ]);

        $expected = 'items[sku,extension_attributes[stock_item[qty,is_in_stock]]]';
        $this->assertSame($expected, (string)$f);
    }

    public function testGetMethod(): void
    {
        $f = new Fields([ 'items' => ['sku','name'] ]);

        $this->assertSame('items[sku,name]', $f->get());
    }

}