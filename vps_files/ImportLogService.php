<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportLogService
{
    private $logData = [];
    private $errors = [];
    private $warnings = [];
    private $success = [];
    private $importType;
    private $userId;
    private $fileName;

    public function __construct($importType, $userId, $fileName = null)
    {
        $this->importType = $importType;
        $this->userId = $userId;
        $this->fileName = $fileName;
        
        $this->logData = [
            'import_type' => $importType,
            'user_id' => $userId,
            'file_name' => $fileName,
            'started_at' => now(),
            'errors' => [],
            'warnings' => [],
            'success' => [],
            'summary' => [
                'total_rows' => 0,
                'processed_rows' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'warning_count' => 0
            ]
        ];
    }

    public function setTotalRows($count)
    {
        $this->logData['summary']['total_rows'] = $count;
    }

    public function addError($rowNumber, $message, $data = [])
    {
        $error = [
            'row' => $rowNumber,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()
        ];
        
        $this->errors[] = $error;
        $this->logData['errors'][] = $error;
        $this->logData['summary']['error_count']++;
        
        // Log to Laravel log
        Log::error("Import Error - {$this->importType}", [
            'user_id' => $this->userId,
            'row' => $rowNumber,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function addWarning($rowNumber, $message, $data = [])
    {
        $warning = [
            'row' => $rowNumber,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()
        ];
        
        $this->warnings[] = $warning;
        $this->logData['warnings'][] = $warning;
        $this->logData['summary']['warning_count']++;
        
        // Log to Laravel log
        Log::warning("Import Warning - {$this->importType}", [
            'user_id' => $this->userId,
            'row' => $rowNumber,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function addSuccess($rowNumber, $message, $data = [])
    {
        $success = [
            'row' => $rowNumber,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()
        ];
        
        $this->success[] = $success;
        $this->logData['success'][] = $success;
        $this->logData['summary']['success_count']++;
    }

    public function incrementProcessed()
    {
        $this->logData['summary']['processed_rows']++;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function getSummary()
    {
        return $this->logData['summary'];
    }

    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    public function hasWarnings()
    {
        return count($this->warnings) > 0;
    }

    public function getErrorCount()
    {
        return count($this->errors);
    }

    public function getWarningCount()
    {
        return count($this->warnings);
    }

    public function getSuccessCount()
    {
        return count($this->success);
    }

    public function finish()
    {
        $this->logData['finished_at'] = now();
        $this->logData['duration'] = $this->logData['started_at']->diffInSeconds($this->logData['finished_at']);
        
        // Log summary
        Log::info("Import Completed - {$this->importType}", [
            'user_id' => $this->userId,
            'summary' => $this->logData['summary'],
            'duration' => $this->logData['duration']
        ]);

        // Save detailed log to file
        $this->saveDetailedLog();
        
        return $this->logData;
    }

    private function saveDetailedLog()
    {
        $logFileName = "import_logs/{$this->importType}_" . now()->format('Y_m_d_H_i_s') . "_user_{$this->userId}.json";
        
        try {
            Storage::disk('local')->put($logFileName, json_encode($this->logData, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::error("Failed to save detailed import log", [
                'error' => $e->getMessage(),
                'file' => $logFileName
            ]);
        }
    }

    public function getDetailedLogs($importType = null, $userId = null, $limit = 10)
    {
        $logs = [];
        $files = Storage::disk('local')->files('import_logs');
        
        foreach ($files as $file) {
            if ($importType && !str_contains($file, $importType)) continue;
            if ($userId && !str_contains($file, "user_{$userId}")) continue;
            
            try {
                $content = Storage::disk('local')->get($file);
                $logData = json_decode($content, true);
                $logs[] = $logData;
            } catch (\Exception $e) {
                Log::error("Failed to read import log file", [
                    'file' => $file,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Sort by started_at desc
        usort($logs, function($a, $b) {
            return strtotime($b['started_at']) - strtotime($a['started_at']);
        });
        
        return array_slice($logs, 0, $limit);
    }

    public function formatErrorsForDisplay()
    {
        $formatted = [];
        
        foreach ($this->errors as $error) {
            $formatted[] = "Baris {$error['row']}: {$error['message']}";
        }
        
        return $formatted;
    }

    public function formatWarningsForDisplay()
    {
        $formatted = [];
        
        foreach ($this->warnings as $warning) {
            $formatted[] = "Baris {$warning['row']}: {$warning['message']}";
        }
        
        return $formatted;
    }
}
