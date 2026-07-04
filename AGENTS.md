# AGENTS.md
# Niktrade Development Guide for Codex

## Project Overview

Niktrade is a production business system.

Every change must be:

- minimal
- isolated
- reversible
- tested

Never rewrite large parts of the application if a small fix is sufficient.

Preserve backwards compatibility whenever possible.

---

# Technology

Framework

- Laravel 13
- PHP 8.4+
- PostgreSQL
- Filament 4
- Livewire
- Vite
- Playwright MCP

---

# Code Style

Always follow Laravel conventions.

Prefer:

- dependency injection
- service classes
- Eloquent relationships
- Form objects
- Policies

Avoid:

- duplicated logic
- giant controllers
- giant Livewire components
- inline javascript
- inline css

---

# UI Rules

Never globally modify Filament styles unless absolutely required.

Prefer:

- local Blade changes
- component scoped css
- utility classes

Never break existing layouts.

---

# CSS Rules

Allowed

- local css
- dedicated css files
- component styles

Avoid

- !important
- global overrides
- changing vendor css

---

# Database

Database is PostgreSQL.

Never:

- drop data
- rename columns without migration
- delete migrations

Always create migrations.

Never modify old production migrations.

---

# Certificates

Business rules.

One certificate may belong to many products.

Certificate information must never be duplicated.

Updating certificate updates all related products.

Expired certificates cannot be selected.

---

# Brands

Brands are separate entities.

Never duplicate brand information inside products.

Use relationships.

---

# Categories

Categories are independent entities.

Do not hardcode category names.

---

# Products

Never duplicate:

- certificates
- brands
- categories

Always use relationships.

---

# Filament

Prefer Filament native components.

Never reinvent existing Filament functionality.

Use:

Forms

Tables

Actions

Infolists

Widgets

---

# Livewire

Prefer reactive updates.

Avoid page reloads.

---

# Performance

Never introduce N+1 queries.

Always eager load relationships.

Use pagination.

---

# Error Handling

Never suppress exceptions.

Use:

try/catch

Log meaningful errors.

---

# Browser Verification

For every UI change:

Use Playwright MCP.

Workflow

Open affected page

Login

Verify page renders

Verify layout

Verify console

Verify network

Verify buttons

Verify forms

Verify validation

Take browser snapshot

If broken

Fix

Repeat

Only finish when browser verification succeeds.

---

# Playwright

Whenever possible:

Open browser

Navigate

Inspect

Interact

Verify

Never assume UI works.

Always verify.

---

# Console

No javascript errors allowed.

No Livewire errors.

No Alpine errors.

No 404 assets.

---

# Network

No failed requests.

No HTTP 500.

No unexpected redirects.

---

# Git

Before finishing:

git status

git diff

Show changed files.

Never modify unrelated files.

Never create formatting-only commits.

---

# Commit Philosophy

Small commits.

Single responsibility.

Clear messages.

---

# Security

Never expose:

Passwords

Secrets

API Keys

Tokens

Environment variables

Do not print sensitive information.

---

# Testing Workflow

For every task:

Understand request

Inspect code

Make minimal change

Run PHP validation

Run browser verification

Fix issues

Repeat until clean

Only then finish.

---

# Decision Making

Prefer existing architecture.

Avoid introducing new abstractions unless necessary.

Avoid unnecessary dependencies.

Keep solutions simple.

---

# Before Editing

Read surrounding code.

Understand architecture.

Do not blindly refactor.

---

# Communication

Always explain:

What changed

Why

Which files changed

Any migration required

Any deployment required

---

# Project Knowledge

Important entities

Products

Brands

Categories

Certificates

Users

Roles

Permissions

Relationships are preferred over duplicated data.

---

# Deployment

Never assume deployment happened.

State explicitly if deployment is required.

---

# Completion Checklist

Before marking task complete:

✓ Code compiles

✓ No syntax errors

✓ Browser verified

✓ Console clean

✓ Network clean

✓ Layout correct

✓ Existing functionality preserved

✓ Changed files listed

Only after all checks pass consider the task finished.