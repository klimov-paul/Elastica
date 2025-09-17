<?php

declare(strict_types=1);

namespace Elastica\Test;

use Elastica\Document;
use Elastica\Index;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;

/**
 * @group functional
 *
 * @internal
 */
class SearchSeqNoPrimaryTermTest extends BaseTest
{
    private Index $index;

    protected function setUp(): void
    {
        parent::setUp();

        $this->index = $this->_createIndex();
        $this->index->addDocument(new Document('1', ['title' => 'Test document']));
        $this->index->refresh();
    }

    protected function tearDown(): void
    {
        $this->index->delete();
        parent::tearDown();
    }

    /**
     * @covers \Elastica\Search::setOption
     *
     * @group unit
     */
    public function testSetSeqNoPrimaryTermOption(): void
    {
        $search = new Search($this->_getClient());
        $search->addIndex($this->index);

        $search->setOption(Search::OPTION_SEQ_NO_PRIMARY_TERM, true);

        $this->assertTrue($search->hasOption(Search::OPTION_SEQ_NO_PRIMARY_TERM));
        $this->assertTrue($search->getOption(Search::OPTION_SEQ_NO_PRIMARY_TERM));
    }

    /**
     * @covers \Elastica\Search::search
     *
     * @group functional
     */
    public function testSearchWithSeqNoPrimaryTerm(): void
    {
        $search = new Search($this->_getClient());
        $search->addIndex($this->index);
        $search->setOption(Search::OPTION_SEQ_NO_PRIMARY_TERM, true);

        $resultSet = $search->search();

        $this->assertGreaterThan(0, $resultSet->count());

        foreach ($resultSet as $result) {
            $this->assertArrayHasKey('_seq_no', $result->getHit());
            $this->assertArrayHasKey('_primary_term', $result->getHit());
            $this->assertIsInt($result->getHit()['_seq_no']);
            $this->assertIsInt($result->getHit()['_primary_term']);
        }
    }

    /**
     * @covers \Elastica\Search::setOptionsAndQuery
     *
     * @group unit
     */
    public function testSetOptionsAndQueryWithSeqNoPrimaryTerm(): void
    {
        $search = new Search($this->_getClient());
        $search->addIndex($this->index);

        $options = [
            Search::OPTION_SEQ_NO_PRIMARY_TERM => true,
            Search::OPTION_SIZE => 10,
        ];

        $search->setOptionsAndQuery($options);

        $this->assertTrue($search->hasOption(Search::OPTION_SEQ_NO_PRIMARY_TERM));
        $this->assertTrue($search->getOption(Search::OPTION_SEQ_NO_PRIMARY_TERM));
        $this->assertEquals(10, $search->getOption(Search::OPTION_SIZE));
    }
}
