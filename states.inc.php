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
 * states.inc.php
 *
 * matrevx game states description
 */

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 2)
    ),

    // Wrestler selection state
    2 => array(
        "name" => "wrestlerSelection",
        "description" => clienttranslate('Players must select their wrestlers'),
        "descriptionmyturn" => clienttranslate('${you} must select a wrestler'),
        "type" => "multipleactiveplayer",
        "args" => "argWrestlerSelection",
        "possibleactions" => array(
            "actSelectWrestler"
        ),
        "transitions" => array("allSelected" => 3)
    ),

    // Start of actual gameplay
    3 => array(
        "name" => "startMatch",
        "description" => '',
        "type" => "game",
        "action" => "stStartMatch",
        "transitions" => array("startGame" => 4)
    ),

    // Position selection state
    4 => array(
        "name" => "selectStartingPosition",
        "description" => clienttranslate('${actplayer} must choose starting position (Offense or Defense)'),
        "descriptionmyturn" => clienttranslate('${you} must choose starting position (Offense or Defense)'),
        "type" => "activeplayer",
        "possibleactions" => array(
            "actSelectPosition"
        ),
        "transitions" => array("positionSelected" => 9)
    ),

    // Set first player for gameplay
    9 => array(
        "name" => "setFirstPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stSetFirstPlayer",
        "transitions" => array("startRound" => 10)
    ),

    // First player selects card
    10 => array(
        "name" => "firstPlayerTurn",
        "description" => clienttranslate('${actplayer} must play a card'),
        "descriptionmyturn" => clienttranslate('${you} must play a card'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "possibleactions" => array(
            "actPlayCard"
        ),
        "transitions" => array("cardPlayed" => 20)
    ),

    // Switch to second player
    20 => array(
        "name" => "switchToSecondPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stSwitchToSecondPlayer",
        "transitions" => array("secondPlayerReady" => 11)
    ),

    // Second player selects card
    11 => array(
        "name" => "secondPlayerTurn",
        "description" => clienttranslate('${actplayer} must play a card'),
        "descriptionmyturn" => clienttranslate('${you} must play a card'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "possibleactions" => array(
            "actPlayCard"
        ),
        "transitions" => array("cardPlayed" => 12)
    ),

    // Reveal both cards and start resolution
    12 => array(
        "name" => "revealCards",
        "description" => '',
        "type" => "game",
        "action" => "stRevealCards",
        "transitions" => array("resolve" => 13)
    ),

    // Step 1: Adjust conditioning
    13 => array(
        "name" => "adjustConditioning",
        "description" => '',
        "type" => "game",
        "action" => "stAdjustConditioning",
        "transitions" => array("rollDice" => 22)
    ),

    // Set first player for dice rolling
    22 => array(
        "name" => "setFirstPlayerForDice",
        "description" => '',
        "type" => "game",
        "action" => "stSetFirstPlayerForDice",
        "transitions" => array("firstPlayerDice" => 14)
    ),

    // First player chooses which die to roll
    14 => array(
        "name" => "firstPlayerChooseDie",
        "description" => clienttranslate('${actplayer} must choose which die to roll'),
        "descriptionmyturn" => clienttranslate('${you} must choose: Red die (STRENGTH) or Blue die (SPEED)'),
        "type" => "activeplayer",
        "possibleactions" => array("actChooseDie"),
        "transitions" => array("diceChosen" => 24)
    ),

    // First player reroll option
    24 => array(
        "name" => "firstPlayerRerollOption",
        "description" => clienttranslate('${actplayer} may choose to reroll their die'),
        "descriptionmyturn" => clienttranslate('${you} may reroll your die (costs 1 token)'),
        "type" => "activeplayer",
        "args" => "argRerollOption",
        "possibleactions" => array("actRerollDice", "actKeepDice"),
        "transitions" => array("reroll" => 25, "keep" => 21)
    ),

    // First player reroll
    25 => array(
        "name" => "firstPlayerReroll",
        "description" => '',
        "type" => "game",
        "action" => "stFirstPlayerReroll",
        "transitions" => array("rerolled" => 21)
    ),

    // Switch to second player for dice
    21 => array(
        "name" => "switchToSecondPlayerForDice",
        "description" => '',
        "type" => "game",
        "action" => "stSwitchToSecondPlayerForDice",
        "transitions" => array("secondPlayerDice" => 23)
    ),

    // Second player chooses which die to roll
    23 => array(
        "name" => "secondPlayerChooseDie", 
        "description" => clienttranslate('${actplayer} must choose which die to roll'),
        "descriptionmyturn" => clienttranslate('${you} must choose: Red die (STRENGTH) or Blue die (SPEED)'),
        "type" => "activeplayer",
        "possibleactions" => array("actChooseDie"),
        "transitions" => array("diceChosen" => 26)
    ),

    // Second player reroll option
    26 => array(
        "name" => "secondPlayerRerollOption",
        "description" => clienttranslate('${actplayer} may choose to reroll their die'),
        "descriptionmyturn" => clienttranslate('${you} may reroll your die (costs 1 token)'),
        "type" => "activeplayer",
        "args" => "argRerollOption",
        "possibleactions" => array("actRerollDice", "actKeepDice"),
        "transitions" => array("reroll" => 27, "keep" => 15)
    ),

    // Second player reroll
    27 => array(
        "name" => "secondPlayerReroll",
        "description" => '',
        "type" => "game",
        "action" => "stSecondPlayerReroll",
        "transitions" => array("rerolled" => 15)
    ),

    // Apply card effects and trademark moves
    15 => array(
        "name" => "applyEffects",
        "description" => '',
        "type" => "game",
        "action" => "stApplyEffects",
        "transitions" => array("handleTokens" => 16)
    ),

    // Collect or pay tokens
    16 => array(
        "name" => "handleTokens",
        "description" => '',
        "type" => "game",
        "action" => "stHandleTokens",
        "transitions" => array("statComparison" => 28)  // CHANGED: Go to stat comparison
    ),

	29 => array(
		"name" => "scrambleResolution",
		"description" => clienttranslate('${actplayer} must resolve the scramble card'),
		"descriptionmyturn" => clienttranslate('${you} must resolve the scramble card'),
		"type" => "activeplayer",
		"possibleactions" => array("actResolveScramble"),
		"transitions" => array("resolved" => 18)  // Go to nextRound after resolution
	),

	// UPDATE state 28 (statComparison) transitions:
	28 => array(
		"name" => "statComparison", 
		"description" => '',
		"type" => "game",
		"action" => "stStatComparison",
		"transitions" => array("drawScramble" => 29, "nextRound" => 18)  // CHANGED: Go to scrambleResolution instead of 17
	),

    // Draw scramble card if applicable
    17 => array(
        "name" => "drawScramble",
        "description" => '',
        "type" => "game",
        "action" => "stDrawScramble",
        "transitions" => array("nextRound" => 18)  // CHANGED: Always go to next round after scramble
    ),

    // Check for next round/period
    18 => array(
        "name" => "nextRound",
        "description" => '',
        "type" => "game",
        "action" => "stNextRound",
        "updateGameProgression" => true,
        "transitions" => array("setNextPlayer" => 19, "endGame" => 99)
    ),

    // Set next player for new round
    19 => array(
        "name" => "setNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stSetNextPlayer",
        "transitions" => array("nextPlayer" => 10)
    ),

    // Final state.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
?>