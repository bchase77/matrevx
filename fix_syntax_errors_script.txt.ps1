# PowerShell Script to Fix Syntax Errors in modules\php directory
# This fixes common syntax issues caused by the previous script

Write-Host "Fixing syntax errors in modules\php..." -ForegroundColor Green

# Check if modules\php directory exists
if (-not (Test-Path "modules\php")) {
    Write-Host "Error: modules\php directory not found!" -ForegroundColor Red
    exit 1
}

# Create additional backup
if (Test-Path "modules\php\Game.php") {
    Copy-Item "modules\php\Game.php" "modules\php\Game.php.syntax-backup" -Force
    Write-Host "Created syntax-backup: modules\php\Game.php.syntax-backup" -ForegroundColor Yellow
}

# Fix Game.php syntax errors
if (Test-Path "modules\php\Game.php") {
    Write-Host "Processing modules\php\Game.php for syntax errors..." -ForegroundColor Cyan
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix 1: Double quote issues in SQL strings
    Write-Host "Fix 1: Correcting SQL string concatenation..." -ForegroundColor Yellow
    
    # Fix malformed SQL concatenations
    $content = $content -replace 'WHERE player_id = " \. \(int\)\$([a-zA-Z_]+)', 'WHERE player_id = " . (int)$$1'
    $content = $content -replace 'wrestler_id = " \. \(int\)\$wrestler_id \. "', 'wrestler_id = " . (int)$wrestler_id . " WHERE player_id = " . (int)$player_id'
    
    # Fix broken UPDATE statements
    $content = $content -replace 'conditioning = " \. \(int\)\$wrestler\[''conditioning_p1''\] \. "', 'conditioning = " . (int)$wrestler[''conditioning_p1''] . " WHERE player_id = " . (int)$player_id'
    $content = $content -replace 'offense = " \. \(int\)\$wrestler\[''offense''\] \. "', 'offense = " . (int)$wrestler[''offense''] . " WHERE player_id = " . (int)$player_id'
    $content = $content -replace 'defense = " \. \(int\)\$wrestler\[''defense''\] \. "', 'defense = " . (int)$wrestler[''defense''] . " WHERE player_id = " . (int)$player_id'
    $content = $content -replace 'top = " \. \(int\)\$wrestler\[''top''\] \. "', 'top = " . (int)$wrestler[''top''] . " WHERE player_id = " . (int)$player_id'
    $content = $content -replace 'bottom = " \. \(int\)\$wrestler\[''bottom''\] \. "', 'bottom = " . (int)$wrestler[''bottom''] . " WHERE player_id = " . (int)$player_id'
    $content = $content -replace 'special_tokens = " \. \(int\)\$wrestler\[''special_tokens''\] \. "', 'special_tokens = " . (int)$wrestler[''special_tokens''] . " WHERE player_id = " . (int)$player_id'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied SQL concatenation fixes"
    
    # Fix 2: Restore proper UPDATE syntax
    Write-Host "Fix 2: Restoring proper UPDATE statements..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Completely rewrite the wrestler assignment section to be safe
    $oldPattern = '\$this->DbQuery\("UPDATE player SET wrestler_id = " \. \(int\)\$wrestler_id \. " WHERE player_id = " \. \(int\)\$player_id"\);[\s]*\$this->DbQuery\("UPDATE player SET conditioning = " \. \(int\)\$wrestler\[''conditioning_p1''\] \. " WHERE player_id = " \. \(int\)\$player_id"\);[\s]*\$this->DbQuery\("UPDATE player SET offense = " \. \(int\)\$wrestler\[''offense''\] \. " WHERE player_id = " \. \(int\)\$player_id"\);[\s]*\$this->DbQuery\("UPDATE player SET defense = " \. \(int\)\$wrestler\[''defense''\] \. " WHERE player_id = " \. \(int\)\$player_id"\);[\s]*\$this->DbQuery\("UPDATE player SET top = " \. \(int\)\$wrestler\[''top''\] \. " WHERE player_id = " \. \(int\)\$player_id"\);[\s]*\$this->DbQuery\("UPDATE player SET bottom = " \. \(int\)\$wrestler\[''bottom''\] \. " WHERE player_id = " \. \(int\)\$player_id"\);[\s]*\$this->DbQuery\("UPDATE player SET special_tokens = " \. \(int\)\$wrestler\[''special_tokens''\] \. " WHERE player_id = " \. \(int\)\$player_id"\);'
    
    $newPattern = '$this->DbQuery("UPDATE player SET wrestler_id = " . (int)$wrestler_id . " WHERE player_id = " . (int)$player_id);
        $this->DbQuery("UPDATE player SET conditioning = " . (int)$wrestler[''conditioning_p1''] . " WHERE player_id = " . (int)$player_id);
        $this->DbQuery("UPDATE player SET offense = " . (int)$wrestler[''offense''] . " WHERE player_id = " . (int)$player_id);
        $this->DbQuery("UPDATE player SET defense = " . (int)$wrestler[''defense''] . " WHERE player_id = " . (int)$player_id);
        $this->DbQuery("UPDATE player SET top = " . (int)$wrestler[''top''] . " WHERE player_id = " . (int)$player_id);
        $this->DbQuery("UPDATE player SET bottom = " . (int)$wrestler[''bottom''] . " WHERE player_id = " . (int)$player_id);
        $this->DbQuery("UPDATE player SET special_tokens = " . (int)$wrestler[''special_tokens''] . " WHERE player_id = " . (int)$player_id);'
    
    # If the complex pattern doesn't work, fix individual DbQuery calls
    $content = $content -replace '\$this->DbQuery\("UPDATE player SET ([a-zA-Z_]+) = " \. \(int\)\$wrestler\[''([a-zA-Z_0-9]+)''\] \. " WHERE player_id = " \. \(int\)\$player_id"\);', '$this->DbQuery("UPDATE player SET $1 = " . (int)$wrestler[''$2''] . " WHERE player_id = " . (int)$player_id);'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied UPDATE statement fixes"
    
    # Fix 3: Fix quote escaping issues
    Write-Host "Fix 3: Fixing quote escaping..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix double-escaped quotes
    $content = $content -replace "''''", "''"
    $content = $content -replace '""', '"'
    
    # Fix common quote patterns
    $content = $content -replace '\[''([a-zA-Z_0-9]+)''\]', '[''$1'']'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied quote escaping fixes"
    
    # Fix 4: Fix array access patterns
    Write-Host "Fix 4: Fixing array access patterns..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix wrestler array access that might be broken
    $content = $content -replace '\(\$wrestler\[''conditioning_p1''\] \?\? 0\)', '$wrestler[''conditioning_p1'']'
    $content = $content -replace '\(\$wrestler\[''offense''\] \?\? 0\)', '$wrestler[''offense'']'
    $content = $content -replace '\(\$wrestler\[''defense''\] \?\? 0\)', '$wrestler[''defense'']'
    $content = $content -replace '\(\$wrestler\[''top''\] \?\? 0\)', '$wrestler[''top'']'
    $content = $content -replace '\(\$wrestler\[''bottom''\] \?\? 0\)', '$wrestler[''bottom'']'
    $content = $content -replace '\(\$wrestler\[''special_tokens''\] \?\? 0\)', '$wrestler[''special_tokens'']'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied array access fixes"
    
    # Fix 5: Fix broken string concatenations
    Write-Host "Fix 5: Fixing string concatenations..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix any remaining malformed concatenations
    $content = $content -replace '" \. \(int\)', '" . (int)'
    $content = $content -replace '\(int\) \. "', '(int) . "'
    $content = $content -replace '" \. " \. ', '" . '
    
    # Fix specific SQL query patterns that might be broken
    $content = $content -replace 'FROM player WHERE player_id = " \. \(int\)\$', 'FROM player WHERE player_id = " . (int)$'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied string concatenation fixes"
    
    # Fix 6: Validate and fix function calls
    Write-Host "Fix 6: Fixing function call syntax..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix any malformed null coalescing that might break syntax
    $content = $content -replace '\(\$player_data\[''conditioning''\] \?\? 0\)', '($player_data[''conditioning''] ?? 0)'
    $content = $content -replace '\(\$player_data\[''special_tokens''\] \?\? 0\)', '($player_data[''special_tokens''] ?? 0)'
    $content = $content -replace '\(\$card\[''conditioning_cost''\] \?\? 0\)', '($card[''conditioning_cost''] ?? 0)'
    $content = $content -replace '\(\$card\[''special_tokens''\] \?\? 0\)', '($card[''special_tokens''] ?? 0)'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied function call fixes"
    
    # Fix 7: Ensure proper method signatures
    Write-Host "Fix 7: Checking method signatures..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Make sure we don't have any broken method declarations
    $content = $content -replace 'public function ([a-zA-Z_]+)\(([^)]*)\) : void', 'public function $1($2): void'
    $content = $content -replace 'private function ([a-zA-Z_]+)\(([^)]*)\) : ([a-zA-Z\\?]+)', 'private function $1($2): $3'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied method signature fixes"
    
    # Fix 8: Final cleanup
    Write-Host "Fix 8: Final syntax cleanup..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Remove any extra spaces in concatenations
    $content = $content -replace ' \. \. ', ' . '
    $content = $content -replace '\. \. ', '. '
    
    # Ensure proper spacing around operators
    $content = $content -replace '"\.\(', '" . ('
    $content = $content -replace '\)\."', ') . "'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied final cleanup"
    
    Write-Host "Game.php syntax fixes complete!" -ForegroundColor Green
} else {
    Write-Host "Game.php not found in modules\php" -ForegroundColor Red
}

Write-Host ""
Write-Host "Syntax error fixes completed!" -ForegroundColor Green
Write-Host "If you still get errors, restore from backup:" -ForegroundColor Yellow
Write-Host "  Copy-Item 'modules\php\Game.php.backup' 'modules\php\Game.php' -Force" -ForegroundColor White
Write-Host ""
Write-Host "Then manually fix the original strict_types issues one by one." -ForegroundColor Cyan