# Run once as Administrator: right-click PowerShell -> Run as administrator, then:
#   Set-Location D:\Laravel\esewa; .\scripts\start-mysql-service.ps1
# Or from Explorer: right-click this file -> Run with PowerShell (as admin).

Set-Service -Name 'MySQL80' -StartupType Manual
Start-Service -Name 'MySQL80'
$s = Get-Service -Name 'MySQL80'
Write-Host "MySQL80 is now $($s.Status)."
