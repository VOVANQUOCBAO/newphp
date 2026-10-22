param(
    [string]$XamppPath = '',
    [int]$MySqlPort = 0,
    [int]$WebPort = 8000
)

$ErrorActionPreference = 'Stop'
$taskProject = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
if ($WebPort -lt 1 -or $WebPort -gt 65535 -or $MySqlPort -lt 0 -or $MySqlPort -gt 65535) {
    throw 'Cong ket noi phai nam trong 1..65535.'
}
if ([string]::IsNullOrWhiteSpace($XamppPath)) {
    foreach ($taskCandidate in @($env:XAMPP_PATH, 'C:\xampp', 'D:\xampp', 'D:\data')) {
        if (-not [string]::IsNullOrWhiteSpace($taskCandidate)) {
            if (Test-Path -LiteralPath (Join-Path $taskCandidate 'php\php.exe')) {
                $XamppPath = $taskCandidate
                break
            }
        }
    }
}
if ([string]::IsNullOrWhiteSpace($XamppPath)) {
    throw "Khong tim thay XAMPP. Chay lai: .\setup.bat -XamppPath 'E:\xampp'"
}
$taskPhp = Join-Path $XamppPath 'php\php.exe'
if (-not (Test-Path -LiteralPath $taskPhp)) {
    throw "Khong tim thay PHP XAMPP: $taskPhp"
}
$taskVersion = & $taskPhp -r 'echo PHP_VERSION_ID;'
if ($LASTEXITCODE -ne 0 -or [int]$taskVersion -lt 80000) {
    throw 'Du an can PHP 8.0 tro len.'
}

function Test-LocalPort([int]$Port) {
    $taskClient = [System.Net.Sockets.TcpClient]::new()
    try {
        $taskConnect = $taskClient.ConnectAsync('127.0.0.1', $Port)
        return $taskConnect.Wait(200) -and $taskClient.Connected
    } catch {
        return $false
    } finally {
        $taskClient.Dispose()
    }
}

$taskConfigFile = Join-Path $taskProject 'support\config\database.local.php'
$taskConfigExample = Join-Path $taskProject 'support\config\database.example.php'
$taskLegacyConfig = Join-Path $taskProject 'config\database.local.php'
if (-not (Test-Path -LiteralPath $taskConfigFile) -and (Test-Path -LiteralPath $taskLegacyConfig)) {
    Copy-Item -LiteralPath $taskLegacyConfig -Destination $taskConfigFile
}
$taskMysqlIni = Join-Path $XamppPath 'mysql\bin\my.ini'
if (-not (Test-Path -LiteralPath $taskConfigFile) -or $MySqlPort -gt 0) {
    $taskInitialPort = $MySqlPort
    if ($taskInitialPort -eq 0) {
        $taskInitialPort = 3306
        if (Test-Path -LiteralPath $taskMysqlIni) {
            $taskIniText = Get-Content -LiteralPath $taskMysqlIni -Raw
            $taskMysqlSection = [regex]::Match($taskIniText, '(?ms)^\[mysqld\]\s*(.*?)(?=^\[|\z)')
            $taskPortSetting = [regex]::Match($taskMysqlSection.Groups[1].Value, '(?m)^\s*port\s*=\s*(\d+)')
            if ($taskPortSetting.Success) {
                $taskInitialPort = [int]$taskPortSetting.Groups[1].Value
            }
        }
        # Neu MySQL XAMPP da chay bang --port khac my.ini, dung dung cong dang chay.
        $taskMysqlPath = Join-Path $XamppPath 'mysql\bin\mysqld.exe'
        $taskRunningMysql = Get-CimInstance Win32_Process -Filter "Name = 'mysqld.exe'" -ErrorAction SilentlyContinue
        foreach ($taskMysqlInstance in $taskRunningMysql) {
            if ($taskMysqlInstance.ExecutablePath -eq $taskMysqlPath) {
                $taskActivePort = [regex]::Match($taskMysqlInstance.CommandLine, '--port(?:=|\s+)(\d+)')
                if ($taskActivePort.Success) {
                    $taskInitialPort = [int]$taskActivePort.Groups[1].Value
                }
            }
        }
    }
    $taskWriteConfig = '$local = is_file($argv[1]) ? require $argv[1] : require $argv[2]; $local[''port''] = (int)$argv[3]; file_put_contents($argv[1], ''<?php'' . PHP_EOL . PHP_EOL . ''return '' . var_export($local, true) . '';'' . PHP_EOL);'
    & $taskPhp -r $taskWriteConfig $taskConfigFile $taskConfigExample $taskInitialPort
    if ($LASTEXITCODE -ne 0) { throw 'Khong tao duoc cau hinh CSDL local' }
}
$taskReadConfig = 'require $argv[1]; echo json_encode(csdl::cauHinh());'
$taskConfigJson = & $taskPhp -r $taskReadConfig (Join-Path $taskProject 'model\csdl.php')
if ($LASTEXITCODE -ne 0) { throw 'Khong doc duoc cau hinh CSDL' }
$taskDbConfig = $taskConfigJson | ConvertFrom-Json
$taskLogDir = Join-Path $env:TEMP 'phpweb-xampp'
New-Item -Path $taskLogDir -ItemType Directory -Force | Out-Null

