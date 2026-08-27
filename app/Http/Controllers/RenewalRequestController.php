<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Models\RenewalRequest;
use App\Models\User;
use Illuminate\Http\Request;

class RenewalRequestController extends Controller
{
    use LogsActivity;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'request_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::with('softwareLicense')
            ->where('email', $validated['email'])
            ->first();

        if (! $user || $user->isDev() || ! $user->software_license_id) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'No renewable client license was found for that email address.'])
                ->withInput(['email' => $validated['email']]);
        }

        if (! $user->isSuperAdmin()) {
            return redirect()
                ->route('login')
                ->with('license_prompt', true)
                ->with('license_reason', 'expired')
                ->with('license_can_request_renewal', false)
                ->withErrors(['email' => 'Only the Super Admin account can request a license renewal. Please contact your Super Admin.'])
                ->withInput(['email' => $validated['email']]);
        }

        $license = $user->softwareLicense;
        $license?->syncResolvedStatus();

        $existingPendingRequest = RenewalRequest::query()
            ->where('software_license_id', $user->software_license_id)
            ->where('requester_email', $validated['email'])
            ->where('status', 'pending')
            ->exists();

        if ($existingPendingRequest) {
            return redirect()
                ->route('login')
                ->with('warning', 'A renewal request for this account is already pending review.')
                ->withInput(['email' => $validated['email']]);
        }

        $renewalRequest = RenewalRequest::create([
            'user_id' => $user->id,
            'software_license_id' => $user->software_license_id,
            'requester_name' => $user->name,
            'requester_email' => $validated['email'],
            'request_note' => $validated['request_note'] ?? null,
            'status' => 'pending',
        ]);

        $this->logActivity(
            'renewal_requests.create',
            'Submitted a renewal request for ' . ($license?->client_name ?: $license?->license_name ?: $validated['email']) . '.',
            [
                'subject' => $renewalRequest,
                'metadata' => [
                    'requester_email' => $validated['email'],
                ],
            ]
        );

        return redirect()
            ->route('login')
            ->with('success', 'Renewal request sent successfully. Please wait for the Dev account owner to review it.')
            ->withInput(['email' => $validated['email']]);
    }

    public function update(Request $request, RenewalRequest $renewalRequest)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,completed'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $renewalRequest->update([
            'status' => $validated['status'],
            'resolution_note' => $validated['resolution_note'] ?? null,
            'reviewed_by_user_id' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        $this->logActivity(
            'renewal_requests.update',
            'Marked renewal request for ' . $renewalRequest->requester_email . ' as ' . $validated['status'] . '.',
            [
                'subject' => $renewalRequest,
                'metadata' => [
                    'status' => $validated['status'],
                ],
            ]
        );

        return redirect()
            ->route('licensing.index')
            ->with('success', 'Renewal request updated successfully.');
    }
}
