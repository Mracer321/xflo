<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadAssetRequest;
use App\Models\Lead;
use App\Models\LeadAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadAssetController extends Controller
{
    /**
     * Store one or more uploaded files for a lead.
     */
    public function store(StoreLeadAssetRequest $request, Lead $lead): RedirectResponse
    {
        // A developer may only upload to leads assigned to them.
        abort_unless($lead->isVisibleTo($request->user()), 403, 'You are not assigned to this lead.');

        $type = $request->validated()['file_type'];

        foreach ($request->file('files') as $file) {
            // Store under lead-assets/{lead_id}/ on the public disk with a unique name.
            $path = $file->store("lead-assets/{$lead->id}", 'public');

            $lead->assets()->create([
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $path,
                'file_type'   => $type,
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return back()->with('status', 'File(s) uploaded successfully.');
    }

    /**
     * Force-download a stored asset.
     */
    public function download(LeadAsset $asset): StreamedResponse
    {
        // Prevent insecure direct object access: the user must be able to see the
        // parent lead (developers are scoped to their assigned leads).
        abort_unless($asset->lead->isVisibleTo(auth()->user()), 403);

        abort_unless(Storage::disk('public')->exists($asset->file_path), 404);

        return Storage::disk('public')->download($asset->file_path, $asset->file_name);
    }

    /**
     * Delete an asset (file on disk + database record).
     */
    public function destroy(LeadAsset $asset): RedirectResponse
    {
        // Same visibility gate as download — no deleting assets on other leads.
        abort_unless($asset->lead->isVisibleTo(auth()->user()), 403);

        $leadId = $asset->lead_id;

        Storage::disk('public')->delete($asset->file_path);
        $asset->delete();

        return redirect()
            ->route('leads.show', $leadId)
            ->with('status', 'File deleted successfully.');
    }
}
