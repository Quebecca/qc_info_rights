<?php
declare(strict_types=1);

namespace Qc\QcInfoRights\Tests\Unit\Domain\Model;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class QcinforightsTest extends UnitTestCase
{
    /**
     * @var \Qc\QcInfoRights\Domain\Model\Qcinforights
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \Qc\QcInfoRights\Domain\Model\Qcinforights();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getIdReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getId()
        );
    }

    /**
     * @test
     */
    public function setIdForStringSetsId()
    {
        $this->subject->setId('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'id',
            $this->subject
        );
    }
}
