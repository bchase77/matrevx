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
                        // Cards will be shown in hand area, just add pass button if needed
                        // You might want to remove pass option or make it conditional
                        // this.addActionButton('btn-pass', _('Pass'), () => this.bgaPerformAction("actPass"), null, null, 'gray'); 
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
        
        enterPlayerTurn: function(args) {
            // Show game area
            document.getElementById('wrestling-mat').style.display = 'block';
            
            // If it's our turn, show our hand
            if (this.isCurrentPlayerActive()) {
                // Get the args from the state
                const stateArgs = args && args.args;
                const playableCardsIds = stateArgs && stateArgs.playableCardsIds || [];
                const currentPosition = stateArgs && stateArgs.current_position || 'unknown';
                
                console.log('Player turn args:', stateArgs);
                
                // Show position info
                this.showPositionInfo(currentPosition);
                
                // Display our available cards
                this.displayPlayerHand(playableCardsIds, currentPosition);
            }
        },

        showPositionInfo: function(position) {
            const gameInfo = document.getElementById('game-info');
            if (gameInfo) {
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
            
            // Update hand title to show position
            const handTitle = handArea.querySelector('h3');
            if (handTitle) {
                handTitle.textContent = `Your ${currentPosition.toUpperCase()} Cards`;
            }
            
            // Use card types from game data - add safety check
            const cardTypes = this.gamedatas && this.gamedatas.cardTypes;
            if (!cardTypes) {
                console.error('No cardTypes found in gamedatas!');
                handContainer.innerHTML = '<div>Error: Card data not available</div>';
                return;
            }
            
            console.log('Available cardTypes:', cardTypes);
            
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
                let cardHTML = '<div class="card-header" style="font-weight: bold; font-size: 14px; margin-bottom: 8px;">' + card.card_name + '</div>';
                
                // Position badge
                const positionColor = this.getPositionColor(card.position);
                cardHTML += '<div class="card-position" style="background: ' + positionColor + '; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; display: inline-block; margin-bottom: 8px;">' + card.position.toUpperCase() + '</div>';
                
                cardHTML += '<div class="card-stats" style="font-size: 12px; line-height: 1.3;">';
                cardHTML += '<div><strong>Conditioning:</strong> ' + card.conditioning_cost + '</div>';
                cardHTML += '<div><strong>Tokens:</strong> ' + card.special_tokens + '</div>';
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
            return colors[position] || '#95a5a6';
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
                selectedCard.style.background = '#f0f8ff';
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
			
			// New gameplay notifications
			dojo.subscribe('firstCardPlayed', this, "notif_firstCardPlayed");
			dojo.subscribe('secondCardPlayed', this, "notif_secondCardPlayed");
			dojo.subscribe('cardsRevealed', this, "notif_cardsRevealed");
			dojo.subscribe('conditioningAdjusted', this, "notif_conditioningAdjusted");
			dojo.subscribe('diceRolled', this, "notif_diceRolled");
			dojo.subscribe('effectsApplied', this, "notif_effectsApplied");
			dojo.subscribe('tokensHandled', this, "notif_tokensHandled");
			dojo.subscribe('scrambleDrawn', this, "notif_scrambleDrawn");
			dojo.subscribe('newRound', this, "notif_newRound");
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
			
			var effectsHTML = '<div style="padding: 15px; background: #d1ecf1; border-radius: 8px; margin: 10px;">';
			effectsHTML += '<h4>Effects Applied:</h4>';
			effectsHTML += '<ul>';
			for (var i = 0; i < notif.args.effects.length; i++) {
				effectsHTML += '<li>' + notif.args.effects[i] + '</li>';
			}
			effectsHTML += '</ul></div>';
			
			document.getElementById('game-info').innerHTML += effectsHTML;
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
		},
   });             
});