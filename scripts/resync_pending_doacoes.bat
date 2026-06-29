@echo off
rem Wrapper to run the PHP resync script (Windows Task Scheduler friendly)
"C:\xampp\php\php.exe" "C:\xampp\htdocs\petfinder\scripts\resync_pending_doacoes.php" %*
