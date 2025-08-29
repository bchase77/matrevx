# Claude Code Project Management Guidelines

## Wrestling Game Development Notes

### Permanent Guidelines for Future Projects

1. **Prompt Storage**: Store every user prompt in this file for later review and analysis for future projects
2. **Time Tracking**: Track time spent on work sessions in ±30 minute intervals  
3. **Todo Management**: Use TodoWrite tool extensively for task tracking and progress visibility
4. **Deployment Process**: Always commit with descriptive messages and deploy to BGA Studio after each set of changes

### User Prompts Archive

#### Session 1 - Initial TODO Fixes (2025-01-XX)
- "help me continue the wrestling game" 
- Layout requests: Move mat, align stat boards, remove orange borders
- Multiple positioning adjustments and refinements

#### Session 2 - Layout Refinements (2025-01-XX)
- "Move the mat to the top of the table area"
- "Closer. Rotate the mat 90 degrees counter clockwise..."
- Multiple alignment and positioning requests
- "closer. Move the 2 wrestler cards slightly to the right on the mat image, to cover the green and red WRESTLER boxes. Remove the circles I drew red Xs over from the game board for now. Tell me what these are and we'll place them later. Move the Opponent stats board so its top aligns with the top of the mat image to its left. That means, the 3 green lines I show should all be 1 straight line horizontally. Move the player stats board so its bottom aligns with the bottom of the mat image to its left. That means, the 3 purple lines I show should all be 1 straight line horizontally. Also change the scaling behavior: When I scale the browser window smaller, when it gets "too small" make all assets on the table scale smaller."

### Technical Notes

#### ✅ CONFIRMED GOOD LAYOUT - Mat Graphics Assets Positioning 
**Date: 2025-01-XX - FINAL POSITIONING APPROVED**

**Wrestling Mat (500x500px)**: Perfect positioning with clean layout
- **Opponent wrestler card**: Top right green WRESTLER box (top: 40px, right: 20px) ✅
- **Opponent move card**: Top center green MOVE rectangle (top: 40px, left: 160px) ✅  
- **Player wrestler card**: Bottom right red WRESTLER box (bottom: 40px, right: 20px) ✅
- **Player move card**: Bottom center red MOVE rectangle (bottom: 40px, left: 160px) ✅
- **Scramble area**: Left yellow box (left: 20px, top: 160px) ✅
- **White dialog box**: High z-index (9999) ensures it appears above all cards ✅

**Status**: All card positions verified and approved. No rotation needed. Layout is production-ready.

#### Current Layout Status
- Clean flexbox layout with mat + stats board side by side
- No complex positioning or rotation transforms  
- Responsive scaling implemented for smaller screens
- Ready for stats board implementation

### Deployment Info
- BGA Studio credentials configured in deploy.sh
- Git workflow: commit with descriptive messages, then deploy
- All changes tracked through todo system and git history