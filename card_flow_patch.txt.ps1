# PowerShell script to patch card selection flow for simultaneous card selection
# Run this from the top directory of your BGA project

Write-Host "Patching card selection flow for simultaneous selection..." -ForegroundColor Green

# 1. Update states.inc.php - Replace sequential states with simultaneous card selection
Write-Host "Updating states.inc.php..." -ForegroundColor Yellow

$statesFile = "states.inc.php"
if (Test-Path $statesFile) {
    $content = Get-Content $statesFile -Raw
    
    # Replace states 10, 11, and 20 with new simultaneous state
    $content = $content -replace 'name" => "firstPlayerTurn"', 'name" => "cardSelection"'
    $content = $content -replace 'description" => clienttranslate\(.+must play a card.+\)', 'description" => clienttranslate(''Players must choose their cards'')'
    $content = $content -replace 'descriptionmyturn" => clienttranslate\(.+you.+ must play a card.+\)', 'descriptionmyturn" => clienttranslate(''${you} must choose a card'')'
    $content = $content -replace '"type" => "activeplayer",', '"type" => "multipleactiveplayer",'
    $content = $content -replace '"args" => "argPlayerTurn",', '"args" => "argCardSelection",'
    $content = $content -replace '"cardPlayed" => 20', '"allCardsPlayed" => 12'
    
    # Remove the switch and second player states (states 20 and 11)
    $content = $content -replace ',\s*// Switch to second player\s*20 => array\([^}]+\),\s*// Second player selects card\s*11 => array\([^}]+\),', ','
    
    Set-Content $statesFile -Value $content -Encoding UTF8
    Write-Host "Updated states.inc.php successfully" -ForegroundColor Green
} else {
    Write-Host "states.inc.php not found!" -ForegroundColor Red
}

# 2. Create new Game.php methods by appending to the file
Write-Host "Updating modules/php/Game.php..." -ForegroundColor Yellow

$gameFile = "modules/php/Game.php"
if (Test-Path $gameFile) {
    $content = Get-Content $gameFile -Raw
    
    # Add new methods before the closing class brace
$newMethods = @'

    /**
     * NEW: Check if player has already played a card this round
     */
    private function hasPlayerPlayedCard(int $player_id): bool
    {
        $first_player_id = $this->getGameStateValue("first_player_id");
        $second_player_id = $this->getGameStateValue("second_player_id");
        
        return ($player_id == $first_player_id && $this->getGameStateValue("first_player_card") > 0) ||
               ($player_id == $second_player_id && $this->getGameStateValue("second_player_card") > 0);
    }

    /**
     * NEW: Store player's card choice
     */
    private function storePlayerCard(int $player_id, int $card_id): void
    {
        $offense_player_id = (int)$this->getGameStateValue("position_offense");
        
        if ($player_id == $offense_player_id) {
            $this->setGameStateValue("first_player_id", $player_id);
            $this->setGameStateValue("first_player_card", $card_id);
        } else {
            $this->setGameStateValue("second_player_id", $player_id);
            $this->setGameStateValue("second_player_card", $card_id);
        }
    }

    /**
     * NEW: Check if all players have selected cards
     */
    private function checkAllPlayersReady(): bool
    {
        $first_card = $this->getGameStateValue("first_player_card");
        $second_card = $this->getGameStateValue("second_player_card");
        
        return ($first_card > 0) && ($second_card > 0);
    }

    /**
     * NEW: Arguments for simultaneous card selection
     */
    public function argCardSelection(): array
    {
        $result = [
            "playableCardsIds" => [],
            "positions" => []
        ];
        
        $players = $this->getCollectionFromDB("SELECT player_id FROM player");
        
        foreach ($players as $player_id => $player_data) {
            $current_position = $this->getPlayerPosition($player_id);
            $available_cards = $this->getAvailableCardsForPosition($current_position);
            
            $playable_cards = [];
            foreach ($available_cards as $card_id) {
                if ($this->canAffordCard($player_id, $card_id)) {
                    $playable_cards[] = $card_id;
                }
            }
            
            if (empty($playable_cards)) {
                $playable_cards[] = 25; // Stall card
            }
            
            $result["playableCardsIds"][$player_id] = $playable_cards;
            $result["positions"][$player_id] = $current_position;
        }
        
        return $result;
    }
'@
    
    # Insert before the final closing brace
    $pattern = '(\s*}\s*\?\>\s*)$'
    $replacement = $newMethods + "`n" + '${1}'
    $content = $content -replace $pattern, $replacement
    
    # Update actPlayCard method - replace the state handling section
$newActPlayCardLogic = @'
if ($state_name !== 'cardSelection') {
            throw new \BgaUserException("Cannot play card in current game state");
        }

        if ($this->hasPlayerPlayedCard($player_id)) {
            throw new \BgaUserException('You have already played a card this round');
        }

        $args = $this->argCardSelection();
        $playableCardsIds = $args['playableCardsIds'][$player_id] ?? [];

        $this->storePlayerCard($player_id, $card_id);
        
        $this->notifyAllPlayers("cardSelected", '${player_name} has selected a card', [
            "player_id" => $player_id,
            "player_name" => $player_name,
        ]);

        $this->gamestate->setPlayerNonMultiactive($player_id, '');
        
        if ($this->checkAllPlayersReady()) {
            $this->gamestate->nextState('allCardsPlayed');
        }
'@
    
    $content = $content -replace 'if \(\$state_name === ''firstPlayerTurn''\) \{[^}]+\} else if \(\$state_name === ''secondPlayerTurn''\) \{[^}]+\} else \{[^}]+\}', $newActPlayCardLogic
    
    # Update stSetFirstPlayer method
$newSetFirstPlayerLogic = @'
$this->setGameStateValue("first_player_id", 0);
        $this->setGameStateValue("second_player_id", 0);
        $this->setGameStateValue("first_player_card", 0);
        $this->setGameStateValue("second_player_card", 0);
        
        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->nextState("startRound");
'@
    
    $content = $content -replace 'this->gamestate->changeActivePlayer\(\$[^;]+;[^}]+this->gamestate->nextState\("startRound"\);', $newSetFirstPlayerLogic
    
    Set-Content $gameFile -Value $content -Encoding UTF8
    Write-Host "Updated modules/php/Game.php successfully" -ForegroundColor Green
} else {
    Write-Host "modules/php/Game.php not found!" -ForegroundColor Red
}

