# Register Windows Scheduled Task to run resync_pending_doacoes.php periodically
# Run this script as Administrator in PowerShell

param(
    [string]$PhpPath = '',
    [int]$IntervalMinutes = 5,
    [string]$TaskName = 'PetFinder_ResyncPix',
    [switch]$Force
)

Write-Host "Preparing to register scheduled task: $TaskName"

if (-not $PhpPath) {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCmd) {
        $PhpPath = $phpCmd.Source
    }
}

if (-not $PhpPath) {
    Write-Host "PHP executable not found in PATH. Please provide path with -PhpPath 'C:\\php\\php.exe'" -ForegroundColor Red
    exit 1
}

$scriptPath = "C:\\xampp\\htdocs\\petfinder\\scripts\\resync_pending_doacoes.php"
if (-not (Test-Path $scriptPath)) {
    Write-Host "Resync script not found: $scriptPath" -ForegroundColor Red
    exit 1
}

# Checar tarefas existentes para evitar duplicação
Write-Host "Checking for existing scheduled task named: $TaskName"
try {
    $check = Start-Process -FilePath schtasks -ArgumentList "/Query","/TN",$TaskName -NoNewWindow -Wait -PassThru -ErrorAction SilentlyContinue
    if ($check.ExitCode -eq 0) {
        Write-Host "A task with name '$TaskName' already exists." -ForegroundColor Yellow
        if ($Force) {
            Write-Host "Removing existing task due to -Force flag..."
            Start-Process -FilePath schtasks -ArgumentList "/Delete","/TN",$TaskName,"/F" -Wait -NoNewWindow
        } else {
            Write-Host "Use -Force to delete and recreate the task, or choose a different TaskName." -ForegroundColor Yellow
            exit 0
        }
    }
} catch {
    # If query fails, continue to creation
}

# Mostrar scripts que podem agendar tarefas para revisão
$scriptFiles = Get-ChildItem -Path "C:\xampp\htdocs\petfinder\scripts" -File | Select-Object -ExpandProperty Name
Write-Host "Scripts in scripts/ directory:" -ForegroundColor Cyan
$scriptFiles | ForEach-Object { Write-Host " - $_" }
Write-Host "If you already have cron/scheduled tasks set up, ensure they do not duplicate this job." -ForegroundColor Cyan

# Build the schtasks command
$quotedAction = "`"$PhpPath`" `"$scriptPath`""
$cmd = "schtasks /Create /SC MINUTE /MO $IntervalMinutes /TN $TaskName /TR $quotedAction /F /RL HIGHEST"

Write-Host "Registering scheduled task (may require admin privileges)..."
Write-Host $cmd

$proc = Start-Process -FilePath schtasks -ArgumentList "/Create","/SC","MINUTE","/MO",$IntervalMinutes,"/TN",$TaskName,"/TR",$quotedAction,"/F","/RL","HIGHEST" -Wait -NoNewWindow -PassThru
if ($proc.ExitCode -eq 0) {
    Write-Host "Scheduled task registered: $TaskName" -ForegroundColor Green
    Write-Host "Task will run every $IntervalMinutes minutes using: $PhpPath $scriptPath"
} else {
    Write-Host "Failed to register scheduled task. Exit code: $($proc.ExitCode)" -ForegroundColor Red
}

Write-Host "To remove the task: schtasks /Delete /TN $TaskName /F"