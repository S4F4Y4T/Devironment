# Devironment — Technical Documentation

**Version:** 2.1.1
**Author:** S4F4Y4T
**License:** MIT
**Language:** PHP 8+
**Platform:** Ubuntu / Debian Linux
**Repository:** https://github.com/S4F4Y4T/Devironment

---

## Table of Contents

1. [Overview](#overview)
2. [Directory Structure](#directory-structure)
3. [System Requirements](#system-requirements)
4. [Installation](#installation)
5. [Architecture](#architecture)
6. [Entry Point — bin/devenv.php](#entry-point)
7. [Core Handler — core/handler.php](#core-handler)
8. [Virtual Host Module — modules/vhost/](#virtual-host-module)
9. [Apache Module — modules/apache/](#apache-module)
10. [Installer — installer.php](#installer)
11. [Command Reference](#command-reference)
12. [Response Format & Status Codes](#response-format--status-codes)
13. [Update Flow](#update-flow)
14. [File System Paths](#file-system-paths)
15. [Error Handling & Validation](#error-handling--validation)

---

## Overview

Devironment is a PHP-based CLI tool for managing local development environments on Ubuntu/Linux. It automates the setup of Apache virtual hosts, project directories, and system-level configuration so developers can spin up a new local domain without touching config files manually.

Core capabilities:

- Create, enable, and disable Apache virtual hosts
- Manage Apache service state
- Install itself globally as the `devenv` system command
- Self-update via git pull + reinstall
- Interactive command loop or direct argument invocation

---

## Directory Structure

```
Devironment/
├── bin/
│   └── devenv.php          # CLI entry point
├── core/
│   └── handler.php         # Command router and business logic
├── modules/
│   ├── apache/
│   │   └── init.php        # Apache service management
│   └── vhost/
│       ├── init.php        # Virtual host CRUD operations
│       ├── validation.php  # Existence checks for vhost configs
│       └── demo            # Placeholder index.html for new projects
├── images/
│   └── ss.png
├── installer.php           # Global install script
├── readme.md
└── license.txt
```

---

## System Requirements

| Requirement | Details |
|-------------|---------|
| PHP | 8.0 or later, with `posix` extension |
| OS | Ubuntu / Debian Linux |
| Web server | Apache2 |
| Init system | systemd (`systemctl`) |
| Tools | `git`, `a2ensite`, `a2dissite`, `ping` |
| Privileges | `sudo` / root required for install, vhost, and apache commands |

---

## Installation

### First-time install

```bash
git clone https://github.com/S4F4Y4T/Devironment
cd Devironment
sudo php installer.php
```

After installation `devenv` is available as a global system command at `/usr/local/bin/devenv`.

### Install via CLI

Once `devenv.php` is reachable:

```bash
sudo devenv install
```

---

## Architecture

Devironment follows a **single-class handler with pluggable modules** pattern.

```
bin/devenv.php
      │
      │  instantiates
      ▼
core/handler.php  ──requires──►  modules/vhost/init.php
      │                           modules/vhost/validation.php
      │           ──requires──►  modules/apache/init.php
      │
      └─ installer.php  (included on install/update)
```

**Control flow:**

1. `devenv.php` parses the command from `$argv` or stdin.
2. It looks up the command in the registry returned by `handler::getCmd()`.
3. It calls the matching handler method, passing optional arguments.
4. The handler method may load a module (using `require`) and delegate to it.
5. The handler returns a response array; `devenv.php` formats and prints it.

The loop runs until the user issues `kill` (or Ctrl+C), which sets `processing = 0`.

---

## Entry Point

**File:** [bin/devenv.php](../bin/devenv.php)

Responsibilities:

- Displays the ASCII banner with app name, version, and author.
- Detects execution context:
  - If the script lives at `/usr/local/bin`, the project dir is `/usr/local/lib/devironment`.
  - Otherwise, the project dir is the parent of `bin/`.
- Checks internet connectivity on startup; if reachable, checks for updates and notifies the user.
- Runs an infinite `while(true)` loop that reads one command per iteration.
- Resolves the command against the registry, calls the corresponding handler method, and prints the colour-coded result.

**Execution modes:**

| Mode | How triggered | Input source |
|------|--------------|-------------|
| Interactive | `devenv` | stdin prompt |
| Direct | `devenv <command>` | `$argv[1]` |

**Colour coding:**

| Status | Colour |
|--------|--------|
| 1 (success) | Green `\033[32m` |
| 0 (failure) | Red `\033[31m` |
| 3 / null (info) | Default terminal colour |

---

## Core Handler

**File:** [core/handler.php](../core/handler.php)

**Class:** `handler`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$cmdList` | array | Registered command map |
| `$appName` | string | Application name |
| `$version` | string | Current version string |
| `$author` | string | Author name |
| `$repository` | string | Remote git URL |
| `$branch` | string | Main branch name |
| `$projectDir` | string | Resolved installation path |

### Public Methods

#### `getCmd(): array`
Returns the full command registry. Each entry contains a description and the name of the handler method to invoke.

#### `version(): array`
Returns a success response containing the current version string.

#### `ping(): array`
Executes `ping -c 1 -W 1 8.8.8.8` to test internet connectivity. Returns status 1 on success, 0 on failure.

#### `status(): array`
Compares the local HEAD commit SHA (`git rev-parse HEAD`) with the remote SHA (`git ls-remote`). Returns status 0 when a newer version exists, 1 when up to date.

#### `sync(): array`
Full update sequence:
1. Verifies internet connectivity via `ping()`.
2. Verifies a git repository exists via `isGit()`.
3. Calls `status()` — aborts if already up to date.
4. Runs `git pull origin {branch}`.
5. Includes `installer.php` to redeploy updated files.

#### `installer(): array`
Checks whether `devenv` is already on `$PATH` (`which devenv`). If not, requires `installer.php` to perform the global install. Returns success or error status.

#### `help(): array`
Iterates the command registry and prints each command with its description.

#### `vhost(string $action, string $domain, string $project_name, string $dir): array`
Requires sudo. Verifies Apache is running. Loads `modules/vhost/init.php`, calls `getOptions()` or the specified action (`create`, `list`, `enable`, `disable`).

#### `apache(string $action): array`
Requires sudo. Loads `modules/apache/init.php`, dispatches to the specified action (`status`).

### Private Methods

| Method | Description |
|--------|-------------|
| `registerCmd()` | Builds the command registry array |
| `getAction(string $action, array $options)` | Validates or interactively prompts for an action choice |
| `isGit(): bool` | Returns true if the project dir is inside a git work tree |
| `sudo(): array` | Returns error response if POSIX UID ≠ 0 |

---

## Virtual Host Module

**Files:** [modules/vhost/init.php](../modules/vhost/init.php), [modules/vhost/validation.php](../modules/vhost/validation.php)

**Namespace:** `vhost`

### Class `vhost`

#### Properties

| Property | Description |
|----------|-------------|
| `$domain` | Domain name for the host |
| `$project_name` | Project identifier |
| `$dir` | Project directory path under `/var/www` |
| `$validation` | Instance of `vhost\validation` |
| `$usrDir` | Base path for projects (`/var/www`) |

#### `getOptions(): array`
Returns the four available actions:

| Key | Label | Description |
|-----|-------|-------------|
| 1 / create | Create | Create a new virtual host |
| 2 / list | List | List all virtual hosts |
| 3 / enable | Enable | Enable an inactive host |
| 4 / disable | Disable | Disable an active host |

#### `create(): array`
Full workflow for setting up a new virtual host:

1. Prompt for domain (validated against `/^[a-zA-Z0-9.]+$/`).
2. Check for duplicate — reject if the config file already exists.
3. Prompt for project name and directory.
4. Validate that the target directory exists under `/var/www`.
5. Write the Apache config file to `/etc/apache2/sites-available/{domain}.conf`.
6. Prepend `127.0.0.1    {domain}` to `/etc/hosts`.
7. Copy `modules/vhost/demo` (placeholder `index.html`) into the project root.
8. Call `enable()` to activate the host immediately.

**Generated Apache config template:**

```apache
<VirtualHost {domain}:80>
    <Directory {projectDir}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
    ServerAdmin admin@{domain}
    ServerName {domain}
    ServerAlias www.{domain}
    DocumentRoot {projectDir}
    ErrorLog ${APACHE_LOG_DIR}/error.log
</VirtualHost>
```

#### `list(string $domain = ''): array`
Scans `/etc/apache2/sites-available` and `/etc/apache2/sites-enabled`. Excludes default Ubuntu configs (`000-default.conf`, `default-ssl.conf`). Displays each host with its status:

- **Active** (green) — config file is present in `sites-enabled`
- **Inactive** — config exists only in `sites-available`

Optional `$domain` argument filters to a single host.

#### `enable(string $domain): array`
Runs `a2ensite {domain}.conf` then restarts Apache. Verifies enablement by checking `sites-enabled/{domain}.conf` exists.

#### `disable(string $domain): array`
Runs `a2dissite {domain}.conf` then restarts Apache. Verifies disablement by confirming the symlink is removed.

### Class `validation`

#### `is_exist(string $conf): bool`
Returns `true` if `/etc/apache2/sites-available/{conf}.conf` exists.

---

## Apache Module

**File:** [modules/apache/init.php](../modules/apache/init.php)

**Namespace:** `apache`

### Class `apache`

#### `getOptions(): array`
Returns available actions:

| Key | Label | Description |
|-----|-------|-------------|
| 1 / status | Status | Check Apache service state |

#### `status(): array`
Executes `systemctl is-active apache2`. Returns status 1 if the service is active, 0 otherwise.

---

## Installer

**File:** [installer.php](../installer.php)

Handles both first-time installation and re-deployment after updates.

**Requirements:** Must be executed with `sudo`.

### Function `processFileSystem(string $source, string $destDir)`

Recursive file copier:

1. Creates the destination directory with `0755` permissions if it does not exist.
2. Iterates entries; skips dotfiles (except `.git`).
3. Directories are recursed; files are copied with `0755` permissions.
4. Ownership of every file and directory is set to `$SUDO_USER` (the invoking non-root user).
5. Special case: `bin/devenv.php` is copied to `/usr/local/bin/devenv` (no `.php` extension).

### Execution modes

| Mode | Trigger | Output |
|------|---------|--------|
| Direct | `sudo php installer.php` | Coloured terminal text |
| Included | Called from `handler::installer()` or `handler::sync()` | JSON response array |

### Installed paths

| Source | Destination |
|--------|-------------|
| Entire project | `/usr/local/lib/devironment/` |
| `bin/devenv.php` | `/usr/local/bin/devenv` |

---

## Command Reference

### Command Registry

| Command | Handler method | Requires sudo | Description |
|---------|---------------|---------------|-------------|
| `install` | `installer()` | Yes | Install `devenv` globally |
| `help` | `help()` | No | List all commands |
| `--v` | `version()` | No | Print current version |
| `status` | `status()` | No | Check for available updates |
| `latest` | `sync()` | No | Pull and apply latest version |
| `ping` | `ping()` | No | Test internet connectivity |
| `host` | `vhost()` | Yes | Manage Apache virtual hosts |
| `apache` | `apache()` | Yes | Manage Apache service |
| `kill` | _(inline)_ | No | Exit the program |

### Virtual Host Sub-commands

```
host create   — create a new virtual host (interactive prompts)
host list     — list all virtual hosts and their status
host enable   — enable an existing inactive virtual host
host disable  — disable an active virtual host
```

### Apache Sub-commands

```
apache status — show whether Apache2 is running
```

---

## Response Format & Status Codes

All handler methods return a PHP array:

```php
[
    'status'     => int,    // 0 = error | 1 = success | 3 = informational
    'message'    => string, // Human-readable message
    'processing' => int,    // 1 = keep loop running | 0 = exit
]
```

The entry point reads `status` to pick a terminal colour, then prints `message`.

---

## Update Flow

```
devenv latest
      │
      ├─ ping()          — abort if no internet
      ├─ isGit()         — abort if not a git repo
      ├─ status()        — abort if already up to date
      ├─ git pull        — fetch latest commits
      └─ installer.php   — redeploy updated files to system paths
```

Version comparison is done by diffing the SHA returned by `git rev-parse HEAD` against the first column of `git ls-remote {repo} {branch}`.

---

## File System Paths

| Path | Purpose |
|------|---------|
| `/usr/local/bin/devenv` | Global CLI binary |
| `/usr/local/lib/devironment/` | Application library files |
| `/etc/apache2/sites-available/` | Apache virtual host configs (all) |
| `/etc/apache2/sites-enabled/` | Symlinks to active configs |
| `/etc/hosts` | System host file — updated on vhost create |
| `/var/www/` | Default base directory for project roots |

---

## Error Handling & Validation

### Privilege checks

Every command that touches system state calls `sudo()` first. If `posix_geteuid() !== 0`, the method returns an error response immediately without executing any system command.

### Domain validation

| Check | Mechanism |
|-------|-----------|
| Format | Regex `/^[a-zA-Z0-9.]+$/` — alphanumeric and dots only |
| Uniqueness | `validation::is_exist()` checks `sites-available` before creation |
| Existence (enable/disable) | `is_exist()` called before running `a2ensite` / `a2dissite` |

### Directory validation

Project directories are validated to exist under `/var/www` before any config file is written.

### Service validation

`vhost()` in the handler verifies Apache is active (via `apache::status()`) before attempting any vhost operation.

### Exception handling

`installer.php` and `modules/vhost/init.php` wrap file operations in try/catch blocks and return structured error messages rather than letting exceptions propagate to the terminal.

### Shell command results

Output from `shell_exec()` is trimmed and compared against expected strings (e.g., `"active"` for systemctl, `"true"` for git inside-work-tree check) to determine success or failure.