# 3. Update matrevx.js
Write-Host "Updating matrevx.js..." -ForegroundColor Yellow

$jsFile = "matrevx.js"
if (Test-Path $jsFile) {
    $content = Get-Content $jsFile -Raw
    
    # Add cardSelection case
    $content = $content -replace "(case 'firstPlayerTurn':)", "case 'cardSelection':`n                    this.enterCardSelection(args);`n                    break;`n                    `n                case 'firstPlayerTurn':"
    
    # Add enterCardSelection method
$newMethod = @'

        enterCardSelection: function(args) {
            console.log('Entering card selection', args);
            
            document.getElementById('wrestling-mat').style.display = 'block';
            
            if (this.isCurrentPlayerActive()) {
                const stateArgs = args && args.args;
                const playableCardsIds = (stateArgs && stateArgs.playableCardsIds && stateArgs.playableCardsIds[this.player_id]) 
                    ? stateArgs.playableCardsIds[this.player_id] : [];
                const currentPosition = (stateArgs && stateArgs.positions && stateArgs.positions[this.player_id]) 
                    ? stateArgs.positions[this.player_id] : 'offense';
                
                if (currentPosition) {
                    this.showPositionInfo(currentPosition);
                }
                
                this.displayPlayerHand(playableCardsIds, currentPosition);
            } else {
                const handArea = document.getElementById('player-hand-area');
                if (handArea) {
                    handArea.style.display = 'none';
                }
                
                const gameInfo = document.getElementById('game-info');
                if (gameInfo) {
                    gameInfo.innerHTML = '<div style="padding: 15px; background: #fff3cd; border-radius: 8px; margin: 10px;"><h4>Waiting for all players to select cards...</h4></div>';
                }
            }
        },
'@
    
    $content = $content -replace "(enterPlayerTurn: function\(args\) \{)", ($newMethod + "`n        enterPlayerTurn: function(args) {")
    
    # Add notification subscription - simpler approach
    $subscriptionLine = "			dojo.subscribe('cardSelected', this, `"notif_cardSelected`");"
    $content = $content -replace "(dojo\.subscribe\('wrestlerSelected')", ($subscriptionLine + "`n			dojo.subscribe('wrestlerSelected'")
    
    # Add notification handler
$notifHandler = @'

        notif_cardSelected: function(notif) {
            console.log('notif_cardSelected', notif);
            
            this.showMessage(notif.args.player_name + ' has selected a card', 'info');
            
            if (notif.args.player_id == this.player_id) {
                const handArea = document.getElementById('player-hand-area');
                if (handArea) {
                    handArea.style.display = 'none';
                }
                
                const gameInfo = document.getElementById('game-info');
                if (gameInfo) {
                    gameInfo.innerHTML = '<div style="padding: 15px; background: #d4edda; border-radius: 8px; margin: 10px;"><h4>Card selected! Waiting for other players...</h4></div>';
                }
            }
        },
'@
    
    $content = $content -replace "(notif_wrestlerSelected: function\(notif\) \{)", ($notifHandler + "`n        notif_wrestlerSelected: function(notif) {")
    
    Set-Content $jsFile -Value $content -Encoding UTF8
    Write-Host "Updated matrevx.js successfully" -ForegroundColor Green
} else {
    Write-Host "matrevx.js not found!" -ForegroundColor Red
}

Write-Host ""
Write-Host "Card selection flow patch completed!" -ForegroundColor Green
Write-Host "Changes made:" -ForegroundColor Cyan
Write-Host "- Updated states.inc.php to use simultaneous card selection" -ForegroundColor White
Write-Host "- Modified Game.php to handle multiactive card selection" -ForegroundColor White  
Write-Host "- Updated matrevx.js client-side handling" -ForegroundColor White
Write-Host ""
Write-Host "Players can now select cards simultaneously!" -ForegroundColor Green