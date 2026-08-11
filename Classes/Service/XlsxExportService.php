<?php

declare(strict_types=1);

namespace Qc\QcInfoRights\Service;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;

class XlsxExportService
{
    /**
     * Check if PhpSpreadsheet is available at runtime.
     */
    public function isAvailable(): bool
    {
        return class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class);
    }

    /**
     * Generate an XLSX download response.
     *
     * @param string[] $headers Column header labels
     * @param array<int, array<int, string|int|null>> $rows Data rows
     * @param string $filename Download filename (e.g. "Backend-user-list-Export-2024-01-15_10-30.xlsx")
     * @return ResponseInterface
     * @throws \RuntimeException if PhpSpreadsheet is not installed (code 1700000001)
     */
    public function generateResponse(array $headers, array $rows, string $filename): ResponseInterface
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException(
                'phpoffice/phpspreadsheet is required for XLSX export. Install it via: composer require phpoffice/phpspreadsheet',
                1700000001
            );
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write header row (row 1)
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, 1], $header);
        }

        // Write data rows (starting at row 2)
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
            }
        }

        // Write to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_export_');
        if ($tempFile === false) {
            throw new \RuntimeException(
                'Unable to create temporary file for XLSX export.'
            );
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        try {
            $writer->save($tempFile);
            $fileContent = file_get_contents($tempFile);
        } finally {
            @unlink($tempFile);
        }

        $response = new Response('php://temp', 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Description' => 'File transfer',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string)strlen($fileContent),
        ]);

        $response->getBody()->write($fileContent);

        return $response;
    }
}
