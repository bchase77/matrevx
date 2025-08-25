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
		73 => [
			"name" => "Goldie Meadows",
			"conditioning_p1" => 40,
			"conditioning_p2" => 15,
			"conditioning_p3" => 8,
			"offense" => 7,
			"defense" => 7,
			"top" => 8,
			"bottom" => 8,
			"special_tokens" => 5,
			"trademark" => "Opponent Star Play - When opponent plays a scoring card, gain 1 Special Token",
			"special_cards" => ["Go Behind (D)", "Granby Roll (B)", "Arm Bar (T)"],
			"image_id" => 73
		],
		74 => [
			"name" => "Frankie Boulay",
			"conditioning_p1" => 38,
			"conditioning_p2" => 12,
			"conditioning_p3" => 12,
			"offense" => 9,
			"defense" => 5,
			"top" => 6,
			"bottom" => 7,
			"special_tokens" => 1,
			"trademark" => "Star Cards 0 - All your scoring cards cost 0 Special Tokens",
			"special_cards" => ["Single Leg (O)", "Sit Out (B)", "Break Down (T)"],
			"image_id" => 74
		],
		77 => [
			"name" => "Po Cret",
			"conditioning_p1" => 42,
			"conditioning_p2" => 11,
			"conditioning_p3" => 10,
			"offense" => 8,
			"defense" => 8,
			"top" => 7,
			"bottom" => 9,
			"special_tokens" => 1,
			"trademark" => "Double Leg - costs only 3 Conditioning and 1 Special Token",
			"special_cards" => ["Double Leg (O)", "Spladle (D)", "Mat Return (T)"],
			"image_id" => 77
		],
		80 => [
			"name" => "Sandy Freefro", 
			"conditioning_p1" => 45,
			"conditioning_p2" => 17,
			"conditioning_p3" => 3,
			"offense" => 6,
			"defense" => 6,
			"top" => 10,
			"bottom" => 5,
			"special_tokens" => 3,
			"trademark" => "Speed Specialist - All speed-based cards cost 1 less conditioning (minimum 1)",
			"special_cards" => ["Hand Fight (O)", "Sprawl (D)", "Stand Up (B)"],
			"image_id" => 80
		],
		82 => [
			"name" => "Najat Abbas",
			"conditioning_p1" => 41,
			"conditioning_p2" => 14,
			"conditioning_p3" => 9,
			"offense" => 7,
			"defense" => 9,
			"top" => 6,
			"bottom" => 8,
			"special_tokens" => 4,
			"trademark" => "Defensive Master - When opponent fails a scoring attempt, gain 1 Special Token",
			"special_cards" => ["Adrenaline Rush (Any)", "Stalling (Any)", "Spladle (D)"],
			"image_id" => 82
		]
	],

    'cardTypes' => [
        // OFFENSE CARDS (3 cards)
        3 => [
            "card_name" => 'Single Leg',
            "position" => "offense",
            "conditioning_cost" => 3,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, steal 1 token from opponent",
            "image_id" => 3
        ],
        7 => [
            "card_name" => 'Double Leg',
            "position" => "offense",
            "conditioning_cost" => 5,
            "special_tokens" => 2,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "Draw Scramble Card",
            "image_id" => 7
        ],
        1 => [
            "card_name" => 'Hand Fight',
            "position" => "offense", 
            "conditioning_cost" => 2,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => false,
            "effect" => "If you roll +1, -1 to opponent conditioning. If you roll +2, -2 to opponent conditioning",
            "image_id" => 1
        ],
        
        // DEFENSE CARDS (3 cards)
        13 => [
            "card_name" => 'Sprawl',
            "position" => "defense",
            "conditioning_cost" => 3,
            "special_tokens" => 2,
            "action" => "roll_strength", 
            "scoring" => false,
            "effect" => "If played against Ankle Pick, Single Leg or High Crotch roll strength again and take best roll",
            "image_id" => 13
        ],
        14 => [
            "card_name" => 'Go Behind',
            "position" => "defense",
            "conditioning_cost" => 3,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, steal 1 token from opponent",
            "image_id" => 14
        ],
        22 => [
            "card_name" => 'Spladle',
            "position" => "defense",
            "conditioning_cost" => 5,
            "special_tokens" => 4,
            "action" => "roll_neither",
            "scoring" => true,
            "effect" => "Draw Scramble card. If win, score 2 point takedown and go to Pin Round 1. If tie, score 2 point takedown, 4 point nearfall, and go to Pin Round 3. If lose, Opponent scores 2 point takedown",
            "image_id" => 22
        ],
        
        // BOTTOM CARDS (3 cards)
        25 => [
            "card_name" => 'Stand Up',
            "position" => "bottom",
            "conditioning_cost" => 2,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, steal 1 token from opponent. Scramble win: Score ESCAPE. Scramble loss: Start next round",
            "image_id" => 25
        ],
        27 => [
            "card_name" => 'Sit Out',
            "position" => "bottom",
            "conditioning_cost" => 6,
            "special_tokens" => 2,
            "action" => "roll_speed_and_strength",
            "scoring" => true,
            "effect" => "Scramble win: Score Reversal. Scramble lose: Start next round",
            "image_id" => 27
        ],
        29 => [
            "card_name" => 'Granby Roll',
            "position" => "bottom",
            "conditioning_cost" => 3,
            "special_tokens" => -2,
            "action" => "roll_neither",
            "scoring" => true,
            "effect" => "Draw Scramble Card. Scramble win: Score ESCAPE; Lower opponent conditioning by 1. Lose: Cannot score this round",
            "image_id" => 29
        ],
        
        // TOP CARDS (3 cards)
        34 => [
            "card_name" => 'Break Down',
            "position" => "top",
            "conditioning_cost" => 2,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => false,
            "effect" => "If you roll +1, -1 to Opp cond. If you roll +2, -2 to Opp cond",
            "image_id" => 34
        ],
        35 => [
            "card_name" => 'Mat Return',
            "position" => "top",
            "conditioning_cost" => 3,
            "special_tokens" => 2,
            "action" => "roll_strength",
            "scoring" => false,
            "effect" => "If opp plays STAND UP, POWER STAND UP, or SIT OUT this round, roll strength again and take best result",
            "image_id" => 35
        ],
        38 => [
            "card_name" => 'Arm Bar',
            "position" => "top",
            "conditioning_cost" => 6,
            "special_tokens" => 0,
            "action" => "roll_speed_and_strength",
            "scoring" => true,
            "effect" => "Lead in to DOUBLE ARM BAR. Scramble win: Score 2 NEARFALL; Go to Pin Round 2. Lose: Start next round",
            "image_id" => 38
        ],
        
        // ANY POSITION CARDS (2 cards) 
        47 => [
            "card_name" => 'Adrenaline Rush',
            "position" => "any",
            "conditioning_cost" => 3,
            "special_tokens" => 1,
            "action" => "adrenaline",
            "scoring" => false,
            "effect" => "Apply +4 to current position. Can be played only once per period",
            "image_id" => 47
        ],
        46 => [
            "card_name" => 'Stalling',
            "position" => "any",
            "conditioning_cost" => -5,
            "special_tokens" => 0,
            "action" => "stall",
            "scoring" => false,
            "effect" => "If played as Offense, Opp cannot play star Defense card. Opp can switch their card to any offense or non-star defense card",
            "image_id" => 46
        ],
    ],
	'scrambleCards' => [
		1 => [
			"id" => 1,
			"name" => "High Stakes Roll",
			"description" => "Roll D10 and D12 together and add up the sum. Continue rolling until the sum is 18 or higher. Capped at 5 rolls.",
			"type" => "dice_challenge",
			"dice_type" => "d10_d12_combo",
			"target_number" => 18,
			"max_attempts" => 5,
			"success_conditions" => [
				"immediate" => [
					"rounds" => "3-5",
					"outcome" => "major_success",
					"points" => 3,
					"description" => "Roll 18 or higher in 3-5 rounds"
				],
				"quick" => [
					"rounds" => "1-2", 
					"outcome" => "critical_success",
					"points" => 4,
					"description" => "Roll 18 or higher in 1-2 rounds"
				]
			],
			"failure_condition" => [
				"outcome" => "failure",
				"points" => 0,
				"penalty" => "lose_momentum",
				"description" => "Don't roll 18 or higher within 5 attempts"
			]
		]
	],
];