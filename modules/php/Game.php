<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * matrevx implementation : © <Your name here> <Your email address here>
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
            "my_first_game_variant" => 100,
            "my_second_game_variant" => 101,
        ]);        

        // Define available wrestlers
        self::$WRESTLERS = [
            1 => [
                "name" => "Po Cret",
                "conditioning" => 42,
                "offense" => 8,
                "defense" => 8,
                "top" => 7,
                "bottom" => 9,
                "special_tokens" => 0,
                "trademark" => "Double Leg costs only 3 Conditioning and 1 Special Token"
            ],
            2 => [
                "name" => "Darnell Rogers", 
                "conditioning" => 46,
                "offense" => 10,
                "defense" => 6,
                "top" => 9,
                "bottom" => 5,
                "special_tokens" => 0,
                "trademark" => "Start each period with +2 conditioning"
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
     * NEW: Wrestler selection action
     */
    public function actSelectWrestler(int $wrestler_id): void
    {
        $player_id = (int)$this->getActivePlayerId();
        
        // Validate wrestler exists
        if (!isset(self::$WRESTLERS[$wrestler_id])) {
            throw new \BgaUserException('Invalid wrestler selection');
        }

        // Check if wrestler already taken
        $sql = "SELECT player_id FROM player WHERE wrestler_id = $wrestler_id";
        $existing = $this->getUniqueValueFromDB($sql);
        if ($existing) {
            throw new \BgaUserException('Wrestler already selected');
        }

        // Assign wrestler to player
        $wrestler = self::$WRESTLERS[$wrestler_id];
        $sql = "UPDATE player SET 
                wrestler_id = $wrestler_id,
                conditioning = {$wrestler['conditioning']},
                offense = {$wrestler['offense']}, 
                defense = {$wrestler['defense']},
                top = {$wrestler['top']},
                bottom = {$wrestler['bottom']},
                special_tokens = {$wrestler['special_tokens']}
                WHERE player_id = $player_id";
        $this->DbQuery($sql);

        // Notify all players
        $this->notify->all("wrestlerSelected", clienttranslate('${player_name} selected ${wrestler_name}'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
            "wrestler_id" => $wrestler_id,
            "wrestler_name" => $wrestler['name'],
        ]);

        // Make player inactive
        $this->gamestate->setPlayerNonMultiactive($player_id, 'allSelected');
    }

    /**
     * Existing card play action (simplified for now)
     */
    public function actPlayCard(int $card_id): void
    {
        $player_id = (int)$this->getActivePlayerId();

        $args = $this->argPlayerTurn();
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds)) {
            throw new \BgaUserException('Invalid card choice');
        }

        $card_name = self::$CARD_TYPES[$card_id]['card_name'];

        $this->notify->all("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
            "card_name" => $card_name,
            "card_id" => $card_id,
            "i18n" => ['card_name'],
        ]);

        $this->gamestate->nextState("playCard");
    }

    public function actPass(): void
    {
        $player_id = (int)$this->getActivePlayerId();

        $this->notify->all("pass", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        $this->gamestate->nextState("pass");
    }

    /**
     * NEW: Arguments for wrestler selection
     */
    public function argWrestlerSelection(): array
    {
        $available_wrestlers = [];
        
        // Get already selected wrestlers
        $sql = "SELECT wrestler_id FROM player WHERE wrestler_id IS NOT NULL";
        $selected = $this->getObjectListFromDB($sql);
        $selected_ids = array_column($selected, 'wrestler_id');

        // Return only available wrestlers
        foreach (self::$WRESTLERS as $id => $wrestler) {
            if (!in_array($id, $selected_ids)) {
                $available_wrestlers[$id] = $wrestler;
            }
        }

        return [
            "available_wrestlers" => $available_wrestlers
        ];
    }

    /**
     * Existing player turn arguments
     */
    public function argPlayerTurn(): array
    {
        // For now, return simplified card options
        return [
            "playableCardsIds" => [1, 2, 3, 4],
        ];
    }

    /**
     * NEW: Start match after wrestler selection
     */
    public function stStartMatch(): void
    {
        // Initialize game state - we don't need game_state table for now, just use global variables
        // $game_id = (int)$this->getGameId();  // This method doesn't exist
        // $sql = "INSERT INTO game_state (game_id, current_period, current_round) 
        //         VALUES ($game_id, 1, 1)";
        // $this->DbQuery($sql);

        // Set initial game state values
        $this->setGameStateInitialValue("current_period", 1);
        $this->setGameStateInitialValue("current_round", 1);

        // Determine who starts based on conditioning
        $players = $this->getCollectionFromDB(
            "SELECT player_id, conditioning FROM player ORDER BY conditioning DESC, player_id ASC"
        );
        $first_player = array_key_first($players);
        $this->gamestate->changeActivePlayer($first_player);

        $this->gamestate->nextState("startGame");
    }

    /**
     * Existing next player logic
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
        
        // Get game state info - use global variables instead of game_state table for now
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
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Set all players as active for wrestler selection
        $this->gamestate->setAllPlayersMultiactive();
    }

    /**
     * Zombie turn handling
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }
            return;
        }

        if ($state["type"] === "multipleactiveplayer") {
            // For wrestler selection, auto-select first available wrestler
            if ($state_name === "wrestlerSelection") {
                $args = $this->argWrestlerSelection();
                $available = $args['available_wrestlers'];
                if (!empty($available)) {
                    $first_wrestler = array_key_first($available);
                    // Simulate wrestler selection for zombie
                    $wrestler = $available[$first_wrestler];
                    $sql = "UPDATE player SET 
                            wrestler_id = $first_wrestler,
                            conditioning = {$wrestler['conditioning']},
                            offense = {$wrestler['offense']}, 
                            defense = {$wrestler['defense']},
                            top = {$wrestler['top']},
                            bottom = {$wrestler['bottom']},
                            special_tokens = {$wrestler['special_tokens']}
                            WHERE player_id = $active_player";
                    $this->DbQuery($sql);
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