<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CertificateController extends Controller
{
    protected $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Show data input form
     */
    public function create(Template $template)
    {
        $template->load('fields');
        return view('certificates.create', compact('template'));
    }

    /**
     * Generate single certificate
     */
    public function generateSingle(Request $request, Template $template)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->input('data');

            // Generate certificate
            $pdfPath = $this->certificateService->generateCertificate($template, $data);

            // Create certificate record
            $certificate = Certificate::create([
                'template_id' => $template->id,
                'recipient_data' => $data,
                'generated_pdf_path' => $pdfPath,
                'status' => Certificate::STATUS_GENERATED,
                'generated_at' => now(),
            ]);

            return redirect()
                ->route('certificates.show', $certificate)
                ->with('success', 'Certificate generated successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Generation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Generate batch certificates
     */
    public function generateBatch(Request $request, Template $template)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|array',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $recipients = $request->input('recipients');

            // Generate batch
            $results = $this->certificateService->generateBatch($template, $recipients);

            $successCount = collect($results)->where('success', true)->count();
            $failCount = collect($results)->where('success', false)->count();

            $message = "{$successCount} certificate(s) generated successfully.";
            if ($failCount > 0) {
                $message .= " {$failCount} failed.";
            }

            return redirect()
                ->route('certificates.batch-results', ['template' => $template])
                ->with('success', $message)
                ->with('results', $results);

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Batch generation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Import recipients from CSV or Excel
     */
    public function importCsv(Request $request, Template $template)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $extension = strtolower($file->getClientOriginalExtension());
            
            $recipients = [];
            
            if (in_array($extension, ['xlsx', 'xls'])) {
                // Handle Excel files using Python
                $recipients = $this->importExcel($file->getRealPath());
            } else {
                // Handle CSV files
                $csvData = array_map('str_getcsv', file($file->getRealPath()));
                
                // First row is headers
                $headers = array_shift($csvData);
                
                // Convert to associative array
                foreach ($csvData as $row) {
                    if (empty(array_filter($row))) {
                        // Skip completely empty rows
                        continue;
                    }
                    if (count($row) === count($headers)) {
                        $recipients[] = array_combine($headers, $row);
                    }
                }
            }

            if (empty($recipients)) {
                return back()
                    ->with('error', 'No valid data rows found in the file. Please check the file format.')
                    ->withInput();
            }

            return back()
                ->with('success', count($recipients) . ' recipients imported successfully.')
                ->with('imported_recipients', $recipients);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Import Excel file using PhpSpreadsheet
     */
    private function importExcel(string $filePath): array
    {
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get all data as array
            $data = $worksheet->toArray(null, true, true, true);
            
            if (empty($data)) {
                throw new \Exception('Excel file is empty');
            }
            
            // First row is headers
            $firstRow = array_shift($data);
            $headers = array_filter($firstRow, function($value) {
                return $value !== null && $value !== '';
            });
            
            if (empty($headers)) {
                throw new \Exception('No valid headers found in Excel file');
            }
            
            $recipients = [];
            
            // Process each data row
            foreach ($data as $rowIndex => $rowData) {
                // Skip completely empty rows
                if (empty(array_filter($rowData))) {
                    continue;
                }
                
                $recipient = [];
                
                foreach ($headers as $colLetter => $header) {
                    $value = $rowData[$colLetter] ?? null;
                    
                    // Get the actual cell to check if it's a date
                    $cellCoordinate = $colLetter . $rowIndex;
                    $cell = $worksheet->getCell($cellCoordinate);
                    
                    // Handle different cell types
                    if ($value !== null && $value !== '') {
                        // Check if it's a date value
                        if (ExcelDate::isDateTime($cell)) {
                            try {
                                $dateValue = ExcelDate::excelToDateTimeObject($value);
                                $recipient[trim($header)] = $dateValue->format('M-d'); // e.g., "Feb-11"
                            } catch (\Exception $e) {
                                $recipient[trim($header)] = trim((string)$value);
                            }
                        } else {
                            $recipient[trim($header)] = trim((string)$value);
                        }
                    } else {
                        $recipient[trim($header)] = '';
                    }
                }
                
                if (!empty($recipient)) {
                    $recipients[] = $recipient;
                }
            }
            
            return $recipients;
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to read Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Show certificate
     */
    public function show(Certificate $certificate)
    {
        $certificate->load('template');
        return view('certificates.show', compact('certificate'));
    }

    /**
     * Download certificate
     */
    public function download(Certificate $certificate)
    {
        if (!$certificate->generated_pdf_path || !Storage::exists($certificate->generated_pdf_path)) {
            return back()->with('error', 'Certificate file not found.');
        }

        return Storage::download(
            $certificate->generated_pdf_path,
            basename($certificate->generated_pdf_path)
        );
    }

    /**
     * List all certificates
     */
    public function index(Request $request)
    {
        $query = Certificate::with('template')->latest();

        if ($request->has('template_id')) {
            $query->where('template_id', $request->input('template_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $certificates = $query->paginate(20);

        return view('certificates.index', compact('certificates'));
    }

    /**
     * Export certificates as ZIP
     */
    public function exportZip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'exists:certificates,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $zipPath = $this->certificateService->createZipArchive(
                $request->input('certificate_ids')
            );

            return Storage::download($zipPath);

        } catch (\Exception $e) {
            return back()->with('error', 'ZIP creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Merge certificates into one PDF
     */
    public function merge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'exists:certificates,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $mergedPath = $this->certificateService->mergeCertificates(
                $request->input('certificate_ids')
            );

            return Storage::download($mergedPath);

        } catch (\Exception $e) {
            return back()->with('error', 'PDF merge failed: ' . $e->getMessage());
        }
    }

    /**
     * Show batch results
     */
    public function batchResults(Template $template)
    {
        $results = session('results', []);
        return view('certificates.batch-results', compact('template', 'results'));
    }

    /**
     * Delete certificate
     */
    public function destroy(Certificate $certificate)
    {
        try {
            // Verify the certificate exists and is loaded properly
            if (!$certificate->exists) {
                return redirect()
                    ->route('certificates.index')
                    ->with('error', 'Certificate not found.');
            }

            // Delete the PDF file if it exists
            if ($certificate->generated_pdf_path && Storage::exists($certificate->generated_pdf_path)) {
                Storage::delete($certificate->generated_pdf_path);
            }

            // Delete the certificate from database
            $certificate->delete();

            return redirect()
                ->route('certificates.index')
                ->with('success', 'Certificate deleted successfully.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('certificates.index')
                ->with('error', 'Certificate not found.');
        } catch (\Exception $e) {
            \Log::error('Certificate deletion failed: ' . $e->getMessage(), [
                'certificate_id' => $certificate->id ?? 'unknown',
                'exception' => $e
            ]);
            
            return back()->with('error', 'Deletion failed. Please try again.');
        }
    }
}
