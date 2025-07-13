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
	'wrestlers' => [
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
		],
		3 => [
			"name" => "Goldie Meadows",
			"conditioning_p1" => 40,
			"conditioning_p2" => 15,
			"conditioning_p3" => 8,
			"offense" => 7,
			"defense" => 7,
			"top" => 8,
			"bottom" => 8,
			"special_tokens" => 1,
			"trademark" => "Opponent Star Play - When opponent plays a scoring card, gain 1 Special Token",
			"special_cards" => ["Counter Attack (D)", "Reversal (B)", "Escape (B)", "Takedown (O)"]
		],
		4 => [
			"name" => "Frankie Boulay",
			"conditioning_p1" => 38,
			"conditioning_p2" => 12,
			"conditioning_p3" => 12,
			"offense" => 9,
			"defense" => 5,
			"top" => 6,
			"bottom" => 7,
			"special_tokens" => 0,
			"trademark" => "Star Cards 0 - All your scoring cards cost 0 Special Tokens",
			"special_cards" => ["Power Shot (O)", "Quick Escape (B)", "Defensive Block (D)", "Pin Attempt (T)"]
		]
	],

    'cardTypes' => [
        // OFFENSE CARDS (6 cards)
        1 => [
            "card_name" => clienttranslate('Single Leg'),
            "position" => "offense",
            "conditioning_cost" => 4,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true
        ],
        2 => [
            "card_name" => clienttranslate('Double Leg'),
            "position" => "offense",
            "conditioning_cost" => 6,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => true
        ],
        3 => [
            "card_name" => clienttranslate('Hand Fight'),
            "position" => "offense", 
            "conditioning_cost" => 2,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => false
        ],
        4 => [
            "card_name" => clienttranslate('Snap Down'),
            "position" => "offense",
            "conditioning_cost" => 3,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => false
        ],
        5 => [
            "card_name" => clienttranslate('Underhook'),
            "position" => "offense",
            "conditioning_cost" => 5,
            "special_tokens" => 0,
            "action" => "roll_strength",
            "scoring" => true
        ],
        6 => [
            "card_name" => clienttranslate('Collar Tie'),
            "position" => "offense",
            "conditioning_cost" => 1,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => false
        ],
        
        // DEFENSE CARDS (6 cards)
        7 => [
            "card_name" => clienttranslate('Sprawl'),
            "position" => "defense",
            "conditioning_cost" => 3,
            "special_tokens" => 0,
            "action" => "roll_speed", 
            "scoring" => false
        ],
        8 => [
            "card_name" => clienttranslate('Down Block'),
            "position" => "defense",
            "conditioning_cost" => 2,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => false
        ],
        9 => [
            "card_name" => clienttranslate('Counter Attack'),
            "position" => "defense",
            "conditioning_cost" => 4,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => true
        ],
        10 => [
            "card_name" => clienttranslate('Circle Away'),
            "position" => "defense",
            "conditioning_cost" => 1,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => false
        ],
        11 => [
            "card_name" => clienttranslate('Defensive Shot'),
            "position" => "defense",
            "conditioning_cost" => 5,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true
        ],
        12 => [
            "card_name" => clienttranslate('Tie Up'),
            "position" => "defense",
            "conditioning_cost" => 2,
            "special_tokens" => 0,
            "action" => "roll_strength",
            "scoring" => false
        ],
        
        // TOP CARDS (7 cards)
        13 => [
            "card_name" => clienttranslate('Half Nelson'),
            "position" => "top",
            "conditioning_cost" => 5,
            "special_tokens" => 0,
            "action" => "roll_strength",
            "scoring" => true
        ],
        14 => [
            "card_name" => clienttranslate('Break Down'),
            "position" => "top",
            "conditioning_cost" => 3,
            "special_tokens" => 1,
            "action" => "roll_strength",
            "scoring" => false
        ],
        15 => [
            "card_name" => clienttranslate('Crossface'),
            "position" => "top",
            "conditioning_cost" => 4,
            "special_tokens" => 0,
            "action" => "roll_strength",
            "scoring" => false
        ],
        16 => [
            "card_name" => clienttranslate('Tilt'),
            "position" => "top",
            "conditioning_cost" => 6,
            "special_tokens" => 1,
            "action" => "roll_strength",
            "scoring" => true
        ],
        17 => [
            "card_name" => clienttranslate('Arm Bar'),
            "position" => "top",
            "conditioning_cost" => 4,
            "special_tokens" => 0,
            "action" => "roll_strength",
            "scoring" => true
        ],
        18 => [
            "card_name" => clienttranslate('Ride'),
            "position" => "top",
            "conditioning_cost" => 2,
            "special_tokens" => 0,
            "action" => "roll_strength",
            "scoring" => false
        ],
        19 => [
            "card_name" => clienttranslate('Pin Attempt'),
            "position" => "top",
            "conditioning_cost" => 7,
            "special_tokens" => 2,
            "action" => "roll_strength",
            "scoring" => true
        ],
        
        // BOTTOM CARDS (5 cards)
        20 => [
            "card_name" => clienttranslate('Stand Up'),
            "position" => "bottom",
            "conditioning_cost" => 4,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true
        ],
        21 => [
            "card_name" => clienttranslate('Sit Out'),
            "position" => "bottom",
            "conditioning_cost" => 3,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true
        ],
        22 => [
            "card_name" => clienttranslate('Switch'),
            "position" => "bottom",
            "conditioning_cost" => 5,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => true
        ],
        23 => [
            "card_name" => clienttranslate('Roll'),
            "position" => "bottom",
            "conditioning_cost" => 2,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => false
        ],
        24 => [
            "card_name" => clienttranslate('Hip Heist'),
            "position" => "bottom",
            "conditioning_cost" => 6,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => true
        ],
        
        // ANY POSITION CARDS (2 cards)
        25 => [
            "card_name" => clienttranslate('Stall'),
            "position" => "any",
            "conditioning_cost" => 0,
            "special_tokens" => 0,
            "action" => "stall",
            "scoring" => false
        ],
        26 => [
            "card_name" => clienttranslate('Adrenaline Rush'),
            "position" => "any",
            "conditioning_cost" => 3,
            "special_tokens" => 1,
            "action" => "adrenaline",
            "scoring" => false
        ],
    ],
];