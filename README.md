# 🎬 Entertainment Tadka Bot v3.0

A powerful Telegram bot for movie search, requests, and channel management.

## ✨ Features

- 🔍 Smart movie search with Hindi/English support
- 📁 Category-wise browsing (Main, Theater, Serial, Backup)
- 📝 Movie request system with daily limits
- 👑 Complete admin panel with buttons
- 📊 Bulk approve/reject movie requests
- 🔐 Toggle forward headers per channel
- 💾 Auto-backup system to backup channel
- 📈 Detailed statistics

## 🚀 Deployment on Render

### Environment Variables (Add in Render Dashboard)

| Variable | Value | Description |
|----------|-------|-------------|
| `BOT_TOKEN` | `8315381064:AAGk0FGVGmB8j5SjpBvW3rD3_kQHe_hyOWU` | Your bot token from @BotFather |
| `ADMIN_ID` | `1080317415` | Your Telegram user ID |

### Steps to Deploy

1. Fork this repository to your GitHub account
2. Log in to [Render.com](https://render.com)
3. Click "New +" and select "Blueprint"
4. Connect your GitHub repository
5. Add environment variables (BOT_TOKEN, ADMIN_ID)
6. Click "Apply"

### Commands

#### User Commands
- `/start` - Welcome message
- `/help` - Help menu
- `/search movie` - Search movies
- `/browse` - Browse by category
- `/request movie` - Request missing movie
- `/myrequests` - Check request status
- `/channel` - All channel links
- `/report` - Report a bug
- `/feedback` - Give feedback
- `/info` - Bot information
- `/ping` - Check bot status

#### Admin Commands
- `/admin` - Open admin panel
- `/pending` - View pending requests
- `/approve [count]` - Bulk approve requests
- `/reject [count]` - Bulk reject requests
- `/forward` - Forward header settings
- `/broadcast msg` - Send message to all users
- `/stats` - Bot statistics
- `/maintenance on/off` - Toggle maintenance mode
- `/cleanup` - Cleanup old backups

## 📢 Channels

- 🍿 Main: @EntertainmentTadka786
- 📥 Request: @EntertainmentTadka7860
- 🎭 Theater: @threater_print_movies
- 📂 Backup: @ETBackup
- 📺 Serial: @Entertainment_Tadka_Serial_786

## 🛠️ Local Development

```bash
# Clone repository
git clone https://github.com/yourusername/entertainment-tadka-bot.git

# Go to directory
cd entertainment-tadka-bot

# Set webhook
php index.php?setwebhook=1