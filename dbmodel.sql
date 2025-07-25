-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- MatRevX implementation : © Michael McKeever, Jack McKeever & Bryan Chase
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Step 1: Add wrestler columns to player table
ALTER TABLE `player` ADD `wrestler_id` INT NULL;
ALTER TABLE `player` ADD `conditioning` INT DEFAULT 0;
ALTER TABLE `player` ADD `offense` INT DEFAULT 0;
ALTER TABLE `player` ADD `defense` INT DEFAULT 0;
ALTER TABLE `player` ADD `top` INT DEFAULT 0;
ALTER TABLE `player` ADD `bottom` INT DEFAULT 0;
ALTER TABLE `player` ADD `special_tokens` INT DEFAULT 0;
ALTER TABLE `player` ADD `stall_count` INT DEFAULT 0;

-- Step 2: Create wrestlers table structure (data populated from material.inc.php)
CREATE TABLE IF NOT EXISTS `wrestlers` (
  `wrestler_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wrestler_name` varchar(255) NOT NULL,
  `conditioning_p1` int(11) NOT NULL,
  `conditioning_p2` int(11) NOT NULL,
  `conditioning_p3` int(11) NOT NULL,
  `offense` int(11) NOT NULL,
  `defense` int(11) NOT NULL,
  `top` int(11) NOT NULL,
  `bottom` int(11) NOT NULL,
  `special_tokens` int(11) NOT NULL,
  `trademark` varchar(500) DEFAULT NULL,
  `special_cards` TEXT DEFAULT NULL,
  PRIMARY KEY (`wrestler_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;