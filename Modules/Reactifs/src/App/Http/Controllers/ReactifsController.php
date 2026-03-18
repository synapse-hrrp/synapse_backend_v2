<?php

namespace Modules\Reactifs\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Reactifs\Models\Reactif;
use Modules\Reactifs\Models\ReactifExamenType;
use Modules\Reactifs\App\Services\ReactifStockService;
use Illuminate\Http\Request;

class ReactifsController extends Controller
{
    public function __construct(
        private ReactifStockService $stockService
    ) {}

    public function index()
    {
        $reactifs = Reactif::where('actif', true)
            ->orderBy('nom')
            ->paginate(20);

        $alertes = $this->stockService->getReactifsEnAlerte();

        return view('reactifs::reactifs.index', compact('reactifs', 'alertes'));
    }

    public function create()
    {
        return view('reactifs::reactifs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'            => 'required|string|unique:reactifs,code',
            'nom'             => 'required|string|max:255',
            'unite'           => 'required|string|max:50',
            'stock_actuel'    => 'required|numeric|min:0',
            'stock_minimum'   => 'required|numeric|min:0',
            'stock_maximum'   => 'nullable|numeric|min:0',
            'localisation'    => 'nullable|string|max:255',
            'date_peremption' => 'nullable|date',
            'notes'           => 'nullable|string',
        ]);

        $reactif = Reactif::create($validated);

        return redirect()
            ->route('reactifs.show', $reactif)
            ->with('success', "Réactif {$reactif->nom} créé avec succès.");
    }

    public function show(Reactif $reactif)
    {
        $reactif->load(['mouvements' => fn($q) => $q->latest()->limit(20)]);
        $examenTypes = ReactifExamenType::where('reactif_id', $reactif->id)->get();

        return view('reactifs::reactifs.show', compact('reactif', 'examenTypes'));
    }

    public function edit(Reactif $reactif)
    {
        return view('reactifs::reactifs.edit', compact('reactif'));
    }

    public function update(Request $request, Reactif $reactif)
    {
        $validated = $request->validate([
            'code'            => 'required|string|unique:reactifs,code,' . $reactif->id,
            'nom'             => 'required|string|max:255',
            'unite'           => 'required|string|max:50',
            'stock_minimum'   => 'required|numeric|min:0',
            'stock_maximum'   => 'nullable|numeric|min:0',
            'localisation'    => 'nullable|string|max:255',
            'date_peremption' => 'nullable|date',
            'notes'           => 'nullable|string',
        ]);

        $reactif->update($validated);

        return redirect()
            ->route('reactifs.show', $reactif)
            ->with('success', "Réactif mis à jour.");
    }

    public function destroy(Reactif $reactif)
    {
        $reactif->delete();

        return redirect()
            ->route('reactifs.index')
            ->with('success', "Réactif supprimé.");
    }

    /**
     * Lier un réactif à un type d'examen
     */
    public function lierExamenType(Request $request, Reactif $reactif)
    {
        $validated = $request->validate([
            'examen_type_id'   => 'required|integer',
            'quantite_utilisee'=> 'required|numeric|min:0.001',
            'unite'            => 'nullable|string|max:50',
            'notes'            => 'nullable|string',
        ]);

        ReactifExamenType::updateOrCreate(
            [
                'reactif_id'     => $reactif->id,
                'examen_type_id' => $validated['examen_type_id'],
            ],
            [
                'quantite_utilisee' => $validated['quantite_utilisee'],
                'unite'             => $validated['unite'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'actif'             => true,
            ]
        );

        return back()->with('success', 'Liaison avec le type d\'examen enregistrée.');
    }

    /**
     * Délier un réactif d'un type d'examen
     */
    public function delierExamenType(Reactif $reactif, int $examenTypeId)
    {
        ReactifExamenType::where('reactif_id', $reactif->id)
            ->where('examen_type_id', $examenTypeId)
            ->delete();

        return back()->with('success', 'Liaison supprimée.');
    }
}