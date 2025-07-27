# PowerShell Script to Fix Strict Types Issues in BGA Game Files
# Run this from the directory containing your PHP files

Write-Host "Starting strict types fixes..." -ForegroundColor Green

# Backup files first
Write-Host "Creating backups..." -ForegroundColor Yellow
Copy-Item "Game.php" "Game.php.backup" -Force
Write-Host "Backup created: Game.php.backup"

# Fix 1: Database query result null handling
Write-Host "Fix 1: Adding null checks to database queries..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Fix getUniqueValueFromDB calls that need null checking
$content = $content -replace 'if \(\$current_wrestler !== null && intval\(\$current_wrestler\) > 0\)', 'if ($current_wrestler !== null && (int)$current_wrestler > 0)'

# Fix player name queries with null fallback
$content = $content -replace '\$player_name = \$this->getUniqueValueFromDB\("SELECT player_name FROM player WHERE player_id = \$player_id"\);[\s]*if \(!\$player_name\) \{[\s]*\$player_name = "Player \$player_id";', '$player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
        if ($player_name === null) {
            $player_name = "Player $player_id";'

Set-Content "Game.php" $content
Write-Host "Applied null check fixes"

# Fix 2: SQL query integer casting
Write-Host "Fix 2: Adding integer casting to SQL queries..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

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

Set-Content "Game.php" $content
Write-Host "Applied SQL casting fixes"

# Fix 3: Comparison operators
Write-Host "Fix 3: Converting to strict comparisons..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Convert == to === for common cases (avoid strings and be careful)
$content = $content -replace '\$player_id == \$offense_player_id', '$player_id === $offense_player_id'
$content = $content -replace '\$player_id == \$defense_player_id', '$player_id === $defense_player_id'
$content = $content -replace '\$player_id == \$first_player_id', '$player_id === $first_player_id'
$content = $content -replace '\$player_id == \$second_player_id', '$player_id === $second_player_id'
$content = $content -replace '\$offense_player == 0', '$offense_player === 0'
$content = $content -replace '\$defense_player == 0', '$defense_player === 0'
$content = $content -replace '\$current_state == 2', '$current_state === 2'

Set-Content "Game.php" $content
Write-Host "Applied strict comparison fixes"

# Fix 4: Type casting for game state values
Write-Host "Fix 4: Adding type casting for game state values..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Cast getGameStateValue results to int where appropriate
$content = $content -replace '\$offense_player = \$this->getGameStateValue\("position_offense"\);', '$offense_player = (int)$this->getGameStateValue("position_offense");'
$content = $content -replace '\$defense_player = \$this->getGameStateValue\("position_defense"\);', '$defense_player = (int)$this->getGameStateValue("position_defense");'
$content = $content -replace '\$first_player_id = \$this->getGameStateValue\("first_player_id"\);', '$first_player_id = (int)$this->getGameStateValue("first_player_id");'
$content = $content -replace '\$second_player_id = \$this->getGameStateValue\("second_player_id"\);', '$second_player_id = (int)$this->getGameStateValue("second_player_id");'
$content = $content -replace '\$momentum_player = \$this->getGameStateValue\("momentum_player"\);', '$momentum_player = (int)$this->getGameStateValue("momentum_player");'

Set-Content "Game.php" $content
Write-Host "Applied game state casting fixes"

# Fix 5: Database result casting
Write-Host "Fix 5: Adding type casting for database results..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Cast intval() to (int) for consistency
$content = $content -replace 'intval\(([^)]+)\)', '(int)$1'

# Add casting for common database result patterns
$content = $content -replace '\$conditioning = \$player_data\[''conditioning''\];', '$conditioning = (int)($player_data[''conditioning''] ?? 0);'
$content = $content -replace '\$tokens = \$player_data\[''special_tokens''\];', '$tokens = (int)($player_data[''special_tokens''] ?? 0);'
$content = $content -replace '\$card_conditioning_cost = \$card\[''conditioning_cost''\];', '$card_conditioning_cost = (int)($card[''conditioning_cost''] ?? 0);'
$content = $content -replace '\$card_token_cost = \$card\[''special_tokens''\];', '$card_token_cost = (int)($card[''special_tokens''] ?? 0);'

Set-Content "Game.php" $content
Write-Host "Applied database result casting fixes"

# Fix 6: Null coalescing for array access
Write-Host "Fix 6: Adding null coalescing operators..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Add null coalescing for wrestler array access
$content = $content -replace '\$wrestler\[''conditioning_p1''\]', '($wrestler[''conditioning_p1''] ?? 0)'
$content = $content -replace '\$wrestler\[''offense''\]', '($wrestler[''offense''] ?? 0)'
$content = $content -replace '\$wrestler\[''defense''\]', '($wrestler[''defense''] ?? 0)'
$content = $content -replace '\$wrestler\[''top''\]', '($wrestler[''top''] ?? 0)'
$content = $content -replace '\$wrestler\[''bottom''\]', '($wrestler[''bottom''] ?? 0)'
$content = $content -replace '\$wrestler\[''special_tokens''\]', '($wrestler[''special_tokens''] ?? 0)'

Set-Content "Game.php" $content
Write-Host "Applied null coalescing fixes"

# Fix 7: Query result null handling
Write-Host "Fix 7: Fixing query result null handling..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Fix specific problematic patterns
$content = $content -replace 'if \(\$player_data\) \{', 'if ($player_data !== null) {'
$content = $content -replace 'if \(!\$player_data\) \{', 'if ($player_data === null) {'

# Fix return value handling for counts
$content = $content -replace '\$players_with_wrestlers = \$this->getUniqueValueFromDB\("SELECT COUNT\(\*\) FROM player WHERE wrestler_id >= 1"\);', '$players_with_wrestlers_result = $this->getUniqueValueFromDB("SELECT COUNT(*) FROM player WHERE wrestler_id >= 1");
        $players_with_wrestlers = $players_with_wrestlers_result !== null ? (int)$players_with_wrestlers_result : 0;'

$content = $content -replace '\$total_players = \$this->getUniqueValueFromDB\("SELECT COUNT\(\*\) FROM player"\);', '$total_players_result = $this->getUniqueValueFromDB("SELECT COUNT(*) FROM player");
        $total_players = $total_players_result !== null ? (int)$total_players_result : 0;'

Set-Content "Game.php" $content
Write-Host "Applied query result handling fixes"

# Fix 8: Clean up SELECT queries with proper casting
Write-Host "Fix 8: Fixing SELECT query formatting..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Fix SELECT queries to use proper integer casting
$content = $content -replace 'FROM player WHERE player_id = \$([a-zA-Z_]+)', 'FROM player WHERE player_id = " . (int)$$1'
$content = $content -replace 'WHERE wrestler_id = \$wrestler_id AND player_id != \$player_id', 'WHERE wrestler_id = " . (int)$wrestler_id . " AND player_id != " . (int)$player_id'

Set-Content "Game.php" $content
Write-Host "Applied SELECT query fixes"

# Fix 9: Math operations type safety
Write-Host "Fix 9: Adding type safety for math operations..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Ensure numeric operations are properly typed
$content = $content -replace '\+ \$points', '+ (int)$points'
$content = $content -replace '\- \$([a-zA-Z_]+_cost)', '- (int)$$1'
$content = $content -replace '\+ \$([a-zA-Z_]+_gain)', '+ (int)$$1'

Set-Content "Game.php" $content
Write-Host "Applied math operation type safety"

# Fix 10: Final cleanup of common patterns
Write-Host "Fix 10: Final cleanup..." -ForegroundColor Cyan

$content = Get-Content "Game.php" -Raw

# Fix any remaining loose ends
$content = $content -replace '!== null && \(int\)\$', '!== null && (int)$'
$content = $content -replace 'if \(\$([a-zA-Z_]+) == 0\)', 'if ($$1 === 0)'
$content = $content -replace 'if \(\$([a-zA-Z_]+) > 0\)', 'if ((int)$$1 > 0)'

Set-Content "Game.php" $content
Write-Host "Applied final cleanup"

Write-Host "All fixes applied successfully!" -ForegroundColor Green
Write-Host "Files modified: Game.php" -ForegroundColor Yellow
Write-Host "Backup available: Game.php.backup" -ForegroundColor Yellow
Write-Host ""
Write-Host "Manual review recommended for:" -ForegroundColor Magenta
Write-Host "1. String comparisons (should use == not ===)" -ForegroundColor White
Write-Host "2. Complex database queries" -ForegroundColor White
Write-Host "3. Array key existence checks" -ForegroundColor White
Write-Host ""
Write-Host "Test your game after these changes!" -ForegroundColor Red