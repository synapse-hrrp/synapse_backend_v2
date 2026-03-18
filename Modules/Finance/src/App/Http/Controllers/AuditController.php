<?php

namespace Modules\Finance\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Finance\App\Models\FinanceAudit;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|integer|min:1',
            'evenement'  => 'nullable|string|max:50',
            'user_id'    => 'nullable|integer|min:1',
            'date_debut' => 'nullable|date',
            'date_fin'   => 'nullable|date|after_or_equal:date_debut',
        ]);

        $q = FinanceAudit::query()->orderByDesc('cree_le');

        if ($request->filled('session_id'))
            $q->where('session_id', $request->integer('session_id'));

        if ($request->filled('evenement'))
            $q->where('evenement', $request->string('evenement'));

        if ($request->filled('user_id'))
            $q->where('user_id', $request->integer('user_id'));

        if ($request->filled('date_debut'))
            $q->where('cree_le', '>=', $request->date('date_debut'));

        if ($request->filled('date_fin'))
            $q->where('cree_le', '<=', $request->date('date_fin'));

        return response()->json(['audits' => $q->paginate(30)]);
    }
}
