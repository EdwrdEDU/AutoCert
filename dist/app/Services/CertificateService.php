<?php

namespace App\Services;

use App\Models\Template;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Generate a single certificate
     */
    public function generateCertificate(Template $template, array $data): string
    {
        // Validate required fields
        $this->validateData($template, $data);

        // Process auto-generated fields
        $processedData = $this->processAutoFields($template, $data);

        // Generate based on file type
        switch ($template->file_type) {
            case 'docx':
                return $this->generateWordCertificate($template, $processedData);
            case 'pptx':
                return $this->generatePowerPointCertificate($template, $processedData);
            default:
                throw new \InvalidArgumentException('Unsupported template type. Only DOCX and PPTX are supported.');
        }
    }

    /**
     * Generate multiple certificates
     */
    public function generateBatch(Template $template, array $recipients): array
    {
        $results = [];
        $individualPaths = [];
        $individualCertificates = [];

        foreach ($recipients as $index => $recipientData) {
            try {
                // Add index for auto-ID generation
                $recipientData['_index'] = $index + 1;

                // Generate certificate
                $certPath = $this->generateCertificate($template, $recipientData);
                $individualPaths[] = $certPath;

                // Create individual certificate record
                // First try standard extraction, then fallback to first non-empty field
                $recipientName = $this->extractRecipientName($recipientData);
                
                if (!$recipientName || $recipientName === 'N/A') {
                    // Fallback: use first field that's not empty
                    foreach ($recipientData as $key => $value) {
                        if (!empty($value) && $key !== '_index' && is_string($value)) {
                            $recipientName = $value;
                            break;
                        }
                    }
                }

                $certificate = Certificate::create([
                    'template_id' => $template->id,
                    'recipient_data' => $recipientData,
                    'recipient_name' => $recipientName ?: 'N/A',
                    'generated_pdf_path' => $certPath,
                    'status' => Certificate::STATUS_GENERATED,
                    'generated_at' => now(),
                ]);

                $individualCertificates[] = $certificate->id;

                $results[] = [
                    'success' => true,
                    'recipient' => $recipientName ?: 'N/A',
                    'path' => $certPath,
                    'certificate_id' => $certificate->id,
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'recipient' => $this->extractRecipientName($recipientData),
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Also create merged file for batch download
        if (!empty($individualPaths)) {
            $mergedPath = $this->mergeCertificatesIntoOne($template, $individualPaths);
            
            // Create batch certificate record
            $batchCertificate = Certificate::create([
                'template_id' => $template->id,
                'recipient_data' => ['batch' => count($recipients) . ' recipients', 'individual_ids' => $individualCertificates],
                'generated_pdf_path' => $mergedPath,
                'status' => Certificate::STATUS_GENERATED,
                'generated_at' => now(),
            ]);

            // Update results with merged file info
            foreach ($results as &$result) {
                if ($result['success']) {
                    $result['batch_certificate_id'] = $batchCertificate->id;
                    $result['merged_pdf_path'] = $mergedPath;
                }
            }
        }

        return $results;
    }

    /**
     * Generate PDF certificate using Python overlay
     */
    private function generatePdfCertificate(Template $template, array $data): string
    {
        $outputFilename = 'cert_' . Str::random(10) . '.pdf';
        $outputPath = storage_path('app/certificates/' . $outputFilename);
        
        // Ensure directory exists
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Use Python-based solution with reportlab for text overlay
        $this->generatePdfWithPython($template->full_path, $data, $outputPath);

        return 'certificates/' . $outputFilename;
    }

    /**
     * Generate PDF with text overlay using Python and reportlab
     */
    private function generatePdfWithPython(string $templatePath, array $data, string $outputPath): void
    {
        try {
            $pythonCode = <<<'PYTHON'
import sys
import json
import os
import re

try:
    from pypdf import PdfReader, PdfWriter
    from reportlab.pdfgen import canvas
    from reportlab.lib.colors import black, white
    from io import BytesIO

    template_path = sys.argv[1]
    data_file = sys.argv[2]
    output_path = sys.argv[3]
    
    # Read data from file
    with open(data_file, 'r') as f:
        data = json.load(f)
    
    # Read template
    reader = PdfReader(template_path)
    writer = PdfWriter()
    
    # Process all pages
    for page_idx, page in enumerate(reader.pages):
        width = float(page.mediabox.width)
        height = float(page.mediabox.height)
        
        # Extract text from page to detect placeholders
        try:
            page_text = page.extract_text()
        except:
            page_text = ""
        
        # Create overlay canvas for this page
        packet = BytesIO()
        can = canvas.Canvas(packet, pagesize=(width, height))
        can.setFont("Helvetica", 11)
        can.setFillColor(black)
        
        # Find all placeholder patterns
        replacements_made = []
        if page_text:
            # All placeholder formats to search for
            placeholder_patterns = [
                r'\[\s*([^\]]+?)\s*\]',       # [fieldname]
                r'\{\{\s*([^}]+?)\s*\}\}',    # {{fieldname}}
                r'\{\s*([^}]+?)\s*\}',        # {fieldname}
                r'<\s*([^>]+?)\s*>',          # <fieldname>
                r'__\s*([^_]+?)\s*__',        # __fieldname__
                r'<<\s*([^>]+?)\s*>>',        # <<fieldname>>
            ]
            
            for pattern in placeholder_patterns:
                for match in re.finditer(pattern, page_text, re.IGNORECASE):
                    field_name = match.group(1).strip()
                    placeholder_full = match.group(0)
                    
                    # Try to find matching data key (flexible matching)
                    for key, value in data.items():
                        if not str(key).startswith('_'):
                            # Exact match (case-insensitive)
                            if key.lower() == field_name.lower():
                                replacements_made.append({
                                    'placeholder': placeholder_full,
                                    'value': str(value),
                                    'position': match.start()
                                })
                                break
                            # Partial match - look for field name within key
                            elif field_name.lower() in key.lower() or key.lower() in field_name.lower():
                                replacements_made.append({
                                    'placeholder': placeholder_full,
                                    'value': str(value),
                                    'position': match.start()
                                })
                                break
        
        # Draw replacements on the overlay
        if replacements_made:
            y_pos = height - 40
            x_pos = 40
            line_num = 0
            
            for i, replacement in enumerate(replacements_made):
                placeholder = replacement['placeholder']
                value = replacement['value'][:60]  # Limit text length
                
                # Draw white background
                text_width = len(value) * 6.5
                can.setFillColor(white)
                can.rect(x_pos - 3, y_pos - 12, text_width + 6, 15, fill=1)
                
                # Draw text
                can.setFillColor(black)
                can.drawString(x_pos, y_pos - 10, value)
                
                y_pos -= 20
                if y_pos < 100:
                    y_pos = height - 40
                    x_pos += 250
        
        can.save()
        
        # Merge overlay with page
        packet.seek(0)
        overlay_reader = PdfReader(packet)
        page.merge_page(overlay_reader.pages[0])
    
    # Write output PDF
    for page in reader.pages:
        writer.add_page(page)
    
    with open(output_path, 'wb') as f:
        writer.write(f)
    
    print(json.dumps({"success": True}))

except Exception as e:
    import traceback
    error_msg = str(e) + " | " + traceback.format_exc()
    print(json.dumps({"success": False, "error": error_msg}))

PYTHON;
            
            $scriptPath = storage_path('app/temp_pdf.py');
            $dataFile = storage_path('app/temp_pdf_data.json');
            
            file_put_contents($scriptPath, $pythonCode);
            file_put_contents($dataFile, json_encode(array_map('strval', $data)));
            
            // Execute
            $command = sprintf(
                'python %s %s %s %s 2>&1',
                escapeshellarg($scriptPath),
                escapeshellarg($templatePath),
                escapeshellarg($dataFile),
                escapeshellarg($outputPath)
            );
            
            $output = shell_exec($command);
            @unlink($scriptPath);
            @unlink($dataFile);
            
            // Check output
            $result = json_decode($output, true);
            if (!$result || !isset($result['success']) || !$result['success']) {
                $error = ($result['error'] ?? 'Unknown error') . ' | Raw: ' . substr($output, 0, 300);
                throw new \Exception($error);
            }
            
        } catch (\Exception $e) {
            throw new \Exception('PDF generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Overlay text fields on PDF (DEPRECATED - using Python instead)
     */
    private function generatePdfWithOverlay(string $templatePath, array $data, string $outputPath): void
    {
        // This method is kept for reference but not used
        // Use generatePdfWithPython instead
    }

    /**
     * Overlay text fields on PDF (DEPRECATED)
     */
    private function overlayTextFields($pdf, array $data, array $pageSize): void
    {
        // This method is kept for reference but not used
    }

    /**
     * Generate Word certificate with improved placeholder handling
     */
    private function generateWordCertificate(Template $template, array $data): string
    {
        // Extract recipient name for filename
        $recipientName = $this->extractRecipientName($data);
        $dateFormat = date('Y-m-d');
        $safeFilename = Str::slug($recipientName) . '_' . $dateFormat;
        
        // Ensure directory exists
        $certDir = storage_path('app/certificates');
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }
        
        // Create temporary DOCX file
        $tempDocxPath = $certDir . '/temp_' . uniqid() . '.docx';
        copy($template->full_path, $tempDocxPath);

        // Load the Word document
        $zip = new \ZipArchive();
        if ($zip->open($tempDocxPath) !== true) {
            throw new \Exception('Cannot open Word document for editing');
        }

        // Read document.xml
        $xmlContent = $zip->getFromName('word/document.xml');
        
        // Strategy: Remove XML tags temporarily to find and replace all placeholders
        // Then restore tags while keeping text intact
        
        // Extract all text nodes and their positions
        preg_match_all('#<w:t[^>]*>([^<]*)</w:t>#', $xmlContent, $textMatches, PREG_OFFSET_CAPTURE);
        
        // Build a map of all unique placeholders and their replacements
        $replacementMap = [];
        $allText = '';
        foreach ($textMatches[1] as $match) {
            $allText .= $match[0];
        }
        
        // Find all placeholders in the concatenated text
        preg_match_all('/\{\{([^}]+)\}\}/', $allText, $placeholderMatches);
        
        foreach ($placeholderMatches[1] as $placeholderName) {
            $placeholderName = trim($placeholderName);
            $value = null;
            
            // Try exact match first
            foreach ($data as $dataKey => $dataValue) {
                if (!str_starts_with($dataKey, '_')) {
                    if (strtolower($dataKey) === strtolower($placeholderName)) {
                        $value = htmlspecialchars($dataValue);
                        break;
                    }
                }
            }
            
            // If not found, try partial/contains match
            if ($value === null) {
                foreach ($data as $dataKey => $dataValue) {
                    if (!str_starts_with($dataKey, '_')) {
                        if (stripos($placeholderName, trim($dataKey)) !== false) {
                            $value = htmlspecialchars($dataValue);
                            break;
                        }
                    }
                }
            }
            
            if ($value !== null) {
                $replacementMap['{{' . $placeholderName . '}}'] = $value;
            }
        }
        
        // Replace all placeholders with a simple but effective method
        foreach ($replacementMap as $placeholder => $value) {
            // Remove XML tags temporarily, do replacement, then be smart about restoration
            $plainXml = preg_replace('#<[^>]+>#', '', $xmlContent);
            if (strpos($plainXml, $placeholder) !== false) {
                // Found in plain text - now replace in actual XML preserving tags
                // Use a pattern that allows XML tags within the placeholder
                $pattern = preg_quote($placeholder);
                // Allow any XML tags inside the placeholder
                $pattern = str_replace('\{\{', '\{\{(?:<[^>]*>)*', $pattern);
                $pattern = str_replace('\}\}', '(?:<[^>]*>)*\}\}', $pattern);
                $xmlContent = preg_replace('#' . $pattern . '#', $value, $xmlContent);
            }
        }

        // Write modified content back
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $xmlContent);
        $zip->close();

        // Save as DOCX to preserve original design
        $outputPath = $certDir . '/' . $safeFilename . '.docx';
        rename($tempDocxPath, $outputPath);
        
        return 'certificates/' . $safeFilename . '.docx';
    }

    /**
     * Generate PowerPoint certificate
     */
    private function generatePowerPointCertificate(Template $template, array $data): string
    {
        // Extract recipient name for filename
        $recipientName = $this->extractRecipientName($data);
        $dateFormat = date('Y-m-d');
        $safeFilename = Str::slug($recipientName) . '_' . $dateFormat;
        
        // Ensure directory exists
        $certDir = storage_path('app/certificates');
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }
        
        // Create temporary PPTX file
        $tempPptxPath = $certDir . '/temp_' . uniqid() . '.pptx';
        copy($template->full_path, $tempPptxPath);

        // Load the PowerPoint document
        $zip = new \ZipArchive();
        if ($zip->open($tempPptxPath) !== true) {
            throw new \Exception('Cannot open PowerPoint document for editing');
        }

        // Process all slides
        for ($i = 1; $i <= 50; $i++) {
            $slidePath = "ppt/slides/slide{$i}.xml";
            $xmlContent = $zip->getFromName($slidePath);
            
            if (!$xmlContent) {
                break; // No more slides
            }

            // Strategy: Remove XML tags temporarily to find and replace all placeholders
            // Then restore tags while keeping text intact
            
            // Extract all text nodes
            preg_match_all('#<a:t[^>]*>([^<]*)</a:t>#', $xmlContent, $textMatches, PREG_OFFSET_CAPTURE);
            
            // Build a map of all unique placeholders and their replacements
            $replacementMap = [];
            $allText = '';
            foreach ($textMatches[1] as $match) {
                $allText .= $match[0];
            }
            
            // Find all placeholders in the concatenated text
            preg_match_all('/\{\{([^}]+)\}\}/', $allText, $placeholderMatches);
            
            foreach ($placeholderMatches[1] as $placeholderName) {
                $placeholderName = trim($placeholderName);
                $value = null;
                
                // Try exact match first
                foreach ($data as $dataKey => $dataValue) {
                    if (!str_starts_with($dataKey, '_')) {
                        if (strtolower($dataKey) === strtolower($placeholderName)) {
                            $value = htmlspecialchars($dataValue);
                            break;
                        }
                    }
                }
                
                // If not found, try partial/contains match
                if ($value === null) {
                    foreach ($data as $dataKey => $dataValue) {
                        if (!str_starts_with($dataKey, '_')) {
                            if (stripos($placeholderName, trim($dataKey)) !== false) {
                                $value = htmlspecialchars($dataValue);
                                break;
                            }
                        }
                    }
                }
                
                if ($value !== null) {
                    $replacementMap['{{' . $placeholderName . '}}'] = $value;
                }
            }
            
            // Replace all placeholders with a simple but effective method
            foreach ($replacementMap as $placeholder => $value) {
                // Remove XML tags temporarily, do replacement, then be smart about restoration
                $plainXml = preg_replace('#<[^>]+>#', '', $xmlContent);
                if (strpos($plainXml, $placeholder) !== false) {
                    // Found in plain text - now replace in actual XML preserving tags
                    // Use a pattern that allows XML tags within the placeholder
                    $pattern = preg_quote($placeholder);
                    // Allow any XML tags inside the placeholder
                    $pattern = str_replace('\{\{', '\{\{(?:<[^>]*>)*', $pattern);
                    $pattern = str_replace('\}\}', '(?:<[^>]*>)*\}\}', $pattern);
                    $xmlContent = preg_replace('#' . $pattern . '#', $value, $xmlContent);
                }
            }

            // Write modified content back
            $zip->deleteName($slidePath);
            $zip->addFromString($slidePath, $xmlContent);
        }

        $zip->close();

        // Save as PPTX to preserve original design
        $outputPath = $certDir . '/' . $safeFilename . '.pptx';
        rename($tempPptxPath, $outputPath);
        
        return 'certificates/' . $safeFilename . '.pptx';
    }

    /**
     * Merge multiple certificates into one file
     */
    private function mergeCertificatesIntoOne(Template $template, array $certificatePaths): string
    {
        $extension = $template->file_type;
        $mergedFilename = 'batch_' . date('Y-m-d_His') . '.' . $extension;
        $mergedPath = storage_path('app/certificates/' . $mergedFilename);

        if ($extension === 'docx') {
            return $this->mergeWordDocuments($certificatePaths, $mergedPath);
        } elseif ($extension === 'pptx') {
            return $this->mergePowerPointPresentations($certificatePaths, $mergedPath);
        }

        return $certificatePaths[0] ?? '';
    }

    /**
     * Merge multiple Word documents into one
     */
    private function mergeWordDocuments(array $docPaths, string $outputPath): string
    {
        $mergedZip = new \ZipArchive();
        $mergedZip->open($outputPath, \ZipArchive::CREATE);

        // Copy first document as base
        $firstDoc = storage_path('app/' . $docPaths[0]);
        $baseZip = new \ZipArchive();
        $baseZip->open($firstDoc);
        
        for ($i = 0; $i < $baseZip->numFiles; $i++) {
            $filename = $baseZip->getNameIndex($i);
            $mergedZip->addFromString($filename, $baseZip->getFromIndex($i));
        }
        $baseZip->close();

        // Get the document.xml content
        $documentXml = $mergedZip->getFromName('word/document.xml');
        
        // Extract body content (between <w:body> tags)
        preg_match('#<w:body>(.*)</w:body>#s', $documentXml, $matches);
        $baseBody = $matches[1] ?? '';
        
        // Remove the closing sectPr from base body
        $baseBody = preg_replace('#<w:sectPr.*?</w:sectPr>#s', '', $baseBody);
        
        // Append content from other documents
        for ($i = 1; $i < count($docPaths); $i++) {
            $docPath = storage_path('app/' . $docPaths[$i]);
            $zip = new \ZipArchive();
            $zip->open($docPath);
            $docXml = $zip->getFromName('word/document.xml');
            $zip->close();
            
            // Extract body content
            preg_match('#<w:body>(.*)</w:body>#s', $docXml, $matches);
            $bodyContent = $matches[1] ?? '';
            
            // Remove sectPr from this content too
            $bodyContent = preg_replace('#<w:sectPr.*?</w:sectPr>#s', '', $bodyContent);
            
            // Add page break before new certificate
            $baseBody .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            $baseBody .= $bodyContent;
        }
        
        // Add back one sectPr at the end
        $baseBody .= '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/></w:sectPr>';
        
        // Rebuild document.xml
        $newDocumentXml = preg_replace(
            '#<w:body>.*</w:body>#s',
            '<w:body>' . $baseBody . '</w:body>',
            $documentXml
        );
        
        $mergedZip->deleteName('word/document.xml');
        $mergedZip->addFromString('word/document.xml', $newDocumentXml);
        $mergedZip->close();

        return 'certificates/' . basename($outputPath);
    }

    /**
     * Merge multiple PowerPoint presentations into one
     */
    private function mergePowerPointPresentations(array $pptxPaths, string $outputPath): string
    {
        $mergedZip = new \ZipArchive();
        $mergedZip->open($outputPath, \ZipArchive::CREATE);

        // Copy first presentation as base
        $firstPptx = storage_path('app/' . $pptxPaths[0]);
        $baseZip = new \ZipArchive();
        $baseZip->open($firstPptx);
        
        for ($i = 0; $i < $baseZip->numFiles; $i++) {
            $filename = $baseZip->getNameIndex($i);
            $mergedZip->addFromString($filename, $baseZip->getFromIndex($i));
        }
        $baseZip->close();

        $slideIndex = 1;
        // Count slides in first presentation
        while ($mergedZip->getFromName("ppt/slides/slide{$slideIndex}.xml")) {
            $slideIndex++;
        }

        // Add slides from other presentations
        for ($i = 1; $i < count($pptxPaths); $i++) {
            $pptxPath = storage_path('app/' . $pptxPaths[$i]);
            $zip = new \ZipArchive();
            $zip->open($pptxPath);
            
            $pptSlideIndex = 1;
            while ($slideXml = $zip->getFromName("ppt/slides/slide{$pptSlideIndex}.xml")) {
                // Copy slide
                $mergedZip->addFromString("ppt/slides/slide{$slideIndex}.xml", $slideXml);
                
                // Copy slide relationships if exists
                $slideRels = $zip->getFromName("ppt/slides/_rels/slide{$pptSlideIndex}.xml.rels");
                if ($slideRels) {
                    $mergedZip->addFromString("ppt/slides/_rels/slide{$slideIndex}.xml.rels", $slideRels);
                }
                
                $slideIndex++;
                $pptSlideIndex++;
            }
            
            $zip->close();
        }

        $mergedZip->close();

        return 'certificates/' . basename($outputPath);
    }

    /**
     * Create ZIP archive of multiple certificates
     */
    public function createZipArchive(array $certificateIds): string
    {
        $certificates = Certificate::whereIn('id', $certificateIds)
            ->where('status', Certificate::STATUS_GENERATED)
            ->get();

        $zipFilename = 'certificates_' . date('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/exports/' . $zipFilename);

        // Ensure directory exists
        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Cannot create ZIP archive');
        }

        foreach ($certificates as $certificate) {
            if ($certificate->generated_pdf_path && Storage::exists($certificate->generated_pdf_path)) {
                $filename = ($certificate->recipient_name ?? 'certificate_' . $certificate->id) . '.' . pathinfo($certificate->generated_pdf_path, PATHINFO_EXTENSION);
                $zip->addFile(
                    storage_path('app/' . $certificate->generated_pdf_path),
                    $filename
                );
            }
        }

        // If batch certificates are selected, also add individual ones
        foreach ($certificates as $certificate) {
            $recipientData = $certificate->recipient_data;
            if (isset($recipientData['individual_ids']) && is_array($recipientData['individual_ids'])) {
                $individuals = Certificate::whereIn('id', $recipientData['individual_ids'])
                    ->where('status', Certificate::STATUS_GENERATED)
                    ->get();
                
                foreach ($individuals as $individual) {
                    if ($individual->generated_pdf_path && Storage::exists($individual->generated_pdf_path)) {
                        $filename = ($individual->recipient_name ?? 'certificate_' . $individual->id) . '.' . pathinfo($individual->generated_pdf_path, PATHINFO_EXTENSION);
                        $zip->addFile(
                            storage_path('app/' . $individual->generated_pdf_path),
                            $filename
                        );
                    }
                }
            }
        }

        $zip->close();

        return 'exports/' . $zipFilename;
    }

    /**
     * Validate data against template fields
     */
    private function validateData(Template $template, array $data): void
    {
        $requiredFields = $template->fields()
            ->where('is_required', true)
            ->where('field_type', '!=', 'auto_id')
            ->get();

        foreach ($requiredFields as $field) {
            if (!isset($data[$field->field_name]) || empty($data[$field->field_name])) {
                throw new \InvalidArgumentException("Required field '{$field->field_name}' is missing");
            }
        }
    }

    /**
     * Process auto-generated fields
     */
    private function processAutoFields(Template $template, array $data): array
    {
        $processedData = $data;
        $index = $data['_index'] ?? null;

        $autoFields = $template->fields()
            ->where('field_type', 'auto_id')
            ->get();

        foreach ($autoFields as $field) {
            if (!isset($processedData[$field->field_name])) {
                $processedData[$field->field_name] = $field->generateAutoValue($index);
            }
        }

        // Remove internal fields
        unset($processedData['_index']);

        return $processedData;
    }

    /**
     * Extract recipient name from data - intelligently detects name-like fields
     * Looks for common name fields in order of preference
     */
    private function extractRecipientName(array $recipientData): string
    {
        // Priority order for name detection
        $nameFieldPatterns = [
            'name',
            'full_name',
            'fullname',
            'recipient_name',
            'recipient',
            'employee_name',
            'first_name',
            'last_name',
            'firstname',
            'lastname',
            'given_name',
            'family_name',
            'email',
            'employee_id',
            'id',
        ];

        // First: try exact matches (case-insensitive)
        foreach ($nameFieldPatterns as $pattern) {
            foreach ($recipientData as $key => $value) {
                if (strtolower($key) === strtolower($pattern) && !empty($value)) {
                    return (string)$value;
                }
            }
        }

        // Second: try pattern matching (contains)
        foreach ($nameFieldPatterns as $pattern) {
            foreach ($recipientData as $key => $value) {
                if (stripos($key, $pattern) !== false && !empty($value)) {
                    return (string)$value;
                }
            }
        }

        // Third: try combining first and last name
        $firstName = null;
        $lastName = null;
        
        foreach ($recipientData as $key => $value) {
            if (!empty($value)) {
                $keyLower = strtolower($key);
                if (stripos($keyLower, 'first') !== false) {
                    $firstName = $value;
                } elseif (stripos($keyLower, 'last') !== false || stripos($keyLower, 'surname') !== false || stripos($keyLower, 'family') !== false) {
                    $lastName = $value;
                }
            }
        }

        if ($firstName && $lastName) {
            return trim($firstName . ' ' . $lastName);
        } elseif ($firstName) {
            return (string)$firstName;
        } elseif ($lastName) {
            return (string)$lastName;
        }

        // Fourth: use first non-empty, non-internal field
        foreach ($recipientData as $key => $value) {
            if (!str_starts_with($key, '_') && !empty($value)) {
                return (string)$value;
            }
        }

        return 'Unknown';
    }
}
