<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
     * Import Excel file using Python
     */
    private function importExcel(string $filePath): array
    {
        // Copy temp file to a proper xlsx file since openpyxl doesn't recognize .tmp
        $tempExcelPath = storage_path('app/temp_excel_' . uniqid() . '.xlsx');
        copy($filePath, $tempExcelPath);

        try {
            $pythonScript = <<<'PYTHON'
import sys
import json

try:
    import openpyxl
    
    def read_excel(file_path):
        """Read Excel file and return data as JSON"""
        try:
            wb = openpyxl.load_workbook(file_path, data_only=True)
            sheet = wb.active
            
            # Get headers from first row
            headers = []
            for cell in sheet[1]:
                if cell.value:
                    headers.append(str(cell.value).strip())
            
            # Get data rows
            data = []
            for row in sheet.iter_rows(min_row=2, values_only=True):
                # Skip empty rows
                if not any(row):
                    continue
                
                # Create dict from headers and row values
                row_data = {}
                for i, header in enumerate(headers):
                    if i < len(row) and row[i] is not None:
                        row_data[header] = str(row[i]).strip()
                    else:
                        row_data[header] = ''
                
                data.append(row_data)
            
            return {
                'success': True,
                'data': data,
                'count': len(data)
            }
        
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }
    
    file_path = sys.argv[1]
    result = read_excel(file_path)
    print(json.dumps(result))

except ImportError:
    print(json.dumps({
        'success': False,
        'error': 'openpyxl library not installed. Install with: pip install openpyxl'
    }))
except Exception as e:
    print(json.dumps({
        'success': False,
        'error': str(e)
    }))
PYTHON;

            // Write script to temp file
            $scriptPath = storage_path('app/temp_excel_import.py');
            file_put_contents($scriptPath, $pythonScript);

            // Execute with the properly named Excel file
            $command = sprintf(
                'python %s %s 2>&1',
                escapeshellarg($scriptPath),
                escapeshellarg($tempExcelPath)
            );
            
            $output = shell_exec($command);
            @unlink($scriptPath);

            // Parse result
            $result = json_decode($output, true);
            
            if (!isset($result['success']) || !$result['success']) {
                $error = $result['error'] ?? 'Unknown error during Excel import';
                throw new \Exception($error);
            }

            return $result['data'] ?? [];
        } finally {
            // Clean up temp Excel file
            if (file_exists($tempExcelPath)) {
                @unlink($tempExcelPath);
            }
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
            if ($certificate->generated_pdf_path) {
                Storage::delete($certificate->generated_pdf_path);
            }

            $certificate->delete();

            return redirect()
                ->route('certificates.index')
                ->with('success', 'Certificate deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Deletion failed: ' . $e->getMessage());
        }
    }
}
