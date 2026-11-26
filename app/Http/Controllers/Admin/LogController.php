<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Logboek: wie deed wat en wanneer (inloggen, registreren, challenges, activiteiten, admin-acties).
 */
class LogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): View
    {
        $action = (string) $request->query('action', '');
        $action = array_key_exists($action, AuditLog::ACTIONS) ? $action : '';
        $search = trim((string) $request->query('q', ''));

        $logs = AuditLog::query()
            ->with('user:id,username')
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($search !== '', fn ($query) => $query->whereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($search).'%']))
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'actions' => AuditLog::ACTIONS,
            'action' => $action,
            'search' => $search,
        ]);
    }
}
