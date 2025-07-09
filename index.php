<?php
error_reporting(0);
ini_set('display_errors', 0);

$required_extensions = ['sockets', 'json'];
$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('Required PHP extensions missing: ' . implode(', ', $missing_extensions));
}

class SAMPServerList {
    private $servers = [
        ['ip' => '51.254.139.153', 'port' => 7777, 'type' => 'samp'],
        ['ip' => '89.45.44.38', 'port' => 7777, 'type' => 'samp'],
        ['ip' => '178.33.83.55', 'port' => 7777, 'type' => 'openmp'],
        // Open.MP sunucular için örnek (kendi sunucularınızı ekleyin)
        // ['ip' => 'openmp-sunucu-ip', 'port' => 7777, 'type' => 'openmp']
    ];

    public function queryServer($ip, $port, $type = 'samp') {
        return $this->queryServerSocket($ip, $port, $type);
    }
    
    private function queryServerSocket($ip, $port, $type = 'samp') {
        return $this->queryWithSocketCreate($ip, $port, $type);
    }
    
    private function queryWithSocketCreate($ip, $port, $type = 'samp') {
        if (!extension_loaded('sockets')) {
            return false;
        }
        
        try {
            $old_error_reporting = error_reporting(0);
            
            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$socket) {
                error_reporting($old_error_reporting);
                return false;
            }
            
            @socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));
            @socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 2, 'usec' => 0));
            
            $packet = $this->buildSAMPQuery($ip, $port, $type);
            
            $start_time = microtime(true);
            $sent = @socket_sendto($socket, $packet, strlen($packet), 0, $ip, $port);
            
            if ($sent === false) {
                @socket_close($socket);
                error_reporting($old_error_reporting);
                return false;
            }
            
            $response = '';
            $from = '';
            $port_from = 0;
            $received = @socket_recvfrom($socket, $response, 1024, 0, $from, $port_from);
            
            $ping = round((microtime(true) - $start_time) * 1000);
            @socket_close($socket);
            error_reporting($old_error_reporting);
            
            if ($received === false || strlen($response) < 11) {
                return false;
            }
            
            return $this->parseSAMPResponse($response, $ping, $type);
            
        } catch (Exception $e) {
            if (isset($socket) && $socket) {
                @socket_close($socket);
            }
            if (isset($old_error_reporting)) {
                error_reporting($old_error_reporting);
            }
            return false;
        }
    }
    
    private function buildSAMPQuery($ip, $port, $type = 'samp') {
        $packet = 'SAMP';
        
        $ip_parts = explode('.', $ip);
        foreach ($ip_parts as $part) {
            $packet .= chr((int)$part);
        }
        
        $packet .= pack('v', $port);
        $packet .= 'i';
        
        return $packet;
    }
    
    private function parseSAMPResponse($response, $ping, $type = 'samp') {
        if (strlen($response) < 11 || substr($response, 0, 4) !== 'SAMP') {
            return false;
        }
        
        try {
            $offset = 11;
            
            if ($offset >= strlen($response)) return false;
            
            $password = ord($response[$offset++]);
            
            if ($offset + 1 >= strlen($response)) return false;
            $players = ord($response[$offset]) | (ord($response[$offset + 1]) << 8);
            $offset += 2;
            
            if ($offset + 1 >= strlen($response)) return false;
            $maxplayers = ord($response[$offset]) | (ord($response[$offset + 1]) << 8);
            $offset += 2;
            
            $players = min(max($players, 0), 1000);
            $maxplayers = min(max($maxplayers, 1), 1000);
            if ($players > $maxplayers) $players = $maxplayers;
            
            if ($offset + 3 >= strlen($response)) return false;
            $hostname_len = ord($response[$offset]) | 
                           (ord($response[$offset + 1]) << 8) | 
                           (ord($response[$offset + 2]) << 16) | 
                           (ord($response[$offset + 3]) << 24);
            $offset += 4;
            
            if ($hostname_len < 0 || $hostname_len > 128 || $offset + $hostname_len > strlen($response)) {
                return false;
            }
            
            $hostname = substr($response, $offset, $hostname_len);
            $offset += $hostname_len;
            
            $gamemode = 'Unknown';
            if ($offset + 3 < strlen($response)) {
                $gamemode_len = ord($response[$offset]) | 
                               (ord($response[$offset + 1]) << 8) | 
                               (ord($response[$offset + 2]) << 16) | 
                               (ord($response[$offset + 3]) << 24);
                $offset += 4;
                
                if ($gamemode_len > 0 && $gamemode_len <= 64 && $offset + $gamemode_len <= strlen($response)) {
                    $gamemode = substr($response, $offset, $gamemode_len);
                    $offset += $gamemode_len;
                }
            }
            
            $language = 'English';
            if ($offset + 3 < strlen($response)) {
                $language_len = ord($response[$offset]) | 
                               (ord($response[$offset + 1]) << 8) | 
                               (ord($response[$offset + 2]) << 16) | 
                               (ord($response[$offset + 3]) << 24);
                $offset += 4;
                
                if ($language_len > 0 && $language_len <= 32 && $offset + $language_len <= strlen($response)) {
                    $language = substr($response, $offset, $language_len);
                }
            }
            
            return [
                'online' => true,
                'hostname' => $this->cleanString($hostname),
                'players' => (int)$players,
                'maxplayers' => (int)$maxplayers, 
                'gamemode' => $this->cleanString($gamemode),
                'language' => $this->cleanString($language),
                'password' => $password == 1,
                'ping' => min($ping, 999),
                'source' => 'udp_query',
                'server_type' => $type,
                'is_openmp' => $type === 'openmp'
            ];
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function cleanString($str) {
        if (empty($str)) return '';
        
        $clean = trim($str);
        $clean = str_replace(["\0", "\r", "\n"], '', $clean);
        $clean = preg_replace('/[^\x20-\x7E\x80-\xFF]/u', '', $clean);
        
        if (!mb_check_encoding($clean, 'UTF-8')) {
            $clean = mb_convert_encoding($clean, 'UTF-8', 'auto');
        }
        
        return trim($clean);
    }

    public function getAllServers() {
        $results = [];
        $stats = [
            'total_checked' => 0,
            'online_servers' => 0,
            'failed_queries' => 0,
            'query_methods' => [],
            'errors' => []
        ];
        
        foreach ($this->servers as $server) {
            $stats['total_checked']++;
            
            $server_type = $server['type'] ?? 'samp';
            
            $start_time = microtime(true);
            $info = $this->queryServer($server['ip'], $server['port'], $server_type);
            $query_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($info) {
                $info['ip'] = $server['ip'];
                $info['port'] = $server['port'];
                $info['query_time_ms'] = $query_time;
                $results[] = $info;
                $stats['online_servers']++;
                $stats['query_methods'][$info['source']] = ($stats['query_methods'][$info['source']] ?? 0) + 1;
            } else {
                $stats['failed_queries']++;
                $stats['errors'][] = "Failed to query {$server['ip']}:{$server['port']} ($server_type)";
            }
        }
        
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'stats' => $stats,
            'results_count' => count($results),
            'php_version' => PHP_VERSION,
            'socket_extension' => extension_loaded('sockets'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
        ];
        file_put_contents('samp_query.log', json_encode($log_entry, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        return $results;
    }
}

if (isset($_GET['api']) && $_GET['api'] == 'servers') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    try {
        $start_time = microtime(true);
        $sampList = new SAMPServerList();
        $servers = $sampList->getAllServers();
        $query_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $response = [
            'success' => true,
            'servers' => $servers,
            'count' => count($servers),
            'query_time_ms' => $query_time,
            'socket_extension' => extension_loaded('sockets'),
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'samp_openmp_udp_query'
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'servers' => [],
            'count' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GTA SAMP/Open.MP Sunucu Monitörü</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.025em;
            background: #fafbfc;
            color: #1e293b;
        }
        
        .main-container {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.4);
            transition: all 0.3s ease;
        }
        
        .server-card {
            background: white;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .server-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .server-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .status-online {
            background: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.3);
            animation: pulse-gentle 2s infinite;
        }
        
        @keyframes pulse-gentle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #10b981, #06b6d4);
            height: 4px;
            border-radius: 2px;
            transition: width 0.6s ease;
        }
        
        .btn-primary {
            background: #6366f1;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary:hover {
            background: #5855eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
        
        .btn-secondary {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .btn-secondary:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .search-input {
            background: white;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }
        
        .info-card {
            background: white;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .metric-card {
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
            border: 1px solid #e2e8f0;
        }
        
        .loading-spinner {
            border: 2px solid #f1f5f9;
            border-top: 2px solid #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .connect-btn {
            background: #10b981;
            transition: all 0.2s ease;
        }
        
        .connect-btn:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }
        
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 2rem 0;
        }
        
        .elegant-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .soft-shadow {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
        }
        
        .icon-container {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 8px;
            padding: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .text-muted {
            color: #64748b;
        }
        
        .text-primary {
            color: #6366f1;
        }
        
        .bg-success {
            background-color: #10b981;
        }
        
        .bg-warning {
            background-color: #f59e0b;
        }
        
        .bg-danger {
            background-color: #ef4444;
        }
    </style>
</head>
<body class="main-container" x-data="sampApp()">
    <header class="py-12 px-4">
        <div class="container mx-auto">
            <div class="glass-card rounded-2xl p-8 elegant-shadow">
                <div class="flex items-center justify-between flex-wrap gap-6">
                    <div class="flex items-center space-x-4">
                        <div class="icon-container">
                            <i class="fas fa-gamepad text-xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-1">
                                GTA SAMP/Open.MP Sunucu Monitörü
                            </h1>
                            <p class="text-muted">Gerçek zamanlı sunucu takibi</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="info-card rounded-xl p-4">
                            <p class="text-sm text-muted mb-1">Aktif Sunucular</p>
                            <p class="text-2xl font-semibold text-primary" x-text="stats.online_servers || 0"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 pb-12">
        <div class="mb-8">
            <div class="glass-card rounded-xl p-6 elegant-shadow">
                <div class="flex flex-col lg:flex-row justify-between items-center space-y-4 lg:space-y-0 lg:space-x-6">
                    <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-4">
                        <button @click="refreshServers()" 
                                :disabled="loading"
                                class="btn-primary px-6 py-3 rounded-lg text-white font-medium flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-sync-alt" :class="{'fa-spin': loading}"></i>
                            <span x-text="loading ? 'Yenileniyor...' : 'Yenile'"></span>
                        </button>
                        
                        <div class="flex items-center space-x-2 text-sm text-muted">
                            <div class="w-2 h-2 bg-success rounded-full status-online"></div>
                            <span>Son güncelleme:</span>
                            <span x-text="lastUpdate" class="font-medium text-slate-700"></span>
                        </div>
                    </div>
                    
                    <div class="relative w-full lg:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-slate-400"></i>
                        </div>
                        <input type="text" 
                               x-model="searchTerm"
                               placeholder="Sunucu ara..."
                               class="search-input w-full pl-10 pr-4 py-3 rounded-lg text-slate-700 placeholder-slate-500">
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
            <template x-for="server in filteredServers" :key="server.ip + ':' + server.port">
                <div class="server-card rounded-xl overflow-hidden">
                    <div class="server-header p-4 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full status-online"></div>
                                <span class="text-xs font-medium uppercase tracking-wider bg-white/20 px-2 py-1 rounded">AKTIF</span>
                                <span x-show="server.is_openmp" class="text-xs font-medium uppercase tracking-wider bg-orange-400 px-2 py-1 rounded text-white">OPEN.MP</span>
                            </div>
                            <div class="flex items-center space-x-2 bg-white/20 px-3 py-1 rounded-lg">
                                <i class="fas fa-users text-sm"></i>
                                <span class="font-semibold" x-text="server.players + '/' + server.maxplayers"></span>
                            </div>
                        </div>
                        <h3 class="font-semibold text-lg truncate mb-3" x-text="server.hostname"></h3>
                        
                        <div class="space-y-1">
                            <div class="w-full bg-white/20 rounded-full h-1">
                                <div class="progress-bar h-1 rounded-full"
                                     :style="'width: ' + (server.players / server.maxplayers * 100) + '%'"></div>
                            </div>
                            <div class="text-xs text-white/80" x-text="Math.round((server.players / server.maxplayers) * 100) + '% dolu'"></div>
                        </div>
                    </div>

                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-slate-600 flex items-center">
                                <i class="fas fa-server mr-2 text-blue-500"></i>
                                <span>Sunucu Tipi</span>
                            </span>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium px-3 py-1 rounded" 
                                      :class="server.is_openmp ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'" 
                                      x-text="server.is_openmp ? 'Open.MP' : 'SA-MP'"></span>
                                <code class="bg-slate-100 text-slate-700 px-3 py-1 rounded font-mono text-sm" x-text="server.ip + ':' + server.port"></code>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-slate-600 flex items-center">
                                <i class="fas fa-gamepad mr-2 text-purple-500"></i>
                                Oyun Modu
                            </span>
                            <span class="text-sm font-medium text-slate-800 bg-purple-50 px-3 py-1 rounded truncate max-w-32" x-text="server.gamemode"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-slate-600 flex items-center">
                                <i class="fas fa-globe mr-2 text-green-500"></i>
                                Dil
                            </span>
                            <span class="text-sm font-medium text-slate-800 bg-green-50 px-3 py-1 rounded" x-text="server.language"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 rounded-lg text-center">
                                <i :class="server.password ? 'fas fa-lock text-red-500' : 'fas fa-lock-open text-green-500'" class="text-lg mb-1"></i>
                                <div class="text-xs font-medium text-slate-600">Şifre</div>
                                <div class="text-sm font-medium" :class="server.password ? 'text-red-600' : 'text-green-600'" x-text="server.password ? 'Korumalı' : 'Açık'"></div>
                            </div>
                            
                            <div class="p-3 bg-gray-50 rounded-lg text-center" x-show="server.ping">
                                <i class="text-lg mb-1 fas fa-wifi" :class="server.ping < 50 ? 'text-green-500' : server.ping < 100 ? 'text-yellow-500' : 'text-red-500'"></i>
                                <div class="text-xs font-medium text-slate-600">Ping</div>
                                <div class="text-sm font-medium" :class="server.ping < 50 ? 'text-green-600' : server.ping < 100 ? 'text-yellow-600' : 'text-red-600'" x-text="server.ping + 'ms'"></div>
                            </div>
                        </div>

                        <div class="p-3 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100" x-show="server.query_time_ms">
                            <div class="flex items-center justify-center space-x-2">
                                <i class="fas fa-clock text-blue-500 text-sm"></i>
                                <span class="text-xs sm:text-sm text-slate-600">Sorgu:</span>
                                <span class="font-bold text-blue-600 text-xs sm:text-sm" x-text="server.query_time_ms + 'ms'"></span>
                            </div>
                        </div>

                        <button @click="connectToServer(server.ip, server.port, server.is_openmp)" 
                                class="w-full connect-btn text-white py-3 px-4 rounded-lg font-medium flex items-center justify-center space-x-2">
                            <i class="fas fa-play"></i>
                            <span x-text="server.is_openmp ? 'Open.MP\'ye Bağlan' : 'SAMP\'ye Bağlan'"></span>
                            <i class="fas fa-external-link-alt text-sm"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="loading && servers.length === 0" class="text-center py-16">
            <div class="glass-card rounded-xl p-8 max-w-md mx-auto elegant-shadow">
                <div class="loading-spinner w-12 h-12 mx-auto mb-4"></div>
                <div class="space-y-2">
                    <h3 class="text-xl font-semibold text-slate-800">Sunucular Yükleniyor</h3>
                    <p class="text-muted">Gerçek zamanlı sunucu verileri alınıyor...</p>
                </div>
            </div>
        </div>

        <div x-show="!loading && filteredServers.length === 0" class="text-center py-16">
            <div class="glass-card rounded-xl p-8 max-w-md mx-auto elegant-shadow">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-server text-2xl text-slate-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-slate-800 mb-2">Sunucu Bulunamadı</h3>
                <p class="text-muted mb-4">Arama kriterlerinize uygun aktif sunucu bulunamadı.</p>
                <button @click="searchTerm = ''; refreshServers()" 
                        class="btn-secondary px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-refresh mr-2"></i>
                    Filtreyi Temizle
                </button>
            </div>
        </div>
    </main>

    <footer class="mt-16 py-8">
        <div class="container mx-auto px-4">
            <div class="glass-card rounded-xl p-6 text-center elegant-shadow">
                <div class="space-y-4">
                    <div class="flex justify-center space-x-4">
                        <a href="https://github.com/thislaex/samp-query" target="_blank"
                           class="w-10 h-10 bg-slate-600 rounded-lg flex items-center justify-center text-white hover:bg-slate-700 transition-colors">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://discord.gg/4wE7W3Avef" target="_blank"
                           class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white hover:bg-blue-600 transition-colors">
                            <i class="fab fa-discord"></i>
                        </a>
                    </div>
                    <p class="font-semibold text-slate-800">
                        &copy; 2025 GTA SAMP/Open.MP Sunucu Monitörü
                    </p>
                    <p class="text-muted text-sm">
                        SAMP ve Open.MP sunucu bilgileri gerçek zamanlı UDP sorguları ile güncellenmektedir
                    </p>
                    <div class="flex justify-center items-center space-x-2 text-sm text-muted">
                        <span>Made with</span>
                        <i class="fas fa-heart text-red-400 pulse-slow"></i>
                        <span>laex for SAMP & Open.MP Community.</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function sampApp() {
            return {
                servers: [],
                loading: true,
                searchTerm: '',
                lastUpdate: '',
                stats: {
                    online_servers: 0,
                    total_players: 0
                },

                get filteredServers() {
                    if (!this.searchTerm) return this.servers;
                    return this.servers.filter(server => 
                        server.hostname.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                        server.gamemode.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                        server.ip.includes(this.searchTerm) ||
                        (server.is_openmp && this.searchTerm.toLowerCase().includes('openmp')) ||
                        (server.is_openmp && this.searchTerm.toLowerCase().includes('open.mp')) ||
                        (!server.is_openmp && this.searchTerm.toLowerCase().includes('samp'))
                    );
                },

                async refreshServers() {
                    this.loading = true;
                    try {
                        const response = await fetch('?api=servers');
                        const data = await response.json();
                        
                        if (data.success) {
                            this.servers = (data.servers || []).filter(server => server.online !== false);
                            this.stats.online_servers = this.servers.length;
                            this.stats.total_players = this.servers.reduce((total, server) => total + (server.players || 0), 0);
                            console.log('Aktif sunucu sayısı:', this.servers.length);
                        } else {
                            console.error('API hatası:', data.error);
                            this.servers = [];
                            this.stats.online_servers = 0;
                            this.stats.total_players = 0;
                        }
                        
                        this.lastUpdate = new Date().toLocaleTimeString('tr-TR');
                    } catch (error) {
                        console.error('Sunucular yüklenirken hata:', error);
                        this.servers = [];
                    } finally {
                        this.loading = false;
                    }
                },

                connectToServer(ip, port, isOpenMP = false) {
                    const serverType = isOpenMP ? 'Open.MP' : 'SAMP';
                    const notification = {
                        title: `${serverType} Sunucusuna Bağlanılıyor...`,
                        message: `${ip}:${port} adresine bağlanıyor...`,
                        type: 'info'
                    };
                    
                    this.showNotification(notification);
                    
                    const sampUrl = `samp://${ip}:${port}`;
                    window.location.href = sampUrl;
                    
                    setTimeout(() => {
                        const fallbackNotification = {
                            title: 'Manuel Bağlantı Bilgileri',
                            message: `IP: ${ip}\nPort: ${port}\nTip: ${serverType}\n\n${isOpenMP ? 'Open.MP' : 'SAMP'} istemcinizde bu bilgileri kullanarak manuel olarak bağlanabilirsiniz.`,
                            type: 'warning'
                        };
                        this.showNotification(fallbackNotification);
                    }, 2000);
                },

                showNotification(notification) {
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification(notification.title, {
                            body: notification.message,
                            icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="%234F46E5"/></svg>'
                        });
                    } else {
                        alert(`${notification.title}\n\n${notification.message}`);
                    }
                },

                requestNotificationPermission() {
                    if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }
                },

                init() {
                    this.requestNotificationPermission();
                    
                    this.refreshServers();
                    
                    setInterval(() => {
                        if (!this.loading) {
                            this.refreshServers();
                        }
                    }, 30000);
                    
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden && !this.loading) {
                            this.refreshServers();
                        }
                    });
                    
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                            e.preventDefault();
                            this.refreshServers();
                        }
                        
                        if (e.key === 'Escape') {
                            this.searchTerm = '';
                        }
                        
                        if (e.ctrlKey && e.key === 'f') {
                            e.preventDefault();
                            document.querySelector('input[type="text"]')?.focus();
                        }
                    });
                }
            }
        }
    </script>
</body>
</html>
