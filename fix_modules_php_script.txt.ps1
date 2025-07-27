# PowerShell Script to Fix Strict Types Issues in modules\php directory
# Run this from the root directory of your BGA game

Write-Host "Starting strict types fixes for modules\php..." -ForegroundColor Green

# Check if modules\php directory exists
if (-not (Test-Path "modules\php")) {
    Write-Host "Error: modules\php directory not found!" -ForegroundColor Red
    Write-Host "Make sure you're running this from your game root directory" -ForegroundColor Yellow
    exit 1
}

# Backup files first
Write-Host "Creating backups..." -ForegroundColor Yellow
if (Test-Path "modules\php\Game.php") {
    Copy-Item "modules\php\Game.php" "modules\php\Game.php.backup" -Force
    Write-Host "Backup created: modules\php\Game.php.backup"
} else {
    Write-Host "Warning: modules\php\Game.php not found" -ForegroundColor Yellow
}

if (Test-Path "modules\php\material.inc.php") {
    Copy-Item "modules\php\material.inc.php" "modules\php\material.inc.php.backup" -Force
    Write-Host "Backup created: modules\php\material.inc.php.backup"
} else {
    Write-Host "Warning: modules\php\material.inc.php not found" -ForegroundColor Yellow
}

# Fix Game.php
if (Test-Path "modules\php\Game.php") {
    Write-Host "Processing modules\php\Game.php..." -ForegroundColor Cyan
    
    # Fix 1: Database query result null handling
    Write-Host "Fix 1: Adding null checks to database queries..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix getUniqueValueFromDB calls that need null checking
    $content = $content -replace 'if \(\$current_wrestler !== null && intval\(\$current_wrestler\) > 0\)', 'if ($current_wrestler !== null && (int)$current_wrestler > 0)'
    
    # Fix player name queries with null fallback
    $content = $content -replace '\$player_name = \$this->getUniqueValueFromDB\("SELECT player_name FROM player WHERE player_id = \$player_id"\);[\s]*if \(!\$player_name\) \{[\s]*\$player_name = "Player \$player_id";', '$player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
            if ($player_name === null) {
                $player_name = "Player $player_id";'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied null check fixes to Game.php"
    
    # Fix 2: SQL query integer casting
    Write-Host "Fix 2: Adding integer casting to SQL queries..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix UPDATE queries with variables
    $content = $content -replace 'WHERE player_id = \$player_id', 'WHERE player_id = " . (int)$player_id'
    $content = $content -replace 'WHERE player_id = \$first_player_id', 'WHERE player_id = " . (int)$first_player_id'
    $content = $content -replace 'WHERE player_id = \$second_player_id', 'WHERE player_id = " . (int)$second_player_id'
    $content = $content -replace 'WHERE player_id = \$offense_player_id', 'WHERE player_id = " . (int)$offense_player_id'
    $content = $content -replace 'WHERE player_id = \$defense_player_id', 'WHERE player_id = " . (int)$defense_player_id'
    
    # Fix INSERT/UPDATE value assignments
    $content = $content -replace 'wrestler_id = \$wrestler_id', 'wrestler_id = " . (int)$wrestler_id . "'
    $content = $content -replace 'conditioning = \{([^}]+)\}', 'conditioning = " . (int)$1 . "'
    $content = $content -replace 'offense = \{([^}]+)\}', 'offense = " . (int)$1 . "'
    $content = $content -replace 'defense = \{([^}]+)\}', 'defense = " . (int)$1 . "'
    $content = $content -replace 'top = \{([^}]+)\}', 'top = " . (int)$1 . "'
    $content = $content -replace 'bottom = \{([^}]+)\}', 'bottom = " . (int)$1 . "'
    $content = $content -replace 'special_tokens = \{([^}]+)\}', 'special_tokens = " . (int)$1 . "'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied SQL casting fixes to Game.php"
    
    # Fix 3: Comparison operators
    Write-Host "Fix 3: Converting to strict comparisons..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Convert == to === for common cases (avoid strings and be careful)
    $content = $content -replace '\$player_id == \$offense_player_id', '$player_id === $offense_player_id'
    $content = $content -replace '\$player_id == \$defense_player_id', '$player_id === $defense_player_id'
    $content = $content -replace '\$player_id == \$first_player_id', '$player_id === $first_player_id'
    $content = $content -replace '\$player_id == \$second_player_id', '$player_id === $second_player_id'
    $content = $content -replace '\$offense_player == 0', '$offense_player === 0'
    $content = $content -replace '\$defense_player == 0', '$defense_player === 0'
    $content = $content -replace '\$current_state == 2', '$current_state === 2'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied strict comparison fixes to Game.php"
    
    # Fix 4: Type casting for game state values
    Write-Host "Fix 4: Adding type casting for game state values..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Cast getGameStateValue results to int where appropriate
    $content = $content -replace '\$offense_player = \$this->getGameStateValue\("position_offense"\);', '$offense_player = (int)$this->getGameStateValue("position_offense");'
    $content = $content -replace '\$defense_player = \$this->getGameStateValue\("position_defense"\);', '$defense_player = (int)$this->getGameStateValue("position_defense");'
    $content = $content -replace '\$first_player_id = \$this->getGameStateValue\("first_player_id"\);', '$first_player_id = (int)$this->getGameStateValue("first_player_id");'
    $content = $content -replace '\$second_player_id = \$this->getGameStateValue\("second_player_id"\);', '$second_player_id = (int)$this->getGameStateValue("second_player_id");'
    $content = $content -replace '\$momentum_player = \$this->getGameStateValue\("momentum_player"\);', '$momentum_player = (int)$this->getGameStateValue("momentum_player");'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied game state casting fixes to Game.php"
    
    # Fix 5: Database result casting
    Write-Host "Fix 5: Adding type casting for database results..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Cast intval() to (int) for consistency
    $content = $content -replace 'intval\(([^)]+)\)', '(int)$1'
    
    # Add casting for common database result patterns
    $content = $content -replace '\$conditioning = \$player_data\[''conditioning''\];', '$conditioning = (int)($player_data[''conditioning''] ?? 0);'
    $content = $content -replace '\$tokens = \$player_data\[''special_tokens''\];', '$tokens = (int)($player_data[''special_tokens''] ?? 0);'
    $content = $content -replace '\$card_conditioning_cost = \$card\[''conditioning_cost''\];', '$card_conditioning_cost = (int)($card[''conditioning_cost''] ?? 0);'
    $content = $content -replace '\$card_token_cost = \$card\[''special_tokens''\];', '$card_token_cost = (int)($card[''special_tokens''] ?? 0);'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied database result casting fixes to Game.php"
    
    # Fix 6: Null coalescing for array access
    Write-Host "Fix 6: Adding null coalescing operators..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Add null coalescing for wrestler array access
    $content = $content -replace '\$wrestler\[''conditioning_p1''\]', '($wrestler[''conditioning_p1''] ?? 0)'
    $content = $content -replace '\$wrestler\[''offense''\]', '($wrestler[''offense''] ?? 0)'
    $content = $content -replace '\$wrestler\[''defense''\]', '($wrestler[''defense''] ?? 0)'
    $content = $content -replace '\$wrestler\[''top''\]', '($wrestler[''top''] ?? 0)'
    $content = $content -replace '\$wrestler\[''bottom''\]', '($wrestler[''bottom''] ?? 0)'
    $content = $content -replace '\$wrestler\[''special_tokens''\]', '($wrestler[''special_tokens''] ?? 0)'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied null coalescing fixes to Game.php"
    
    # Fix 7: Query result null handling
    Write-Host "Fix 7: Fixing query result null handling..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix specific problematic patterns
    $content = $content -replace 'if \(\$player_data\) \{', 'if ($player_data !== null) {'
    $content = $content -replace 'if \(!\$player_data\) \{', 'if ($player_data === null) {'
    
    # Fix return value handling for counts
    $content = $content -replace '\$players_with_wrestlers = \$this->getUniqueValueFromDB\("SELECT COUNT\(\*\) FROM player WHERE wrestler_id >= 1"\);', '$players_with_wrestlers_result = $this->getUniqueValueFromDB("SELECT COUNT(*) FROM player WHERE wrestler_id >= 1");
            $players_with_wrestlers = $players_with_wrestlers_result !== null ? (int)$players_with_wrestlers_result : 0;'
    
    $content = $content -replace '\$total_players = \$this->getUniqueValueFromDB\("SELECT COUNT\(\*\) FROM player"\);', '$total_players_result = $this->getUniqueValueFromDB("SELECT COUNT(*) FROM player");
            $total_players = $total_players_result !== null ? (int)$total_players_result : 0;'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied query result handling fixes to Game.php"
    
    # Fix 8: Clean up SELECT queries with proper casting
    Write-Host "Fix 8: Fixing SELECT query formatting..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix SELECT queries to use proper integer casting
    $content = $content -replace 'FROM player WHERE player_id = \$([a-zA-Z_]+)', 'FROM player WHERE player_id = " . (int)$$1'
    $content = $content -replace 'WHERE wrestler_id = \$wrestler_id AND player_id != \$player_id', 'WHERE wrestler_id = " . (int)$wrestler_id . " AND player_id != " . (int)$player_id'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied SELECT query fixes to Game.php"
    
    # Fix 9: Math operations type safety
    Write-Host "Fix 9: Adding type safety for math operations..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Ensure numeric operations are properly typed
    $content = $content -replace '\+ \$points', '+ (int)$points'
    $content = $content -replace '\- \$([a-zA-Z_]+_cost)', '- (int)$$1'
    $content = $content -replace '\+ \$([a-zA-Z_]+_gain)', '+ (int)$$1'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied math operation type safety to Game.php"
    
    # Fix 10: Final cleanup of common patterns
    Write-Host "Fix 10: Final cleanup for Game.php..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\Game.php" -Raw
    
    # Fix any remaining loose ends
    $content = $content -replace '!== null && \(int\)\$', '!== null && (int)$'
    $content = $content -replace 'if \(\$([a-zA-Z_]+) == 0\)', 'if ($$1 === 0)'
    $content = $content -replace 'if \(\$([a-zA-Z_]+) > 0\)', 'if ((int)$$1 > 0)'
    
    Set-Content "modules\php\Game.php" $content
    Write-Host "Applied final cleanup to Game.php"
    
    Write-Host "Game.php processing complete!" -ForegroundColor Green
} else {
    Write-Host "Skipping Game.php - file not found" -ForegroundColor Yellow
}

# Fix material.inc.php if it exists
if (Test-Path "modules\php\material.inc.php") {
    Write-Host "Processing modules\php\material.inc.php..." -ForegroundColor Cyan
    
    # Material file typically just has arrays, but let's add type safety
    Write-Host "Ensuring type safety in material arrays..." -ForegroundColor Yellow
    
    $content = Get-Content "modules\php\material.inc.php" -Raw
    
    # Ensure all numeric values are properly typed (this is mostly for consistency)
    # The material file should already be fine, but let's make sure
    $content = $content -replace '"conditioning_p1" => ([0-9]+)', '"conditioning_p1" => (int)$1'
    $content = $content -replace '"conditioning_p2" => ([0-9]+)', '"conditioning_p2" => (int)$1'
    $content = $content -replace '"conditioning_p3" => ([0-9]+)', '"conditioning_p3" => (int)$1'
    $content = $content -replace '"offense" => ([0-9]+)', '"offense" => (int)$1'
    $content = $content -replace '"defense" => ([0-9]+)', '"defense" => (int)$1'
    $content = $content -replace '"top" => ([0-9]+)', '"top" => (int)$1'
    $content = $content -replace '"bottom" => ([0-9]+)', '"bottom" => (int)$1'
    $content = $content -replace '"special_tokens" => ([0-9]+)', '"special_tokens" => (int)$1'
    $content = $content -replace '"conditioning_cost" => ([0-9]+)', '"conditioning_cost" => (int)$1'
    
    # Actually, let's undo those changes - material arrays should be plain values
    # The (int) casting would be syntactically incorrect in array definitions
    $content = $content -replace '"conditioning_p1" => \(int\)([0-9]+)', '"conditioning_p1" => $1'
    $content = $content -replace '"conditioning_p2" => \(int\)([0-9]+)', '"conditioning_p2" => $1'
    $content = $content -replace '"conditioning_p3" => \(int\)([0-9]+)', '"conditioning_p3" => $1'
    $content = $content -replace '"offense" => \(int\)([0-9]+)', '"offense" => $1'
    $content = $content -replace '"defense" => \(int\)([0-9]+)', '"defense" => $1'
    $content = $content -replace '"top" => \(int\)([0-9]+)', '"top" => $1'
    $content = $content -replace '"bottom" => \(int\)([0-9]+)', '"bottom" => $1'
    $content = $content -replace '"special_tokens" => \(int\)([0-9]+)', '"special_tokens" => $1'
    $content = $content -replace '"conditioning_cost" => \(int\)([0-9]+)', '"conditioning_cost" => $1'
    
    Set-Content "modules\php\material.inc.php" $content
    Write-Host "material.inc.php processing complete!" -ForegroundColor Green
} else {
    Write-Host "Skipping material.inc.php - file not found" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "All fixes applied successfully!" -ForegroundColor Green
Write-Host "Files processed in modules\php:" -ForegroundColor Yellow
if (Test-Path "modules\php\Game.php.backup") {
    Write-Host "  - Game.php (backup: Game.php.backup)" -ForegroundColor White
}
if (Test-Path "modules\php\material.inc.php.backup") {
    Write-Host "  - material.inc.php (backup: material.inc.php.backup)" -ForegroundColor White
}
Write-Host ""
Write-Host "Manual review recommended for:" -ForegroundColor Magenta
Write-Host "1. String comparisons (should use == not ===)" -ForegroundColor White
Write-Host "2. Complex database queries" -ForegroundColor White
Write-Host "3. Array key existence checks" -ForegroundColor White
Write-Host ""
Write-Host "Test your game after these changes!" -ForegroundColor Red