/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * matrevx implementation : © <Your name here> <Your email address here>
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
                        
                    case 'playerTurn':    
                        const playableCardsIds = args.playableCardsIds;

                        playableCardsIds.forEach(
                            cardId => this.statusBar.addActionButton(_('Play card ${card_id}').replace('${card_id}', cardId), () => this.onCardClick(cardId))
                        ); 

                        this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction("actPass"), { color: 'secondary' }); 
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
            
            // Clear previous wrestlers
            const container = document.getElementById('available-wrestlers');
            container.innerHTML = '';
            
            // Fix: BGA wraps args in args.args
            const available_wrestlers = args && args.args && args.args.available_wrestlers;
            
            if (!available_wrestlers) {
                container.innerHTML = '<div>No wrestlers available</div>';
                console.log('Could not find available_wrestlers in:', args);
                return;
            }
            
            console.log('Found available wrestlers:', available_wrestlers);
            
            // Add available wrestlers
            Object.entries(available_wrestlers).forEach(([wrestlerId, wrestler]) => {
                console.log('Adding wrestler:', wrestlerId, wrestler);
                
                const wrestlerDiv = document.createElement('div');
                wrestlerDiv.className = 'wrestler-card';
                wrestlerDiv.id = `wrestler-${wrestlerId}`;
                wrestlerDiv.innerHTML = `
                    <div class="wrestler-name">${wrestler.name}</div>
                    <div class="wrestler-stats">
                        <div>Conditioning: ${wrestler.conditioning_p1}/${wrestler.conditioning_p2}/${wrestler.conditioning_p3}</div>
                        <div>Offense: ${wrestler.offense}, Defense: ${wrestler.defense}</div>
                        <div>Top: ${wrestler.top}, Bottom: ${wrestler.bottom}</div>
                        <div>Special Tokens: ${wrestler.special_tokens}</div>
                    </div>
                    <div class="wrestler-trademark"><small>${wrestler.trademark}</small></div>
                    <div class="wrestler-special-cards"><small>Special Cards: ${wrestler.special_cards ? wrestler.special_cards.join(', ') : 'None'}</small></div>
                `;
                wrestlerDiv.style.cssText = `
                    border: 2px solid #ccc;
                    margin: 10px;
                    padding: 15px;
                    cursor: pointer;
                    border-radius: 8px;
                    background: #f9f9f9;
                    max-width: 300px;
                `;
                
                // Add click handler
                wrestlerDiv.addEventListener('click', () => this.onWrestlerClick(wrestlerId));
                
                container.appendChild(wrestlerDiv);
            });
            
            console.log('Total wrestlers added:', container.children.length);
        },
        
        leaveWrestlerSelection: function() {
            // Hide wrestler selection area
            document.getElementById('wrestler-selection-area').style.display = 'none';
            document.getElementById('wrestling-mat').style.display = 'block';
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
            
            // Highlight selected wrestler
            document.querySelectorAll('.wrestler-card').forEach(card => {
                card.style.border = '2px solid #ccc';
                card.style.background = '#f9f9f9';
            });
            
            const selectedCard = document.getElementById(`wrestler-${wrestlerId}`);
            selectedCard.style.border = '2px solid #0066cc';
            selectedCard.style.background = '#e6f3ff';
            
            this.selectedWrestler = wrestlerId;
            
            // Add confirm button if not already present
            if (!document.getElementById('confirm-wrestler-btn')) {
                this.statusBar.addActionButton(
                    _('Confirm Wrestler Selection'), 
                    () => this.confirmWrestlerSelection(),
                    { id: 'confirm-wrestler-btn', color: 'primary' }
                );
            }
        },
        
        confirmWrestlerSelection: function() {
            if (!this.selectedWrestler) {
                this.showMessage(_('Please select a wrestler first'), 'error');
                return;
            }
            
            console.log('Confirming wrestler selection:', this.selectedWrestler);
            
            this.bgaPerformAction("actSelectWrestler", { 
                wrestler_id: parseInt(this.selectedWrestler)
            }).then(() => {
                // Success handled by notification
            }).catch(error => {
                console.error('Error selecting wrestler:', error);
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
            
            // Subscribe to card played notification  
            dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
            
            // Subscribe to pass notification
            dojo.subscribe('pass', this, "notif_pass");
        },
        
        notif_wrestlerSelected: function(notif) {
            console.log('notif_wrestlerSelected', notif);
            
            const playerId = notif.args.player_id;
            const wrestlerName = notif.args.wrestler_name;
            
            // Update player panel
            const wrestlerNameElement = document.getElementById(`wrestler-name-${playerId}`);
            if (wrestlerNameElement) {
                wrestlerNameElement.textContent = wrestlerName;
            }
            
            // Remove wrestler from available list if visible
            const wrestlerCard = document.getElementById(`wrestler-${notif.args.wrestler_id}`);
            if (wrestlerCard) {
                wrestlerCard.style.opacity = '0.5';
                wrestlerCard.style.pointerEvents = 'none';
                wrestlerCard.innerHTML += '<div style="color: red; font-weight: bold;">SELECTED</div>';
            }
            
            // Update conditioning display
            const conditioningElement = document.getElementById(`conditioning-${playerId}`);
            if (conditioningElement && this.gamedatas.players[playerId]) {
                // We'll need to get the updated conditioning from the wrestler data
                // For now, just show that wrestler was selected
                conditioningElement.textContent = `${wrestlerName} Selected`;
            }
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