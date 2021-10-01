<?php
declare(strict_types=1);

namespace Qc\QcInfoRights\Tests\Unit\Controller;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class QcinforightsControllerTest extends UnitTestCase
{
    /**
     * @var \Qc\QcInfoRights\Controller\QcinforightsController
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(\Qc\QcInfoRights\Controller\QcinforightsController::class)
            ->setMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function listActionFetchesAllQcinforightssFromRepositoryAndAssignsThemToView()
    {
        $allQcinforightss = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $qcinforightsRepository = $this->getMockBuilder(\Qc\QcInfoRights\Domain\Repository\QcinforightsRepository::class)
            ->setMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $qcinforightsRepository->expects(self::once())->method('findAll')->will(self::returnValue($allQcinforightss));
        $this->inject($this->subject, 'qcinforightsRepository', $qcinforightsRepository);

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('qcinforightss', $allQcinforightss);
        $this->inject($this->subject, 'view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenQcinforightsToView()
    {
        $qcinforights = new \Qc\QcInfoRights\Domain\Model\Qcinforights();

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $this->inject($this->subject, 'view', $view);
        $view->expects(self::once())->method('assign')->with('qcinforights', $qcinforights);

        $this->subject->showAction($qcinforights);
    }
}
