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
            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                <div id="wrestler-selection-area" style="display: none;">
                    <h3>Select Your Wrestler</h3>
                    <div id="available-wrestlers"></div>
                </div>
                <div id="wrestling-mat" style="display: none;">
                    <div id="player-stats"></div>
                    <div id="game-info"></div>
                </div>
            `);
            
            // Setting up player boards with wrestler info
            Object.values(gamedatas.players).forEach(player => {
                // Add wrestler info to player panel
                this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                    <div id="player-wrestler-info-${player.id}">
                        <div id="wrestler-name-${player.id}">
                            ${player.wrestler ? player.wrestler.name : 'No wrestler selected'}
                        </div>
                        <div id="conditioning-${player.id}" class="stat-display">
                            C: ${player.conditioning || 0}
                        </div>
                    </div>
                `);

                // Add player table in main area
                document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                    <div id="player-table-${player.id}" style="display: none;">
                        <strong>${player.name}</strong>
                        <div class="wrestler-stats">
                            <span>O: ${player.offense || 0}</span>
                            <span>D: ${player.defense || 0}</span>
                            <span>T: ${player.top || 0}</span>
                            <span>B: ${player.bottom || 0}</span>
                            <span>Tokens: ${player.special_tokens || 0}</span>
                        </div>
                    </div>
                `);
            });

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
                    
                case 'playerTurn':
                    this.enterPlayerTurn();
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
                        
                    case 'playerTurn':    
                        const playableCardsIds = args.playableCardsIds;

                        playableCardsIds.forEach(
                            cardId => this.addActionButton('btn-card-' + cardId, _('Play card ${card_id}').replace('${card_id}', cardId), () => this.onCardClick(cardId))
                        ); 

                        this.addActionButton('btn-pass', _('Pass'), () => this.bgaPerformAction("actPass"), null, null, 'gray'); 
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
        
        enterPlayerTurn: function() {
            // Show game area
            document.getElementById('wrestling-mat').style.display = 'block';
            
            // Show player tables
            Object.keys(this.gamedatas.players).forEach(playerId => {
                document.getElementById(`player-table-${playerId}`).style.display = 'block';
            });
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
            
            // Subscribe to wrestler selection notification
            dojo.subscribe('wrestlerSelected', this, "notif_wrestlerSelected");
            
            // Subscribe to starting position notifications
            dojo.subscribe('startingPositionChoice', this, "notif_startingPositionChoice");
            dojo.subscribe('positionSelected', this, "notif_positionSelected");
            
            // Subscribe to card played notification  
            dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
            
            // Subscribe to pass notification
            dojo.subscribe('pass', this, "notif_pass");
        },
        
        notif_wrestlerSelected: function(notif) {
            console.log('notif_wrestlerSelected', notif);
            
            const playerId = notif.args.player_id;
            const wrestlerName = notif.args.wrestler_name;
            const wrestlerId = notif.args.wrestler_id;
            
            // Update player panel
            const wrestlerNameElement = document.getElementById(`wrestler-name-${playerId}`);
            if (wrestlerNameElement) {
                wrestlerNameElement.textContent = wrestlerName;
            }
            
            // Update conditioning display
            const conditioningElement = document.getElementById(`conditioning-${playerId}`);
            if (conditioningElement) {
                conditioningElement.textContent = `${wrestlerName} Selected`;
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
        }
   });             
});