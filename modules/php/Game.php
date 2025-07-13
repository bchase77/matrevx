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
			"my_first_game_variant" => 100,
			"my_second_game_variant" => 101,
		]);
		
		// Load material from material.inc.php
		$material = require(__DIR__ . '/material.inc.php');
		self::$CARD_TYPES = $material['cardTypes'];
		self::$WRESTLERS = $material['wrestlers'];
	}
	
	// Global variables to track round state
    private static array $ROUND_STATE = [
        'first_player_card' => null,
        'second_player_card' => null,
        'first_player_id' => null,
        'second_player_id' => null
    ];

    /**
     * Get current position for a player - IMPROVED with debugging
     */
    private function getPlayerPosition(int $player_id): string
    {
        $offense_player = $this->getGameStateValue("position_offense");
        $defense_player = $this->getGameStateValue("position_defense");
        
        $this->trace("getPlayerPosition: Player $player_id, offense_player=$offense_player, defense_player=$defense_player");
        
        // For now, we only have offense/defense positions
        // You'll need to add logic for when players go to top/bottom
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
     * Check if player can afford to play a card
     */
    private function canAffordCard(int $player_id, int $card_id): bool
    {
        $card = self::$CARD_TYPES[$card_id];
        
        // Get player's current resources
        $player_data = $this->getObjectFromDB(
            "SELECT conditioning, special_tokens FROM player WHERE player_id = $player_id"
        );
        
        if (!$player_data) {
            return false;
        }
        
        // Check if player has enough conditioning and special tokens
        return ($player_data['conditioning'] >= $card['conditioning_cost']) && 
               ($player_data['special_tokens'] >= $card['special_tokens']);
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

        // Verification of the update
        $verification = $this->getUniqueValueFromDB("SELECT wrestler_id FROM player WHERE player_id = $player_id");
        $this->trace("actSelectWrestler: Verification - player $player_id now has wrestler_id: $verification");

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

        // Log which players have wrestlers for debugging
        $wrestlers_assigned = $this->getObjectListFromDB("SELECT player_id, wrestler_id FROM player WHERE wrestler_id IS NOT NULL AND wrestler_id != 0");
        foreach ($wrestlers_assigned as $assignment) {
            $this->trace("actSelectWrestler: Player {$assignment['player_id']} has wrestler {$assignment['wrestler_id']}");
        }

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

		// Don't change active player here - let the next state handle it
		$this->gamestate->nextState("positionSelected");
	}
	
    /**
     * Updated actPlayCard with better debugging
     */
    public function actPlayCard(int $card_id): void
    {
        $player_id = (int)$this->getActivePlayerId();
        $state_name = $this->gamestate->state()['name'];

        $this->trace("actPlayCard: Player $player_id playing card $card_id in state $state_name");

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
        $player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");
        if (!$player_name) {
            $player_name = "Player $player_id";
        }

        $this->trace("actPlayCard: Player $player_id ($player_name) successfully playing card $card_id ($card_name)");

        if ($state_name === 'firstPlayerTurn') {
            // Store first player's card (hidden)
            self::$ROUND_STATE['first_player_card'] = $card_id;
            self::$ROUND_STATE['first_player_id'] = $player_id;
            
            $this->trace("actPlayCard: Stored first player card, transitioning to second player");
            
            // Notify that first player played (but don't reveal the card)
            $this->notifyAllPlayers("firstCardPlayed", clienttranslate('${player_name} has played a card'), [
                "player_id" => $player_id,
                "player_name" => $player_name,
            ]);

            $this->gamestate->nextState("cardPlayed");
            
        } else if ($state_name === 'secondPlayerTurn') {
            // Store second player's card
            self::$ROUND_STATE['second_player_card'] = $card_id;
            self::$ROUND_STATE['second_player_id'] = $player_id;
            
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
     * Reveal both cards simultaneously
     */
    public function stRevealCards(): void
    {
        $first_card = self::$ROUND_STATE['first_player_card'];
        $second_card = self::$ROUND_STATE['second_player_card'];
        $first_player_id = self::$ROUND_STATE['first_player_id'];
        $second_player_id = self::$ROUND_STATE['second_player_id'];

        $first_player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $first_player_id");
        $second_player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $second_player_id");
        
        $first_card_name = self::$CARD_TYPES[$first_card]['card_name'];
        $second_card_name = self::$CARD_TYPES[$second_card]['card_name'];

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

        $this->gamestate->nextState("resolve");
    }
	
    /**
     * Step 1: Adjust conditioning based on cards played
     */
    public function stAdjustConditioning(): void
    {
        $first_card_id = self::$ROUND_STATE['first_player_card'];
        $second_card_id = self::$ROUND_STATE['second_player_card'];
        $first_player_id = self::$ROUND_STATE['first_player_id'];
        $second_player_id = self::$ROUND_STATE['second_player_id'];

        $first_card = self::$CARD_TYPES[$first_card_id];
        $second_card = self::$CARD_TYPES[$second_card_id];

        // Deduct conditioning costs
        $first_cost = $first_card['conditioning_cost'];
        $second_cost = $second_card['conditioning_cost'];

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
     * Step 2: Roll dice for stat changes and star card outcomes
     */
    public function stRollDice(): void
    {
        // Roll red and blue dice (1-6)
        $red_die = bga_rand(1, 6);
        $blue_die = bga_rand(1, 6);

        $this->notifyAllPlayers("diceRolled", clienttranslate('Dice rolled: Red ${red_die}, Blue ${blue_die}'), [
            "red_die" => $red_die,
            "blue_die" => $blue_die
        ]);

        // Store dice results for use in next steps
        $this->setGameStateValue("red_die", $red_die);
        $this->setGameStateValue("blue_die", $blue_die);

        $this->gamestate->nextState("applyEffects");
    }

    /**
     * Step 3: Apply card effects and trademark moves
     */
    public function stApplyEffects(): void
    {
        $first_card_id = self::$ROUND_STATE['first_player_card'];
        $second_card_id = self::$ROUND_STATE['second_player_card'];
        $first_player_id = self::$ROUND_STATE['first_player_id'];
        $second_player_id = self::$ROUND_STATE['second_player_id'];

        $first_card = self::$CARD_TYPES[$first_card_id];
        $second_card = self::$CARD_TYPES[$second_card_id];

        // Get wrestler data for trademark effects
        $first_wrestler_id = $this->getUniqueValueFromDB("SELECT wrestler_id FROM player WHERE player_id = $first_player_id");
        $second_wrestler_id = $this->getUniqueValueFromDB("SELECT wrestler_id FROM player WHERE player_id = $second_player_id");
        
        $first_wrestler = self::$WRESTLERS[$first_wrestler_id];
        $second_wrestler = self::$WRESTLERS[$second_wrestler_id];

        // Apply card effects (placeholder for now - you'll expand this)
        $effects_applied = [];
        
        if ($first_card['scoring']) {
            $effects_applied[] = "First player's card has scoring potential";
        }
        
        if ($second_card['scoring']) {
            $effects_applied[] = "Second player's card has scoring potential";
        }

        // Apply trademark effects (placeholder)
        $effects_applied[] = "Trademark effects: " . $first_wrestler['trademark'];
        $effects_applied[] = "Trademark effects: " . $second_wrestler['trademark'];

        $this->notifyAllPlayers("effectsApplied", clienttranslate('Card and trademark effects applied'), [
            "effects" => $effects_applied
        ]);

        $this->gamestate->nextState("handleTokens");
    }

    /**
     * Step 4: Handle special token collection/payment
     */
    public function stHandleTokens(): void
    {
        $first_card_id = self::$ROUND_STATE['first_player_card'];
        $second_card_id = self::$ROUND_STATE['second_player_card'];
        $first_player_id = self::$ROUND_STATE['first_player_id'];
        $second_player_id = self::$ROUND_STATE['second_player_id'];

        $first_card = self::$CARD_TYPES[$first_card_id];
        $second_card = self::$CARD_TYPES[$second_card_id];

        // Deduct special tokens for cards that require them
        $first_token_cost = $first_card['special_tokens'];
        $second_token_cost = $second_card['special_tokens'];

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
     * Determine next round/period
     */
    public function stNextRound(): void
    {
        // Clear round state
        self::$ROUND_STATE = [
            'first_player_card' => null,
            'second_player_card' => null,
            'first_player_id' => null,
            'second_player_id' => null
        ];

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
     * Updated player turn arguments - returns cards based on position - WITH MORE DEBUGGING
     */
    public function argPlayerTurn(): array
    {
        $player_id = (int)$this->getActivePlayerId();
        
        $this->trace("argPlayerTurn: START for player $player_id");
        
        // Get player's current position
        $current_position = $this->getPlayerPosition($player_id);
        $this->trace("argPlayerTurn: Player $player_id current position: $current_position");
        
        // Get all available cards for this position
        $available_cards = $this->getAvailableCardsForPosition($current_position);
        $this->trace("argPlayerTurn: Available cards for position $current_position: " . implode(', ', $available_cards));
        
        // Get player resources for debugging
        $player_data = $this->getObjectFromDB(
            "SELECT conditioning, special_tokens FROM player WHERE player_id = $player_id"
        );
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
     * Get all game data
     */
	protected function getAllDatas(): array
	{
		$result = [];
		$current_player_id = (int) $this->getCurrentPlayerId();

		// Get player information including wrestler data
		$result["players"] = $this->getCollectionFromDb(
			"SELECT 
				player_id id, 
				player_score score, 
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

		// Add wrestler details
		foreach ($result["players"] as &$player) {
			if ($player['wrestler_id']) {
				$player['wrestler'] = self::$WRESTLERS[$player['wrestler_id']];
			}
		}

		// Get available wrestlers for selection
		$result["wrestlers"] = self::$WRESTLERS;
		
		// Get game state info
		$result["game_state"] = [
			"current_period" => $this->getGameStateValue("current_period"),
			"current_round" => $this->getGameStateValue("current_round")
		];

		$result["cardTypes"] = self::$CARD_TYPES;

		return $result;
	}
	
    /**
     * Setup new game
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
						$player_name = "Player $active_player"; // Fixed variable name
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