if ($taskDbConfig.host -in @('localhost', '127.0.0.1') -and -not (Test-LocalPort $taskDbConfig.port)) {
    $taskMysql = Join-Path $XamppPath 'mysql\bin\mysqld.exe'
    $taskMysqlStart = @{
        FilePath = $taskMysql
        WorkingDirectory = Split-Path $taskMysql
        ArgumentList = @(('--defaults-file="' + $taskMysqlIni + '"'), "--port=$($taskDbConfig.port)", '--bind-address=127.0.0.1', '--console')
        WindowStyle = 'Hidden'
        PassThru = $true
        RedirectStandardOutput = Join-Path $taskLogDir 'mysql.stdout.log'
        RedirectStandardError = Join-Path $taskLogDir 'mysql.stderr.log'
    }
    $taskMysqlProcess = Start-Process @taskMysqlStart
    for ($taskAttempt = 0; $taskAttempt -lt 50 -and -not (Test-LocalPort $taskDbConfig.port); $taskAttempt++) {
        Start-Sleep -Milliseconds 100
    }
    if (-not (Test-LocalPort $taskDbConfig.port)) { throw "MySQL chua khoi dong. Xem $taskLogDir" }
    Write-Output "MySQL XAMPP PID: $($taskMysqlProcess.Id)"
}

& $taskPhp (Join-Path $taskProject 'support\scripts\khoitao_csdl.php')
if ($LASTEXITCODE -ne 0) {
    throw 'Ket noi/khoi tao CSDL that bai. Kiem tra database.local.php; neu cong 3306 thuoc dich vu khac, thu -MySqlPort 3307.'
}

if (-not (Test-LocalPort $WebPort)) {
    $taskWebStart = @{
        FilePath = $taskPhp
        WorkingDirectory = $taskProject
        ArgumentList = @('-S', "127.0.0.1:$WebPort", '-t', ('"' + $taskProject + '"'))
        WindowStyle = 'Hidden'
        PassThru = $true
        RedirectStandardOutput = Join-Path $taskLogDir "php-$WebPort.stdout.log"
        RedirectStandardError = Join-Path $taskLogDir "php-$WebPort.stderr.log"
    }
    $taskWebProcess = Start-Process @taskWebStart
    for ($taskAttempt = 0; $taskAttempt -lt 50 -and -not (Test-LocalPort $WebPort); $taskAttempt++) {
        Start-Sleep -Milliseconds 100
    }
    if (-not (Test-LocalPort $WebPort)) { throw "Server PHP chua khoi dong. Xem $taskLogDir" }
    Write-Output "PHP XAMPP PID: $($taskWebProcess.Id)"
} else {
    Write-Output "Cong $WebPort dang mo. Kiem tra day dung la server cua du an."
}
Write-Output "Mo http://127.0.0.1:$WebPort/"
Write-Output "CSDL: $($taskDbConfig.dbname), MySQL port $($taskDbConfig.port)"
