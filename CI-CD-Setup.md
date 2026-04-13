# CI/CD Setup Guide

This guide covers two deployment methods for the Repair Management System:

- **Method A** — GitHub Actions + SSH (recommended for VPS)
- **Method B** — CyberPanel + Webhook (recommended if server runs CyberPanel)

---

## Method A — GitHub Actions + SSH

### How it works

```
git push → GitHub → Actions runs PHP lint → rsync files to server via SSH
```

Every push to `main` triggers the pipeline. If the PHP syntax check passes, files are synced to the server automatically.

---

### Step 1 — Generate a deploy SSH key

Run this on your **local machine** (not the server):

```bash
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy
```

Press **Enter** twice when asked for a passphrase (leave it empty).

This creates two files:
- `~/.ssh/github_deploy` — private key (goes into GitHub)
- `~/.ssh/github_deploy.pub` — public key (goes onto the server)

---

### Step 2 — Add the public key to your server

SSH into your server and append the public key to the authorized keys file:

```bash
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

Or use `ssh-copy-id`:

```bash
ssh-copy-id -i ~/.ssh/github_deploy.pub your_user@your-server.com
```

---

### Step 3 — Add secrets to GitHub

Go to your repository on GitHub:

**Settings → Secrets and variables → Actions → New repository secret**

Add the following secrets:

| Secret Name | Value |
|---|---|
| `SSH_HOST` | Your server IP or domain (e.g. `repair.arifs.work`) |
| `SSH_USER` | Your SSH username (e.g. `arifs.work`) |
| `SSH_PRIVATE_KEY` | Full contents of `~/.ssh/github_deploy` (the private key file) |
| `SSH_PORT` | `22` (or your custom SSH port) |
| `DEPLOY_PATH` | Full path on server (e.g. `/home/arifs.work/repair.arifs.work`) |

> To copy the private key content: `cat ~/.ssh/github_deploy` — copy everything including the `-----BEGIN` and `-----END` lines.

---

### Step 4 — Create the workflow file

Create the directory and file in your repository:

```
.github/
└── workflows/
    └── deploy.yml
```

Paste the following content into `.github/workflows/deploy.yml`:

```yaml
name: CI/CD — Deploy to Production

on:
  push:
    branches:
      - main

jobs:

  # ── Stage 1: PHP Syntax Check ──────────────────────────────────────────────
  lint:
    name: PHP Syntax Check
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP 8.3
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Check syntax of all PHP files
        run: |
          find . -name "*.php" \
            -not -path "./vendor/*" \
            -not -path "./.git/*" \
            | xargs -I{} php -l {}

  # ── Stage 2: Deploy via SSH ────────────────────────────────────────────────
  deploy:
    name: Deploy to Server
    runs-on: ubuntu-latest
    needs: lint                   # only runs if lint job passes

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup SSH key
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.SSH_PRIVATE_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -p ${{ secrets.SSH_PORT }} ${{ secrets.SSH_HOST }} >> ~/.ssh/known_hosts

      - name: Deploy files via rsync
        run: |
          rsync -az --delete \
            -e "ssh -i ~/.ssh/deploy_key -p ${{ secrets.SSH_PORT }}" \
            --exclude='.git' \
            --exclude='.github' \
            --exclude='README.md' \
            --exclude='CI-CD-Setup.md' \
            --exclude='schema.sql' \
            --exclude='config/.env' \
            --exclude='public/uploads/' \
            --exclude='logs/*.log' \
            ./ ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }}:${{ secrets.DEPLOY_PATH }}/
```

---

### Step 5 — Push to trigger the pipeline

```bash
git add .github/workflows/deploy.yml
git commit -m "Add GitHub Actions CI/CD workflow"
git push origin main
```

Go to your repository → **Actions** tab to watch the pipeline run.

---

### Pipeline overview

```
Push to main
    │
    ▼
┌─────────────────────┐
│  Job 1: lint        │  PHP syntax check on all .php files
│  (ubuntu-latest)    │
└────────┬────────────┘
         │ passes
         ▼
