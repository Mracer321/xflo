<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeveloperTaskRequest;
use App\Http\Requests\UpdateDeveloperTaskRequest;
use App\Models\DeveloperTask;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class DeveloperTaskController extends Controller
{
    /**
     * Assign a developer to a lead (create or update the workflow record).
     */
    public function store(StoreDeveloperTaskRequest $request, Lead $lead): RedirectResponse
    {
        $lead->developerTask()->updateOrCreate(
            ['lead_id' => $lead->id],
            ['developer_id' => $request->validated()['developer_id']],
        );

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Developer assigned successfully.');
    }

    /**
     * Update the developer workflow for a task.
     */
    public function update(UpdateDeveloperTaskRequest $request, DeveloperTask $developerTask): RedirectResponse
    {
        $data = $request->validated();

        // Drop the reason once the status no longer requires one.
        if (! DeveloperTask::statusRequiresReason($data['status'])) {
            $data['reason'] = null;
        }

        $developerTask->update($data);

        return redirect()
            ->route('leads.show', $developerTask->lead_id)
            ->with('status', 'Developer task updated successfully.');
    }
}
