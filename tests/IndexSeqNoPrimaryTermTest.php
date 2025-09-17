<?php

declare(strict_types=1);

namespace Elastica\Test;

use Elastica\Document;
use Elastica\Index;
use Elastica\Test\Base as BaseTest;

/**
 * @group functional
 *
 * @internal
 */
class IndexSeqNoPrimaryTermTest extends BaseTest
{
    private Index $index;

    protected function setUp(): void
    {
        parent::setUp();

        $this->index = $this->_createIndex();
    }

    protected function tearDown(): void
    {
        $this->index->delete();
        parent::tearDown();
    }

    /**
     * @covers \Elastica\AbstractUpdateAction::setPrimaryTerm
     * @covers \Elastica\AbstractUpdateAction::setSequenceNumber
     * @covers \Elastica\Index::addDocument
     *
     * @group functional
     */
    public function testAddDocumentWithSeqNoPrimaryTerm(): void
    {
        $doc = new Document('1', ['title' => 'Test document']);
        $doc->setSequenceNumber(1);
        $doc->setPrimaryTerm(1);

        $response = $this->index->addDocument($doc);

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Elastica\AbstractUpdateAction::setPrimaryTerm
     * @covers \Elastica\AbstractUpdateAction::setSequenceNumber
     * @covers \Elastica\Index::addDocument
     *
     * @group functional
     */
    public function testAddDocumentWithOptimisticConcurrencyControl(): void
    {
        // First, add a document
        $doc1 = new Document('1', ['title' => 'Original document']);
        $response1 = $this->index->addDocument($doc1);
        $this->assertTrue($response1->isOk());

        // Get the document to retrieve its sequence number and primary term
        $retrievedDoc = $this->index->getDocument('1');
        $this->assertTrue($retrievedDoc->hasSequenceNumber());
        $this->assertTrue($retrievedDoc->hasPrimaryTerm());

        // Update the document using the retrieved sequence number and primary term
        $doc2 = new Document('1', ['title' => 'Updated document']);
        $doc2->setSequenceNumber($retrievedDoc->getSequenceNumber());
        $doc2->setPrimaryTerm($retrievedDoc->getPrimaryTerm());

        $response2 = $this->index->addDocument($doc2);
        $this->assertTrue($response2->isOk());

        // Verify the document was updated
        $updatedDoc = $this->index->getDocument('1');
        $this->assertEquals('Updated document', $updatedDoc->get('title'));
    }

    /**
     * @covers \Elastica\AbstractUpdateAction::setPrimaryTerm
     * @covers \Elastica\AbstractUpdateAction::setSequenceNumber
     * @covers \Elastica\Index::addDocument
     *
     * @group functional
     */
    public function testAddDocumentWithStaleSeqNoPrimaryTerm(): void
    {
        // First, add a document
        $doc1 = new Document('1', ['title' => 'Original document']);
        $response1 = $this->index->addDocument($doc1);
        $this->assertTrue($response1->isOk());

        // Get the document to retrieve its sequence number and primary term
        $retrievedDoc = $this->index->getDocument('1');
        $originalSeqNo = $retrievedDoc->getSequenceNumber();
        $originalPrimaryTerm = $retrievedDoc->getPrimaryTerm();

        // Update the document once to change the sequence number
        $doc2 = new Document('1', ['title' => 'First update']);
        $doc2->setSequenceNumber($originalSeqNo);
        $doc2->setPrimaryTerm($originalPrimaryTerm);
        $response2 = $this->index->addDocument($doc2);
        $this->assertTrue($response2->isOk());

        // Try to update with the old sequence number and primary term (should fail)
        $doc3 = new Document('1', ['title' => 'Second update']);
        $doc3->setSequenceNumber($originalSeqNo);
        $doc3->setPrimaryTerm($originalPrimaryTerm);

        $response3 = $this->index->addDocument($doc3);
        $this->assertFalse($response3->isOk());
    }
}
