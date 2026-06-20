<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\DeveloperTask;
use App\Models\Lead;
use App\Models\LeadAsset;
use App\Models\LeadEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LeadController extends Controller
{
    /**
     * Display a listing of leads with search, filters and pagination.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // The set of filters this role is allowed to use. This is the single
        // source of truth for both the query below and the Blade UI, so a
        // hidden filter can never be applied by crafting the query string.
        $allowed = $this->allowedFilters($user);

        // Pull each input only when the role is permitted to use it; disallowed
        // filters collapse to their neutral default and are skipped by the query.
        $read = fn (string $key, $default = null) => in_array($key, $allowed, true)
            ? $request->input($key, $default)
            : $default;

        // Workflow / demo-status dropdown options are themselves scoped to the
        // role; these are the authoritative whitelists for both the UI and the
        // query, so a crafted out-of-scope value is ignored, not just hidden.
        $workflowOptions = Lead::workflowStatusOptionsFor($user);
        $demoOptions = Lead::demoStatusOptionsFor($user);

        $search = trim((string) $read('search', ''));
        $status = $read('status');
        $website = $read('website'); // '1', '0' or null
        $workflow = $read('workflow_status');
        $demo = $read('demo_status');
        $devId = $read('developer_id');
        $createdBy = $read('created_by');
        $dateFrom = $read('date_from');
        $dateTo = $read('date_to');

        $leads = Lead::query()
            ->visibleTo($user)
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
            ->when($status !== null && array_key_exists($status, Lead::STATUSES), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($website === '1' || $website === '0', function ($query) use ($website) {
                $query->where('website_exists', $website === '1');
            })
            ->when($workflow !== null && array_key_exists($workflow, $workflowOptions), function ($query) use ($workflow) {
                $query->where('workflow_status', $workflow);
            })
            ->when($demo !== null && array_key_exists($demo, $demoOptions), function ($query) use ($demo) {
                $query->where('demo_status', $demo);
            })
            ->when(! empty($devId), function ($query) use ($devId) {
                $query->where('developer_id', $devId);
            })
            ->when(! empty($createdBy), function ($query) use ($createdBy) {
                // "Created By" is derived from the lead's creation timeline event.
                $query->whereHas('events', function (Builder $q) use ($createdBy) {
                    $q->where('type', LeadEvent::TYPE_CREATED)
                        ->where('user_id', $createdBy);
                });
            })
            ->when(! empty($dateFrom), function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when(! empty($dateTo), function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->with('developer')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('leads.index', [
            'leads' => $leads,
            'statuses' => Lead::STATUSES,
            'workflowStatuses' => $workflowOptions,
            'demoStatuses' => $demoOptions,
            'visibleFilters' => $allowed,
            'developers' => in_array('developer_id', $allowed, true)
                ? User::where('role', User::ROLE_DEVELOPER)->orderBy('name')->get()
                : new Collection,
            'creators' => in_array('created_by', $allowed, true)
                ? $this->leadCreators()
                : new Collection,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'website' => $website,
                'workflow_status' => $workflow,
                'demo_status' => $demo,
                'developer_id' => $devId,
                'created_by' => $createdBy,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * The filter keys a given user's role is permitted to use on the lead list.
     *
     * Every role may search and filter by workflow status, demo status and date.
     * Higher roles unlock the assignment, creator and legacy-pipeline filters.
     *
     * @return array<int, string>
     */
    private function allowedFilters(User $user): array
    {
        // Available to every role (developers are additionally scoped to their
        // own leads by Lead::scopeVisibleTo).
        $common = ['search', 'workflow_status', 'demo_status', 'date_from', 'date_to'];

        return match (true) {
            $user->isSuperAdmin() => array_merge($common, ['status', 'website', 'developer_id', 'created_by']),
            $user->hasRole(User::ROLE_LEADS_ADMIN) => array_merge($common, ['developer_id']),
            default => $common, // sales, developer
        };
    }

    /**
     * Users who have created at least one lead, for the "Created By" filter.
     */
    private function leadCreators(): Collection
    {
        $creatorIds = LeadEvent::where('type', LeadEvent::TYPE_CREATED)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::whereIn('id', $creatorIds)->orderBy('name')->get();
    }

    /**
     * Display the lead details page (business info, assets, developer workflow).
     */
    public function show(Lead $lead): View
    {
        $lead->load([
            'assets.uploadedBy',
            'developerTask.developer',
            'developer',
            'events.user',
        ]);

        $user = auth()->user();

        // Developers may only view leads assigned to them (Phase 3 task or Phase 5 workflow).
        if ($user->isDeveloper()) {
            $assigned = $lead->developer_id === $user->id
                || ($lead->developerTask && $lead->developerTask->developer_id === $user->id);

            abort_unless($assigned, 403, 'You are not assigned to this lead.');
        }

        return view('leads.show', [
            'lead' => $lead,
            'assetsByType' => $lead->assets->groupBy('file_type'),
            'assetTypes' => LeadAsset::TYPES,
            'developers' => User::where('role', User::ROLE_DEVELOPER)->orderBy('name')->get(),
            'developerStatuses' => DeveloperTask::STATUSES,
            'platforms' => DeveloperTask::PLATFORMS,
            'reasonStatuses' => DeveloperTask::REASON_REQUIRED_STATUSES,
            // Phase 5 workflow data
            'workflowStatuses' => Lead::WORKFLOW_STATUSES,
            'devWorkflowStatuses' => Lead::DEV_WORKFLOW_STATUSES,
            'salesWorkflowStatuses' => Lead::SALES_WORKFLOW_STATUSES,
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
        $lead = Lead::create($request->validated());

        $lead->recordEvent(LeadEvent::TYPE_CREATED, 'Lead created.');

        return redirect()->route('leads.index')
            ->with('status', 'Lead created successfully.');
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead): View
    {
        return view('leads.edit', [
            'lead' => $lead,
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
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:leads,id'],
        ]);

        $count = Lead::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('leads.index')
            ->with('status', "{$count} lead(s) deleted successfully.");
    }
}
