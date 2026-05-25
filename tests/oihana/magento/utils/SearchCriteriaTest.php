<?php

namespace tests\oihana\magento\utils;

use oihana\enums\Order;
use oihana\magento\enums\SearchCriteriaParam;
use oihana\magento\utils\SearchCriteria;
use PHPUnit\Framework\TestCase;

final class SearchCriteriaTest extends TestCase
{
    public function testDefaultInitialization(): void
    {
        $criteria = new SearchCriteria();
        $result = $criteria->get();

        $this->assertSame(1, $result['searchCriteria[currentPage]']);
        $this->assertSame(20, $result['searchCriteria[pageSize]']);
        $this->assertCount(0, array_filter(array_keys($result), fn($k) => str_contains($k, 'filterGroups')));
        $this->assertCount(0, array_filter(array_keys($result), fn($k) => str_contains($k, 'sortOrders')));
    }

    public function testInitializeWithValues(): void
    {
        $criteria = new SearchCriteria([
            SearchCriteriaParam::CURRENT_PAGE => 3,
            SearchCriteriaParam::PAGE_SIZE => 50
        ]);

        $result = $criteria->get();
        $this->assertSame(3, $result['searchCriteria[currentPage]']);
        $this->assertSame(50, $result['searchCriteria[pageSize]']);
    }

    public function testSetPaging(): void
    {
        $criteria = new SearchCriteria();
        $criteria->setCurrentPage(5)->setPageSize(100);
        $result = $criteria->get();

        $this->assertSame(5, $result['searchCriteria[currentPage]']);
        $this->assertSame(100, $result['searchCriteria[pageSize]']);
    }

    public function testAddFilterGroup(): void
    {
        $criteria = new SearchCriteria();
        $criteria->addFilterGroup
        ([
            [ 'field'=>'status','value'=>'1','condition_type'=>'eq' ]
        ]);
        $result = $criteria->get();
        $this->assertSame('status' , $result['searchCriteria[filter_groups][0][filters][0][field]']);
        $this->assertSame('1'      , $result['searchCriteria[filter_groups][0][filters][0][value]']);
        $this->assertSame('eq'     , $result['searchCriteria[filter_groups][0][filters][0][condition_type]']);
    }

    public function testResetFilterGroups(): void
    {
        $criteria = new SearchCriteria();
        $criteria->addFilterGroup([['field'=>'status','value'=>'1']]);
        $criteria->resetFilterGroups();

        $result = $criteria->get();
        $this->assertCount(0, array_filter(array_keys($result), fn($k) => str_contains($k, 'filterGroups')));
    }

    public function testAddSortOrder(): void
    {
        $criteria = new SearchCriteria();
        $criteria->addSortOrder('created_at', Order::DESC);

        $result = $criteria->get();
        $this->assertSame('created_at', $result['searchCriteria[sortOrders][0][field]']);
        $this->assertSame(Order::DESC, $result['searchCriteria[sortOrders][0][direction]']);
    }

    public function testResetSortOrders(): void
    {
        $criteria = new SearchCriteria();
        $criteria->addSortOrder('created_at');
        $criteria->resetSortOrders();

        $result = $criteria->get();
        $this->assertCount(0, array_filter(array_keys($result), fn($k) => str_contains($k, 'sortOrders')));
    }

    public function testResetPaging(): void
    {
        $criteria = new SearchCriteria();
        $criteria->setCurrentPage(5)->setPageSize(100);
        $criteria->resetPaging();

        $result = $criteria->get();
        $this->assertSame(1, $result['searchCriteria[currentPage]']);
        $this->assertSame(20, $result['searchCriteria[pageSize]']);
    }

    public function testResetAll(): void
    {
        $criteria = new SearchCriteria();
        $criteria->setCurrentPage(5)
            ->setPageSize(100)
            ->addFilterGroup([['field'=>'status','value'=>'1']])
            ->addSortOrder('created_at');

        $criteria->reset();
        $result = $criteria->get();

        $this->assertSame(1, $result['searchCriteria[currentPage]']);
        $this->assertSame(20, $result['searchCriteria[pageSize]']);
        $this->assertCount(0, array_filter(array_keys($result), fn($k) => str_contains($k, 'filterGroups')));
        $this->assertCount(0, array_filter(array_keys($result), fn($k) => str_contains($k, 'sortOrders')));
    }

}