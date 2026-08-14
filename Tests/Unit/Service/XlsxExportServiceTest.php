<?php

declare(strict_types=1);

namespace Qc\QcInfoRights\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qc\QcInfoRights\Service\XlsxExportService;

#[CoversClass(XlsxExportService::class)]
final class XlsxExportServiceTest extends TestCase
{
    private XlsxExportService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new XlsxExportService();
    }

    #[Test]
    public function isAvailableReturnsBool(): void
    {
        // isAvailable returns true if PhpSpreadsheet is installed, false otherwise
        $result = $this->subject->isAvailable();
        self::assertIsBool($result);
    }

    #[Test]
    public function generateResponseThrowsExceptionWhenLibraryNotAvailable(): void
    {
        // This test only applies when PhpSpreadsheet is NOT installed.
        // When it IS installed (in dev), test the happy path instead.
        if ($this->subject->isAvailable()) {
            // Happy path: generates a valid response
            $response = $this->subject->generateResponse(
                ['UID', 'Name'],
                [[1, 'Test User'], [2, 'Other User']],
                'test-export.xlsx'
            );

            self::assertSame(200, $response->getStatusCode());
            self::assertSame(
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                $response->getHeaderLine('Content-Type')
            );
            self::assertStringContainsString('test-export.xlsx', $response->getHeaderLine('Content-Disposition'));
            self::assertGreaterThan(0, (int)$response->getHeaderLine('Content-Length'));
        } else {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionCode(1700000001);
            $this->subject->generateResponse(['UID'], [[1]], 'test.xlsx');
        }
    }

    #[Test]
    public function generateResponseProducesCorrectHeaders(): void
    {
        if (!$this->subject->isAvailable()) {
            self::markTestSkipped('PhpSpreadsheet not installed');
        }

        $headers = ['UID', 'Username', 'Email'];
        $rows = [
            [1, 'admin', 'admin@example.com'],
            [2, 'editor', 'editor@example.com'],
        ];

        $response = $this->subject->generateResponse($headers, $rows, 'Backend-user-list-Export-2025-07-20_14-00.xlsx');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('attachment', $response->getHeaderLine('Content-Disposition'));
        self::assertStringContainsString('Backend-user-list-Export-2025-07-20_14-00.xlsx', $response->getHeaderLine('Content-Disposition'));
    }

    #[Test]
    public function generateResponseHandlesEmptyRows(): void
    {
        if (!$this->subject->isAvailable()) {
            self::markTestSkipped('PhpSpreadsheet not installed');
        }

        $response = $this->subject->generateResponse(['Col1', 'Col2'], [], 'empty.xlsx');

        self::assertSame(200, $response->getStatusCode());
        self::assertGreaterThan(0, (int)$response->getHeaderLine('Content-Length'));
    }

    #[Test]
    public function generateResponsePreservesUtf8Characters(): void
    {
        if (!$this->subject->isAvailable()) {
            self::markTestSkipped('PhpSpreadsheet not installed');
        }

        $headers = ['Nom', 'Courriel'];
        $rows = [
            ['André Côté', 'andre.cote@québec.ca'],
            ['Éloïse Thériault', 'eloise@example.com'],
        ];

        // Should not throw — UTF-8 is handled natively by PhpSpreadsheet
        $response = $this->subject->generateResponse($headers, $rows, 'utf8-test.xlsx');
        self::assertSame(200, $response->getStatusCode());
    }
}
