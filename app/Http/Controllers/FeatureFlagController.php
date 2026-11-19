<?php

namespace App\Http\Controllers;

use App\Services\FeatureFlagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeatureFlagController extends Controller
{
    public function __construct(
        private readonly FeatureFlagService $featureFlags,
    ) {}

    public function index(): Response
    {
        return Inertia::render('features/Index', [
            'flags' => $this->featureFlags->listFlags(),
        ]);
    }

    public function update(Request $request, string $feature): RedirectResponse
    {
        abort_unless($this->featureFlags->exists($feature), 404);

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $flag = $this->featureFlags->flag($feature);
        $active = (bool) $validated['active'];

        $this->featureFlags->toggle($feature, $active, $request->user(), $request);

        return back()->with(
            'success',
            $active
                ? "Fitur {$flag['label']} berhasil diaktifkan."
                : "Fitur {$flag['label']} dinonaktifkan.",
        );
    }
}
