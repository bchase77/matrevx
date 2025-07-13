<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * matrevx implementation : © Mike McKeever, Jack McKeever, Bryan Chase <bryanchase@yahoo.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\matrevx;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    private static array $CARD_TYPES;
    private static array $WRESTLERS;

    /**
     * Your global variables labels:
     */
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            "current_period" => 10,
            "current_round" => 11,
            "position_offense" => 12,
            "position_defense" => 13,
            "red_die" => 14,
            "blue_die" => 15,
            // Round state tracking
            "first_player_id" => 16,
            "second_player_id" => 17,
            "first_player_card" => 18,
            "second_player_card" => 19,
            // NEW: Track which die each player chose and their values
            "first_player_die_choice" => 20,  // 'red' or 'blue'
            "second_player_die_choice" => 21, // 'red' or 'blue'
            "first_player_die_value" => 22,   // actual rolled value
            "second_player_die_value" => 23,  // actual rolled value
            // Existing variants
            "my_first_game_variant" => 100,
            "my_second_game_variant" => 101,
        ]);
        
        // Load material from material.inc.php
        $material = require(__DIR__ . '/material.inc.php');
        self::$CARD_TYPES = $material['cardTypes'];
        self::$WRESTLERS = $material['wrestlers'];
    }

    /**
     * Get current position for a player - IMPROVED with debugging and error handling
     */
    private function getPlayerPosition(int $player_id): string
    {
        $offense_player = $this->getGameStateValue("position_offense");
        $defense_player = $this->getGameStateValue("position_defense");
        
        $this->trace("getPlayerPosition: Player $player_id, offense_player=$offense_player, defense_player=$defense_player");
        
        // ADD: Check if positions are set
        if ($offense_player == 0 || $defense_player == 0) {
            $this->trace("getPlayerPosition: WARNING - Positions not set yet, defaulting to offense");
            return "offense";
        }
        
        // For now, we only have offense/defense positions
        if ($player_id == $offense_player) {
            $this->trace("getPlayerPosition: Player $player_id is OFFENSE");
            return "offense";
        } else if ($player_id == $defense_player) {
            $this->trace("getPlayerPosition: Player $player_id is DEFENSE");
            return "defense";
        }
        
        // Default fallback - SHOULD NOT HAPPEN
        $this->trace("getPlayerPosition: WARNING - Player $player_id position unknown, defaulting to offense");
        return "offense";
    }

    /**
     * Get available cards based on player's current position
     */
    private function getAvailableCardsForPosition(string $position): array
    {
        $available_cards = [];
        
        foreach (self::$CARD_TYPES as $card_id => $card) {
            // Include cards that match the position OR are "any" position
            if ($card['position'] === $position || $card['position'] === 'any') {
                $available_cards[] = $card_id;
            }
        }
        
        return $available_cards;
    }

    /**
     * Check if player can afford to play a card - IMPROVED with safety checks
     */
    private function canAffordCard(int $player_id, int $card_id): bool
    {
        // ADD: Check if card exists
        if (!isset(self::$CARD_TYPES[$card_id])) {
            $this->trace("canAffordCard: Card $card_id does not exist");
            return false;
        }
        
        $card = self::$CARD_TYPES[$card_id];
        
        // Get player's current resources
        $player_data = $this->getObjectFromDB(
            "SELECT conditioning, special_tokens FROM player WHERE player_id = $player_id"
        );
        
        if (!$player_data) {
            $this->trace("canAffordCard: Could not find player data for $player_id");
            return false;
        }
        
        // Ensure we have valid values
        $conditioning = intval($player_data['conditioning']);
        $tokens = intval($player_data['special_tokens']);
        $card_conditioning_cost = intval($card['conditioning_cost']);
        $card_token_cost = intval($card['special_tokens']);
        
        // Check if player has enough conditioning and special tokens
        return ($conditioning >= $card_conditioning_cost) && 
               ($tokens >= $card_token_cost);
    }

    /**
     * FINAL: Wrestler selection with correct BGA methods only
     */
    public function actSelectWrestler(int $wrestler_id): void
    {
        $player_id = (int)$this->getCurrentPlayerId();
        
        $this->trace("actSelectWrestler: START - Player $player_id selecting wrestler $wrestler_id");
        
        // Validate wrestler exists
        if (!isset(self::$WRESTLERS[$wrestler_id])) {
            throw new \BgaUserException('Invalid wrestler selection');
        }

        // Check if player already has a wrestler
        $current_wrestler = $this->getUniqueValueFromDB("SELECT wrestler_id FROM player WHERE player_id = $player_id");
        if ($current_wrestler !== null && intval($current_wrestler) > 0) {
            throw new \BgaUserException('You have already selected a wrestler');
        }

        // Check if wrestler already taken by another player
        $existing_player = $this->getUniqueValueFromDB("SELECT player_id FROM player WHERE wrestler_id = $wrestler_id AND player_id != $player_id");
        if ($existing_player) {
            throw new \BgaUserException('Wrestler already selected by another player');
        }

        // Assign wrestler to player
        $wrestler = self::$WRESTLERS[$wrestler_id];
        
        $this->trace("actSelectWrestler: Updating player $player_id with wrestler data");
        $this->DbQuery("UPDATE player SET wrestler_id = $wrestler_id WHERE player_id = $player_id");
        $this->DbQuery("UPDATE player SET conditioning = {$wrestler['conditioning_p1']} WHERE player_id = $player_id");
        $this->DbQuery("UPDATE player SET offense = {$wrestler['offense']} WHERE player_id = $player_id");
        $this->DbQuery("UPDATE player SET defense = {$wrestler['defense']} WHERE player_id = $player_id");
        $this->DbQuery("UPDATE player SET top = {$wrestler['top']} WHERE player_id = $player_id");
        $this->DbQuery("UPDATE player SET bottom = {$wrestler['bottom']} WHERE player_id = $player_id");
        $this->DbQuery("UPDATE player SET special_tokens = {$wrestler['special_tokens']} WHERE player_id = $player_id");

        $this->trace("actSelectWrestler: Successfully updated player $player_id with wrestler $wrestler_id");

        // Get player name from database for notification
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");
        if (!$player_name) {
            $player_name = "Player $player_id"; // Fallback if name not found
        }
        
        // Notify all players
        $this->notifyAllPlayers("wrestlerSelected", clienttranslate('${player_name} selected ${wrestler_name}'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
            "wrestler_id" => $wrestler_id,
            "wrestler_name" => $wrestler['name'],
        ]);

        // Make player inactive in multiactive state
        $this->gamestate->setPlayerNonMultiactive($player_id, '');
        $this->trace("actSelectWrestler: Set player $player_id as non-multiactive");
        
        // Better query to count players with wrestlers
        $players_with_wrestlers = $this->getUniqueValueFromDB("SELECT COUNT(*) FROM player WHERE wrestler_id >= 1");     
        $total_players = $this->getUniqueValueFromDB("SELECT COUNT(*) FROM player");
        
        $this->trace("actSelectWrestler: Progress - $players_with_wrestlers / $total_players players have selected");

        // If all players have selected, transition to next state
        if ($players_with_wrestlers >= $total_players) {
            $this->trace("actSelectWrestler: All players selected - checking if transition needed");
            $current_state = $this->gamestate->state_id();
            if ($current_state == 2) { // Only transition if still in wrestler selection state
                $this->trace("actSelectWrestler: Transitioning to next state");
                $this->gamestate->nextState('allSelected');
            } else {
                $this->trace("actSelectWrestler: Already transitioned to state $current_state, no action needed");
            }
        } else {
            $this->trace("actSelectWrestler: Still waiting - need " . ($total_players - $players_with_wrestlers) . " more selections");
        }
    }

    /**
     * Position selection action
     */
    public function actSelectPosition(string $position): void
    {
        $player_id = (int)$this->getCurrentPlayerId();
        
        // Validate position
        if (!in_array($position, ['offense', 'defense'])) {
            throw new \BgaUserException('Invalid position selection');
        }

        // Get both players
        $players = $this->getCollectionFromDB("SELECT player_id, player_name FROM player");
        $other_player_id = null;
        foreach ($players as $pid => $player) {
            if ($pid != $player_id) {
                $other_player_id = $pid;
                break;
            }
        }

        // Get current player name
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");

        // Set positions
        $offense_player_id = $position === 'offense' ? $player_id : $other_player_id;
        $defense_player_id = $position === 'defense' ? $player_id : $other_player_id;
        
        $this->setGameStateValue("position_offense", $offense_player_id);
        $this->setGameStateValue("position_defense", $defense_player_id);

        // Notify all players about position selection
        $this->notifyAllPlayers("positionSelected", clienttranslate('${player_name} chooses ${position}. Match begins!'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
            "position" => ucfirst($position),
            "offense_player_id" => $offense_player_id,
            "defense_player_id" => $defense_player_id,
            "period" => 1,
            "round" => 1
        ]);

        $this->gamestate->nextState("positionSelected");
    }
    
    /**
     * NEW: Player chooses which die to roll (red or blue)
     */
    public function actChooseDie(string $die_choice): void
    {
        $player_id = (int)$this->getCurrentPlayerId();
        $state_name = $this->gamestate->state()['name'];
        
        $this->trace("actChooseDie: Player $player_id choosing $die_choice in state $state_name");

        // Validate die choice
        if (!in_array($die_choice, ['red', 'blue'])) {
            throw new \BgaUserException('Invalid die choice - must be red or blue');
        }

        // Get player name
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
        if (!$player_name) {
            $player_name = "Player $player_id";
        }

        $die_label = $die_choice === 'red' ? 'Red (STRENGTH)' : 'Blue (SPEED)';
        $this->trace("actChooseDie: Player $player_id chose $die_label");

        // Store the player's die choice
        if ($state_name === 'firstPlayerChooseDie') {
            $this->setGameStateValue("first_player_die_choice", $die_choice === 'red' ? 1 : 2); // 1=red, 2=blue
        } else if ($state_name === 'secondPlayerChooseDie') {
            $this->setGameStateValue("second_player_die_choice", $die_choice === 'red' ? 1 : 2);
        }

        // Roll the chosen die
        if ($die_choice === 'red') {
            $die_value = $this->rollRedDie();
            $die_face = $this->getGameStateValue("red_die");
            
            // Apply dice costs and rewards
            $this->applyDiceCosts($player_id, 'red');
            
        } else { // blue
            $die_value = $this->rollBlueDie();
            $die_face = $this->getGameStateValue("blue_die");
            
            // Apply dice costs and rewards
            $this->applyDiceCosts($player_id, 'blue');
        }

        // Store the player's die value
        if ($state_name === 'firstPlayerChooseDie') {
            $this->setGameStateValue("first_player_die_value", $die_value);
        } else if ($state_name === 'secondPlayerChooseDie') {
            $this->setGameStateValue("second_player_die_value", $die_value);
        }

        // Notify all players about the die choice and roll
        $this->notifyAllPlayers("playerChoseDie", clienttranslate('${player_name} chose ${die_label} and rolled: ${die_value}'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
            "die_choice" => $die_choice,
            "die_label" => $die_label,
            "die_face" => $die_face,
            "die_value" => $die_value,
        ]);

        $this->gamestate->nextState("diceChosen");
    }

    /**
     * First player reroll state - reroll their chosen die
     */
    public function stFirstPlayerReroll(): void
    {
        $player_id = (int)$this->getActivePlayerId();
        
        $this->trace("stFirstPlayerReroll: Player $player_id rerolling their chosen die");
        
        // Get which die they originally chose
        $die_choice_value = $this->getGameStateValue("first_player_die_choice");
        $die_type = $die_choice_value == 1 ? 'red' : 'blue';
        
        // Reroll the same die they chose
        if ($die_type === 'red') {
            $die_value = $this->rollRedDie();
            $die_face = $this->getGameStateValue("red_die");
        } else {
            $die_value = $this->rollBlueDie();
            $die_face = $this->getGameStateValue("blue_die");
        }
        
        // Update their stored die value
        $this->setGameStateValue("first_player_die_value", $die_value);
        
        // Get player name
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
        if (!$player_name) {
            $player_name = "Player $player_id";
        }
        
        $die_label = $die_type === 'red' ? 'Red (STRENGTH)' : 'Blue (SPEED)';
        
        // Notify about the reroll result
        $this->notifyAllPlayers("diceRerolled", clienttranslate('${player_name} rerolled ${die_label}: ${die_value}'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
            "die_choice" => $die_type,
            "die_label" => $die_label,
            "die_face" => $die_face,
            "die_value" => $die_value,
        ]);
        
        $this->gamestate->nextState("rerolled");
    }

    /**
     * Second player reroll state - reroll their chosen die
     */
    public function stSecondPlayerReroll(): void
    {
        $player_id = (int)$this->getActivePlayerId();
        
        $this->trace("stSecondPlayerReroll: Player $player_id rerolling their chosen die");
        
        // Get which die they originally chose
        $die_choice_value = $this->getGameStateValue("second_player_die_choice");
        $die_type = $die_choice_value == 1 ? 'red' : 'blue';
        
        // Reroll the same die they chose
        if ($die_type === 'red') {
            $die_value = $this->rollRedDie();
            $die_face = $this->getGameStateValue("red_die");
        } else {
            $die_value = $this->rollBlueDie();
            $die_face = $this->getGameStateValue("blue_die");
        }
        
        // Update their stored die value
        $this->setGameStateValue("second_player_die_value", $die_value);
        
        // Get player name
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
        if (!$player_name) {
            $player_name = "Player $player_id";
        }
        
        $die_label = $die_type === 'red' ? 'Red (STRENGTH)' : 'Blue (SPEED)';
        
        // Notify about the reroll result
        $this->notifyAllPlayers("diceRerolled", clienttranslate('${player_name} rerolled ${die_label}: ${die_value}'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
            "die_choice" => $die_type,
            "die_label" => $die_label,
            "die_face" => $die_face,
            "die_value" => $die_value,
        ]);
        
        $this->gamestate->nextState("rerolled");
    }
    
    /**
     * Updated reroll methods to use player's chosen die
     */
    public function actRerollDice(): void
    {
        $player_id = (int)$this->getCurrentPlayerId();
        $state_name = $this->gamestate->state()['name'];
        
        $this->trace("actRerollDice: Player $player_id wants to reroll in state $state_name");

        // Check if player can afford reroll
        if (!$this->canPlayerReroll($player_id)) {
            throw new \BgaUserException("You need at least 1 token to reroll");
        }

        // Deduct 1 token for reroll
        $this->DbQuery("UPDATE player SET special_tokens = special_tokens - 1 WHERE player_id = $player_id");

        // Get player name
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
        if (!$player_name) {
            $player_name = "Player $player_id";
        }

        $this->notifyAllPlayers("playerReroll", clienttranslate('${player_name} spends 1 token to reroll'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
        ]);

        $this->gamestate->nextState("reroll");
    }

    /**
     * Updated reroll argument method - IMPROVED with error handling
     */
    public function argRerollOption(): array
    {
        $player_id = (int)$this->getActivePlayerId();
        $can_reroll = $this->canPlayerReroll($player_id);
        
        $state_name = $this->gamestate->state()['name'];
        
        // Determine which player and get their die choice and value
        $die_choice_value = 0;
        $die_value = 0;
        
        if (str_contains($state_name, 'first')) {
            $die_choice_value = $this->getGameStateValue("first_player_die_choice");
            $die_value = $this->getGameStateValue("first_player_die_value");
        } else {
            $die_choice_value = $this->getGameStateValue("second_player_die_choice");
            $die_value = $this->getGameStateValue("second_player_die_value");
        }
        
        // ADD: Safety check for die choice value
        if ($die_choice_value == 0) {
            $this->trace("argRerollOption: WARNING - No die choice recorded, defaulting to red");
            $die_choice_value = 1; // Default to red
        }
        
        $die_type = $die_choice_value == 1 ? 'red' : 'blue';
        
        // GET: Current tokens safely
        $current_tokens = $this->getUniqueValueFromDB("SELECT special_tokens FROM player WHERE player_id = $player_id");
        if ($current_tokens === null) {
            $current_tokens = 0;
        }
        
        $this->trace("argRerollOption: Player $player_id, die_type=$die_type, value=$die_value, can_reroll=" . ($can_reroll ? 'YES' : 'NO'));
        
        return [
            "can_reroll" => $can_reroll,
            "die_type" => $die_type,
            "die_value" => $die_value,
            "current_tokens" => $current_tokens
        ];
    }
    
    /**
     * Fixed actPlayCard - consistent use of global variables
     */
    public function actPlayCard(int $card_id): void
    {
        $player_id = (int)$this->getCurrentPlayerId();
        $state_name = $this->gamestate->state()['name'];

        $this->trace("actPlayCard: Player $player_id playing card $card_id in state $state_name");

        // Validate that we have a valid player ID
        if ($player_id <= 0) {
            $this->trace("actPlayCard: ERROR - Invalid player ID: $player_id");
            throw new \BgaUserException('Invalid player - please refresh the page');
        }

        // Validate card choice using the same logic as argPlayerTurn
        $args = $this->argPlayerTurn();
        $playableCardsIds = $args['playableCardsIds'];
        
        $this->trace("actPlayCard: Playable cards for player $player_id: " . implode(', ', $playableCardsIds));
        
        if (!in_array($card_id, $playableCardsIds)) {
            $this->trace("actPlayCard: ERROR - Card $card_id not in playable list");
            throw new \BgaUserException('Invalid card choice - you cannot afford this card or it is not available in your position');
        }

        // Double-check affordability
        if (!$this->canAffordCard($player_id, $card_id)) {
            $this->trace("actPlayCard: ERROR - Player $player_id cannot afford card $card_id");
            throw new \BgaUserException('You do not have enough conditioning or special tokens to play this card');
        }

        $card_name = self::$CARD_TYPES[$card_id]['card_name'];
        
        // Get player name with proper casting and validation
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
        if (!$player_name) {
            $this->trace("actPlayCard: WARNING - Could not find name for player $player_id, using fallback");
            $player_name = "Player $player_id";
        }

        $this->trace("actPlayCard: Player $player_id ($player_name) successfully playing card $card_id ($card_name)");

        if ($state_name === 'firstPlayerTurn') {
            // Store first player's card in global variables
            $this->setGameStateValue("first_player_id", $player_id);
            $this->setGameStateValue("first_player_card", $card_id);            
            $this->trace("actPlayCard: Stored first player card, transitioning to second player");
            
            // Notify that first player played (but don't reveal the card)
            $this->notifyAllPlayers("firstCardPlayed", clienttranslate('${player_name} has played a card'), [
                "player_id" => $player_id,
                "player_name" => $player_name,
            ]);

            $this->gamestate->nextState("cardPlayed");
            
        } else if ($state_name === 'secondPlayerTurn') {
            // Store second player's card in global variables
            $this->setGameStateValue("second_player_id", $player_id);
            $this->setGameStateValue("second_player_card", $card_id);
            
            $this->trace("actPlayCard: Stored second player card, transitioning to reveal");
            
            // Notify that second player played (but don't reveal yet)
            $this->notifyAllPlayers("secondCardPlayed", clienttranslate('${player_name} has played a card'), [
                "player_id" => $player_id,
                "player_name" => $player_name,
            ]);

            $this->gamestate->nextState("cardPlayed");
        } else {
            $this->trace("actPlayCard: ERROR - Unexpected state $state_name");
            throw new \BgaUserException("Cannot play card in current game state");
        }
    }

    /**
     * NEW: Player chooses to keep their die result
     */
    public function actKeepDice(): void
    {
        $player_id = (int)$this->getCurrentPlayerId();
        $state_name = $this->gamestate->state()['name'];
        
        $this->trace("actKeepDice: Player $player_id keeps dice in state $state_name");

        // Get player name
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$player_id);
        if (!$player_name) {
            $player_name = "Player $player_id";
        }

        $this->notifyAllPlayers("playerKeepDice", clienttranslate('${player_name} keeps their dice result'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
        ]);

        $this->gamestate->nextState("keep");
    }

    /**
     * Populate wrestlers table from material.inc.php data
     */
    private function populateWrestlersTable(): void
    {
        $this->trace("populateWrestlersTable: START");
        
        // Clear existing data
        $this->DbQuery("DELETE FROM wrestlers");
        
        // Insert wrestlers from material data
        foreach (self::$WRESTLERS as $wrestler_id => $wrestler) {
            $name = addslashes($wrestler['name']);
            $trademark = addslashes($wrestler['trademark']);
            $special_cards = addslashes(implode(',', $wrestler['special_cards']));
            
            $sql = "INSERT INTO wrestlers 
                    (wrestler_id, wrestler_name, conditioning_p1, conditioning_p2, conditioning_p3, 
                     offense, defense, top, bottom, special_tokens, trademark, special_cards) 
                    VALUES 
                    ($wrestler_id, '$name', {$wrestler['conditioning_p1']}, {$wrestler['conditioning_p2']}, {$wrestler['conditioning_p3']}, 
                     {$wrestler['offense']}, {$wrestler['defense']}, {$wrestler['top']}, {$wrestler['bottom']}, 
                     {$wrestler['special_tokens']}, '$trademark', '$special_cards')";
            
            $this->DbQuery($sql);
            $this->trace("populateWrestlersTable: Inserted wrestler $wrestler_id - {$wrestler['name']}");
        }
        
        $this->trace("populateWrestlersTable: COMPLETE - Inserted " . count(self::$WRESTLERS) . " wrestlers");
    }

    /**
     * Updated stRevealCards with direct data access
     */
	public function stRevealCards(): void
	{
		// Get the data from global variables and cast to int
		$first_player_id = (int)$this->getGameStateValue("first_player_id");  // CAST TO INT
		$second_player_id = (int)$this->getGameStateValue("second_player_id"); // CAST TO INT
		$first_card = $this->getGameStateValue("first_player_card");
		$second_card = $this->getGameStateValue("second_player_card");

		$this->trace("stRevealCards: first_player=$first_player_id, first_card=$first_card, second_player=$second_player_id, second_card=$second_card");

		// Validate that we have the data
		if (!$first_card || !$second_card || !$first_player_id || !$second_player_id) {
			$this->trace("stRevealCards: ERROR - Missing card data");
			throw new \BgaSystemException("Missing card data in stRevealCards");
		}

		// Get player names with proper casting
		$first_player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$first_player_id);
		$second_player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . (int)$second_player_id);
		
		// Fallback names if queries fail
		if (!$first_player_name) {
			$first_player_name = "Player $first_player_id";
		}
		if (!$second_player_name) {
			$second_player_name = "Player $second_player_id";
		}
		
		$first_card_name = self::$CARD_TYPES[$first_card]['card_name'];
		$second_card_name = self::$CARD_TYPES[$second_card]['card_name'];

		$this->trace("stRevealCards: Revealing cards - $first_player_name played $first_card_name, $second_player_name played $second_card_name");

		// Reveal both cards
		$this->notifyAllPlayers("cardsRevealed", clienttranslate('Cards revealed: ${first_player_name} played ${first_card_name}, ${second_player_name} played ${second_card_name}'), [
			"first_player_id" => $first_player_id,
			"first_player_name" => $first_player_name,
			"first_card_id" => $first_card,
			"first_card_name" => $first_card_name,
			"second_player_id" => $second_player_id,
			"second_player_name" => $second_player_name,
			"second_card_id" => $second_card,
			"second_card_name" => $second_card_name,
		]);

		// Clean up - reset the temporary score storage
		$this->DbQuery("UPDATE player SET player_score = 0 WHERE player_id = " . (int)$first_player_id);

		$this->gamestate->nextState("resolve");
	}
    
    /**
     * Step 1: Adjust conditioning based on cards played - FIXED
     */
	// 3. FIX: stAdjustConditioning method - cast player IDs to int
	public function stAdjustConditioning(): void
	{
		// FIXED: Use global variables and cast to int
		$first_card_id = $this->getGameStateValue("first_player_card");
		$second_card_id = $this->getGameStateValue("second_player_card");
		$first_player_id = (int)$this->getGameStateValue("first_player_id");  // CAST TO INT
		$second_player_id = (int)$this->getGameStateValue("second_player_id"); // CAST TO INT

		$this->trace("stAdjustConditioning: first_card=$first_card_id, second_card=$second_card_id, first_player=$first_player_id, second_player=$second_player_id");

		// Validate we have the data
		if (!$first_card_id || !$second_card_id || !$first_player_id || !$second_player_id) {
			$this->trace("stAdjustConditioning: ERROR - Missing round state data");
			throw new \BgaSystemException("Missing round state data in stAdjustConditioning");
		}

		$first_card = self::$CARD_TYPES[$first_card_id];
		$second_card = self::$CARD_TYPES[$second_card_id];

		// Deduct conditioning costs
		$first_cost = $first_card['conditioning_cost'];
		$second_cost = $second_card['conditioning_cost'];

		$this->trace("stAdjustConditioning: Deducting conditioning - Player $first_player_id: $first_cost, Player $second_player_id: $second_cost");

		$this->DbQuery("UPDATE player SET conditioning = conditioning - $first_cost WHERE player_id = $first_player_id");
		$this->DbQuery("UPDATE player SET conditioning = conditioning - $second_cost WHERE player_id = $second_player_id");

		// Get updated conditioning values
		$first_conditioning = $this->getUniqueValueFromDB("SELECT conditioning FROM player WHERE player_id = $first_player_id");
		$second_conditioning = $this->getUniqueValueFromDB("SELECT conditioning FROM player WHERE player_id = $second_player_id");

		$this->notifyAllPlayers("conditioningAdjusted", clienttranslate('Conditioning adjusted'), [
			"updates" => [
				$first_player_id => ["conditioning" => $first_conditioning, "cost" => $first_cost],
				$second_player_id => ["conditioning" => $second_conditioning, "cost" => $second_cost]
			]
		]);

		$this->gamestate->nextState("rollDice");
	}

    /**
     * CORRECTED stApplyEffects - Apply each player's chosen die result to their offense
     */
	// 1. FIX: stApplyEffects method - cast player IDs to int
	public function stApplyEffects(): void
	{
		// Get the data from global variables
		$first_card_id = $this->getGameStateValue("first_player_card");
		$second_card_id = $this->getGameStateValue("second_player_card");
		$first_player_id = (int)$this->getGameStateValue("first_player_id");  // CAST TO INT
		$second_player_id = (int)$this->getGameStateValue("second_player_id"); // CAST TO INT

		// Validate that we have the data
		if (!$first_card_id || !$second_card_id || !$first_player_id || !$second_player_id) {
			$this->trace("stApplyEffects: ERROR - Missing round state data");
			throw new \BgaSystemException("Missing round state data in stApplyEffects");
		}

		// Get die choices and values for each player
		$first_die_choice = $this->getGameStateValue("first_player_die_choice"); // 1=red, 2=blue
		$second_die_choice = $this->getGameStateValue("second_player_die_choice");
		$first_die_value = $this->getGameStateValue("first_player_die_value");
		$second_die_value = $this->getGameStateValue("second_player_die_value");

		$first_die_type = $first_die_choice == 1 ? 'red' : 'blue';
		$second_die_type = $second_die_choice == 1 ? 'red' : 'blue';

		$this->trace("stApplyEffects: Player $first_player_id chose $first_die_type = $first_die_value, Player $second_player_id chose $second_die_type = $second_die_value");

		$first_card = self::$CARD_TYPES[$first_card_id];
		$second_card = self::$CARD_TYPES[$second_card_id];

		// Get wrestler data - CAST TO INT
		$first_wrestler_id = (int)$this->getUniqueValueFromDB("SELECT wrestler_id FROM player WHERE player_id = " . (int)$first_player_id);
		$second_wrestler_id = (int)$this->getUniqueValueFromDB("SELECT wrestler_id FROM player WHERE player_id = " . (int)$second_player_id);
		
		if (!$first_wrestler_id || !$second_wrestler_id) {
			throw new \BgaSystemException("Could not find wrestler IDs");
		}
		
		$first_wrestler = self::$WRESTLERS[$first_wrestler_id];
		$second_wrestler = self::$WRESTLERS[$second_wrestler_id];

		// APPLY DICE RESULTS TO OFFENSE STATS - ENSURE INT PARAMETERS
		$this->applyDiceToOffense($first_player_id, (int)$first_die_value);
		$this->applyDiceToOffense($second_player_id, (int)$second_die_value);

		// Get updated offense values for notification
		$first_new_offense = $this->getUniqueValueFromDB("SELECT offense FROM player WHERE player_id = $first_player_id");
		$second_new_offense = $this->getUniqueValueFromDB("SELECT offense FROM player WHERE player_id = $second_player_id");

		// Build effects description
		$effects_applied = [];
		
		// Dice effects
		$first_die_label = $first_die_type === 'red' ? 'Red (STRENGTH)' : 'Blue (SPEED)';
		$second_die_label = $second_die_type === 'red' ? 'Red (STRENGTH)' : 'Blue (SPEED)';
		
		if ($first_die_value != 0) {
			$effects_applied[] = "{$first_wrestler['name']} gained $first_die_value offense from $first_die_label die (new total: $first_new_offense)";
		} else {
			$effects_applied[] = "{$first_wrestler['name']}'s offense unchanged from $first_die_label die (total: $first_new_offense)";
		}
		
		if ($second_die_value != 0) {
			$effects_applied[] = "{$second_wrestler['name']} gained $second_die_value offense from $second_die_label die (new total: $second_new_offense)";
		} else {
			$effects_applied[] = "{$second_wrestler['name']}'s offense unchanged from $second_die_label die (total: $second_new_offense)";
		}
		
		// Basic card resolution logic
		if ($first_card['scoring']) {
			$effects_applied[] = "{$first_wrestler['name']}'s {$first_card['card_name']} has scoring potential";
		}
		
		if ($second_card['scoring']) {
			$effects_applied[] = "{$second_wrestler['name']}'s {$second_card['card_name']} has scoring potential";
		}

		// Apply trademark effects (placeholder)
		$effects_applied[] = "{$first_wrestler['name']} trademark: " . $first_wrestler['trademark'];
		$effects_applied[] = "{$second_wrestler['name']} trademark: " . $second_wrestler['trademark'];

		$this->trace("stApplyEffects: Effects applied: " . implode(', ', $effects_applied));

		$this->notifyAllPlayers("effectsApplied", clienttranslate('Card and dice effects applied'), [
			"effects" => $effects_applied,
			"first_die_choice" => $first_die_type,
			"second_die_choice" => $second_die_type,
			"first_die_value" => $first_die_value,
			"second_die_value" => $second_die_value,
			"first_card" => $first_card['card_name'],
			"second_card" => $second_card['card_name'],
			"offense_updates" => [
				$first_player_id => $first_new_offense,
				$second_player_id => $second_new_offense
			]
		]);

		$this->gamestate->nextState("handleTokens");
	}


	// 2. FIX: stHandleTokens method - cast player IDs to int
	public function stHandleTokens(): void
	{
		// FIXED: Use global variables and cast to int
		$first_card_id = $this->getGameStateValue("first_player_card");
		$second_card_id = $this->getGameStateValue("second_player_card");
		$first_player_id = (int)$this->getGameStateValue("first_player_id");  // CAST TO INT
		$second_player_id = (int)$this->getGameStateValue("second_player_id"); // CAST TO INT

		$this->trace("stHandleTokens: first_card=$first_card_id, second_card=$second_card_id, first_player=$first_player_id, second_player=$second_player_id");

		// Validate we have the data
		if (!$first_card_id || !$second_card_id || !$first_player_id || !$second_player_id) {
			$this->trace("stHandleTokens: ERROR - Missing round state data");
			throw new \BgaSystemException("Missing round state data in stHandleTokens");
		}

		$first_card = self::$CARD_TYPES[$first_card_id];
		$second_card = self::$CARD_TYPES[$second_card_id];

		// Deduct special tokens for cards that require them
		$first_token_cost = $first_card['special_tokens'];
		$second_token_cost = $second_card['special_tokens'];

		$this->trace("stHandleTokens: Token costs - Player $first_player_id: $first_token_cost, Player $second_player_id: $second_token_cost");

		if ($first_token_cost > 0) {
			$this->DbQuery("UPDATE player SET special_tokens = special_tokens - $first_token_cost WHERE player_id = $first_player_id");
		}

		if ($second_token_cost > 0) {
			$this->DbQuery("UPDATE player SET special_tokens = special_tokens - $second_token_cost WHERE player_id = $second_player_id");
		}

		// Get updated token values
		$first_tokens = $this->getUniqueValueFromDB("SELECT special_tokens FROM player WHERE player_id = $first_player_id");
		$second_tokens = $this->getUniqueValueFromDB("SELECT special_tokens FROM player WHERE player_id = $second_player_id");

		$this->notifyAllPlayers("tokensHandled", clienttranslate('Special tokens updated'), [
			"updates" => [
				$first_player_id => ["special_tokens" => $first_tokens, "cost" => $first_token_cost],
				$second_player_id => ["special_tokens" => $second_tokens, "cost" => $second_token_cost]
			]
		]);

		$this->gamestate->nextState("drawScramble");
	}

    
    /**
     * Step 5: Draw scramble card if applicable
     */
    public function stDrawScramble(): void
    {
        // Placeholder logic - determine if scramble card should be drawn
        $should_draw_scramble = false; // You'll implement the logic for when this happens
        
        if ($should_draw_scramble) {
            $this->notifyAllPlayers("scrambleDrawn", clienttranslate('Scramble card drawn!'), []);
        }

        $this->gamestate->nextState("nextRound");
    }

    /**
     * Roll the 8-sided red die (STRENGTH)
     * Values: -2, -2, 0, 0, 1, 2, 3, 3
     */
    private function rollRedDie(): int
    {
        $red_die_values = [-2, -2, 0, 0, 1, 2, 3, 3];
        $die_face = bga_rand(1, 8);
        $die_value = $red_die_values[$die_face - 1];
        
        $this->trace("rollRedDie: Face $die_face = Value $die_value");
        
        // Store both the face and the value
        $this->setGameStateValue("red_die", $die_face);
        
        return $die_value;
    }

    /**
     * Roll the 8-sided blue die (SPEED) 
     * Values: -1, -1, 0, 0, 1, 1, 2, 2
     */
    private function rollBlueDie(): int
    {
        $blue_die_values = [-1, -1, 0, 0, 1, 1, 2, 2];
        $die_face = bga_rand(1, 8);
        $die_value = $blue_die_values[$die_face - 1];
        
        $this->trace("rollBlueDie: Face $die_face = Value $die_value");
        
        // Store both the face and the value
        $this->setGameStateValue("blue_die", $die_face);
        
        return $die_value;
    }

    /**
     * Apply dice costs and token rewards to player
     */
	// 5. FIX: applyDiceCosts method - ensure proper typing  
	private function applyDiceCosts(int $player_id, string $die_type): void
	{
		if ($die_type === 'red') {
			// Red die: costs 3 conditioning, gain 1 token
			$this->DbQuery("UPDATE player SET conditioning = conditioning - 3, special_tokens = special_tokens + 1 WHERE player_id = $player_id");
			$this->trace("applyDiceCosts: Player $player_id - Red die cost 3 conditioning, gained 1 token");
		} else if ($die_type === 'blue') {
			// Blue die: costs 2 conditioning, gain 2 tokens
			$this->DbQuery("UPDATE player SET conditioning = conditioning - 2, special_tokens = special_tokens + 2 WHERE player_id = $player_id");
			$this->trace("applyDiceCosts: Player $player_id - Blue die cost 2 conditioning, gained 2 tokens");
		}
	}

    /**
     * Apply dice result to player's offense stat
     */
	// 4. FIX: applyDiceToOffense method - ensure proper typing
	private function applyDiceToOffense(int $player_id, int $dice_total): void
	{
		$this->trace("applyDiceToOffense: Player $player_id getting $dice_total offense adjustment");
		
		if ($dice_total != 0) {
			$this->DbQuery("UPDATE player SET offense = offense + $dice_total WHERE player_id = $player_id");
			
			// Get updated offense value
			$new_offense = $this->getUniqueValueFromDB("SELECT offense FROM player WHERE player_id = $player_id");
			$this->trace("applyDiceToOffense: Player $player_id new offense: $new_offense");
		}
	}


    /**
     * Check if player can afford to reroll (needs 1+ tokens)
     */
	// 6. FIX: canPlayerReroll method - ensure proper typing
	private function canPlayerReroll(int $player_id): bool
	{
		$tokens = (int)$this->getUniqueValueFromDB("SELECT special_tokens FROM player WHERE player_id = $player_id");
		return $tokens >= 1;
	}

    /**
     * Determine next round/period
     */
    public function stNextRound(): void
    {
        // Clear ALL round state using global variables
        $this->setGameStateValue("first_player_card", 0);
        $this->setGameStateValue("second_player_card", 0);
        $this->setGameStateValue("first_player_id", 0);
        $this->setGameStateValue("second_player_id", 0);
        
        // Clear die choice state
        $this->setGameStateValue("first_player_die_choice", 0);
        $this->setGameStateValue("second_player_die_choice", 0);
        $this->setGameStateValue("first_player_die_value", 0);
        $this->setGameStateValue("second_player_die_value", 0);

        // Increment round
        $current_round = $this->getGameStateValue("current_round");
        $current_period = $this->getGameStateValue("current_period");
        
        $current_round++;
        
        // Check if period should advance
        $rounds_per_period = [1 => 9, 2 => 6, 3 => 6];
        if ($current_round > $rounds_per_period[$current_period]) {
            $current_period++;
            $current_round = 1;
            
            if ($current_period > 3) {
                // Game ends
                $this->gamestate->nextState("endGame");
                return;
            }
        }
        
        $this->setGameStateValue("current_round", $current_round);
        $this->setGameStateValue("current_period", $current_period);

        $this->notifyAllPlayers("newRound", clienttranslate('Period ${period}, Round ${round}'), [
            "period" => $current_period,
            "round" => $current_round
        ]);

        $this->gamestate->nextState("setNextPlayer");
    }

    /**
     * Set the first player for the round - IMPROVED with debugging
     */
    public function stSetFirstPlayer(): void
    {
        $this->trace("stSetFirstPlayer: START");
        
        // Get the offense player to go first
        $offense_player_id = $this->getGameStateValue("position_offense");
        $defense_player_id = $this->getGameStateValue("position_defense");
        
        $this->trace("stSetFirstPlayer: Offense player: $offense_player_id, Defense player: $defense_player_id");
        
        if ($offense_player_id == 0) {
            throw new \BgaSystemException("No offense player set in stSetFirstPlayer");
        }
        
        // Set the active player
        $this->gamestate->changeActivePlayer($offense_player_id);
        
        $this->trace("stSetFirstPlayer: Set offense player $offense_player_id as active");
        $this->trace("stSetFirstPlayer: COMPLETE - transitioning to first player turn");
        $this->gamestate->nextState("startRound");
    }

    /**
     * Switch from first player to second player
     */
    public function stSwitchToSecondPlayer(): void
    {
        $this->trace("stSwitchToSecondPlayer: START");
        
        $current_player_id = (int)$this->getActivePlayerId();
        $this->trace("stSwitchToSecondPlayer: Current player: $current_player_id");
        
        // Get both players
        $players = $this->getCollectionFromDB("SELECT player_id FROM player");
        $player_ids = array_keys($players);
        
        $this->trace("stSwitchToSecondPlayer: All player IDs: " . implode(', ', $player_ids));
        
        // Find the other player (the one who will play second)
        $second_player_id = null;
        foreach ($player_ids as $pid) {
            if ($pid != $current_player_id) {
                $second_player_id = $pid;
                break;
            }
        }
        
        if ($second_player_id === null) {
            throw new \BgaSystemException("Could not determine second player");
        }
        
        $this->trace("stSwitchToSecondPlayer: Setting second player to: $second_player_id");
        
        // Set the active player to the second player
        $this->gamestate->changeActivePlayer($second_player_id);
        
        $this->trace("stSwitchToSecondPlayer: COMPLETE - transitioning to second player turn");
        $this->gamestate->nextState("secondPlayerReady");
    }

    /**
     * Switch to second player for dice rolling - FIXED
     */
    public function stSwitchToSecondPlayerForDice(): void
    {
        $this->trace("stSwitchToSecondPlayerForDice: START");
        
        $current_player_id = (int)$this->getActivePlayerId();
        $this->trace("stSwitchToSecondPlayerForDice: Current player: $current_player_id");
        
        // Get both players
        $players = $this->getCollectionFromDB("SELECT player_id FROM player");
        $player_ids = array_keys($players);
        
        // Find the other player
        $second_player_id = null;
        foreach ($player_ids as $pid) {
            if ($pid != $current_player_id) {
                $second_player_id = $pid;
                break;
            }
        }
        
        if ($second_player_id === null) {
            throw new \BgaSystemException("Could not determine second player for dice rolling");
        }
        
        $this->trace("stSwitchToSecondPlayerForDice: Setting second player to: $second_player_id");
        
        // Set the active player to the second player
        $this->gamestate->changeActivePlayer($second_player_id);
        
        $this->trace("stSwitchToSecondPlayerForDice: COMPLETE");
        
        // FIXED: Use the correct transition name
        $this->gamestate->nextState('secondPlayerDice');
    }
    
    /**
     * Set first player for dice rolling - FIXED
     */
    public function stSetFirstPlayerForDice(): void
    {
        $this->trace("stSetFirstPlayerForDice: START");
        
        // Get the first player (offense player goes first for dice rolling too)
        $first_player_id = $this->getGameStateValue("position_offense");
        
        $this->trace("stSetFirstPlayerForDice: Setting first player $first_player_id as active for dice rolling");
        
        // Set the active player
        $this->gamestate->changeActivePlayer($first_player_id);
        
        $this->trace("stSetFirstPlayerForDice: COMPLETE");
        
        // FIXED: Use the correct transition name
        $this->gamestate->nextState('firstPlayerDice');
    }

    /**
     * Set the next player for the round - IMPROVED
     */
    public function stSetNextPlayer(): void 
    {
        $current_player_id = (int)$this->getActivePlayerId();
        $this->trace("stSetNextPlayer: Current player: $current_player_id");
        
        // Get both players
        $players = $this->getCollectionFromDB("SELECT player_id FROM player");
        $player_ids = array_keys($players);
        
        $this->trace("stSetNextPlayer: All player IDs: " . implode(', ', $player_ids));
        
        // Find the other player
        $next_player_id = null;
        foreach ($player_ids as $pid) {
            if ($pid != $current_player_id) {
                $next_player_id = $pid;
                break;
            }
        }
        
        if ($next_player_id === null) {
            throw new \BgaSystemException("Could not determine next player");
        }
        
        $this->trace("stSetNextPlayer: Setting next player to: $next_player_id");
        
        // Set the active player
        $this->gamestate->changeActivePlayer($next_player_id);
        
        $this->trace("stSetNextPlayer: COMPLETE - transitioning to next player turn");
        $this->gamestate->nextState("nextPlayer");
    }

    public function actPass(): void
    {
        $player_id = (int)$this->getActivePlayerId();
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");
        if (!$player_name) {
            $player_name = "Player $player_id"; // Fallback if name not found
        }

        $this->notifyAllPlayers("pass", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
        ]);

        $this->gamestate->nextState("pass");
    }

    /**
     * Arguments for wrestler selection
     */
    public function argWrestlerSelection(): array
    {
        $this->trace("argWrestlerSelection: START");
        
        $available_wrestlers = [];
        
        // Better query for selected wrestlers
        $selected_wrestlers = $this->getObjectListFromDB("SELECT wrestler_id FROM player WHERE wrestler_id IS NOT NULL AND wrestler_id != 0");
        $selected_ids = array_column($selected_wrestlers, 'wrestler_id');
        
        $this->trace("argWrestlerSelection: Selected wrestler IDs: " . implode(', ', $selected_ids));

        // Return only available wrestlers
        foreach (self::$WRESTLERS as $id => $wrestler) {
            if (!in_array($id, $selected_ids)) {
                $available_wrestlers[$id] = $wrestler;
            }
        }

        $this->trace("argWrestlerSelection: Available wrestlers: " . implode(', ', array_keys($available_wrestlers)));

        return [
            "available_wrestlers" => $available_wrestlers
        ];
    }

    /**
     * Updated argPlayerTurn using robust player detection - IMPROVED with error handling
     */
    public function argPlayerTurn(): array
    {
        $player_id = $this->getTurnPlayerId();
        
        $this->trace("argPlayerTurn: START for player $player_id");
        
        // Get player's current position - ADD ERROR HANDLING
        try {
            $current_position = $this->getPlayerPosition($player_id);
        } catch (Exception $e) {
            $this->trace("argPlayerTurn: ERROR getting position for player $player_id: " . $e->getMessage());
            $current_position = 'offense'; // Safe fallback
        }
        
        $this->trace("argPlayerTurn: Player $player_id current position: $current_position");
        
        // Get all available cards for this position
        $available_cards = $this->getAvailableCardsForPosition($current_position);
        $this->trace("argPlayerTurn: Available cards for position $current_position: " . implode(', ', $available_cards));
        
        // Get player resources for debugging
        $player_data = $this->getObjectFromDB(
            "SELECT conditioning, special_tokens FROM player WHERE player_id = $player_id"
        );
        
        if (!$player_data) {
            $this->trace("argPlayerTurn: ERROR - Could not find player data for player $player_id");
            // Return safe defaults instead of throwing exception
            return [
                "playableCardsIds" => [25], // Just stall card
                "current_position" => $current_position,
            ];
        }
        
        $this->trace("argPlayerTurn: Player $player_id resources - conditioning: {$player_data['conditioning']}, tokens: {$player_data['special_tokens']}");
        
        // Filter to only cards the player can afford
        $playable_cards = [];
        foreach ($available_cards as $card_id) {
            $can_afford = $this->canAffordCard($player_id, $card_id);
            $card = self::$CARD_TYPES[$card_id];
            $this->trace("argPlayerTurn: Card $card_id ({$card['card_name']}) - cost: {$card['conditioning_cost']}, tokens: {$card['special_tokens']}, can afford: " . ($can_afford ? 'YES' : 'NO'));
            
            if ($can_afford) {
                $playable_cards[] = $card_id;
            }
        }
        
        // If no cards are affordable, player can at least play Stall (card 25)
        if (empty($playable_cards)) {
            $this->trace("argPlayerTurn: No affordable cards, adding Stall (25)");
            $playable_cards[] = 25; // Stall card costs 0
        }
        
        $this->trace("argPlayerTurn: Final playable cards for player $player_id: " . implode(', ', $playable_cards));
        
        return [
            "playableCardsIds" => $playable_cards,
            "current_position" => $current_position,
        ];
    }
    
    /**
     * Get the player ID for the current turn - more robust method - IMPROVED
     */
    private function getTurnPlayerId(): int
    {
        // Try multiple methods to get the player ID
        $active_player = (int)$this->getActivePlayerId();
        $current_player = (int)$this->getCurrentPlayerId();
        $state_name = $this->gamestate->state()['name'];
        
        $this->trace("getTurnPlayerId: active=$active_player, current=$current_player, state=$state_name");
        
        // For first player turn, use offense player
        if ($state_name === 'firstPlayerTurn') {
            $offense_player = (int)$this->getGameStateValue("position_offense");
            if ($offense_player > 0) {
                $this->trace("getTurnPlayerId: First player turn, using offense player: $offense_player");
                return $offense_player;
            }
        }
        
        // For second player turn, use defense player
        if ($state_name === 'secondPlayerTurn') {
            $defense_player = (int)$this->getGameStateValue("position_defense");
            if ($defense_player > 0) {
                $this->trace("getTurnPlayerId: Second player turn, using defense player: $defense_player");
                return $defense_player;
            }
        }
        
        // Fallback to active player if valid
        if ($active_player > 0) {
            $this->trace("getTurnPlayerId: Using active player: $active_player");
            return $active_player;
        }
        
        // Final fallback to current player
        if ($current_player > 0) {
            $this->trace("getTurnPlayerId: Using current player: $current_player");
            return $current_player;
        }
        
        // LAST RESORT: Get first player from database
        $first_player = (int)$this->getUniqueValueFromDB("SELECT player_id FROM player LIMIT 1");
        if ($first_player > 0) {
            $this->trace("getTurnPlayerId: EMERGENCY fallback to first player: $first_player");
            return $first_player;
        }
        
        throw new \BgaSystemException("Could not determine player ID for turn");
    }

    /**
     * Start match after wrestler selection
     */
    public function stStartMatch(): void
    {
        $this->trace("stStartMatch: START");
        
        // Set initial game state values
        $this->setGameStateInitialValue("current_period", 1);
        $this->setGameStateInitialValue("current_round", 1);

        // Determine who starts based on conditioning
        $players = $this->getCollectionFromDB(
            "SELECT player_id, player_name, conditioning FROM player ORDER BY conditioning DESC, player_id ASC"
        );
        
        if (empty($players)) {
            throw new \BgaSystemException("No players found in stStartMatch");
        }
        
        $first_player_id = array_key_first($players);
        $this->trace("stStartMatch: First player (highest conditioning): $first_player_id");
        
        // Set the active player
        $this->gamestate->changeActivePlayer($first_player_id);
        
        // Notify about who gets to choose starting position
        $first_player = $players[$first_player_id];
        $this->notifyAllPlayers("startingPositionChoice", 
            clienttranslate('${player_name} has higher conditioning (${conditioning}) and chooses starting position'), [
            "player_name" => $first_player['player_name'],
            "conditioning" => $first_player['conditioning']
        ]);

        $this->trace("stStartMatch: COMPLETE - transitioning to position selection");
        $this->gamestate->nextState("startGame");
    }

    /**
     * Game progression
     */
    public function getGameProgression()
    {
        // Simple progression based on current period/round
        $period = $this->getGameStateValue("current_period");
        $round = $this->getGameStateValue("current_round");
        
        // 3 periods: 9, 6, 6 rounds = 21 total rounds
        $total_rounds = 21;
        $completed_rounds = 0;
        
        if ($period > 1) $completed_rounds += 9; // Period 1
        if ($period > 2) $completed_rounds += 6; // Period 2
        if ($period == 3) $completed_rounds += $round; // Current period 3
        else if ($period <= 2) $completed_rounds += $round; // Current period 1 or 2

        return min(100, intval($completed_rounds * 100 / $total_rounds));
    }

    /**
     * Get all game data - IMPROVED with safety checks
     */
    protected function getAllDatas(): array
    {
        $result = [];
        $current_player_id = (int) $this->getCurrentPlayerId();

        try {
            // Get player information including wrestler data
            $result["players"] = $this->getCollectionFromDb(
                "SELECT 
                    player_id id, 
                    player_score score, 
                    player_name name,
                    wrestler_id,
                    conditioning,
                    offense,
                    defense,
                    top,
                    bottom,
                    special_tokens,
                    stall_count 
                FROM player"
            );

            // Add wrestler details - with safety checks
            foreach ($result["players"] as &$player) {
                if ($player['wrestler_id'] && isset(self::$WRESTLERS[$player['wrestler_id']])) {
                    $player['wrestler'] = self::$WRESTLERS[$player['wrestler_id']];
                } else {
                    $player['wrestler'] = null;
                }
            }

            // Get available wrestlers for selection
            $result["wrestlers"] = self::$WRESTLERS;
            
            // Get game state info - with safety checks
            $result["game_state"] = [
                "current_period" => $this->getGameStateValue("current_period") ?? 1,
                "current_round" => $this->getGameStateValue("current_round") ?? 1
            ];

            $result["cardTypes"] = self::$CARD_TYPES;
            
        } catch (Exception $e) {
            $this->trace("getAllDatas: ERROR - " . $e->getMessage());
            // Return minimal safe data
            $result = [
                "players" => [],
                "wrestlers" => self::$WRESTLERS,
                "game_state" => ["current_period" => 1, "current_round" => 1],
                "cardTypes" => self::$CARD_TYPES
            ];
        }

        return $result;
    }
    
    /**
     * Updated setupNewGame to populate wrestlers
     */
    protected function setupNewGame($players, $options = [])
    {
        $this->trace("setupNewGame: START with " . count($players) . " players");
        
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        $query_values = [];
        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        $this->DbQuery(sprintf(
            "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
            implode(",", $query_values)
        ));

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Populate wrestlers table from material data
        $this->populateWrestlersTable();

        // Set all players as active for wrestler selection
        $this->gamestate->setAllPlayersMultiactive();
        $this->trace("setupNewGame: Set all players multiactive for wrestler selection");
        
        // Initialize game state values
        $this->setGameStateInitialValue("current_period", 1);
        $this->setGameStateInitialValue("current_round", 1);
        
        $this->trace("setupNewGame: COMPLETE");
    }
    
    /**
     * Zombie turn handling
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];
        $this->trace("zombieTurn: Handling zombie for player $active_player in state $state_name");

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                case "selectStartingPosition":
                    $this->actSelectPosition("offense");
                    return;
                case "firstPlayerTurn":
                case "secondPlayerTurn":
                    // Auto-play the first available card
                    $args = $this->argPlayerTurn();
                    $playable_cards = $args['playableCardsIds'];
                    if (!empty($playable_cards)) {
                        $this->actPlayCard($playable_cards[0]);
                    }
                    return;
                case "firstPlayerChooseDie":
                case "secondPlayerChooseDie":
                    // Auto-choose red die
                    $this->actChooseDie("red");
                    return;
                case "firstPlayerRerollOption":
                case "secondPlayerRerollOption":
                    // Auto-keep dice
                    $this->actKeepDice();
                    return;
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }
            return;
        }

        if ($state["type"] === "multipleactiveplayer") {
            if ($state_name === "wrestlerSelection") {
                $args = $this->argWrestlerSelection();
                $available = $args['available_wrestlers'];
                if (!empty($available)) {
                    $first_wrestler_id = array_key_first($available);
                    $this->trace("zombieTurn: Auto-selecting wrestler $first_wrestler_id for zombie player $active_player");
                    
                    $wrestler = $available[$first_wrestler_id];
                    $this->DbQuery("UPDATE player SET wrestler_id = $first_wrestler_id WHERE player_id = $active_player");
                    $this->DbQuery("UPDATE player SET conditioning = {$wrestler['conditioning_p1']} WHERE player_id = $active_player");
                    $this->DbQuery("UPDATE player SET offense = {$wrestler['offense']} WHERE player_id = $active_player");
                    $this->DbQuery("UPDATE player SET defense = {$wrestler['defense']} WHERE player_id = $active_player");
                    $this->DbQuery("UPDATE player SET top = {$wrestler['top']} WHERE player_id = $active_player");
                    $this->DbQuery("UPDATE player SET bottom = {$wrestler['bottom']} WHERE player_id = $active_player");
                    $this->DbQuery("UPDATE player SET special_tokens = {$wrestler['special_tokens']} WHERE player_id = $active_player");
                    
                    $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $active_player");
                    if (!$player_name) {
                        $player_name = "Player $active_player";
                    }                    
                    
                    $this->notifyAllPlayers("wrestlerSelected", clienttranslate('${player_name} selected ${wrestler_name}'), [
                        "player_id" => $active_player,
                        "player_name" => $player_name,
                        "wrestler_id" => $first_wrestler_id,
                        "wrestler_name" => $wrestler['name'],
                    ]);
                }
            }
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }

    /**
     * Upgrade table database
     */
    public function upgradeTableDb($from_version)
    {
        // Database upgrade logic when needed
    }

    /**
     * Get game name
     */
    protected function getGameName()
    {
        return "matrevx";
    }
}
?>