┌─────────────────────┐
│  Job 2: deploy      │  rsync files to server via SSH
│  (ubuntu-latest)    │  skips config/.env and uploads/
└─────────────────────┘
```

If **lint fails**, the deploy job is skipped automatically.

---

### Branch strategy

| Branch | Purpose | Auto-deploy |
|---|---|---|
| `main` | Production | Yes |
| `develop` | Work in progress | No |
| `feature/*` | New features | No |

Merge `develop` → `main` when ready to ship:

```bash
git checkout main
git merge develop
git push origin main   # triggers deploy
```

---

---

## Method B — CyberPanel + Webhook

### How it works

```
git push → GitHub → sends webhook → CyberPanel pulls latest code → server updated
```

No SSH key management needed. CyberPanel handles the pull automatically when GitHub notifies it.

---

### Step 1 — Connect your repo in CyberPanel

1. Log in to **CyberPanel**
2. Go to **Websites** → click your website → **Git Manager** (or **Git Integration**)
3. Fill in the form:

   | Field | Value |
   |---|---|
   | Repository URL | `https://github.com/arifbillahcse/a-repair-management-database-system.git` |
   | Branch | `main` |
   | Web Root / Path | `/home/arifs.work/repair.arifs.work` (your document root) |

4. Click **Create / Connect**

CyberPanel will clone the repository to your server path.

---

### Step 2 — Copy the webhook URL

After connecting, CyberPanel will display a **Webhook URL**. It looks like:

```
https://your-server.com:8090/api/webhook/gitPull?token=XXXXXXXXXXXX
```

Copy this URL — you will paste it into GitHub in the next step.

---

### Step 3 — Add the webhook to GitHub

1. Go to your GitHub repository
2. Click **Settings → Webhooks → Add webhook**
3. Fill in:

   | Field | Value |
   |---|---|
   | Payload URL | Paste the CyberPanel webhook URL |
   | Content type | `application/json` |
   | Which events | Select **Just the push event** |
   | Active | Checked |

4. Click **Add webhook**

---

### Step 4 — Test the webhook

Make a small change, commit, and push to `main`:

```bash
git add .
git commit -m "Test webhook deploy"
git push origin main
```

Then check:
- GitHub → **Settings → Webhooks** → click your webhook → **Recent Deliveries** — should show a green tick
- CyberPanel → **Git Manager** → check the pull log — should show the latest commit

---

### Step 5 — Add PHP syntax check (optional but recommended)

To add a CI lint check before the webhook fires, combine both methods:

Create `.github/workflows/lint.yml`:

```yaml
name: PHP Lint Check

on:
  push:
    branches:
      - main

jobs:
  lint:
    name: PHP Syntax Check
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Check PHP syntax
        run: |
          find . -name "*.php" \
            -not -path "./vendor/*" \
            | xargs -I{} php -l {}
```

> With this file added, every push to `main` will first run the syntax check in GitHub Actions. The CyberPanel webhook fires at the same time — both happen in parallel. If the lint fails you will see the error in the Actions tab, but note that the webhook will still fire. To fully block deploys on failure, use **Method A** instead.

---

### Pipeline overview

```
Push to main
    │
    ├──▶ GitHub Actions (lint check) ──▶ shows pass/fail in Actions tab
    │
    └──▶ GitHub Webhook ──▶ CyberPanel ──▶ git pull on server ──▶ live
```

---

---

## Important Notes for Both Methods

### Files never deployed (excluded)

These files and folders are excluded from every deploy:

| Path | Reason |
|---|---|
| `config/.env` | Contains DB credentials and secret keys — set manually on server |
| `public/uploads/` | User-uploaded files live only on the server |
| `logs/*.log` | Server logs should not be overwritten |
| `.git/` `.github/` | Git internals, not needed on server |
| `schema.sql` `README.md` | Documentation only |

### Database migrations

CI/CD does **not** run SQL migrations automatically. After deploying code that requires a schema change, run the SQL manually in **phpMyAdmin** or via SSH:

```sql
-- Example: add colleague client type
ALTER TABLE customers
  MODIFY COLUMN client_type
  ENUM('individual','company','colleague')
  NOT NULL DEFAULT 'individual';
```

### Setting environment variables on the server

Never commit `config/.env`. Instead, create it manually on the server once:

```bash
# SSH into server then:
nano /home/arifs.work/repair.arifs.work/config/.env
```

Paste your environment variables and save. The file will not be touched by future deploys because it is excluded.

### Checking deploy status

| Method | Where to check |
|---|---|
| GitHub Actions + SSH | GitHub → Actions tab → latest workflow run |
| CyberPanel Webhook | GitHub → Settings → Webhooks → Recent Deliveries |
|  | CyberPanel → Git Manager → pull log |
