#!/bin/bash
# Enhanced deployment workflow for matrevx BGA game
# Usage: ./deploy-workflow.sh "commit message"

set -e  # Exit on any error

echo "🎯 Starting matrevx deployment workflow..."

# Check if commit message provided
if [ -z "$1" ]; then
    echo "❌ Error: Please provide a commit message"
    echo "Usage: $0 \"Your commit message\""
    exit 1
fi

COMMIT_MESSAGE="$1"

echo "📝 Checking git status..."
git status

echo "📦 Adding all changes to git..."
git add -A

echo "💾 Committing changes..."
git commit -m "$COMMIT_MESSAGE

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>" --allow-empty

echo "🚀 Pushing to remote repository..."
git push origin main

echo "🎮 Deploying to BGA Studio..."
# Run the existing deploy script
./deploy.sh

echo "✅ Deployment workflow completed successfully!"
echo "📍 Your changes are now:"
echo "   - Committed to git"
echo "   - Pushed to GitHub"  
echo "   - Deployed to BGA Studio"