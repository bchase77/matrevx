#!/bin/bash
# One-shot sync to BGA Studio using environment variables

# Check if environment variables are set
if [ -z "$BGA_USERNAME" ]; then
    echo "❌ Error: BGA_USERNAME environment variable not set"
    echo "Please set it with: export BGA_USERNAME='your_username'"
    exit 1
fi

if [ -z "$BGA_PASSWORD" ]; then
    echo "❌ Error: BGA_PASSWORD environment variable not set"
    echo "Please set it with: export BGA_PASSWORD='your_password'"
    exit 1
fi

echo "🚀 Deploying to BGA Studio as user: $BGA_USERNAME"

# Deploy using environment variables
lftp sftp://$BGA_USERNAME:$BGA_PASSWORD@1.studio.boardgamearena.com:2022/ -e "set net:max-retries 1; mirror --reverse --parallel=10 --delete ~/Documents/Claude/matrevx/ matrevx/; exit"

if [ $? -eq 0 ]; then
    echo "✅ Deployment to BGA Studio completed successfully!"
else
    echo "❌ Deployment failed!"
    exit 1
fi