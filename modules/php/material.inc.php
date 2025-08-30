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
			"conditioning_p1" => 32,
			"conditioning_p2" => 14,
			"conditioning_p3" => 10,
			"offense" => 8,
			"defense" => 8,
			"top" => 7,
			"bottom" => 9,
			"special_tokens" => 1,
			"trademark" => "Double Leg - costs only 3 Conditioning and 2 Adrenaline",
			"special_cards" => [],
			"image_id" => 1
		],
		2 => [
			"name" => "Darnell Hogler",
			"conditioning_p1" => 36,
			"conditioning_p2" => 17,
			"conditioning_p3" => 7,
			"offense" => 6,
			"defense" => 6,
			"top" => 10,
			"bottom" => 7,
			"special_tokens" => 3,
			"trademark" => "You may perform an Adrenaline Rush up to twice per period",
			"special_cards" => ["Single Leg (O)", "Sit Out (B)", "Break Down (T)"],
			"image_id" => 2
		],
		3 => [
			"name" => "Percy Hercules",
			"conditioning_p1" => 34,
			"conditioning_p2" => 9,
			"conditioning_p3" => 14,
			"offense" => 8,
			"defense" => 6,
			"top" => 6,
			"bottom" => 7,
			"special_tokens" => 5,
			"trademark" => "Anytime you roll +2 on red or blue die Opponent loses 1 conditioning",
			"special_cards" => [],
			"image_id" => 3
		],
		4 => [
			"name" => "Willie Karzoni",
			"conditioning_p1" => 37,
			"conditioning_p2" => 14,
			"conditioning_p3" => 8,
			"offense" => 3,
			"defense" => 10,
			"top" => 7,
			"bottom" => 5,
			"special_tokens" => 2,
			"trademark" => "At match start, remove 2 Offense Move cards from opponents deck",
			"special_cards" => [],
			"image_id" => 4
		],
		5 => [
			"name" => "Debbie Chancer",
			"conditioning_p1" => 35,
			"conditioning_p2" => 12,
			"conditioning_p3" => 19,
			"offense" => 5,
			"defense" => 5,
			"top" => 9,
			"bottom" => 10,
			"special_tokens" => 2,
			"trademark" => "Every time you score an Escape or Reversal, gain 2 Adrenaline",
			"special_cards" => [],
			"image_id" => 5
		],
		6 => [
			"name" => "Tina Howser",
			"conditioning_p1" => 47,
			"conditioning_p2" => 2,
			"conditioning_p3" => 13,
			"offense" => 10,
			"defense" => 7,
			"top" => 1,
			"bottom" => 4,
			"special_tokens" => 1,
			"trademark" => "Cannot be pinned",
			"special_cards" => [],
			"image_id" => 6
		],
		7 => [
			"name" => "Sandy Freefro",
			"conditioning_p1" => 40,
			"conditioning_p2" => 15,
			"conditioning_p3" => 12,
			"offense" => 6,
			"defense" => 4,
			"top" => 6,
			"bottom" => 4,
			"special_tokens" => 0,
			"trademark" => "When trailing the match, add +2 to all red/blue dice rolls",
			"special_cards" => ["Sprawl (D)", "Hip Heist (B)", "Mat Return (T)"],
			"image_id" => 7
		],
		8 => [
			"name" => "Maria Banini",
			"conditioning_p1" => 33,
			"conditioning_p2" => 15,
			"conditioning_p3" => 15,
			"offense" => 7,
			"defense" => 7,
			"top" => 7,
			"bottom" => 7,
			"special_tokens" => 4,
			"trademark" => "In the 3rd period, all moves cost 1 less conditioning",
			"special_cards" => [],
			"image_id" => 8
		],
		9 => [
			"name" => "Ernie the Cactus",
			"conditioning_p1" => 30,
			"conditioning_p2" => 10,
			"conditioning_p3" => 10,
			"offense" => 10,
			"defense" => 10,
			"top" => 10,
			"bottom" => 10,
			"special_tokens" => 0,
			"trademark" => "You may remove all -1 Stat tokens from dropping below 10 conditioning when Stats reset from a Score or end of period",
			"special_cards" => [],
			"image_id" => 9
		]
	],

    'cardTypes' => [
        // OFFENSE CARDS (5 cards)
        16 => [
            "card_name" => 'Hand Fight',
            "position" => "offense", 
            "conditioning_cost" => 2,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => false,
            "effect" => "If you roll +1, -1 to opponent conditioning. If you roll +2, -2 to opponent conditioning",
            "image_id" => 16
        ],
        17 => [
            "card_name" => 'Fake Shot',
            "position" => "offense",
            "conditioning_cost" => 3,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, steal 1 token from opponent",
            "image_id" => 17
        ],
        18 => [
            "card_name" => 'Snap Down',
            "position" => "offense",
            "conditioning_cost" => 2,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, opponent goes to bottom position",
            "image_id" => 18
        ],
        19 => [
            "card_name" => 'Single Leg',
            "position" => "offense",
            "conditioning_cost" => 3,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, steal 1 token from opponent",
            "image_id" => 19
        ],
        20 => [
            "card_name" => 'Ankle Pick',
            "position" => "offense",
            "conditioning_cost" => 4,
            "special_tokens" => 2,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "Draw Scramble Card",
            "image_id" => 20
        ],
        
        // DEFENSE CARDS (5 cards)
        25 => [
            "card_name" => 'Underhook',
            "position" => "defense",
            "conditioning_cost" => 2,
            "special_tokens" => 1,
            "action" => "roll_strength", 
            "scoring" => false,
            "effect" => "If you roll +1, -1 to opponent conditioning. If you roll +2, -2 to opponent conditioning",
            "image_id" => 25
        ],
        26 => [
            "card_name" => 'Sprawl',
            "position" => "defense",
            "conditioning_cost" => 3,
            "special_tokens" => 2,
            "action" => "roll_strength", 
            "scoring" => false,
            "effect" => "If played against Ankle Pick, Single Leg or High Crotch roll strength again and take best roll",
            "image_id" => 26
        ],
        27 => [
            "card_name" => 'Russian Tie Up',
            "position" => "defense",
            "conditioning_cost" => 3,
            "special_tokens" => 1,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, steal 1 token from opponent",
            "image_id" => 27
        ],
        28 => [
            "card_name" => 'Go Behind',
            "position" => "defense",
            "conditioning_cost" => 3,
            "special_tokens" => 0,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "If you roll +1 or higher, steal 1 token from opponent",
            "image_id" => 28
        ],
        29 => [
            "card_name" => 'Re-Shot',
            "position" => "defense",
            "conditioning_cost" => 4,
            "special_tokens" => 3,
            "action" => "roll_speed",
            "scoring" => true,
            "effect" => "Draw Scramble Card if opponent attempted takedown",
            "image_id" => 29
        ],
        
        // ANY POSITION CARDS (2 cards) 
        15 => [
            "card_name" => 'Stalling',
            "position" => "any",
            "conditioning_cost" => -5,
            "special_tokens" => -1,
            "action" => "stalling",
            "scoring" => false,
            "effect" => "Gain 5 Conditioning. If played 5 times by same player, opponent scores 1 pt. Can be played at any time",
            "image_id" => 15
        ],
        99 => [
            "card_name" => 'Adrenaline Rush',
            "position" => "any",
            "conditioning_cost" => 3,
            "special_tokens" => 1,
            "action" => "adrenaline",
            "scoring" => false,
            "effect" => "Apply +4 to current position. Can be played only once per period",
            "image_id" => 99
        ],
        
        // SCRAMBLE CARD (1 card)
        50 => [
            "card_name" => 'Scramble',
            "position" => "scramble",
            "conditioning_cost" => 0,
            "special_tokens" => 0,
            "action" => "scramble",
            "scoring" => true,
            "effect" => "Engage in scramble situation to determine who gains advantage",
            "image_id" => 50
        ]
    ],

    'scrambleCards' => [
        1 => [
            "id" => 1,
            "name" => "Basic Scramble",
            "description" => "Standard wrestling scramble situation",
            "type" => "basic",
            "image_id" => 50
        ]
    ]
];