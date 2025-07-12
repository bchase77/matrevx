<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MatRevX implementation : © Mike & Jack McKeever and Bryan Chase bryanchase@yahoo.com
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * MatRevX game material description
 */

return [
    'wrestlerCards' => [
        1 => [
            'name' => 'Goldie Meadows',
            'trademark' => 'Opponent Star Play',
        ],
        2 => [
            'name' => 'Frankie Boulay', 
            'trademark' => 'Star Cards 0',
        ],
    ],
    'cardTypes' => [
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
    ],
];