#!/bin/bash

echo "📤 Pushing latest local code to GitHub..."
git add .
git commit -m "🛠 Local development update on $(date +%Y-%m-%d_%H:%M)"
git push origin main || { echo "❌ Push failed"; exit 1; }

echo "📝 Logging deployment activity..."
LOG="deployment-history.txt"
echo "Deploy from local to GitHub on $(date +%Y-%m-%d_%H:%M) by $(whoami)" >> $LOG

git add $LOG
git commit -m "📦 Logged local deployment on $(date +%Y-%m-%d)"
git push origin main

echo "✅ Local deployment completed successfully!"
