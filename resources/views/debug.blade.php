<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Info - SISPEMA YASMU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-section { margin-bottom: 2rem; }
        .debug-item { margin-bottom: 1rem; }
        .debug-label { font-weight: bold; color: #495057; }
        .debug-value { font-family: monospace; background: #f8f9fa; padding: 0.5rem; border-radius: 0.25rem; }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">🔍 Debug Info - SISPEMA YASMU</h1>
        
        <!-- System Info -->
        <div class="debug-section">
            <h3>🖥️ System Information</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">PHP Version:</div>
                        <div class="debug-value">{{ $debug['php_version'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Laravel Version:</div>
                        <div class="debug-value">{{ $debug['laravel_version'] }}</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Memory Limit:</div>
                        <div class="debug-value">{{ $debug['memory_limit'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Max Execution Time:</div>
                        <div class="debug-value">{{ $debug['max_execution_time'] }} seconds</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Authentication Info -->
        <div class="debug-section">
            <h3>🔐 Authentication Status</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Is Authenticated:</div>
                        <div class="debug-value">
                            @if($debug['is_authenticated'])
                                <span class="status-ok">✅ YES</span>
                            @else
                                <span class="status-error">❌ NO</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">User Role:</div>
                        <div class="debug-value">{{ $debug['user_role'] }}</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Session ID:</div>
                        <div class="debug-value">{{ $debug['session_id'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">User Info:</div>
                        <div class="debug-value">
                            @if($debug['user'])
                                ID: {{ $debug['user']['id'] }}<br>
                                Name: {{ $debug['user']['name'] }}<br>
                                Email: {{ $debug['user']['email'] }}<br>
                                Role: {{ $debug['user']['role'] }}
                            @else
                                <span class="status-warning">⚠️ No user logged in</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PhpSpreadsheet Info -->
        <div class="debug-section">
            <h3>📊 PhpSpreadsheet Status</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">PhpSpreadsheet Loaded:</div>
                        <div class="debug-value">
                            @if($debug['phpspreadsheet_loaded'])
                                <span class="status-ok">✅ YES - Library Ready</span>
                            @else
                                <span class="status-error">❌ NO - Library Missing</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Route Info -->
        <div class="debug-section">
            <h3>🛣️ Route Information</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Current URL:</div>
                        <div class="debug-value">{{ $debug['route_info']['url'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">HTTP Method:</div>
                        <div class="debug-value">{{ $debug['route_info']['method'] }}</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Current Route:</div>
                        <div class="debug-value">{{ $debug['route_info']['current_route'] ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="debug-item">
                        <div class="debug-label">Middleware:</div>
                        <div class="debug-value">
                            @if(is_array($debug['route_info']['middleware']))
                                @foreach($debug['route_info']['middleware'] as $middleware)
                                    <span class="badge bg-secondary me-1">{{ $middleware }}</span>
                                @endforeach
                            @else
                                {{ $debug['route_info']['middleware'] ?? 'N/A' }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="debug-section">
            <h3>🚀 Quick Actions</h3>
            <div class="row">
                <div class="col-md-4">
                    <a href="/debug-info" class="btn btn-info w-100 mb-2" target="_blank">
                        📋 View JSON Debug
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/debug-export" class="btn btn-success w-100 mb-2">
                        📊 Test Excel Export
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/login" class="btn btn-primary w-100 mb-2">
                        🔑 Go to Login
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="debug-section">
            <h3>📝 Summary</h3>
            <div class="alert alert-info">
                <strong>Status:</strong>
                @if($debug['is_authenticated'])
                    <span class="status-ok">✅ User is logged in</span>
                @else
                    <span class="status-warning">⚠️ User is NOT logged in</span>
                @endif
                
                @if($debug['phpspreadsheet_loaded'])
                    <br><span class="status-ok">✅ PhpSpreadsheet is ready</span>
                @else
                    <br><span class="status-error">❌ PhpSpreadsheet is missing</span>
                @endif
                
                <br><strong>Next Step:</strong>
                @if(!$debug['is_authenticated'])
                    <span class="status-warning">⚠️ Please login first to test export template</span>
                @else
                    <span class="status-ok">✅ You can now test export template</span>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
