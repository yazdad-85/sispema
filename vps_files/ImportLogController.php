<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportLogService;
use Illuminate\Support\Facades\Storage;

class ImportLogController extends Controller
{
    public function index(Request $request)
    {
        $importType = $request->get('type');
        $userId = $request->get('user_id');
        
        $logService = new ImportLogService('', 0);
        $logs = $logService->getDetailedLogs($importType, $userId, 50);
        
        return view('import-logs.index', compact('logs', 'importType', 'userId'));
    }

    public function show($logId)
    {
        $files = Storage::disk('local')->files('import_logs');
        $logData = null;
        
        foreach ($files as $file) {
            if (str_contains($file, $logId)) {
                try {
                    $content = Storage::disk('local')->get($file);
                    $logData = json_decode($content, true);
                    break;
                } catch (\Exception $e) {
                    return back()->with('error', 'Gagal membaca log file');
                }
            }
        }
        
        if (!$logData) {
            return back()->with('error', 'Log tidak ditemukan');
        }
        
        return view('import-logs.show', compact('logData'));
    }

    public function download($logId)
    {
        $files = Storage::disk('local')->files('import_logs');
        
        foreach ($files as $file) {
            if (str_contains($file, $logId)) {
                return Storage::disk('local')->download($file);
            }
        }
        
        return back()->with('error', 'Log file tidak ditemukan');
    }

    public function clear()
    {
        try {
            $files = Storage::disk('local')->files('import_logs');
            foreach ($files as $file) {
                Storage::disk('local')->delete($file);
            }
            
            return back()->with('success', 'Semua log berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus log: ' . $e->getMessage());
        }
    }
}
