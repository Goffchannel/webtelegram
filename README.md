# TeleBot - Laravel Telegram Bot with Stripe Integration

A Laravel-based Telegram bot that handles video content delivery with Stripe payment integration.

## 🚀 One-Command Deployment (Recommended)

**Deploy everything with a single command:**

```bash
curl -fsSL https://raw.githubusercontent.com/AlexCodeForge/telebot/master/deploy-telebot.sh | bash -s -- \
  "YOUR_TELEGRAM_TOKEN" \
  "YOUR_STRIPE_PUBLIC_KEY" \
  "YOUR_STRIPE_SECRET_KEY" \
  "alexcodeforge.com"
```

**Or download and run locally:**

```bash
wget https://raw.githubusercontent.com/AlexCodeForge/telebot/master/deploy-telebot.sh
chmod +x deploy-telebot.sh
./deploy-telebot.sh "YOUR_TELEGRAM_TOKEN" "YOUR_STRIPE_PUBLIC_KEY" "YOUR_STRIPE_SECRET_KEY"
```

**That's it!** The script will:

-   ✅ Install Docker (if needed)
-   ✅ Clone the repository
-   ✅ Configure all credentials securely.
-   ✅ Set up SSL certificates automatically
-   ✅ Start your TeleBot with Nginx Proxy Manager

Replace these placeholders with your actual credentials:

-   **Telegram Token:** `YOUR_TELEGRAM_BOT_TOKEN`
-   **Stripe Public:** `YOUR_STRIPE_PUBLIC_KEY`
-   **Stripe Secret:** `YOUR_STRIPE_SECRET_KEY`

## 🌐 SSL Setup (5 minutes)

After deployment:

1. Go to `http://YOUR_SERVER_IP:81`
2. Login: `admin@example.com` / `changeme`
3. Add proxy host for `alexcodeforge.com` → `telebot-app:80`
4. Enable SSL with Let's Encrypt
5. Set Telegram webhook to `https://alexcodeforge.com/api/telegram/webhook`

## ✨ Features

-   🤖 **Telegram Bot Integration** - Full bot functionality with webhook support
-   👥 **User Management** - Registration, authentication, and user profiles
-   🎥 **Video Content System** - Upload, manage, and deliver video content
-   💳 **Stripe Payment Integration** - Secure payment processing with Laravel Cashier
-   🔐 **Admin Panel** - Web interface for managing users, videos, and purchases
-   📊 **Purchase Tracking** - Track user purchases and delivery status
-   🐳 **Docker Ready** - Complete containerized deployment solution
-   🔒 **SSL Support** - Automatic HTTPS certificates via Let's Encrypt
-   ☁️ **Vercel Blob Storage** - Serverless image storage for Vercel deployments (FREE)

## ☁️ Vercel Deployment (Serverless)

Deploy to Vercel for free serverless hosting:

1. **Fork this repository** to your GitHub account
2. **Connect to Vercel** and import your forked repository
3. **Set up Vercel Blob Storage** for thumbnail uploads:
   - Go to your Vercel project → Storage → Create Blob store
   - Copy the `BLOB_READ_WRITE_TOKEN`
   - Add it to your Vercel environment variables
4. **Configure Environment Variables** in Vercel:
   - `TELEGRAM_BOT_TOKEN` - Your bot token
   - `STRIPE_KEY` - Your Stripe publishable key  
   - `STRIPE_SECRET` - Your Stripe secret key
   - `BLOB_READ_WRITE_TOKEN` - Your Vercel Blob token
   - `DB_CONNECTION=sqlite` (or your database)
5. **Deploy** and set your webhook to `https://yourapp.vercel.app/api/telegram/webhook`

📖 **Detailed Setup**: See [docs/vercel-blob-setup.md](docs/vercel-blob-setup.md)

## 🛠️ Manual Development Setup

If you want to develop locally:

```bash
git clone https://github.com/AlexCodeForge/telebot.git
cd telebot
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## 📝 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
