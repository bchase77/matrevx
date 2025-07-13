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

    // NEW STATE: Switch to second player
    20 => array(
        "name" => "switchToSecondPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stSwitchToSecondPlayer",
        "transitions" => array("secondPlayerReady" => 11)
    ),

    // Second player selects card (without seeing first player's card)
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

    // FIXED: Set first player for dice rolling
    22 => array(
        "name" => "setFirstPlayerForDice",
        "description" => '',
        "type" => "game",
        "action" => "stSetFirstPlayerForDice",
        "transitions" => array("firstPlayerDice" => 14)  // FIXED: Added proper transition
    ),

    // UPDATED: First player rolls red die
    14 => array(
        "name" => "firstPlayerRollDice",
        "description" => clienttranslate('${actplayer} must roll the red die'),
        "descriptionmyturn" => clienttranslate('${you} must roll the red die'),
        "type" => "activeplayer",
        "possibleactions" => array("actRollDice"),
        "transitions" => array("diceRolled" => 21)
    ),

    // NEW STATE: Switch to second player for dice
    21 => array(
        "name" => "switchToSecondPlayerForDice",
        "description" => '',
        "type" => "game",
        "action" => "stSwitchToSecondPlayerForDice",
        "transitions" => array("secondPlayerDice" => 23)  // FIXED: Added proper transition
    ),

    // NEW STATE: Second player rolls blue die
    23 => array(
        "name" => "secondPlayerRollDice", 
        "description" => clienttranslate('${actplayer} must roll the blue die'),
        "descriptionmyturn" => clienttranslate('${you} must roll the blue die'),
        "type" => "activeplayer",
        "possibleactions" => array("actRollDice"),
        "transitions" => array("diceRolled" => 15)
    ),

    // Step 3: Apply card effects and trademark moves
    15 => array(
        "name" => "applyEffects",
        "description" => '',
        "type" => "game",
        "action" => "stApplyEffects",
        "transitions" => array("handleTokens" => 16)
    ),

    // Step 4: Collect or pay tokens
    16 => array(
        "name" => "handleTokens",
        "description" => '',
        "type" => "game",
        "action" => "stHandleTokens",
        "transitions" => array("drawScramble" => 17)
    ),

    // Step 5: Draw scramble card if applicable
    17 => array(
        "name" => "drawScramble",
        "description" => '',
        "type" => "game",
        "action" => "stDrawScramble",
        "transitions" => array("nextRound" => 18, "endGame" => 99)
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