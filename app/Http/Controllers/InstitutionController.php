<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstitutionController extends Controller
{
    public function index()
    {
        $institutions = Institution::all();
        return view('institutions.index', compact('institutions'));
    }

    public function create()
    {
        return view('institutions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();
        
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $logoPath;
        }

        Institution::create($data);

        return redirect()->route('institutions.index')->with('success', 'Lembaga berhasil ditambahkan');
    }

    public function show(Institution $institution)
    {
        return view('institutions.show', compact('institution'));
    }

    public function edit(Institution $institution)
    {
        return view('institutions.edit', compact('institution'));
    }

    public function update(Request $request, Institution $institution)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();
        
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($institution->logo && \Storage::disk('public')->exists($institution->logo)) {
                \Storage::disk('public')->delete($institution->logo);
            }
            
            $logoPath = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $logoPath;
        }

        $institution->update($data);

        return redirect()->route('institutions.index')->with('success', 'Data lembaga berhasil diperbarui');
    }

    public function destroy(Institution $institution)
    {
        // Delete logo if exists
        if ($institution->logo && \Storage::disk('public')->exists($institution->logo)) {
            \Storage::disk('public')->delete($institution->logo);
        }

        $institution->delete();

        return redirect()->route('institutions.index')->with('success', 'Lembaga berhasil dihapus');
    }
}
