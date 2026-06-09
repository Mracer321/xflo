<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\DeveloperTask;
use App\Models\Lead;
use App\Models\LeadAsset;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    /**
     * Display a listing of leads with search, filters and pagination.
     */
    public function index(Request $request): View
    {
        $search  = trim((string) $request->input('search', ''));
        $status  = $request->input('status');
        $website = $request->input('website'); // '1', '0' or null

        $leads = Lead::query()
            ->visibleTo($request->user())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('business_name', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('whatsapp_number', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, Lead::STATUSES), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($website === '1' || $website === '0', function ($query) use ($website) {
                $query->where('website_exists', $website === '1');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('leads.index', [
            'leads'    => $leads,
            'statuses' => Lead::STATUSES,
            'filters'  => [
                'search'  => $search,
                'status'  => $status,
                'website' => $website,
            ],
        ]);
    }

    /**
     * Display the lead details page (business info, assets, developer workflow).
     */
    public function show(Lead $lead): View
    {
        $lead->load([
            'assets.uploadedBy',
            'developerTask.developer',
        ]);

        $user = auth()->user();

        // Developers may only view leads assigned to them.
        if ($user->isDeveloper()) {
            abort_unless(
                $lead->developerTask && $lead->developerTask->developer_id === $user->id,
                403,
                'You are not assigned to this lead.',
            );
        }

        return view('leads.show', [
            'lead'             => $lead,
            'assetsByType'     => $lead->assets->groupBy('file_type'),
            'assetTypes'       => LeadAsset::TYPES,
            'developers'       => User::where('role', User::ROLE_DEVELOPER)->orderBy('name')->get(),
            'developerStatuses' => DeveloperTask::STATUSES,
            'platforms'        => DeveloperTask::PLATFORMS,
            'reasonStatuses'   => DeveloperTask::REASON_REQUIRED_STATUSES,
        ]);
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create(): View
    {
        return view('leads.create', [
            'statuses' => Lead::STATUSES,
        ]);
    }

    /**
     * Store a newly created lead.
     */
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        Lead::create($request->validated());

        return redirect()->route('leads.index')
            ->with('status', 'Lead created successfully.');
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead): View
    {
        return view('leads.edit', [
            'lead'     => $lead,
            'statuses' => Lead::STATUSES,
        ]);
    }

    /**
     * Update the specified lead.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return redirect()->route('leads.index')
            ->with('status', 'Lead updated successfully.');
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index')
            ->with('status', 'Lead deleted successfully.');
    }

    /**
     * Remove multiple leads selected via bulk-select.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:leads,id'],
        ]);

        $count = Lead::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('leads.index')
            ->with('status', "{$count} lead(s) deleted successfully.");
    }
}
