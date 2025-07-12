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
    "my_first_game_variant" => 100,
    "my_second_game_variant" => 101,
]);
        // Define available wrestlers
        self::$WRESTLERS = [
            1 => [
                "name" => "Po Cret",
                "conditioning_p1" => 42,
                "conditioning_p2" => 11,
                "conditioning_p3" => 10,
                "offense" => 8,
                "defense" => 8,
                "top" => 7,
                "bottom" => 9,
                "special_tokens" => 0,
                "trademark" => "Double Leg - costs only 3 Conditioning and 1 Special Token",
                "special_cards" => ["Double Leg (O)", "Splits (D)", "Tilt (T)", "Switch (B)", "Hip Heist (B)"]
            ],
            2 => [
                "name" => "Darnell Hogler", 
                "conditioning_p1" => 45,
                "conditioning_p2" => 17,
                "conditioning_p3" => 3,
                "offense" => 6,
                "defense" => 6,
                "top" => 10,
                "bottom" => 5,
                "special_tokens" => 2,
                "trademark" => "When you draw a Scramble Card, pick from the top 3 and put the other two on the bottom of the deck",
                "special_cards" => ["Super Duck (O)", "Splits (D)", "Cranky Roll (B)", "Neckbridge (B)"]
            ]
        ];

        // Basic card types - just 2 of each position for now
        self::$CARD_TYPES = [
            // Offense cards
            1 => [
                "card_name" => clienttranslate('Single Leg'),
                "position" => "offense",
                "conditioning_cost" => 2,
                "special_tokens" => 0,
                "action" => "roll_speed",
                "scoring" => true
            ],
            2 => [
                "card_name" => clienttranslate('Hand Fight'),
                "position" => "offense", 
                "conditioning_cost" => 1,
                "special_tokens" => 1,
                "action" => "roll_speed",
                "scoring" => false
            ],
            // Defense cards
            3 => [
                "card_name" => clienttranslate('Down Block'),
                "position" => "defense",
                "conditioning_cost" => 2,
                "special_tokens" => 1,
                "action" => "roll_speed",
                "scoring" => false
            ],
            4 => [
                "card_name" => clienttranslate('Sprawl'),
                "position" => "defense",
                "conditioning_cost" => 1,
                "special_tokens" => 0,
                "action" => "roll_speed", 
                "scoring" => false
            ],
            // Top cards
            5 => [
                "card_name" => clienttranslate('Half Nelson'),
                "position" => "top",
                "conditioning_cost" => 3,
                "special_tokens" => 0,
                "action" => "roll_strength",
                "scoring" => true
            ],
            6 => [
                "card_name" => clienttranslate('Break Down'),
                "position" => "top",
                "conditioning_cost" => 2,
                "special_tokens" => 1,
                "action" => "roll_strength",
                "scoring" => false
            ],
            // Bottom cards
            7 => [
                "card_name" => clienttranslate('Stand Up'),
                "position" => "bottom",
                "conditioning_cost" => 2,
                "special_tokens" => 0,
                "action" => "roll_speed",
                "scoring" => true
            ],
            8 => [
                "card_name" => clienttranslate('Sit Out'),
                "position" => "bottom",
                "conditioning_cost" => 1,
                "special_tokens" => 0,
                "action" => "roll_speed",
                "scoring" => true
            ],
        ];
    }

    /**
     * FINAL: Wrestler selection with correct BGA methods only
     */
    public function actSelectWrestler(int $wrestler_id): void
    {
        //$player_id = (int)$this->getActivePlayerId();
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
		$this->setGameStateValue("position_offense", $position === 'offense' ? $player_id : $other_player_id);
		$this->setGameStateValue("position_defense", $position === 'defense' ? $player_id : $other_player_id);

		// Notify all players about position selection
		$this->notifyAllPlayers("positionSelected", clienttranslate('${player_name} chooses ${position}. Match begins!'), [
			"player_id" => $player_id,
			"player_name" => $player_name,
			"position" => ucfirst($position),
			"offense_player_id" => $position === 'offense' ? $player_id : $other_player_id,
			"defense_player_id" => $position === 'defense' ? $player_id : $other_player_id,
			"period" => 1,
			"round" => 1
		]);

		// REMOVED: Don't change active player here, let the next state handle it
		// REMOVED: $this->gamestate->changeActivePlayer($position === 'offense' ? $player_id : $other_player_id);
		
		$this->gamestate->nextState("positionSelected");
	}
    public function actPlayCard(int $card_id): void
    {
        $player_id = (int)$this->getActivePlayerId();

        $args = $this->argPlayerTurn();
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds)) {
            throw new \BgaUserException('Invalid card choice');
        }

        $card_name = self::$CARD_TYPES[$card_id]['card_name'];
$player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");
if (!$player_name) {
    $player_name = "Player $player_id"; // Fallback if name not found
}

        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
            "player_id" => $player_id,
            "player_name" => $player_name,
            "card_name" => $card_name,
            "card_id" => $card_id,
            "i18n" => ['card_name'],
        ]);

        $this->gamestate->nextState("playCard");
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
     * Player turn arguments
     */
    public function argPlayerTurn(): array
    {
        // For now, return simplified card options
        return [
            "playableCardsIds" => [1, 2, 3, 4],
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
     * Next player logic
     */
    public function stNextPlayer(): void {
        $player_id = (int)$this->getActivePlayerId();
        $this->giveExtraTime($player_id);
        $this->activeNextPlayer();
        $this->gamestate->nextState("nextPlayer");
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
                    
$player_name = $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");
if (!$player_name) {
    $player_name = "Player $player_id"; // Fallback if name not found
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