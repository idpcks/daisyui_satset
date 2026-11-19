<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(): Response
    {
        return Inertia::render('audit/Index', [
            'logs' => $this->auditLogs->paginateImpersonationLogs(),
        ]);
    }
}
