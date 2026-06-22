# VillaCRM

Welcome to **VillaCRM**, the complete command center for modern real estate agencies. Designed to be a high-performance Property Operating System, VillaCRM unifies CRM, property management, financial accounting, and tenant portals under one elegant, lightning-fast platform powered by Laravel and Livewire v3.

## 🚀 Key Features

### 🏢 Agency & Team Management
- **Multi-Tenant Architecture**: Supports multiple real estate agencies with customized branding, custom domains, and configurable billing cycles.
- **Role-Based Access**: Granular permissions for Principals, Agents, and Admins.
- **Commission Splits**: Advanced configuration for team payouts and lead routing logic.

### 💼 Intelligent CRM & Lead Pipeline
- **Pipeline Stages**: Fully customizable drag-and-drop Kanban boards for tracking leads and active deals.
- **Automated Lead Routing**: Intelligently assign incoming leads to agents based on round-robin or performance metrics.
- **Omnichannel Communication**: Built-in email account integration, call management (via LiveKit/Twilio), and SMS notifications.

### 🏡 Property & Listing Management
- **Rich Listings**: Manage properties, media, and marketing copy in one place.
- **Viewings & Open Houses**: Schedule day-views, track RSVPs via public portals, and collect automated public feedback via geo-fenced check-ins.
- **Offers & Contracts**: Digital offer management and e-signature tracking.

### 💰 Financial Accounting & Rent Collection
- **End-to-End Accounting**: Built-in ledgers for expenses, invoices, and budgets.
- **Lease & Deposit Management**: Track active leases, automate renewal reminders, and manage security deposits securely.
- **Rent Collection Dashboard**: Seamless integrations with local gateways (like Paystack) for automated tenant billing and reconciliation.

### 🤝 Tenant Portal
- **Self-Service**: Empower tenants with a dedicated portal to view their active leases, submit maintenance requests, and pay rent online.
- **Compliance & Quit Notices**: Automated compliance reminders and formal notice generation.

### 🧠 AI & Agent Training
- **Skills Library & Role-Play**: Internal coaching tools to help agents practice objection handling and refine their sales pitches using AI simulation.
- **Smart Insights**: AI-powered call summaries, sentiment analysis, and dashboard widgets.

### 📱 Native Mobile App
- **VillaCRMMobile**: A companion React Native mobile application for agents on the go.
- **Features**: Push notifications, geo-fenced viewing check-ins, LiveKit native call integrations, and an Apple Watch companion app for quick daily briefs.

## 🛠 Tech Stack

### Web Application
- **Framework**: Laravel 11.x
- **Frontend**: Livewire v3, Alpine.js, Tailwind CSS
- **Database**: MySQL
- **Real-Time**: Pusher / WebSockets
- **Media/Voice**: LiveKit

### Mobile Application (`/mobile`)
- **Framework**: React Native (0.85.x)
- **Styling**: NativeWind (Tailwind)
- **State Management**: Zustand
- **Local Storage**: react-native-mmkv
- **Integrations**: LiveKit React Native SDK, CallKeep, Firebase Cloud Messaging

## ⚙️ Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm/yarn
- MySQL
- Laravel Herd / Valet (Recommended for local development)

### Installation

1. **Clone the repository and install dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup:**
   Copy the `.env.example` to `.env` and configure your database, LiveKit, and Paystack credentials.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Migration & Seeding:**
   Run the database migrations and populate the platform with initial roles and demo agency data.
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Build Assets:**
   Compile the Tailwind CSS and JS assets.
   ```bash
   npm run dev
   ```

5. **Start Services:**
   Make sure your queue worker and Reverb/WebSockets server are running.
   ```bash
   php artisan queue:work
   php artisan reverb:start
   ```

### Mobile App Development
To run the companion React Native app:
```bash
cd mobile
npm install
npm run ios # or npm run android
```

## 🔒 Security & Compliance
- Enforced password policies and passkey/biometric support.
- Mandatory 2FA for sensitive agency data access.
- API Key management and Webhook subscriptions for secure 3rd party integrations.

---
*VillaCRM - Your Real Estate Operating System.*
