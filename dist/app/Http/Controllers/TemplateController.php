<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\TemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    protected $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display all templates
     */
    public function index()
    {
        $templates = Template::with('fields')->latest()->get();
        return view('templates.index', compact('templates'));
    }

    /**
     * Show upload form
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Upload and analyze template
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:docx,pptx|max:10240', // 10MB max, only Word and PowerPoint
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Upload template
            $template = $this->templateService->uploadTemplate(
                $request->file('file'),
                $request->input('name')
            );

            // Analyze template
            $detectedFields = $this->templateService->analyzeTemplate($template);

            // Save detected fields
            $this->templateService->saveFields($template, $detectedFields);

            return redirect()
                ->route('templates.show', $template)
                ->with('success', 'Template uploaded successfully. ' . count($detectedFields) . ' fields detected.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Template upload failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show template details and fields
     */
    public function show(Template $template)
    {
        $template->load('fields');
        return view('templates.show', compact('template'));
    }

    /**
     * Update template fields
     */
    public function updateFields(Request $request, Template $template)
    {
        $validator = Validator::make($request->all(), [
            'fields' => 'required|array',
            'fields.*.id' => 'required|exists:template_fields,id',
            'fields.*.field_type' => 'required|in:text,date,auto_id,number',
            'fields.*.is_required' => 'boolean',
            'fields.*.default_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            foreach ($request->input('fields') as $fieldData) {
                $field = $template->fields()->find($fieldData['id']);
                if ($field) {
                    $field->update([
                        'field_type' => $fieldData['field_type'],
                        'is_required' => $fieldData['is_required'] ?? true,
                        'default_value' => $fieldData['default_value'] ?? null,
                    ]);
                }
            }

            return back()->with('success', 'Fields updated successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete template
     */
    public function destroy(Template $template)
    {
        try {
            $this->templateService->deleteTemplate($template);
            return redirect()
                ->route('templates.index')
                ->with('success', 'Template deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Re-analyze template
     */
    public function reanalyze(Template $template)
    {
        try {
            // Delete existing fields
            $template->fields()->delete();

            // Re-analyze
            $detectedFields = $this->templateService->analyzeTemplate($template);

            // Save new fields
            $this->templateService->saveFields($template, $detectedFields);

            return back()->with('success', 'Template re-analyzed. ' . count($detectedFields) . ' fields detected.');

        } catch (\Exception $e) {
            return back()->with('error', 'Re-analysis failed: ' . $e->getMessage());
        }
    }
}
