<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BootstrapSeederManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SeederController extends Controller
{
    public function __construct(
        private readonly BootstrapSeederManager $seeders
    ) {}

    public function index(): View
    {
        $statuses = $this->seeders->statuses();

        return view('seeders.index', [
            'seeders' => $statuses,
            'pendingCount' => collect($statuses)->where('done', false)->count(),
        ]);
    }

    public function run(): RedirectResponse
    {
        $result = $this->seeders->runAllPending();

        if ($result['ran'] === []) {
            return redirect()
                ->route('seeders.index')
                ->with('error', 'Seeder installation is already completed.');
        }

        $message = 'Seeder installation completed: '.implode(', ', $result['ran']).'.';

        if ($result['skipped'] !== []) {
            $message .= ' Already completed: '.implode(', ', $result['skipped']).'.';
        }

        return redirect()
            ->route('seeders.index')
            ->with('success', $message);
    }
}
