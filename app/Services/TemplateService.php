<?php

namespace App\Services;

use App\Models\Template;
use App\Models\TemplateField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateService
{
    /**
     * Upload and analyze a certificate template
     */
    public function uploadTemplate(UploadedFile $file, string $name): Template
    {
        // Validate file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['docx', 'pptx'])) {
            throw new \InvalidArgumentException('Unsupported file type. Only DOCX and PPTX are allowed.');
        }

        // Store file
        $filename = Str::slug($name) . '_' . time() . '.' . $extension;
        $path = $file->storeAs('templates', $filename, 'local');

        // Create template record
        $template = Template::create([
            'name' => $name,
            'file_path' => $path,
            'file_type' => $extension,
            'status' => 'draft',
        ]);

        return $template;
    }

    /**
     * Analyze template and extract possible fields
     */
    public function analyzeTemplate(Template $template): array
    {
        $filePath = $template->full_path;

        // Debug: Log the path being checked
        \Log::info('Analyzing template', [
            'template_id' => $template->id,
            'file_path_stored' => $template->file_path,
            'full_path_computed' => $filePath,
            'file_exists' => file_exists($filePath),
            'storage_path' => storage_path('app'),
        ]);

        // Verify file exists
        if (!file_exists($filePath)) {
            throw new \Exception("Template file not found at: {$filePath}. Please try uploading again.");
        }

        switch ($template->file_type) {
            case 'docx':
                return $this->analyzeWordTemplate($filePath);
            case 'pptx':
                return $this->analyzePowerPointTemplate($filePath);
            default:
                throw new \InvalidArgumentException('Unsupported file type');
        }
    }

    /**
     * Analyze PDF template for placeholders
     */
    private function analyzePdfTemplate(string $filePath): array
    {
        $fields = [];
        
        // Use pypdf to extract text and find placeholders
        $pythonScript = <<<PYTHON
import sys
import json
from pypdf import PdfReader
import re

def analyze_pdf(filepath):
    try:
        reader = PdfReader(filepath)
        fields = []
        
        for page_num, page in enumerate(reader.pages):
            text = page.extract_text()
            
            # Find placeholders in {{Field}} format only
            patterns = [
                r'\{\{([^}]+)\}\}',   # {{Field}}
            ]
            
            for pattern in patterns:
                matches = re.finditer(pattern, text)
                for match in matches:
                    field_name = match.group(1).strip()
                    # Remove any leading/trailing braces, brackets, or special characters
                    field_name = re.sub(r'^[\{\[\(<_]+|[\}\]\)>_]+$', '', field_name).strip()
                    
                    if field_name and len(field_name) < 100:  # Reasonable field name length
                        fields.append({
                            'name': field_name,
                            'placeholder': match.group(0),
                            'page': page_num + 1,
                            'type': 'text'
                        })
        
        # Remove duplicates - use case-insensitive comparison
        unique_fields = {}
        for field in fields:
            # Normalize key by converting to lowercase and stripping whitespace
            key = field['name'].strip().lower()
            if key and key not in unique_fields:
                # Keep original case for display
                unique_fields[key] = field
        
        return list(unique_fields.values())
    
    except Exception as e:
        return {'error': str(e)}

result = analyze_pdf(sys.argv[1])
print(json.dumps(result))
PYTHON;

        // Write Python script to temp file
        $scriptPath = storage_path('app/temp_analyze.py');
        file_put_contents($scriptPath, $pythonScript);

        // Execute Python script (use 'python' for Windows compatibility)
        $command = sprintf(
            'python %s %s 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($filePath)
        );
        $output = shell_exec($command);

        // Clean up
        @unlink($scriptPath);

        // Parse result
        $result = json_decode($output, true);
        
        if (!$result) {
            // Log raw output for debugging
            \Log::error('PDF analysis failed', [
                'output' => $output,
                'file' => $filePath
            ]);
            throw new \Exception('PDF analysis failed: Invalid JSON response. Output: ' . substr($output, 0, 200));
        }
        
        if (isset($result['error'])) {
            throw new \Exception('PDF analysis failed: ' . $result['error']);
        }

        return $result ?? [];
    }

    /**
     * Analyze Word template for placeholders
     */
    private function analyzeWordTemplate(string $filePath): array
    {
        $fields = [];
        
        try {
            // Verify file exists and is readable
            if (!file_exists($filePath)) {
                throw new \Exception('Word file not found');
            }

            if (!is_readable($filePath)) {
                throw new \Exception('Word file is not readable. Check permissions.');
            }

            // Verify it's a valid DOCX file (should be a ZIP archive)
            $fileHandle = fopen($filePath, 'rb');
            $fileHeader = fread($fileHandle, 4);
            fclose($fileHandle);
            
            // Check for ZIP signature (PK)
            if (substr($fileHeader, 0, 2) !== 'PK') {
                throw new \Exception('File is not a valid Word document (invalid format)');
            }

            // Load Word document using ZipArchive
            $zip = new \ZipArchive();
            $openResult = $zip->open($filePath);
            
            if ($openResult !== true) {
                throw new \Exception('Cannot open Word document (ZipArchive error code: ' . $openResult . ')');
            }

            // Verify it's actually a DOCX by checking for essential files
            if ($zip->locateName('word/document.xml') === false) {
                $zip->close();
                throw new \Exception('Not a valid Word file (missing document.xml). Make sure you uploaded a .docx file, not .doc (old format).');
            }

            // Read document.xml
            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();

            if (!$xmlContent) {
                throw new \Exception('Cannot read document content');
            }

            // CRITICAL FIX: Strip ALL XML tags first to get clean text
            // Word splits placeholders across multiple XML tags
            // Example: [Name] becomes <w:t>[</w:t><w:t>Name</w:t><w:t>]</w:t>
            
            // Remove all XML tags to get plain text
            $cleanText = strip_tags($xmlContent);
            
            // Decode XML entities
            $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_XML1, 'UTF-8');

            // Find placeholders in clean text (without XML tags) - only {{ }} format
            $patterns = [
                '/\{\{([^}]+)\}\}/',   // {{Field}}
            ];

            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $cleanText, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $index => $fieldName) {
                        $fieldName = trim($fieldName);
                        // Remove any leading/trailing braces, brackets, or special characters
                        $fieldName = preg_replace('/^[\{\[\(<_]+|[\}\]\)>_]+$/', '', $fieldName);
                        $fieldName = trim($fieldName);
                        
                        if (strlen($fieldName) > 0 && strlen($fieldName) < 100) {
                            // Use lowercase key for deduplication
                            $key = strtolower($fieldName);
                            if (!isset($fields[$key])) {
                                $fields[$key] = [
                                    'name' => $fieldName,
                                    'placeholder' => $matches[0][$index],
                                    'type' => 'text',
                                ];
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            // Log the detailed error
            \Log::error('Word analysis error: ' . $e->getMessage(), [
                'file' => $filePath,
                'exists' => file_exists($filePath),
                'readable' => file_exists($filePath) ? is_readable($filePath) : false,
                'size' => file_exists($filePath) ? filesize($filePath) : 0,
            ]);
            
            throw new \Exception('Word analysis failed: ' . $e->getMessage());
        }

        return array_values($fields);
    }

    /**
     * Analyze PowerPoint template for placeholders
     */
    private function analyzePowerPointTemplate(string $filePath): array
    {
        $fields = [];
        
        try {
            // Verify file exists and is readable
            if (!file_exists($filePath)) {
                throw new \Exception('PowerPoint file not found');
            }

            if (!is_readable($filePath)) {
                throw new \Exception('PowerPoint file is not readable. Check permissions.');
            }

            // Verify it's a valid PPTX file (should be a ZIP archive)
            $fileHandle = fopen($filePath, 'rb');
            $fileHeader = fread($fileHandle, 4);
            fclose($fileHandle);
            
            // Check for ZIP signature (PK)
            if (substr($fileHeader, 0, 2) !== 'PK') {
                throw new \Exception('File is not a valid PowerPoint document (invalid format)');
            }

            // Load PowerPoint using ZipArchive
            $zip = new \ZipArchive();
            $openResult = $zip->open($filePath);
            
            if ($openResult !== true) {
                $errorMessages = [
                    \ZipArchive::ER_EXISTS => 'File already exists',
                    \ZipArchive::ER_INCONS => 'Inconsistent archive',
                    \ZipArchive::ER_INVAL => 'Invalid argument',
                    \ZipArchive::ER_MEMORY => 'Memory allocation failure',
                    \ZipArchive::ER_NOENT => 'No such file',
                    \ZipArchive::ER_NOZIP => 'Not a zip archive',
                    \ZipArchive::ER_OPEN => 'Cannot open file',
                    \ZipArchive::ER_READ => 'Read error',
                    \ZipArchive::ER_SEEK => 'Seek error',
                ];
                
                $errorMsg = $errorMessages[$openResult] ?? 'Unknown error (code: ' . $openResult . ')';
                throw new \Exception('Cannot open PowerPoint: ' . $errorMsg);
            }

            // Verify it's actually a PPTX by checking for essential files
            if ($zip->locateName('ppt/presentation.xml') === false) {
                $zip->close();
                throw new \Exception('Not a valid PowerPoint file (missing presentation.xml). Make sure you uploaded a .pptx file, not .ppt (old format).');
            }

            // Read all slide XML files
            $slideFound = false;
            for ($i = 1; $i <= 50; $i++) {
                $slidePath = "ppt/slides/slide{$i}.xml";
                $xmlContent = $zip->getFromName($slidePath);
                
                if ($xmlContent === false) {
                    break; // No more slides
                }

                $slideFound = true;

                // CRITICAL FIX: Strip ALL XML tags first to get clean text
                // PowerPoint splits placeholders across multiple XML tags like Word
                
                // Remove all XML tags to get plain text
                $cleanText = strip_tags($xmlContent);
                
                // Decode XML entities
                $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_XML1, 'UTF-8');

                // Find placeholders in clean text (without XML tags) - only {{ }} format
                $patterns = [
                    '/\{\{([^}]+)\}\}/',   // {{Field}}
                ];

                foreach ($patterns as $pattern) {
                    preg_match_all($pattern, $cleanText, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $index => $fieldName) {
                            $fieldName = trim($fieldName);
                            // Remove any leading/trailing braces, brackets, or special characters
                            $fieldName = preg_replace('/^[\{\[\(<_]+|[\}\]\)>_]+$/', '', $fieldName);
                            $fieldName = trim($fieldName);
                            
                            if (strlen($fieldName) > 0 && strlen($fieldName) < 100) {
                                // Use lowercase key for deduplication
                                $key = strtolower($fieldName);
                                if (!isset($fields[$key])) {
                                    $fields[$key] = [
                                        'name' => $fieldName,
                                        'placeholder' => $matches[0][$index],
                                        'slide' => $i,
                                        'type' => 'text',
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            $zip->close();

            if (!$slideFound) {
                throw new \Exception('No slides found in PowerPoint file');
            }

        } catch (\Exception $e) {
            // Log the detailed error
            \Log::error('PowerPoint analysis error: ' . $e->getMessage(), [
                'file' => $filePath,
                'exists' => file_exists($filePath),
                'readable' => file_exists($filePath) ? is_readable($filePath) : false,
                'size' => file_exists($filePath) ? filesize($filePath) : 0,
            ]);
            
            throw new \Exception('PowerPoint analysis failed: ' . $e->getMessage());
        }

        return array_values($fields);
    }

    /**
     * Save detected fields to database
     */
    public function saveFields(Template $template, array $fieldsData): void
    {
        foreach ($fieldsData as $fieldData) {
            TemplateField::create([
                'template_id' => $template->id,
                'field_name' => $fieldData['name'],
                'field_type' => $this->guessFieldType($fieldData['name']),
                'placeholder' => $fieldData['placeholder'] ?? '',
                'position_data' => json_encode($fieldData),
                'is_required' => true,
            ]);
        }
    }

    /**
     * Guess field type from field name
     */
    private function guessFieldType(string $fieldName): string
    {
        $nameLower = strtolower($fieldName);

        if (str_contains($nameLower, 'date') || str_contains($nameLower, 'issued')) {
            return TemplateField::TYPE_DATE;
        }

        if (str_contains($nameLower, 'id') || str_contains($nameLower, 'number') || str_contains($nameLower, 'cert')) {
            return TemplateField::TYPE_AUTO_ID;
        }

        return TemplateField::TYPE_TEXT;
    }

    /**
     * Delete template and associated files
     */
    public function deleteTemplate(Template $template): void
    {
        // Delete file
        Storage::delete($template->file_path);

        // Delete fields
        $template->fields()->delete();

        // Delete certificates
        foreach ($template->certificates as $certificate) {
            if ($certificate->generated_pdf_path) {
                Storage::delete($certificate->generated_pdf_path);
            }
        }
        $template->certificates()->delete();

        // Delete template
        $template->delete();
    }
}
