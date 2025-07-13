/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * matrevx implementation : © Mike McKeever, Jack McKeever, Bryan Chase <bryanchase@yahoo.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * matrevx.js
 *
 * matrevx user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.matrevx", ebg.core.gamegui, {
        constructor: function(){
            console.log('matrevx constructor');
            // Global variables
            this.selectedWrestler = null;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
        */
        
setup: function( gamedatas )
{
    console.log( "Starting game setup" );
    console.log( "Game data:", gamedatas );

    // Set up main game area
    var gameAreaHTML = '';
    gameAreaHTML += '<div id="wrestler-selection-area" style="display: none;">';
    gameAreaHTML += '<h3>Select Your Wrestler</h3>';
    gameAreaHTML += '<div id="available-wrestlers"></div>';
    gameAreaHTML += '</div>';
    gameAreaHTML += '<div id="wrestling-mat" style="display: none;">';
    gameAreaHTML += '<div id="player-stats"></div>';
    gameAreaHTML += '<div id="game-info"></div>';
    gameAreaHTML += '</div>';
    gameAreaHTML += '<div id="player-hand-area" style="display: none; margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">';
    gameAreaHTML += '<h3>Your Hand</h3>';
    gameAreaHTML += '<div id="player-hand" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>';
    gameAreaHTML += '</div>';
    
    document.getElementById('game_play_area').insertAdjacentHTML('beforeend', gameAreaHTML);
    
    // Setting up player boards with wrestler info
    var players = Object.values ? Object.values(gamedatas.players) : Object.keys(gamedatas.players).map(function(k) { return gamedatas.players[k]; });
    
    for (var i = 0; i < players.length; i++) {
        var player = players[i];
        var wrestlerName = player.wrestler ? player.wrestler.name : 'No wrestler selected';
        var conditioning = player.conditioning || 0;
        var offense = player.offense || 0;
        var defense = player.defense || 0;
        var top = player.top || 0;
        var bottom = player.bottom || 0;
        
        var playerInfoHTML = '';
        playerInfoHTML += '<div id="player-wrestler-info-' + player.id + '">';
        playerInfoHTML += '<div id="wrestler-name-' + player.id + '">' + wrestlerName + '</div>';
        playerInfoHTML += '<div id="conditioning-' + player.id + '" class="stat-display">Conditioning: ' + conditioning + '</div>';
        playerInfoHTML += '<div class="wrestler-stats-compact">O:' + offense + ' D:' + defense + ' T:' + top + ' B:' + bottom + '</div>';
        playerInfoHTML += '</div>';
        
        this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', playerInfoHTML);
    }

    // Store game data
    this.gamedatas = gamedatas;
    
    // Setup game notifications
    this.setupNotifications();

    console.log( "Ending game setup" );
},       
        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName, args );
            
            switch( stateName )
            {
                case 'wrestlerSelection':
                    this.enterWrestlerSelection(args);
                    break;
                    
                case 'selectStartingPosition':
                    this.enterPositionSelection(args);
                    break;
                    
                case 'firstPlayerTurn':
                case 'secondPlayerTurn':
                    this.enterPlayerTurn(args);
                    break;
                    
                case 'firstPlayerRollDice':
                case 'secondPlayerRollDice':
                    this.enterDiceRolling(args);
                    break;
                    
                case 'dummy':
                    break;
            }
        },

        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
                case 'wrestlerSelection':
                    this.leaveWrestlerSelection();
                    break;
                    
                case 'playerTurn':
                    this.leavePlayerTurn();
                    break;
                    
                case 'dummy':
                    break;
            }               
        }, 
        
        // NEW: Action handler for die choice
        onChooseDie: function(dieChoice) {
            console.log('Player choosing die:', dieChoice);
            
            // Disable both die buttons immediately to prevent double-clicks
            const redBtn = document.getElementById('btn-choose-red');
            const blueBtn = document.getElementById('btn-choose-blue');
            
            if (redBtn) {
                redBtn.disabled = true;
                redBtn.style.opacity = '0.5';
            }
            if (blueBtn) {
                blueBtn.disabled = true;
                blueBtn.style.opacity = '0.5';
            }
            
            this.bgaPerformAction("actChooseDie", { die_choice: dieChoice }).then(() => {
                console.log('Die choice successful');
            }).catch(error => {
                console.error('Error choosing die:', error);
                // Re-enable buttons on error
                if (redBtn) {
                    redBtn.disabled = false;
                    redBtn.style.opacity = '1';
                }
                if (blueBtn) {
                    blueBtn.disabled = false;
                    blueBtn.style.opacity = '1';
                }
            });
        },

        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName, args );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'wrestlerSelection':
                        // Action buttons will be added by enterWrestlerSelection
                        break;
                        
                    case 'selectStartingPosition':
                        this.addActionButton('btn-offense', _('Choose Offense'), () => this.onPositionClick('offense'), null, null, 'blue');
                        this.addActionButton('btn-defense', _('Choose Defense'), () => this.onPositionClick('defense'), null, null, 'gray');
                        break;
                        
                    case 'firstPlayerTurn':
                    case 'secondPlayerTurn':
                        // Cards will be shown in hand area
                        break;
                        
                    // NEW: Die choice states
                    case 'firstPlayerChooseDie':
                    case 'secondPlayerChooseDie':
                        this.addActionButton('btn-choose-red', _('Roll Red Die (STRENGTH)'), () => this.onChooseDie('red'), null, null, 'red');
                        this.addActionButton('btn-choose-blue', _('Roll Blue Die (SPEED)'), () => this.onChooseDie('blue'), null, null, 'blue');
                        
                        // Show die information
                        const dieInfoDiv = document.createElement('div');
                        dieInfoDiv.style.cssText = 'margin: 15px 0; padding: 15px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid #0066cc;';
                        dieInfoDiv.innerHTML = `
                            <div style="font-weight: bold; margin-bottom: 10px;">Choose Your Die:</div>
                            <div style="display: flex; gap: 20px; justify-content: center;">
                                <div style="text-align: center; padding: 10px; background: #ffebee; border-radius: 5px;">
                                    <div style="font-weight: bold; color: #d32f2f;">Red Die (STRENGTH)</div>
                                    <div style="font-size: 12px;">Values: -2, -2, 0, 0, 1, 2, 3, 3</div>
                                    <div style="font-size: 12px; color: #666;">Cost: 3 conditioning, Gain: 1 token</div>
                                </div>
                                <div style="text-align: center; padding: 10px; background: #e3f2fd; border-radius: 5px;">
                                    <div style="font-weight: bold; color: #1976d2;">Blue Die (SPEED)</div>
                                    <div style="font-size: 12px;">Values: -1, -1, 0, 0, 1, 1, 2, 2</div>
                                    <div style="font-size: 12px; color: #666;">Cost: 2 conditioning, Gain: 2 tokens</div>
                                </div>
                            </div>
                        `;
                        
                        const actionBar = document.getElementById('generalactions');
                        if (actionBar) {
                            actionBar.appendChild(dieInfoDiv);
                        }
                        break;
                        
                    // Reroll option states - ADD SAFETY CHECKS
                    case 'firstPlayerRerollOption':
                    case 'secondPlayerRerollOption':
                        const rerollArgs = args && args.args;
                        const canReroll = rerollArgs && rerollArgs.can_reroll;
                        const dieType = rerollArgs && rerollArgs.die_type;
                        const dieValue = rerollArgs && rerollArgs.die_value;
                        const currentTokens = (rerollArgs && rerollArgs.current_tokens) || 0;
                        
                        console.log('Reroll options:', { canReroll, dieType, dieValue, currentTokens });
                        
                        // Always show keep button
                        this.addActionButton('btn-keep-dice', _('Keep Result'), () => this.onKeepDice(), null, null, 'gray');
                        
                        // Show reroll button only if player can afford it
                        if (canReroll && dieType) {
                            const dieLabel = dieType === 'red' ? 'Red Die (STRENGTH)' : 'Blue Die (SPEED)';
                            this.addActionButton('btn-reroll-dice', _('Reroll ' + dieLabel + ' (1 token)'), () => this.onRerollDice(), null, null, 'blue');
                        } else {
                            // Show disabled reroll button with explanation
                            const disabledBtn = this.addActionButton('btn-reroll-disabled', _('Reroll (Need 1 token)'), null, null, null, 'gray');
                            const buttonElement = document.getElementById('btn-reroll-disabled');
                            if (buttonElement) {
                                buttonElement.disabled = true;
                                buttonElement.style.opacity = '0.5';
                            }
                        }
                        
                        // Show current status - ADD SAFETY CHECKS
                        const statusDiv = document.createElement('div');
                        statusDiv.style.cssText = 'margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 5px;';
                        statusDiv.innerHTML = `
                            <div><strong>Your ${(dieType || 'unknown').toUpperCase()} die result:</strong> ${dieValue || '?'}</div>
                            <div><strong>Your tokens:</strong> ${currentTokens}</div>
                        `;
                        
                        const actionBar2 = document.getElementById('generalactions');
                        if (actionBar2) {
                            actionBar2.appendChild(statusDiv);
                        }
                        break;
                }
            }
        },
        
        
        ///////////////////////////////////////////////////
        //// Utility methods
        
        enterWrestlerSelection: function(args) {
            console.log('Entering wrestler selection', args);
            
            // Show wrestler selection area
            document.getElementById('wrestler-selection-area').style.display = 'block';
            document.getElementById('wrestling-mat').style.display = 'none';
            
            // Clear previous wrestlers and selection state
            const container = document.getElementById('available-wrestlers');
            container.innerHTML = '';
            this.selectedWrestler = null;
            
            // Fix: BGA wraps args in args.args
            const available_wrestlers = args && args.args && args.args.available_wrestlers;
            
            if (!available_wrestlers) {
                container.innerHTML = '<div>No wrestlers available</div>';
                console.log('Could not find available_wrestlers in:', args);
                return;
            }
            
            console.log('Found available wrestlers:', available_wrestlers);
            
            // Check if current player is active (can select)
            const canSelect = this.isCurrentPlayerActive();
            console.log('Can current player select wrestler?', canSelect);
            
            // Add available wrestlers
            Object.entries(available_wrestlers).forEach(([wrestlerId, wrestler]) => {
                console.log('Adding wrestler:', wrestlerId, wrestler);
                
                const wrestlerDiv = document.createElement('div');
                wrestlerDiv.className = 'wrestler-card';
                wrestlerDiv.id = `wrestler-${wrestlerId}`;
                
                wrestlerDiv.innerHTML = `
                    <div class="wrestler-card-container">
                        <div class="wrestler-info" style="padding: 20px;">
                            <div class="wrestler-name" style="font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #333;">${wrestler.name}</div>
                            <div class="wrestler-stats" style="font-size: 13px; line-height: 1.4;">
                                <div><strong>Conditioning:</strong> ${wrestler.conditioning_p1}/${wrestler.conditioning_p2}/${wrestler.conditioning_p3}</div>
                                <div><strong>Offense:</strong> ${wrestler.offense} | <strong>Defense:</strong> ${wrestler.defense}</div>
                                <div><strong>Top:</strong> ${wrestler.top} | <strong>Bottom:</strong> ${wrestler.bottom}</div>
                                <div><strong>Special Tokens:</strong> ${wrestler.special_tokens}</div>
                            </div>
                            <div class="wrestler-trademark" style="font-style: italic; margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px; font-size: 12px;"><strong>Trademark:</strong> ${wrestler.trademark}</div>
                        </div>
                    </div>
                `;
                
                wrestlerDiv.style.cssText = `
                    border: 3px solid #ccc;
                    margin: 10px;
                    padding: 5px;
                    cursor: ${canSelect ? 'pointer' : 'not-allowed'};
                    border-radius: 12px;
                    background: #fff;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    transition: all 0.3s ease;
                    display: inline-block;
                    vertical-align: top;
                    opacity: ${canSelect ? '1' : '0.6'};
                `;
                
                // Only add interaction if player can select
                if (canSelect) {
                    // Add hover effects
                    wrestlerDiv.addEventListener('mouseenter', function() {
                        if (!this.classList.contains('selected')) {
                            this.style.transform = 'scale(1.05)';
                            this.style.boxShadow = '0 6px 12px rgba(0,0,0,0.2)';
                        }
                    });
                    
                    wrestlerDiv.addEventListener('mouseleave', function() {
                        if (!this.classList.contains('selected')) {
                            this.style.transform = 'scale(1)';
                            this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                        }
                    });
                    
                    // Add click handler
                    wrestlerDiv.addEventListener('click', () => this.onWrestlerClick(wrestlerId));
                }
                
                container.appendChild(wrestlerDiv);
            });
            
            console.log('Total wrestlers added:', container.children.length);
            
            // Show appropriate message
            if (!canSelect) {
                const messageDiv = document.createElement('div');
                messageDiv.style.cssText = 'text-align: center; margin: 20px; font-size: 16px; color: #666;';
                messageDiv.textContent = 'Waiting for other players to select their wrestlers...';
                container.appendChild(messageDiv);
            }
        },
        
        leaveWrestlerSelection: function() {
            // Hide wrestler selection area
            document.getElementById('wrestler-selection-area').style.display = 'none';
            document.getElementById('wrestling-mat').style.display = 'block';
        },
        
        enterPositionSelection: function(args) {
            console.log('Entering position selection', args);
            
            // Hide wrestler selection area and show match area
            document.getElementById('wrestler-selection-area').style.display = 'none';
            document.getElementById('wrestling-mat').style.display = 'block';
            
            // Show game info
            document.getElementById('game-info').innerHTML = `
                <div style="padding: 20px; background: #f9f9f9; border-radius: 8px; margin: 10px;">
                    <h3>Match Starting!</h3>
                    <p><strong>Period 1, Round 1</strong></p>
                    <p>Player with higher conditioning chooses starting position.</p>
                </div>
            `;
        },
        
       enterDiceRolling: function(args) {
            console.log('Entering dice rolling state', args);
            
            // Hide hand area
            const handArea = document.getElementById('player-hand-area');
            if (handArea) {
                handArea.style.display = 'none';
            }
            
            // Show dice rolling info
            const gameInfo = document.getElementById('game-info');
            if (!gameInfo) {
                console.error('Game info element not found');
                return;
            }
            
            // SAFETY CHECK: Get state name safely
            const stateName = this.gamedatas && this.gamedatas.gamestate && this.gamedatas.gamestate.name;
            
            let diceHTML = '<div style="padding: 20px; background: #fff3cd; border-radius: 8px; margin: 10px;">';
            diceHTML += '<h3>Dice Rolling Phase</h3>';
            
            if (stateName === 'firstPlayerRollDice') {
                diceHTML += '<p>First player rolls the <strong style="color: red;">RED DIE</strong></p>';
            } else if (stateName === 'secondPlayerRollDice') {
                diceHTML += '<p>Second player rolls the <strong style="color: blue;">BLUE DIE</strong></p>';
            } else {
                diceHTML += '<p>Dice rolling phase</p>';
            }
            
            diceHTML += '</div>';
            gameInfo.innerHTML = diceHTML;
        },

        onRollDice: function() {
            console.log('Rolling dice...');
            
            // Disable the roll button immediately to prevent double-clicks
            const rollBtn = document.querySelector('[id^="btn-roll"]');
            if (rollBtn) {
                rollBtn.disabled = true;
                rollBtn.textContent = 'Rolling...';
                rollBtn.style.opacity = '0.5';
            }
            
            this.bgaPerformAction("actRollDice", {}).then(() => {
                console.log('Dice roll successful');
            }).catch(error => {
                console.error('Error rolling dice:', error);
                // Re-enable button on error
                if (rollBtn) {
                    rollBtn.disabled = false;
                    rollBtn.textContent = rollBtn.id.includes('red') ? 'Roll Red Die' : 'Roll Blue Die';
                    rollBtn.style.opacity = '1';
                }
            });
        },

        // NEW: Action handlers for reroll system
        onRerollDice: function() {
            console.log('Player choosing to reroll dice...');
            
            // Disable the reroll button immediately to prevent double-clicks
            const rerollBtn = document.querySelector('[id^="btn-reroll"]');
            if (rerollBtn) {
                rerollBtn.disabled = true;
                rerollBtn.textContent = 'Rerolling...';
                rerollBtn.style.opacity = '0.5';
            }
            
            this.bgaPerformAction("actRerollDice", {}).then(() => {
                console.log('Reroll action successful');
            }).catch(error => {
                console.error('Error rerolling dice:', error);
                // Re-enable button on error
                if (rerollBtn) {
                    rerollBtn.disabled = false;
                    rerollBtn.textContent = rerollBtn.textContent.replace('Rerolling...', 'Reroll');
                    rerollBtn.style.opacity = '1';
                }
            });
        },

        onKeepDice: function() {
            console.log('Player choosing to keep dice result...');
            
            // Disable the keep button immediately to prevent double-clicks
            const keepBtn = document.getElementById('btn-keep-dice');
            if (keepBtn) {
                keepBtn.disabled = true;
                keepBtn.textContent = 'Keeping...';
                keepBtn.style.opacity = '0.5';
            }
            
            this.bgaPerformAction("actKeepDice", {}).then(() => {
                console.log('Keep dice action successful');
            }).catch(error => {
                console.error('Error keeping dice:', error);
                // Re-enable button on error
                if (keepBtn) {
                    keepBtn.disabled = false;
                    keepBtn.textContent = 'Keep Result';
                    keepBtn.style.opacity = '1';
                }
            });
        },
        
        enterPlayerTurn: function(args) {
            // Show game area
            document.getElementById('wrestling-mat').style.display = 'block';
            
            // If it's our turn, show our hand
            if (this.isCurrentPlayerActive()) {
                // Get the args from the state - ADD SAFETY CHECKS
                const stateArgs = args && args.args;
                const playableCardsIds = (stateArgs && stateArgs.playableCardsIds) ? stateArgs.playableCardsIds : [];
                const currentPosition = (stateArgs && stateArgs.current_position) ? stateArgs.current_position : 'offense';
                
                console.log('Player turn args:', stateArgs);
                console.log('Current position:', currentPosition);
                console.log('Playable cards:', playableCardsIds);
                
                // Show position info - with safety check
                if (currentPosition) {
                    this.showPositionInfo(currentPosition);
                }
                
                // Display our available cards
                this.displayPlayerHand(playableCardsIds, currentPosition);
            }
        },

        showPositionInfo: function(position) {
            const gameInfo = document.getElementById('game-info');
            if (gameInfo && position) {
                const positionHTML = `
                    <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; margin: 10px;">
                        <h4>Your Current Position: <strong>${position.toUpperCase()}</strong></h4>
                        <p>Available cards are based on your wrestling position.</p>
                    </div>
                `;
                gameInfo.innerHTML = positionHTML;
            }
        },       
        
       displayPlayerHand: function(playableCardsIds, currentPosition) {
            console.log('displayPlayerHand called with:', playableCardsIds, 'position:', currentPosition);
            
            const handContainer = document.getElementById('player-hand');
            const handArea = document.getElementById('player-hand-area');
            
            if (!handContainer || !handArea) {
                console.error('Hand containers not found');
                return;
            }
            
            // Clear previous cards
            handContainer.innerHTML = '';
            
            // Show hand area with position info
            handArea.style.display = 'block';
            
            // Update hand title to show position - ADD SAFETY CHECK
            const handTitle = handArea.querySelector('h3');
            if (handTitle && currentPosition) {
                handTitle.textContent = `Your ${currentPosition.toUpperCase()} Cards`;
            } else if (handTitle) {
                handTitle.textContent = 'Your Cards';
            }
            
            // Use card types from game data - add safety check
            const cardTypes = this.gamedatas && this.gamedatas.cardTypes;
            if (!cardTypes) {
                console.error('No cardTypes found in gamedatas!');
                handContainer.innerHTML = '<div>Error: Card data not available</div>';
                return;
            }
            
            console.log('Available cardTypes:', cardTypes);
            
            // SAFETY CHECK: Ensure playableCardsIds is an array
            if (!Array.isArray(playableCardsIds)) {
                console.warn('playableCardsIds is not an array:', playableCardsIds);
                playableCardsIds = [];
            }
            
            // Create card elements
            for (let i = 0; i < playableCardsIds.length; i++) {
                const cardId = playableCardsIds[i];
                console.log('Processing card ID:', cardId);
                
                const card = cardTypes[cardId];
                console.log('Card data:', card);
                
                if (!card) {
                    console.warn('No card found for ID:', cardId);
                    continue;
                }

                // CREATE THE CARD ELEMENT
                const cardElement = document.createElement('div');
                cardElement.className = 'card';
                cardElement.id = 'card-' + cardId;

                // Build the inner HTML safely with better styling
                let cardHTML = '<div class="card-header" style="font-weight: bold; font-size: 14px; margin-bottom: 8px;">' + (card.card_name || 'Unknown Card') + '</div>';
                
                // Position badge - ADD SAFETY CHECK
                if (card.position) {
                    const positionColor = this.getPositionColor(card.position);
                    cardHTML += '<div class="card-position" style="background: ' + positionColor + '; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; display: inline-block; margin-bottom: 8px;">' + card.position.toUpperCase() + '</div>';
                }
                
                cardHTML += '<div class="card-stats" style="font-size: 12px; line-height: 1.3;">';
                cardHTML += '<div><strong>Conditioning:</strong> ' + (card.conditioning_cost || 0) + '</div>';
                cardHTML += '<div><strong>Tokens:</strong> ' + (card.special_tokens || 0) + '</div>';
                if (card.scoring) {
                    cardHTML += '<div class="scoring-indicator" style="color: #ff6b35; font-weight: bold;">⭐ Scoring</div>';
                }
                cardHTML += '</div>';
                
                cardElement.innerHTML = cardHTML;
                
                // Set styles
                cardElement.style.border = '2px solid #333';
                cardElement.style.borderRadius = '8px';
                cardElement.style.padding = '12px';
                cardElement.style.background = 'white';
                cardElement.style.cursor = 'pointer';
                cardElement.style.minWidth = '160px';
                cardElement.style.maxWidth = '180px';
                cardElement.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                cardElement.style.transition = 'all 0.2s ease';
                
                // Add hover effects
                cardElement.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                    this.style.borderColor = '#0066cc';
                });
                
                cardElement.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                    this.style.borderColor = '#333';
                });
                
                // Add click handler
                const self = this;
                cardElement.addEventListener('click', function() {
                    self.onCardClick(cardId);
                });
                
                handContainer.appendChild(cardElement);
            }
            
            console.log('Added', handContainer.children.length, 'cards to hand');
        },
        
        getPositionColor: function(position) {
            const colors = {
                'offense': '#e74c3c',   // Red
                'defense': '#3498db',   // Blue  
                'top': '#f39c12',       // Orange
                'bottom': '#27ae60',    // Green
                'any': '#9b59b6'        // Purple
            };
            return colors[position] || '#95a5a6'; // Gray fallback
        },
        
        leavePlayerTurn: function() {
            // Clean up any temporary UI elements
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        onWrestlerClick: function(wrestlerId) {
            console.log('Wrestler clicked:', wrestlerId);
            
            // Check if player can still select
            if (!this.isCurrentPlayerActive()) {
                console.log('Player cannot select - not active');
                return;
            }
            
            // Remove selection from all wrestlers
            document.querySelectorAll('.wrestler-card').forEach(card => {
                card.classList.remove('selected');
                card.style.border = '3px solid #ccc';
                card.style.transform = 'scale(1)';
                card.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                card.style.background = '#fff';
            });
            
            // Highlight selected wrestler
            const selectedCard = document.getElementById(`wrestler-${wrestlerId}`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
                selectedCard.style.border = '3px solid #0066cc';
                selectedCard.style.transform = 'scale(1.05)';
                selectedCard.style.boxShadow = '0 8px 16px rgba(0,102,204,0.3)';
			}
            this.selectedWrestler = wrestlerId;
            
            // Remove any existing confirm button
            const existingBtn = document.getElementById('confirm-wrestler-btn');
            if (existingBtn) {
                existingBtn.remove();
            }
            
            // Add confirm button
            this.addActionButton('confirm-wrestler-btn', _('Confirm Wrestler Selection'), () => this.confirmWrestlerSelection(), null, null, 'blue');
        },
        
        onPositionClick: function(position) {
            console.log('Position selected:', position);
            
            this.bgaPerformAction("actSelectPosition", { 
                position: position
            }).then(() => {
                // Success handled by notification
            }).catch(error => {
                console.error('Error selecting position:', error);
            });
        },
        
        confirmWrestlerSelection: function() {
            if (!this.selectedWrestler) {
                this.showMessage(_('Please select a wrestler first'), 'error');
                return;
            }
            
            console.log('Confirming wrestler selection:', this.selectedWrestler);
            
            // Disable the confirm button immediately to prevent double-clicks
            const confirmBtn = document.getElementById('confirm-wrestler-btn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Selecting...';
                confirmBtn.style.opacity = '0.5';
            }
            
            // Disable all wrestler cards to prevent further selection
            document.querySelectorAll('.wrestler-card').forEach(card => {
                card.style.pointerEvents = 'none';
                card.style.opacity = '0.7';
            });
            
            this.bgaPerformAction("actSelectWrestler", { 
                wrestler_id: parseInt(this.selectedWrestler)
            }).then(() => {
                console.log('Wrestler selection successful');
                // Success handled by notification
            }).catch(error => {
                console.error('Error selecting wrestler:', error);
                // Re-enable interface on error
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Confirm Wrestler Selection';
                    confirmBtn.style.opacity = '1';
                }
                document.querySelectorAll('.wrestler-card').forEach(card => {
                    card.style.pointerEvents = 'auto';
                    card.style.opacity = '1';
                });
            });
        },
        
        onCardClick: function( card_id )
        {
            console.log( 'onCardClick', card_id );

            this.bgaPerformAction("actPlayCard", { 
                card_id,
            }).then(() => {                
                // Success handled by game state change
            });        
        },    

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

		setupNotifications: function()
		{
			console.log( 'notifications subscriptions setup' );
			
			// Existing notifications
			dojo.subscribe('wrestlerSelected', this, "notif_wrestlerSelected");
			dojo.subscribe('startingPositionChoice', this, "notif_startingPositionChoice");
			dojo.subscribe('positionSelected', this, "notif_positionSelected");
			dojo.subscribe('firstCardPlayed', this, "notif_firstCardPlayed");
			dojo.subscribe('secondCardPlayed', this, "notif_secondCardPlayed");
			dojo.subscribe('cardsRevealed', this, "notif_cardsRevealed");
			dojo.subscribe('conditioningAdjusted', this, "notif_conditioningAdjusted");
			dojo.subscribe('effectsApplied', this, "notif_effectsApplied");
			dojo.subscribe('tokensHandled', this, "notif_tokensHandled");
			dojo.subscribe('scrambleDrawn', this, "notif_scrambleDrawn");
			dojo.subscribe('newRound', this, "notif_newRound");
			
			// Die choice and reroll notifications
			dojo.subscribe('playerChoseDie', this, "notif_playerChoseDie");
			dojo.subscribe('playerReroll', this, "notif_playerReroll");
			dojo.subscribe('playerKeepDice', this, "notif_playerKeepDice");
			dojo.subscribe('diceRerolled', this, "notif_diceRerolled");
			
			// NEW: Stat comparison and scramble card notifications
			dojo.subscribe('statComparison', this, "notif_statComparison");
			dojo.subscribe('scrambleCardDrawn', this, "notif_scrambleCardDrawn");
			dojo.subscribe('scrambleCardResolved', this, "notif_scrambleCardResolved");
		},

		// Handle stat comparison results
		notif_statComparison: function(notif) {
			console.log('notif_statComparison', notif);
			
			const gameInfo = document.getElementById('game-info');
			if (!gameInfo) return;
			
			// Create comparison result display
			let comparisonHTML = '<div style="padding: 20px; background: #f0f8ff; border-radius: 8px; margin: 10px; border-left: 5px solid #0066cc;">';
			comparisonHTML += '<h3>🥊 Stat Comparison Results</h3>';
			
			// Show the comparison
			comparisonHTML += '<div style="display: flex; justify-content: center; align-items: center; gap: 20px; margin: 15px 0;">';
			comparisonHTML += '<div style="text-align: center; padding: 15px; background: #ffebee; border-radius: 8px;">';
			comparisonHTML += '<div style="font-weight: bold; color: #d32f2f;">OFFENSE</div>';
			comparisonHTML += '<div style="font-size: 24px; font-weight: bold;">' + notif.args.offense_value + '</div>';
			comparisonHTML += '<div style="font-size: 12px;">' + notif.args.offense_player_name + '</div>';
			comparisonHTML += '</div>';
			
			comparisonHTML += '<div style="font-size: 24px; font-weight: bold;">VS</div>';
			
			comparisonHTML += '<div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 8px;">';
			comparisonHTML += '<div style="font-weight: bold; color: #1976d2;">DEFENSE</div>';
			comparisonHTML += '<div style="font-size: 24px; font-weight: bold;">' + notif.args.defense_value + '</div>';
			comparisonHTML += '<div style="font-size: 12px;">' + notif.args.defense_player_name + '</div>';
			comparisonHTML += '</div>';
			comparisonHTML += '</div>';
			
			// Show result with appropriate styling
			let resultColor = '#666';
			let resultIcon = '';
			
			switch(notif.args.comparison_type) {
				case 'offense_wins':
					resultColor = '#d32f2f';
					resultIcon = '🎯';
					break;
				case 'tie':
					resultColor = '#ff9800';
					resultIcon = '⚖️';
					break;
				case 'defense_wins':
					resultColor = '#1976d2';
					resultIcon = '🛡️';
					break;
			}
			
			comparisonHTML += '<div style="text-align: center; padding: 15px; background: rgba(0,0,0,0.05); border-radius: 5px; margin-top: 10px;">';
			comparisonHTML += '<div style="font-size: 18px; font-weight: bold; color: ' + resultColor + ';">' + resultIcon + ' ' + notif.args.result + '</div>';
			
			if (notif.args.scoring_card_played && notif.args.comparison_type === 'offense_wins') {
				comparisonHTML += '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 5px; border: 1px solid #ffc107;">';
				comparisonHTML += '<strong>⭐ SCORING OPPORTUNITY!</strong><br>Scramble card will be drawn...';
				comparisonHTML += '</div>';
			}
			
			comparisonHTML += '</div>';
			comparisonHTML += '</div>';
			
			gameInfo.innerHTML = comparisonHTML;
			
			// Show message
			this.showMessage(notif.args.result, 'info');
		},

		// Handle scramble card being drawn
		notif_scrambleCardDrawn: function(notif) {
			console.log('notif_scrambleCardDrawn', notif);
			
			const gameInfo = document.getElementById('game-info');
			if (!gameInfo) return;
			
			// Create scramble card display
			let scrambleHTML = '<div style="padding: 20px; background: linear-gradient(135deg, #ff6b6b, #feca57); border-radius: 8px; margin: 10px; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">';
			scrambleHTML += '<h3>🎲 SCRAMBLE CARD DRAWN!</h3>';
			scrambleHTML += '<div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 5px; margin: 10px 0;">';
			scrambleHTML += '<div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">' + notif.args.card_name + '</div>';
			scrambleHTML += '<div style="font-size: 14px; line-height: 1.4;">' + notif.args.card_description + '</div>';
			scrambleHTML += '</div>';
			scrambleHTML += '<div style="text-align: center; font-style: italic;">Resolving effects...</div>';
			scrambleHTML += '</div>';
			
			gameInfo.innerHTML = scrambleHTML;
			
			// Show message
			this.showMessage(notif.args.player_name + ' draws scramble card: ' + notif.args.card_name, 'info');
		},

		// Handle scramble card resolution
		notif_scrambleCardResolved: function(notif) {
			console.log('notif_scrambleCardResolved', notif);
			
			const gameInfo = document.getElementById('game-info');
			if (!gameInfo) return;
			
			// Update the scramble card display with results
			let resolvedHTML = '<div style="padding: 20px; background: linear-gradient(135deg, #4ecdc4, #45b7d1); border-radius: 8px; margin: 10px; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">';
			resolvedHTML += '<h3>✅ SCRAMBLE CARD RESOLVED!</h3>';
			resolvedHTML += '<div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 5px; margin: 10px 0;">';
			resolvedHTML += '<div style="font-size: 18px; font-weight: bold; margin-bottom: 10px;">' + notif.args.card_name + '</div>';
			resolvedHTML += '<div style="font-size: 14px; line-height: 1.4;">';
			
			// Show each effect
			for (let i = 0; i < notif.args.effects.length; i++) {
				resolvedHTML += '<div style="margin: 5px 0;">• ' + notif.args.effects[i] + '</div>';
			}
			
			resolvedHTML += '</div>';
			resolvedHTML += '</div>';
			resolvedHTML += '<div style="text-align: center; font-weight: bold;">Round complete!</div>';
			resolvedHTML += '</div>';
			
			gameInfo.innerHTML = resolvedHTML;
			
			// Show message
			this.showMessage('Scramble card resolved: ' + notif.args.effects.join(', '), 'info');
			
			// Update player scores if points were scored (you might want to add score tracking to player panels)
			// This would require additional UI elements to show current scores
		},

		// ALSO ADD: Enhanced version of existing scrambleDrawn notification
		notif_scrambleDrawn: function(notif) {
			console.log('notif_scrambleDrawn', notif);
			this.showMessage('Scramble situation occurred!', 'info');
		},

        // NEW notification handlers:
        notif_playerChoseDie: function(notif) {
            console.log('notif_playerChoseDie', notif);
            
            // Show die choice and result in game info
            const gameInfo = document.getElementById('game-info');
            const dieColor = notif.args.die_choice === 'red' ? '#d32f2f' : '#1976d2';
            const bgColor = notif.args.die_choice === 'red' ? '#ffebee' : '#e3f2fd';
            
            let diceHTML = '<div style="padding: 20px; background: ' + bgColor + '; border-radius: 8px; margin: 10px;">';
            diceHTML += '<h3>Die Choice & Roll</h3>';
            diceHTML += '<p><strong>' + notif.args.player_name + '</strong> chose <strong style="color: ' + dieColor + ';">' + notif.args.die_label + '</strong></p>';
            diceHTML += '<div style="display: flex; align-items: center; gap: 15px; margin: 15px 0; justify-content: center;">';
            diceHTML += '<span style="font-size: 36px; font-weight: bold; color: ' + dieColor + '; border: 3px solid ' + dieColor + '; border-radius: 8px; padding: 15px; background: white;">Face ' + notif.args.die_face + '</span>';
            diceHTML += '<span style="font-size: 24px; font-weight: bold;">= Value ' + notif.args.die_value + '</span>';
            diceHTML += '</div>';
            
            // Show costs/gains
            if (notif.args.die_choice === 'red') {
                diceHTML += '<p><em>Cost: 3 conditioning, Gained: 1 token</em></p>';
            } else {
                diceHTML += '<p><em>Cost: 2 conditioning, Gained: 2 tokens</em></p>';
            }
            
            // Check if this is the second player
            const currentHTML = gameInfo.innerHTML;
            if (currentHTML.includes('Die Choice & Roll')) {
                // This is the second player, show both results
                diceHTML = currentHTML + diceHTML;
                diceHTML += '<div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.8); border-radius: 5px; text-align: center;"><strong>Both players have rolled - results will be applied to offense stats</strong></div>';
            } else {
                diceHTML += '<p style="text-align: center; margin-top: 10px;"><em>Waiting for second player...</em></p>';
            }
            
            diceHTML += '</div>';
            gameInfo.innerHTML = diceHTML;
        },

        notif_playerReroll: function(notif) {
            console.log('notif_playerReroll', notif);
            this.showMessage(notif.args.player_name + ' spent 1 token to reroll their die', 'info');
        },

        notif_playerKeepDice: function(notif) {
            console.log('notif_playerKeepDice', notif);
            this.showMessage(notif.args.player_name + ' kept their dice result', 'info');
        },

        notif_diceRerolled: function(notif) {
            console.log('notif_diceRerolled', notif);
            
            this.showMessage(
                notif.args.player_name + ' rerolled ' + notif.args.die_label + ': ' + notif.args.die_value, 
                'info'
            );
            
            // Update the game info display if it exists
            const gameInfo = document.getElementById('game-info');
            if (gameInfo && gameInfo.innerHTML.includes('Die Choice & Roll')) {
                // Find and update the appropriate player's die display
                const playerName = notif.args.player_name;
                const regex = new RegExp('(<p><strong>' + playerName + '</strong>.*?Face )\\d+', 's');
                gameInfo.innerHTML = gameInfo.innerHTML.replace(regex, '$1' + notif.args.die_face);
                
                const valueRegex = new RegExp('(' + playerName + '.*?= Value )\\d+', 's');
                gameInfo.innerHTML = gameInfo.innerHTML.replace(valueRegex, '$1' + notif.args.die_value);
            }
        },

        // Add these new notification handlers:
        notif_redDiceRolled: function(notif) {
            console.log('notif_redDiceRolled', notif);
            
            // Show red die result in game info
            const gameInfo = document.getElementById('game-info');
            let diceHTML = '<div style="padding: 20px; background: #f8d7da; border-radius: 8px; margin: 10px;">';
            diceHTML += '<h3>Dice Results</h3>';
            diceHTML += '<p><strong style="color: red;">Red Die:</strong> <span style="font-size: 24px; font-weight: bold; color: red;">' + notif.args.die_result + '</span></p>';
            diceHTML += '<p>Waiting for blue die...</p>';
            diceHTML += '</div>';
            
            gameInfo.innerHTML = diceHTML;
        },

        notif_blueDiceRolled: function(notif) {
            console.log('notif_blueDiceRolled', notif);
            
            // Update to show blue die result
            const gameInfo = document.getElementById('game-info');
            let existingHTML = gameInfo.innerHTML;
            
            // Replace "Waiting for blue die..." with the actual result
            existingHTML = existingHTML.replace('<p>Waiting for blue die...</p>', 
                '<p><strong style="color: blue;">Blue Die:</strong> <span style="font-size: 24px; font-weight: bold; color: blue;">' + notif.args.die_result + '</span></p>');
            
            gameInfo.innerHTML = existingHTML;
        },

        notif_diceRollComplete: function(notif) {
            console.log('notif_diceRollComplete', notif);
            
            // Show final dice results
            const gameInfo = document.getElementById('game-info');
            let diceHTML = '<div style="padding: 20px; background: #d1ecf1; border-radius: 8px; margin: 10px;">';
            diceHTML += '<h3>Final Dice Results</h3>';
            diceHTML += '<div style="display: flex; gap: 30px; justify-content: center; margin: 15px 0;">';
            diceHTML += '<div style="text-align: center;">';
            diceHTML += '<div style="font-size: 18px; color: red; font-weight: bold;">Red Die</div>';
            diceHTML += '<div style="font-size: 36px; font-weight: bold; color: red; border: 3px solid red; border-radius: 8px; padding: 10px; background: white;">' + notif.args.red_die + '</div>';
            diceHTML += '</div>';
            diceHTML += '<div style="text-align: center;">';
            diceHTML += '<div style="font-size: 18px; color: blue; font-weight: bold;">Blue Die</div>';
            diceHTML += '<div style="font-size: 36px; font-weight: bold; color: blue; border: 3px solid blue; border-radius: 8px; padding: 10px; background: white;">' + notif.args.blue_die + '</div>';
            diceHTML += '</div>';
            diceHTML += '</div>';
            diceHTML += '</div>';
            
            gameInfo.innerHTML = diceHTML;
            
            this.showMessage('Dice rolling complete: Red ' + notif.args.red_die + ', Blue ' + notif.args.blue_die, 'info');
        },
        
        notif_wrestlerSelected: function(notif) {
            console.log('notif_wrestlerSelected', notif);
            
            const playerId = notif.args.player_id;
            const wrestlerName = notif.args.wrestler_name;
            const wrestlerId = notif.args.wrestler_id;
            
            // Update local player data with wrestler stats
            if (this.gamedatas.players[playerId]) {
                const wrestler = this.gamedatas.wrestlers[wrestlerId];
                if (wrestler) {
                    this.gamedatas.players[playerId].wrestler_id = wrestlerId;
                    this.gamedatas.players[playerId].conditioning = wrestler.conditioning_p1;
                    this.gamedatas.players[playerId].offense = wrestler.offense;
                    this.gamedatas.players[playerId].defense = wrestler.defense;
                    this.gamedatas.players[playerId].top = wrestler.top;
                    this.gamedatas.players[playerId].bottom = wrestler.bottom;
                    this.gamedatas.players[playerId].special_tokens = wrestler.special_tokens;
                    this.gamedatas.players[playerId].wrestler = wrestler;
                }
            }
            
            // Update player panel
            const wrestlerNameElement = document.getElementById(`wrestler-name-${playerId}`);
            if (wrestlerNameElement) {
                wrestlerNameElement.textContent = wrestlerName;
            }
            
            // Update conditioning display
            const conditioningElement = document.getElementById(`conditioning-${playerId}`);
            if (conditioningElement && this.gamedatas.players[playerId]) {
                conditioningElement.textContent = `Conditioning: ${this.gamedatas.players[playerId].conditioning || 0}`;
            }
            
            // Update stats display
            const statsElement = document.querySelector(`#player-wrestler-info-${playerId} .wrestler-stats-compact`);
            if (statsElement && this.gamedatas.players[playerId]) {
                const player = this.gamedatas.players[playerId];
                statsElement.textContent = `O:${player.offense || 0} D:${player.defense || 0} T:${player.top || 0} B:${player.bottom || 0}`;
            }
            
            // Remove wrestler from available list if visible
            const wrestlerCard = document.getElementById(`wrestler-${wrestlerId}`);
            if (wrestlerCard) {
                wrestlerCard.style.opacity = '0.3';
                wrestlerCard.style.pointerEvents = 'none';
                wrestlerCard.innerHTML += '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: red; font-weight: bold; font-size: 18px; background: rgba(255,255,255,0.9); padding: 5px 10px; border-radius: 5px;">SELECTED</div>';
                wrestlerCard.style.position = 'relative';
            }
            
            // Check if this was our selection
            if (playerId == this.player_id) {
                // Remove confirm button
                const confirmBtn = document.getElementById('confirm-wrestler-btn');
                if (confirmBtn) {
                    confirmBtn.remove();
                }
                
                // Show message
                this.showMessage('Wrestler selected! Waiting for other players...', 'info');
                
                // Disable all remaining cards for this player
                document.querySelectorAll('.wrestler-card').forEach(card => {
                    card.style.pointerEvents = 'none';
                });
            }
        },        
        
        notif_startingPositionChoice: function(notif) {
            console.log('notif_startingPositionChoice', notif);
            
            // Display message about who gets to choose
            this.showMessage(`${notif.args.player_name} has higher conditioning (${notif.args.conditioning}) and chooses starting position`, 'info');
        },
        
        notif_positionSelected: function(notif) {
            console.log('notif_positionSelected', notif);
            
            // Update game info to show current positions
            document.getElementById('game-info').innerHTML = `
                <div style="padding: 20px; background: #e8f5e8; border-radius: 8px; margin: 10px;">
                    <h3>Match Started!</h3>
                    <p><strong>Period ${notif.args.period}, Round ${notif.args.round}</strong></p>
                    <p><strong>Positions:</strong></p>
                    <p>Offense: ${this.gamedatas.players[notif.args.offense_player_id].name}</p>
                    <p>Defense: ${this.gamedatas.players[notif.args.defense_player_id].name}</p>
                </div>
            `;
        },
        
        notif_cardPlayed: function(notif) {
            console.log('notif_cardPlayed', notif);
            
            // Display card played message
            const playerId = notif.args.player_id;
            const cardName = notif.args.card_name;
            
            // You could add visual effects here like showing the card played
            this.showMessage(`${notif.args.player_name} played ${cardName}`, 'info');
        },
        
        notif_pass: function(notif) {
            console.log('notif_pass', notif);
            
            // Display pass message
            this.showMessage(`${notif.args.player_name} passed`, 'info');
        },

        notif_firstCardPlayed: function(notif) {
            console.log('notif_firstCardPlayed', notif);
            this.showMessage(notif.args.player_name + ' has played a card', 'info');
            
            // Hide hand if it was our turn
            if (notif.args.player_id == this.player_id) {
                document.getElementById('player-hand-area').style.display = 'none';
            }
        },

        notif_secondCardPlayed: function(notif) {
            console.log('notif_secondCardPlayed', notif);
            this.showMessage(notif.args.player_name + ' has played a card', 'info');
            
            // Hide hand if it was our turn
            if (notif.args.player_id == this.player_id) {
                document.getElementById('player-hand-area').style.display = 'none';
            }
        },

        notif_cardsRevealed: function(notif) {
            console.log('notif_cardsRevealed', notif);
            
            // Show both cards played
            var gameInfo = document.getElementById('game-info');
            var revealHTML = '<div style="padding: 20px; background: #fff3cd; border-radius: 8px; margin: 10px;">';
            revealHTML += '<h3>Cards Revealed!</h3>';
            revealHTML += '<p><strong>' + notif.args.first_player_name + '</strong> played: ' + notif.args.first_card_name + '</p>';
            revealHTML += '<p><strong>' + notif.args.second_player_name + '</strong> played: ' + notif.args.second_card_name + '</p>';
            revealHTML += '</div>';
            
            gameInfo.innerHTML = revealHTML;
        },

        notif_conditioningAdjusted: function(notif) {
            console.log('notif_conditioningAdjusted', notif);
            
            // Update conditioning displays for both players
            for (var playerId in notif.args.updates) {
                var update = notif.args.updates[playerId];
                var conditioningElement = document.getElementById('conditioning-' + playerId);
                if (conditioningElement) {
                    conditioningElement.textContent = 'Conditioning: ' + update.conditioning;
                }
            }
            
            this.showMessage('Conditioning adjusted based on cards played', 'info');
        },

        notif_diceRolled: function(notif) {
            console.log('notif_diceRolled', notif);
            
            var gameInfo = document.getElementById('game-info');
            var diceHTML = '<div style="padding: 20px; background: #f8d7da; border-radius: 8px; margin: 10px;">';
            diceHTML += '<h3>Dice Rolled!</h3>';
            diceHTML += '<p>Red Die: <strong>' + notif.args.red_die + '</strong></p>';
            diceHTML += '<p>Blue Die: <strong>' + notif.args.blue_die + '</strong></p>';
            diceHTML += '</div>';
            
            gameInfo.innerHTML += diceHTML;
        },

        notif_effectsApplied: function(notif) {
            console.log('notif_effectsApplied', notif);
            
            // Update offense displays for both players if offense_updates is provided
            if (notif.args.offense_updates) {
                for (const playerId in notif.args.offense_updates) {
                    const newOffense = notif.args.offense_updates[playerId];
                    
                    // Update the compact stats display in player panel
                    const statsElement = document.querySelector(`#player-wrestler-info-${playerId} .wrestler-stats-compact`);
                    if (statsElement && this.gamedatas.players[playerId]) {
                        const player = this.gamedatas.players[playerId];
                        
                        // Update the local data
                        this.gamedatas.players[playerId].offense = newOffense;
                        
                        // Update the display
                        statsElement.textContent = `O:${newOffense} D:${player.defense || 0} T:${player.top || 0} B:${player.bottom || 0}`;
                        
                        // Add a visual highlight to show the change
                        statsElement.style.background = '#ffeb3b';
                        statsElement.style.transition = 'background 2s ease';
                        setTimeout(() => {
                            statsElement.style.background = '';
                        }, 2000);
                    }
                }
            }
            
            // Show the effects in game info
            let effectsHTML = '<div style="padding: 15px; background: #d1ecf1; border-radius: 8px; margin: 10px;">';
            effectsHTML += '<h4>Effects Applied:</h4>';
            
            // Show dice results if provided
            if (notif.args.first_die_value !== undefined && notif.args.second_die_value !== undefined) {
                effectsHTML += '<div style="margin: 10px 0; padding: 10px; background: #e8f5e8; border-radius: 5px;">';
                effectsHTML += '<strong>Dice Results Applied to Offense:</strong><br>';
                effectsHTML += `Player 1 ${notif.args.first_die_choice.toUpperCase()} die: ${notif.args.first_die_value}<br>`;
                effectsHTML += `Player 2 ${notif.args.second_die_choice.toUpperCase()} die: ${notif.args.second_die_value}`;
                effectsHTML += '</div>';
            }
            
            // Show other effects
            effectsHTML += '<ul>';
            for (let i = 0; i < notif.args.effects.length; i++) {
                effectsHTML += '<li>' + notif.args.effects[i] + '</li>';
            }
            effectsHTML += '</ul></div>';
            
            document.getElementById('game-info').innerHTML += effectsHTML;
            
            this.showMessage('Card and dice effects applied - check your stats!', 'info');
        },
        
        notif_tokensHandled: function(notif) {
            console.log('notif_tokensHandled', notif);
            
            // Update special token displays for both players
            for (var playerId in notif.args.updates) {
                var update = notif.args.updates[playerId];
                // Update player panel if you have token display there
                // For now just show message
            }
            
            this.showMessage('Special tokens updated', 'info');
        },

        notif_scrambleDrawn: function(notif) {
            console.log('notif_scrambleDrawn', notif);
            this.showMessage('Scramble card drawn!', 'info');
        },

        notif_newRound: function(notif) {
            console.log('notif_newRound', notif);
            
            var gameInfo = document.getElementById('game-info');
            var roundHTML = '<div style="padding: 20px; background: #d4edda; border-radius: 8px; margin: 10px;">';
            roundHTML += '<h3>Period ' + notif.args.period + ', Round ' + notif.args.round + '</h3>';
            roundHTML += '<p>New round begins!</p>';
            roundHTML += '</div>';
            
            gameInfo.innerHTML = roundHTML;
        }
   });             
});